# Vue "établissements par section" — regroupement, pagination et recherche

## Contexte

La vue `admin/etablissement/listetablissementsbysection.html.twig` (route `_etablissementsbysection`,
contrôleur `EtablissementController::listEtablissementsBySection`) est incluse en fragment (`render(controller(...))`)
depuis les pages de section (`section.content == 'ALL_ETABLISSEMENTS'`, voir
`templates/webapp/section/listsections.html.twig` et `include/_onesection.html.twig`).
Elle affichait initialement tous les établissements actifs d'une section sous forme de grille plate.

Trois évolutions ont été apportées à cette vue :

1. Regroupement visuel par **type d'établissement**.
2. Limitation à **8 établissements affichés par type**, avec bouton "Voir tous" vers une page dédiée si le type en compte plus.
3. Ajout du **markup HTML** d'un formulaire de recherche (texte + filtre par type), sans logique de traitement.

---

## 1. Regroupement par type d'établissement

- `EtablissementRepository::listEtablissementsBySection($idsection)` : ajout d'une jointure `e.typeEtablissement` (`t`)
  et sélection de `t.id as idTypeEtablissement, t.libelle as typeEtablissementLibelle`, tri par libellé de type puis par ville.
- `EtablissementController::listEtablissementsBySection` : regroupe le résultat plat en `$etablissementsByType`
  (clé = libellé du type, `'Autre'` si non renseigné) avant de le passer au twig.
- Le twig boucle sur `etablissementsByType` et affiche un `<h3>` par type au-dessus de chaque grille.

## 2. Limite à 8 + page dédiée par type

- Dans le twig, chaque grille utilise `etablissements|slice(0, 8)`.
- Si `etablissements|length > 8`, un bouton "Voir tous les établissements (N)" apparaît, pointant vers la nouvelle route
  `op_webapp_etablissement_bytype` (`/webapp/etablissement/type/{idtype}`), avec `idtype = etablissements[0].idTypeEtablissement`.
