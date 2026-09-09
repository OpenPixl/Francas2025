# Recherche et filtrage des articles (Elasticsearch)

## Contexte

Sur le modèle de la recherche établissements par section (voir `NOTES_VUE_ETABLISSEMENTS_PAR_SECTION.md`), la page
`webapp/articles/listallarticles.html.twig` (route `op_webapp_article_all_by_etablissement`,
contrôleur `ArticleController::listAllArticles`) permet de rechercher/filtrer les articles indexés dans Elasticsearch
(texte, type d'établissement, thème), regroupés par type d'établissement, avec un accès à une page dédiée dès
qu'un groupe dépasse 8 résultats.

---

## 1. Indexation Elasticsearch de l'entité `Article`

> **Mise à jour 08–09/09/2026** — le mapping ci-dessous a évolué depuis : ajout de `theme.name` /
> `etablissement.name` en `text` (§12, recherche instantanée), puis passage de `theme` (objet unique) à
> `themes` (objets multiples, `themes.id` / `themes.name`) suite au ManyToMany (§13). Toute
> modification de mapping impose un `fos:elastica:reset` + `populate` au déploiement.

`config/packages/fos_elastica.yaml` — index `article` (modèle `App\Entity\Webapp\Article`) :

```yaml
article:
    persistence:
        driver: orm
        model: App\Entity\Webapp\Article
        ...
    properties:
        title:
            type: text
        content:
            type: text
        theme:
            type: object
            properties:
                id: ~
        support:
            type: object
            properties:
                id: ~
        etablissement:
            type: object
            properties:
                id: ~
        typeEtablissement:
            property_path: etablissement?.typeEtablissement?.id
        updatedAt:
            type: date
```

- `theme` et `etablissement` sont mappés en objets imbriqués (`type: object`) : interrogeables via `theme.id` /
  `etablissement.id`.
- `typeEtablissement` est un **champ à plat** (`property_path`), qui traverse la relation
  `Article → Etablissement → TypeEtablissement → id` via le `PropertyAccessor` de Symfony. La syntaxe **null-safe**
  (`?.`) est indispensable : certains articles n'ont pas d'établissement, et sans elle le populate échouait avec
  `PropertyAccessor requires a graph of objects or arrays [...] found type "NULL"`.
- Point de vigilance corrigé en cours de route : le bloc `article` avait été créé par copier-coller du bloc
  `etablissement` et pointait encore vers `model: App\Entity\Admin\Etablissement` — corrigé vers
  `App\Entity\Webapp\Article`.
- Réindexation après chaque changement de mapping : `php bin/console fos:elastica:populate --index=article` (reset +
  repopulate complet, nécessaire dès qu'un champ est ajouté/modifié).

## 2. Formulaire de recherche (`ArticleSearchType`)

`src/Form/Search/ArticleSearchType.php` :

- `query` (`TextType`, libre) — recherche plein texte sur `title`.
- `etablissementChoice` (`ChoiceType`) — filtre par **type** d'établissement. Les choix (`etablissementsChoices`)
  sont construits côté contrôleur avec le **libellé du type en clé** et **l'ID du type en valeur** — le
  `<select>` du template les rend depuis `form.etablissementChoice.vars.choices` (source de vérité Symfony) plutôt
  que depuis une boucle manuelle sur les données brutes, pour garantir que la valeur soumise corresponde toujours à
  ce que le formulaire attend.
- `theme` (`ChoiceType`) — même principe, choix `themesChoices` (libellé du thème en clé, ID en valeur).

## 3. Contrôleur (`ArticleController::listAllArticles`)

- `$boolQuery->addFilter(new Exists('etablissement'))` : exclut des résultats les articles sans établissement
  (rédigés directement par les administrateurs) — **sans les retirer de l'index** FOS, uniquement de cette
  recherche.
- Filtre texte : `MultiMatch` sur `title`.
- Filtre type d'établissement : `Term` sur `typeEtablissement` (le champ à plat, pas `etablissement.typeEtablissement.id`
  qui n'existe pas dans le mapping — cette confusion a été la cause d'un premier essai renvoyant 0 résultat).
- Filtre thème : `Term` sur `theme.id` (champ imbriqué existant).
- Tri : `$query->setSort(['updatedAt' => ['order' => 'desc']]);` — du plus récent au plus vieux.
- Les critères de recherche actuellement actifs (`theme`, `query`) sont transmis à la vue des résultats
  (`currentTheme`, `currentQuery`) pour que le lien "voir tous" (point 5) puisse les reporter.

### Bugs corrigés au passage (copiés/adaptés depuis le contrôleur établissement, non ajustés pour `Article`)

- `$r->getTypeEtablissement()` appelé sur une entité `Article` (n'existe pas) → `$r->getEtablissement()?->getTypeEtablissement()`.
- `$r->getName()` / `$r->getLogoName()` (getters d'`Etablissement`) → `$r->getTitle()` / `$r->getImageName()`.

## 4. `ArticleRepository::allArticles()` — liste initiale (hors recherche)

Utilisée pour peupler la page au premier chargement et construire les choix des selects. Trois bugs bloquants
corrigés (empêchaient tout chargement de la page, 500 sur `QueryException`) :

- `a.updatedtedAt as updatedAt` (coquille) → `a.updatedAt as updatedAt`.
- `a.doc as doc` sélectionné deux fois dans le même `addSelect()` → "Semantical Error: 'doc' is already defined".
- `orderBy('a.id', 'ASC')` → `orderBy('a.updatedAt', 'DESC')`, pour rester cohérent avec le tri de la recherche
  Elastica.

## 5. Page dédiée "voir tous les articles" par type + thème

À la différence de la page établissements (qui ne filtre que par type via `op_webapp_etablissement_bytype`), le
bouton "voir tous" des articles doit conserver **la combinaison de filtres en cours** (type d'établissement du
groupe **+** thème **+** texte éventuellement recherchés), pas uniquement le type.

- Nouvelle route `op_webapp_articles_bytype` (`/webapp/articles/type/{idtype}`, `ArticleController::listArticlesByType`) :
  reconstruit la même requête Elastica combinée (`Exists('etablissement')` + `Term('typeEtablissement', $idtype)` +
  éventuellement `Term('theme.id', $idtheme)` et `MultiMatch` sur `title`), `$idtheme`/`$q` lus en query string.
- Le lien "voir tous" (`_listesearch.html.twig`) transmet `idtype` (déduit du groupe affiché) et les critères
  `currentTheme`/`currentQuery` via `path()` — Symfony ajoute automatiquement les paramètres hors pattern de route
  en query string (`?theme=2&query=...`), pas besoin de construction manuelle.
- Nouveau template `webapp/articles/listarticlesbytype.html.twig` (page complète, `extends base.html.twig`), même
  gabarit de vignette que la recherche.

## 6. Vignettes articles — habillage visuel

`templates/webapp/articles/include/_listesearch.html.twig` (et son pendant `listarticlesbytype.html.twig`) :

- Image en `object-cover` plein cadre (au lieu de `object-contain`), avec un dégradé sombre
  (`bg-gradient-to-b from-black/50 via-transparent to-black/60`) pour la lisibilité du texte superposé.
- Nom de l'établissement en `<h3>`, en haut à gauche, précédé d'une pastille pour le logo
  (`<img src="" class="h-6 w-6 rounded-full ...">` — `src` volontairement laissé vide, à compléter séparément).
- Titre de l'article centré sur la vignette.
- Thème en badge arrondi, bas gauche, en superposition — couleur `#ffd100` (jaune/or, couleur secondaire déjà
  présente dans la charte du site — navbar, en-têtes de page — reprise ici plutôt que le orange principal
  `#d84519`, pour ne pas dupliquer la couleur d'accent utilisée par les CTA/liens).

## 7. Pagination de la page "voir tous" (KnpPaginator)

La page dédiée `listarticlesbytype.html.twig` (point 5) affichait jusqu'à 200 articles d'un coup
(`$query->setSize(200)`), sans pagination — peu lisible dès qu'un type + thème regroupe beaucoup de résultats.

- `ArticleController::listArticlesByType` : le tableau d'articles construit depuis les résultats Elastica est
  passé à `PaginatorInterface::paginate()` (déjà utilisé ailleurs dans le projet, notamment
  `ArticleController::listArticlesByEtablissement`) — KnpPaginator gère nativement un tableau PHP simple, pas
  besoin d'une requête Doctrine. Taille de page fixée à **12** (3 lignes de 4 dans la grille).
- `listarticlesbytype.html.twig` : ajout de `{{ knp_pagination_render(articles, 'include/pagination_public.html.twig') }}`
  sous la grille — même gabarit de pagination que `listarticlesbypageetablissement.html.twig`.
- Les filtres actifs (`theme`, `query`) restent conservés d'une page à l'autre : KnpPaginator reprend
  automatiquement les paramètres de la requête HTTP en cours pour générer les liens (`?theme=2&page=2`), aucune
  logique supplémentaire nécessaire.

## 8. Page de détail d'un article (`show.html.twig`) : bloc support + largeur

Demandes complémentaires sur la page individuelle d'un article (`webapp/articles/show.html.twig`, route
`op_webapp_articles_show`, entité réelle passée au template — contrairement aux pages de ce document qui
manipulent des tableaux issus d'Elastica/DQL) :

- Largeur de la page passée de `max-w-4xl` à `max-w-7xl`, pour être cohérente avec le reste du site (listes,
  recherche, pages établissement, toutes déjà en `max-w-7xl`).
- Ajout du bloc « support chargé » (radio/audio, vidéo, document à télécharger), qui existait déjà sur
  `articleEtablissementSlug.html.twig` mais pas ici. Repris à l'identique (mêmes trois cas
  `idsupport`/`support.id` 1/2/3, même lecteur audio personnalisé, même rendu vidéo/document) et inséré dans la
  balise `<article>`, juste après `{{ article.content|raw }}`. Adapté à l'accès **entité** plutôt que tableau :
  `article.support.id` (relation Doctrine) au lieu de `article.idsupport` (alias DQL), et la fonction Twig
  `article_doc_url(article)` (voir `NOTES_REORGANISATION_MEDIAS.md`, §1) au lieu du champ `article.docUrl`
  précalculé côté contrôleur pour les listings.
- Le lecteur audio (`#playBtn`, `#rail`, `#audio`, etc.) est piloté par `initArticleIndex()`
  (`assets/js/app/etablissement/article.js`), jusqu'ici uniquement câblée sur la route
  `op_webapp_articles_articleSlug` (`assets/app.js`) — ajout de la route `op_webapp_articles_show` au même
  `case`. Bug latent corrigé au passage : `initArticleIndex()` appelait `player_audio()` sans vérifier que
  `#audio` existe réellement dans la page — cassait (exception JS silencieuse, lecteur audio non fonctionnel
  sans message d'erreur visible) sur **tout** article n'ayant pas de support audio, ce qui devient nettement
  plus fréquent en branchant cette fonction sur la page `show` (grand public) plutôt que sur la seule page
  `articleEtablissementSlug`. Corrigé par un simple retour anticipé (`if (!audio) return;`).

## 9. Bug corrigé : listing établissement (`_blocarticle.html.twig`) plantait en 500

`templates/webapp/articles/include/_blocarticle.html.twig` (utilisé par
`listarticlesbypageetablissement.html.twig`, route `op_webapp_articles_pagebyetablissement`) appelait
`asset(article.logoUrl)` sans vérifier que `logoUrl` n'était pas `null` — ce qui arrive dès que l'établissement
de l'article n'a pas de logo uploadé (`etablissementImageUrl()` du `MediaPathResolver` renvoie `null` dans ce
cas, voir `NOTES_REORGANISATION_MEDIAS.md`). `asset()` de Symfony n'accepte pas `null` en argument → `TypeError`
→ page entière en erreur 500, dès qu'un seul établissement de la liste n'a pas de logo. Deux occurrences
touchées par le même bug (lignes 8 et 28 du template) :

- Ligne 8 (vignette de remplacement quand l'article lui-même n'a pas d'image) : fallback désormais sur
  `config.headerName` (bandeau par défaut du site) plutôt que sur le logo de l'établissement, en reprenant le
  même principe que `show.html.twig` (§8) pour une grande image plutôt qu'un petit avatar.
- Ligne 28 (logo de l'établissement dans le bloc « posté par ») : ajout d'un `{% if article.logoNameEtablissement %}`
  (champ brut, déjà sélectionné par `ArticleRepository::listArticlesByEtablissement()`) avec fallback sur
  `config.vignetteName` (avatar par défaut du site) — même convention déjà en place dans
  `_listesearch.html.twig` (§6).
- `ArticleController::listArticlesByPageEtablissement()` ne transmettait pas `config` à la vue (nécessaire pour
  les deux fallbacks ci-dessus) — ajouté, sur le même modèle que les autres actions du contrôleur qui en ont
  besoin.

## 10. Bug corrigé : page "voir tous" d'un type (`listarticlesbytype.html.twig`) plantait en 500 au clic depuis le chargement initial

Même bug que §9, non répliqué dans ce template lors de sa création (§5) : `asset(article.logoUrl)` était appelé **sans garde**, alors que `MediaPathResolver::etablissementLogoUrl()` renvoie `null` dès qu'un établissement n'a pas de logo uploadé. `asset()` de Symfony n'accepte pas `null` → `TypeError` → page entière en erreur 500.

- Repéré en testant le bouton "Voir tous les articles" **sans aucun filtre appliqué** (clic direct depuis le chargement initial de `listallarticles.html.twig`) : la page non filtrée regroupe jusqu'à 200 articles par type (`$query->setSize(200)` dans `listArticlesByType`), ce qui augmente fortement la probabilité de tomber sur un établissement sans logo. Les scénarios de test documentés en §"Vérifications effectuées" combinaient systématiquement un thème + une recherche texte, un sous-ensemble qui n'avait par chance touché que des établissements avec logo — ce cas limite n'avait donc pas été couvert.
- Correctif : ajout de la garde `{% if article.logoUrl %}...{% else %}...{% endif %}` avec fallback sur `config.vignetteName` (avatar par défaut du site), sur le même modèle que `_listesearch.html.twig` (§6) et `_blocarticle.html.twig` (§9). Contrairement à `_listesearch.html.twig`, le tableau construit par `ArticleController::listArticlesByType()` n'expose pas de champ `logoEtablissement` (nom de fichier brut) — la garde porte donc directement sur `article.logoUrl` (URL déjà résolue, `null` si pas de logo) plutôt que sur le nom de fichier.

## 11. Réinitialisation du formulaire après recherche (17/08/2026)

`assets/js/app/page/show.js` (`initShowPage()`, code partagé avec la recherche établissements — voir
`NOTES_VUE_ETABLISSEMENTS_PAR_SECTION.md`) : après réception de la réponse AJAX et réinjection du HTML dans
`#form_results`, un appel à `form.reset()` a été ajouté pour vider les champs du formulaire (texte + selects)
après chaque recherche réussie, plutôt que de laisser les valeurs saisies affichées.

## 12. Recherche instantanée dans la navbar admin (08/09/2026)

Sur la liste d'administration des articles (`_navbarsearchform.html.twig`, alimenté par
`NavbarSearchController`), les suggestions s'affichent désormais **au fil de la frappe** sous le champ,
sans recharger la liste principale — la recherche « Entrée » (rechargement complet du listing) est
conservée à l'identique.

### Mapping — noms indexés en plus des ids

`config/packages/fos_elastica.yaml` : les objets imbriqués `theme` et `etablissement` indexent aussi
`name` (`type: text`), pour pouvoir chercher « radio », « collège X »… et pas seulement filtrer par id.

```yaml
theme:
    type: object
    properties:
        id: ~
        name:
            type: text
etablissement:
    type: object
    properties:
        id: ~
        name:
            type: text
```

### `ArticleController::searchLive()`

```php
public const SEARCH_LIVE_MIN_CHARS = 5;

#[Route(path: '/webapp/articles/search-live', name: 'op_webapp_articles_search_live', methods: ['GET'])]
public function searchLive(Request $request): Response
```

- **À déclarer avant la route `/{id}`** du même contrôleur, sinon `search-live` est capté comme un id.
- En dessous de `SEARCH_LIVE_MIN_CHARS` (5) caractères : renvoie `{'html' => '', 'count' => 0}` sans
  interroger ES.
- `MultiMatch` sur `title^3`, `themes.name^2`, `etablissement.name^2`, `content` —
  `TYPE_BEST_FIELDS`, `FUZZINESS_AUTO` ; `setSize(10)` ; tri `_score` desc puis `updatedAt` desc.
- **Tolérante à un Elasticsearch injoignable** : `try { $this->finder->find(...) } catch (\Throwable)`
  → panneau vide plutôt qu'une 500 pendant la saisie.
- Réponse JSON `{'html' => <rendu de _search_suggestions>, 'count' => N}`.

Le même jeu de champs (`title^3` / `themes.name^2` / `etablissement.name^2` / `content`, best_fields,
fuzziness AUTO) a été appliqué à la recherche « Entrée » de l'index (`indexAdmin()`), qui ne cherchait
que sur `title` ; son tri passe aussi à `_score` puis `updatedAt`.

### Front — `_navbarsearchform.html.twig` + `IndexArticles.js`

- Le `<form>` reçoit `class: 'relative …'` et `data-live-url: path('op_webapp_articles_search_live')`.
- Nouveau conteneur `#navbar_search_results` (`absolute top-full`, `hidden` par défaut,
  `max-h-96 overflow-y-auto`, `role="listbox"`).
- `initLiveSearch(form)` dans `IndexArticles.js` (appelée depuis `initIndexArticle()` après le
  câblage du `submit`) :
  - `input` **debouncé 250 ms** ;
  - **anti-course** : un `requestId` incrémental, les réponses obsolètes sont ignorées ;
  - masquage sous `MIN_CHARS` (5), à la touche `Échap` (+ `blur`), et au clic **hors** du formulaire ;
  - injecte `response.data.html` dans le panneau.

### Template `templates/webapp/articles/include/_search_suggestions.html.twig` (nouveau)

Liste compacte : titre de l'article en gras, puis `thème · établissement · date` en petit. Chaque
ligne est un lien vers l'**édition** de l'article (`op_webapp_articles_edit_admin`). Message
« Aucun résultat pour "…" » si vide.

### Déploiement

Reindex obligatoire (mapping modifié) :

```
php bin/console fos:elastica:reset && php bin/console fos:elastica:populate
```

## 13. Thème d'article : passage en ManyToMany — impact sur la recherche (09/09/2026)

`Article.theme` (relation unique `ManyToOne`) devient `Article.themes` (`ManyToMany`, table de
jointure `article_theme`). Détail entité / formulaires / Tom Select multi dans le commit ; ci-dessous
uniquement ce qui touche les listings et la recherche décrits par ce document.

### Migration `Version20260909120000`

Crée `article_theme`, **reprend les affectations existantes**
(`INSERT INTO article_theme (article_id, theme_id) SELECT id, theme_id FROM article WHERE theme_id IS NOT NULL`),
puis supprime la FK et la colonne `article.theme_id`. `down()` ne peut restaurer qu'un thème par
article (`MIN(theme_id)`).

### `Article::getTheme()` — conservé, mais renvoie une **chaîne**

Les templates historiques lisaient `article.theme` (objet) : `{% if article.theme %}` /
`{{ article.theme.name }}`. La méthode est conservée pour compat mais renvoie désormais les libellés
concaténés :

```php
public function getTheme(): string
{
    return implode(', ', array_map(
        static fn (Theme $t): string => (string) $t->getName(),
        $this->themes->toArray()
    ));
}
```

Chaîne vide = *falsy*, donc `{% if article.theme %}` continue de fonctionner. Les accès `.name` sur
le résultat ont été retirés :

- `templates/webapp/articles/show.html.twig` : `{{ article.theme.name }}` → `{{ article.theme }}`
  (badge du §6).
- `templates/webapp/articles/include/_search_suggestions.html.twig` (§12) : idem.

### `ArticleRepository::withThemeLabels()` (nouveau, privé)

Les requêtes à **hydratation scalaire** ne peuvent plus faire `->leftJoin('a.theme','t')` +
`t.name as theme` : un `ManyToMany` multiplierait les lignes par thème, et `getOneOrNullResult()`
lèverait une exception. `withThemeLabels(array $rows)` prend des lignes identifiées par `id`, fait
**une** requête d'agrégation (`join a.themes`, `IN (:ids)`, `getScalarResult()`) et injecte dans
chaque ligne une clé `theme` = « Thème A, Thème B » (ou `null`).

Appliqué à : `allArticles()`, `listArticlesBySection()`, `listArticlesByEtablissement()`,
`listFiveArticles()`, `articleEtablissementSlug()`. Les `leftJoin('a.theme', 't')` et les
`t.id as idtheme` / `t.name as theme` correspondants ont été supprimés de ces méthodes.

La méthode morte `searchArticles()` (`MATCH_AGAINST`, plus appelée) est supprimée au passage.

### Contrôleur & mapping

- `config/packages/fos_elastica.yaml` : bloc `theme` → `themes` (mêmes sous-propriétés `id` + `name`).
- `ArticleController` : `theme.id` → `themes.id` et `theme.name` → `themes.name` partout
  (`searchLive()`, filtre thème de `listAllArticles()`, `listArticlesByType()`).
- `listAllArticles()` : `themesChoices` ne peut plus être déduit ligne à ligne (un article a
  potentiellement plusieurs thèmes). Il est reconstruit depuis **tous** les `Theme`
  (`findBy([], ['name' => 'ASC'])`, libellé en clé / id en valeur) → injection de
  `EntityManagerInterface` dans l'action. Le champ `theme` du `ArticleSearchType` (filtre §2) reste
  **mono-valué** : on filtre sur « au moins ce thème ».

### Formulaires (`ArticlesType`, `Articles2Type`)

Champ `theme` → `themes` : `EntityType`, `multiple => true`, `expanded => false`,
`by_reference => false`, label « Thèmes du projet ». Rendu via le composant
`input_selectMulti_horizontale.html.twig` + Tom Select multi (voir
`NOTES_TOMSELECT_ET_TURBO.md`, §1–2).

### Déploiement

```
php bin/console doctrine:migrations:migrate
php bin/console fos:elastica:reset && php bin/console fos:elastica:populate
```

(cf. mémoire projet « Article : thèmes multiples »).

---

## Fichiers créés

- `templates/webapp/articles/listarticlesbytype.html.twig`
- `templates/webapp/articles/include/_search_suggestions.html.twig` (§12)
- `migrations/Version20260909120000.php` (§13)
- `NOTES_RECHERCHE_ARTICLES.md`

## Fichiers modifiés

- `config/packages/fos_elastica.yaml`
- `src/Form/Search/ArticleSearchType.php`
- `src/Controller/Webapp/ArticleController.php`
- `src/Repository/Webapp/ArticleRepository.php`
- `templates/webapp/articles/listallarticles.html.twig`
- `templates/webapp/articles/include/_listesearch.html.twig`
- `templates/webapp/articles/show.html.twig` (§8 : largeur 7xl, bloc support)
- `templates/webapp/articles/include/_blocarticle.html.twig` (§9 : fallback logo/image, bug 500 corrigé)
- `templates/webapp/articles/listarticlesbytype.html.twig` (§10 : fallback logo établissement, bug 500 corrigé)
- `assets/app.js` (§8 : route `op_webapp_articles_show` ajoutée au câblage du lecteur audio)
- `assets/js/app/etablissement/article.js` (§8 : garde-fou `if (!audio) return;`)
- `assets/js/app/page/show.js` (§11 : `form.reset()` après recherche réussie)
- `config/packages/fos_elastica.yaml` (§12 : `theme.name`/`etablissement.name` ; §13 : `theme` → `themes`)
- `src/Entity/Webapp/Article.php` (§13 : `themes` ManyToMany, `getTheme()` renvoie une chaîne)
- `src/Entity/Gestapp/Theme.php` (§13 : côté inverse `ManyToMany mappedBy: 'themes'`)
- `src/Repository/Webapp/ArticleRepository.php` (§13 : `withThemeLabels()`, retrait des jointures `a.theme`, suppression de `searchArticles()`)
- `src/Form/Webapp/ArticlesType.php`, `src/Form/Webapp/Articles2Type.php` (§13 : champ `themes` multiple)
- `src/Controller/Webapp/ArticleController.php` (§12 : `searchLive()`, MultiMatch enrichi ; §13 : `themes.*`, `themesChoices` global)
- `templates/admin/include/_navbarsearchform.html.twig` (§12 : `data-live-url`, panneau `#navbar_search_results`)
- `assets/js/admin/webapp/IndexArticles.js` (§12 : `initLiveSearch()`)
- `templates/webapp/articles/show.html.twig` (§13 : `{{ article.theme.name }}` → `{{ article.theme }}`)
- `templates/webapp/articles/_form.html.twig`, `_form2.html.twig` (§13 : composant select multi « Thèmes »)

## Vérifications effectuées

- `php -l` sur le contrôleur et le repository — OK.
- `php bin/console lint:twig` sur les templates modifiés/créés — OK.
- `php bin/console debug:router op_webapp_articles_bytype` — route bien enregistrée.
- `php bin/console fos:elastica:populate --index=article` puis `--all` — repopulate sans erreur (632 documents).
- Comparaison systématique requête Elasticsearch brute (`curl .../_search`) vs réponse réelle du contrôleur
  (POST simulé avec reconstruction du cookie CSRF "same-origin"), pour chaque filtre (texte, type, thème, tri,
  combinaison type+thème) — décomptes identiques des deux côtés à chaque étape.
- Scénario bout en bout : recherche "Centre de loisirs" + thème "Education" → bouton annonce 13 articles → page
  dédiée (`/webapp/articles/type/2?theme=2`) affiche exactement 13 vignettes.
- Pagination : page 1 → 12 articles + lien `?theme=2&page=2` (filtre conservé) ; page 2 → 1 article restant
  (12 + 1 = 13, cohérent avec le total).
- §8 : rendu testé sur un article de chaque support (audio, vidéo, document, aucun) — bloc correct dans chaque
  cas, aucune erreur JS pour les articles sans support.
- §9 : `/webapp/articles/etablissement2/11` (établissement sans logo, 15 articles) passait de 500 à 200 après
  correctif ; établissement avec logo réel (id 1) toujours affiché correctement (pas de bascule intempestive
  sur le fallback).
