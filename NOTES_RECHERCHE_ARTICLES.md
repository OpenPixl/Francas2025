# Recherche et filtrage des articles (Elasticsearch)

## Contexte

Sur le modèle de la recherche établissements par section (voir `NOTES_VUE_ETABLISSEMENTS_PAR_SECTION.md`), la page
`webapp/articles/listallarticles.html.twig` (route `op_webapp_article_all_by_etablissement`,
contrôleur `ArticleController::listAllArticles`) permet de rechercher/filtrer les articles indexés dans Elasticsearch
(texte, type d'établissement, thème), regroupés par type d'établissement, avec un accès à une page dédiée dès
qu'un groupe dépasse 8 résultats.

---

## 1. Indexation Elasticsearch de l'entité `Article`

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

---

## Fichiers créés

- `templates/webapp/articles/listarticlesbytype.html.twig`
- `NOTES_RECHERCHE_ARTICLES.md`

## Fichiers modifiés

- `config/packages/fos_elastica.yaml`
- `src/Form/Search/ArticleSearchType.php`
- `src/Controller/Webapp/ArticleController.php`
- `src/Repository/Webapp/ArticleRepository.php`
- `templates/webapp/articles/listallarticles.html.twig`
- `templates/webapp/articles/include/_listesearch.html.twig`

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