- Nouveau : `EtablissementRepository::listEtablissementsByType($idTypeEtablissement)` (établissements actifs d'un type donné, triés par ville).
- Nouveau : `EtablissementController::listEtablissementsByType` — rend `templates/admin/etablissement/listetablissementsbytype.html.twig`
  (page complète, `extends base.html.twig`, même grille visuelle, titre = libellé du type).

## 3. Markup du formulaire de recherche (sans logique)

Un `<form method="get" action="">` a été ajouté en haut de la vue, avec :
- un champ texte `q` (recherche libre, style repris du composant `composants/forms/inputs/input_search.html.twig`) ;
- un `<select name="typeEtablissement">` listant les types présents dans `etablissementsByType` ;
- un bouton "Rechercher".

**Ce formulaire n'est pas branché.** Il n'y a ni route de traitement, ni contrôleur, ni requête Elastica derrière —
uniquement le HTML/Tailwind, à intégrer volontairement par vos soins.

### État de la configuration Elastica (constat, non modifié)

`config/packages/fos_elastica.yaml` indexe actuellement `App\Entity\Gestapp\Articles`, **une classe qui n'existe pas**
dans le code (probablement un reliquat du scaffolding initial du bundle, cf. commit "Préparation fos/elastica -
installation du bundle"). L'entité réelle représentant les "posts" des établissements est `App\Entity\Webapp\Article`
(`title`, `intro`, `content`, `etablissement`, `createdAt`, ...), reliée à `Etablissement` (et donc à `TypeEtablissement`
via `etablissement.typeEtablissement`). Un service Elasticsearch 7.17 tourne déjà en local (`docker-compose`,
port 9200) et `friendsofsymfony/elastica-bundle` (v7.2.0) est installé. À corriger/compléter avant de brancher la
recherche sur cette entité.

---

## Fichiers créés

- `templates/admin/etablissement/listetablissementsbytype.html.twig`

## Fichiers modifiés

- `templates/admin/etablissement/listetablissementsbysection.html.twig`
- `src/Repository/Admin/EtablissementRepository.php`
- `src/Controller/Admin/EtablissementController.php`

## Vérifications effectuées

- `php bin/console lint:twig` sur les deux templates — OK.
- `php -l` sur le contrôleur et le repository — OK.
- `php bin/console debug:router` — route `op_webapp_etablissement_bytype` bien enregistrée, pas de conflit.

---

## Mise à jour du 12/08/2026 — Branchement du formulaire (JS, contrôleur, Elasticsearch)

Le formulaire de recherche décrit en section 3 (jusque-là pur HTML, non branché) a été rendu opérationnel de
bout en bout : soumission JS en AJAX, validation côté Symfony, filtrage Elasticsearch, réinjection du résultat.
Au passage, plusieurs bugs préexistants et sans lien direct avec la recherche ont été corrigés sur le chemin
(dispatch JS par route, doublon de nom de route Symfony, index Elasticsearch orphelin).

### 4. Dispatch JS par route (`assets/app.js`)

Le script de page (`initShowPage()`, `assets/js/app/page/show.js`) n'était appelé que pour la route nommée
`op_webapp_page`. Or le template `webapp/page/page.html.twig` est rendu par trois routes différentes
(`op_webapp_page`, et deux routes portant le **même nom** `op_webapp_page_slug` — voir point suivant), donc le
JS de page ne s'exécutait pas selon le chemin d'accès emprunté.

- `assets/app.js` : ajout des cas `op_webapp_page_slug` et `op_webapp_page_display` dans le `switch` du
  dispatcher, en plus de `op_webapp_page`.

### 5. Doublon de route Symfony (`PageController.php`)

`PageController` définissait **deux routes nommées `op_webapp_page_slug`** :
- `page()` (ligne ~145, chemin `/webapp/page/{slug}`, avec vérification "site hors-ligne"), et
- `pagebyslug()` (ligne ~221, chemin `/page/{slug}`).

Un nom de route dupliqué fait que Symfony ne conserve que la dernière route déclarée sous ce nom pour la
génération d'URL (`path()`) — la première (`page()`, avec la logique hors-ligne) était donc **inaccessible**
depuis les templates, bien que son URL reste théoriquement valide.

- `page()` renommée en `op_webapp_page_display`.
- Bug additionnel corrigé dans la foulée : `page()` ne transmettait pas la variable `page` au template
  (`return $this->render('webapp/page/page.html.twig')` sans paramètre), contrairement à `pagebyslug()` et à
  `DashboardController::showPage()` — corrigé pour passer `'page' => $page`.

### 6. Soumission JS du formulaire (`show.js`)

`initShowPage()` détecte la présence d'un formulaire dans `#form_search`, intercepte sa soumission et l'envoie
en AJAX via `axios` plutôt que de laisser le navigateur naviguer nativement :

```js
const section = document.querySelector('#form_search');
if (section) {
    const form = section.querySelector('form');
    const button = section.querySelector('button[type="submit"], input[type="submit"]');
    if (form && button) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            const formData = new FormData(form);
            axios.post(form.action, formData).then(response => {
                const results = document.querySelector('#form_results');
                if (results && response.data.liste) {
                    results.innerHTML = response.data.liste;
                }
            }).catch(error => console.error('Erreur lors de la recherche', error));
        });
    }
}
```

Le formulaire s'appuie sur son `action`/`method` propres (générés par Symfony) plutôt que sur une URL codée en
dur côté JS. Le mécanisme de protection CSRF "same-origin" existant (`assets/controllers/csrf_protection_controller.js`,
double-submit cookie) fonctionne tel quel avec ce flux : le `submit` natif déclenché avant `preventDefault()`
laisse le listener CSRF (posé en phase de capture sur `document`) régénérer le token et le cookie avant que le
handler ci-dessus ne lise le `FormData`.

### 7. `EtablissementSearchType` et cohérence des choix du `<select>`

Plusieurs bugs en cascade sur le champ `etablissementChoice` (`ChoiceType`) :

- Les `choices` passées au formulaire (`$etablissementsChoices[$type] = $e`, une ligne brute) ne correspondaient
  ni aux valeurs que Symfony génère pour ses `<option>`, ni à ce qui était écrit à la main dans le twig
  (`<option value="{{ type }}">`) — le formulaire échouait systématiquement sa validation dès qu'un type était
  sélectionné (`isValid() === false`, sans autre message explicite côté template).
- Le template (`listetablissementsbysection.html.twig`) rend maintenant les options depuis
  `form.etablissementChoice.vars.choices` (`choice.value` / `choice.label`, la liste réelle générée par
  Symfony) plutôt que depuis une boucle manuelle sur les données brutes — garantit que la valeur soumise
  correspondra toujours à ce que le formulaire attend, quelle que soit l'évolution des `choices`.
- Les `choices` construites côté contrôleur utilisent désormais l'**ID** du type comme valeur et le **libellé**
  comme clé/label (`$etablissementsChoices[$type] = $e['idTypeEtablissement']`) — pour matcher le mapping
  Elasticsearch (`typeEtablissement.id`, cf. section 8) plutôt qu'une comparaison par libellé.

### 8. Contrôleur : filtrage Elasticsearch et cohérence des formats de données

`EtablissementController::listEtablissementsBySection` (route renommée `op_admin_etablissement_bysection`,
désormais `methods: ['GET', 'POST']` — elle n'acceptait que `GET`, incompatible avec la méthode `POST` par
défaut d'un formulaire Symfony) :

- **Fusion incorrecte des résultats** : après une recherche, les résultats Elasticsearch étaient ajoutés à
  `$etablissementsByType` déjà rempli avec la liste complète (non filtrée) construite en haut de la méthode,
  au lieu de la remplacer — corrigé en réinitialisant `$etablissementsByType = []` avant d'y injecter les
  résultats filtrés.
- **Incompatibilité entité/tableau** : `finder->find($query)` (FOSElastica) retourne des objets `Etablissement`
  hydratés, alors que la requête native utilisée au chargement initial (`EtablissementRepository::listEtablissementsBySection`)
  renvoie des tableaux associatifs bruts (`id`, `name`, `city`, `isActive`, `logoName`, `idsection`,
  `idTypeEtablissement`, `typeEtablissementLibelle`). Le même template (`_listesearch.html.twig`) consommant
  les deux, chaque source cassait l'autre selon la clé/le getter attendu. Corrigé en normalisant les résultats
  Elasticsearch en tableaux du même format avant de les passer au twig :
  ```php
  foreach ($results as $r) {
      $type = $r->getTypeEtablissement()?->getLibelle() ?? 'Autre';
      $etablissementsByType[$type][] = [
          'id' => $r->getId(),
          'name' => $r->getName(),
          'city' => $r->getCity(),
          'isActive' => $r->getIsActive(),
          'logoName' => $r->getLogoName(),
          'idTypeEtablissement' => $r->getTypeEtablissement()?->getId(),
          'typeEtablissementLibelle' => $type,
      ];
  }
  ```
- Réponse JSON de la branche AJAX : ajout de la variable `config` manquante au `renderView('admin/etablissement/include/_listesearch.html.twig', ...)` (utilisée pour l'image de secours quand un établissement n'a pas de logo).
- Clé de données incorrecte corrigée : `$data['structure']` (n'existe pas) → `$data['etablissementChoice']`
  dans la construction du `Term` Elasticsearch.
- Nom de champ Elasticsearch corrigé pour le filtre par type : `structure.id` (hérité d'un autre outil, sans
  rapport avec le mapping `Etablissement`) → `typeEtablissement.id`, conforme au mapping (voir section 9).

### 9. Elasticsearch : ré-indexation après renommage `articles` → `etablissement`

`config/packages/fos_elastica.yaml` a été reconfiguré : l'index `articles` (modèle inexistant
`App\Entity\Gestapp\Articles`, cf. constat section 3 ci-dessus) a été remplacé par un index `etablissement`
(modèle `App\Entity\Admin\Etablissement`, propriétés `name`, `zipcode`, `city`, `typeEtablissement.id`).

Renommer la configuration ne renomme ni ne supprime l'index déjà créé côté Elasticsearch : `GET /_cat/indices?v`
montrait encore un index `articles` (44 docs) en plus du nouvel index `etablissement`, tous deux visibles via
`GET /_search` (qui interroge l'ensemble des index du cluster en l'absence de nom d'index explicite dans l'URL).

- Ancien index supprimé : `DELETE http://127.0.0.1:9202/articles`.
- Ré-indexation : `php bin/console fos:elastica:populate` (reset + 44/44 documents réindexés sous `etablissement`).

---

## Mise à jour du 17/08/2026 — Réinitialisation du formulaire après recherche

`show.js` (§6) est partagé entre cette vue et la recherche d'articles (voir `NOTES_RECHERCHE_ARTICLES.md`) : les
deux formulaires (`#form_search`) sont interceptés par le même gestionnaire `submit`. Après réception de la
réponse AJAX et réinjection du HTML dans `#form_results`, un appel à `form.reset()` a été ajouté pour vider les
champs du formulaire (texte + selects) après chaque recherche réussie, plutôt que de laisser les valeurs
saisies affichées.

```js
.then(response => {
    const results = document.querySelector('#form_results');
    if (results && response.data.liste) {
        results.innerHTML = response.data.liste;
    }
    form.reset();
})
```

`form.reset()` revient aux valeurs par défaut du DOM au chargement de la page — comme ces formulaires ne sont
jamais pré-remplis côté serveur au chargement initial, cela revient en pratique à des champs vides.

## Fichiers modifiés (session du 12/08/2026)

- `assets/app.js`
- `assets/js/app/page/show.js`
- `src/Controller/Webapp/PageController.php`
- `src/Controller/Admin/EtablissementController.php`
- `src/Form/Search/EtablissementSearchType.php`
- `templates/admin/etablissement/listetablissementsbysection.html.twig`
- `templates/admin/etablissement/include/_listesearch.html.twig`
- `config/packages/fos_elastica.yaml`

## Vérifications effectuées (session du 12/08/2026)

- `php -l` sur les contrôleurs modifiés — OK.
- `php bin/console lint:twig` sur les templates modifiés — OK.
- `php bin/console debug:router` — plus de doublon sur `op_webapp_page_slug` / `op_webapp_page_display`.
- Tests `curl` simulant la soumission du formulaire (CSRF "same-origin" reconstitué manuellement, cookie +
  token) : chargement (GET) 200, soumission sans filtre 200 avec résultats complets, soumission avec filtre
  type 200, lien "voir tous" (>8 résultats) sans erreur.
- `GET /_cat/indices?v` avant/après suppression de l'index `articles` et ré-indexation de `etablissement`.
