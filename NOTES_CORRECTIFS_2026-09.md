# Correctifs divers — septembre 2026

Petits correctifs du 08/09/2026 qui ne relèvent d'aucun des chantiers documentés séparément
(`NOTES_RECHERCHE_ARTICLES.md`, `NOTES_GESTION_PAGES.md`, `NOTES_TOMSELECT_ET_TURBO.md`).

---

## 1. Suppression du `meta refresh` 300 s du layout admin

`templates/admin.html.twig` :

```diff
- <meta http-equiv="refresh" content="300">
```

Le back-office se rechargeait **intégralement toutes les 5 minutes**. Sur une saisie longue (édition de
contenu CKEditor, formulaire de page), le rechargement se produisait pendant la frappe et **faisait
perdre tout le travail non enregistré**. Aucune raison fonctionnelle identifiée pour ce refresh.

## 2. Fix 500 à l'enregistrement d'un article (`editAdmin`)

`src/Controller/Webapp/ArticleController.php`, `editAdmin()` :

```php
// avant
$supprvignettechkbx = $form->get('isSupprImage')->getData();
$supprDocChkbx       = $form->get('isSupprDoc')->getData();

// après
$supprvignettechkbx = $form->has('isSupprImage') ? $form->get('isSupprImage')->getData() : null;
$supprDocChkbx       = $form->has('isSupprDoc')   ? $form->get('isSupprDoc')->getData()   : null;
```

`ArticlesType` ne déclare plus les cases `isSupprImage` / `isSupprDoc` (retirées en `a12aca4`), mais
`editAdmin()` les lisait encore via `$form->get(...)` → `OutOfBoundsException` **à chaque
enregistrement** d'article depuis l'admin. Lecture désormais gardée par `$form->has(...)` ; les blocs
« STEP 1 » (suppression image) et « STEP 3 » (suppression support) deviennent inertes tant que les
champs sont absents du formulaire.

## 3. Fix CSRF de la page de login

`templates/admin/security/login.html.twig` :

```diff
  <input type="hidden" name="_csrf_token"
+        data-controller="csrf-protection"
         value="{{ csrf_token('authenticate') }}">
```

Le jeton `authenticate` est en **CSRF stateless** (double-submit cookie, `config/packages/csrf.yaml`) :
le contrôleur Stimulus `csrf-protection` doit s'exécuter au `submit` pour poser le cookie
correspondant. Le champ `_csrf_token` étant **écrit à la main** dans `login.html.twig` (et non généré
par le form builder Symfony), l'attribut `data-controller` manquait ⇒ le contrôleur (chargé en *lazy*)
ne s'initialisait jamais sur cette page ⇒ « Invalid CSRF token » au login.

Masqué jusque-là par Turbo Drive, qui gardait un écouteur `submit` global persistant d'une page à
l'autre : la régression n'est apparue qu'avec la désactivation de Turbo
(`NOTES_TOMSELECT_ET_TURBO.md`, §4).

## 4. Suppression des médias sur la page Paramètres du site

Symptôme : sur `admin/config/edit.html.twig`, les boutons « supprimer » du bandeau et de la vignette ne
font rien, et le JS dédié (`EditConfig.js`) ne contient que `bindHeaderSaveButton()` + CKEditor.

Cause (cumul de trois trous, jamais complété depuis le passage du composant `bloc_insert_image` à un
bouton AJAX `data-action="delete-media"`) :

1. **`EditConfig.js`** n'avait aucun gestionnaire pour `[data-action="delete-media"]`.
2. **`admin/config/_form.html.twig`** ne passait pas `delete_url` au composant (il passait un
   `routename` bidon `op_webapp_message_new`, ignoré) → `data-delete-url=""` vide, donc le handler
   sortirait de toute façon sur `if (!url) return`.
3. **`ConfigController`** n'avait aucune route de suppression de média (contrairement à
   `op_admin_etablissement_delete_media`).

Le mécanisme historique (case `isSupprVignette` traitée au submit dans `edit()`) était en plus
**buggé** : `STEP 1` faisait `setHeaderName(null)` au lieu de `setVignetteName(null)`, `STEP 2`
cherchait le fichier à écraser dans `etablissement_directory` au lieu de `config_directory`, et le
`headerFile` (bandeau) n'était jamais traité à l'édition.

### Correctif — calqué sur la page établissement

- **`ConfigController::deleteMedia()`** : nouvelle route
  `POST /opadmin/config/{id}/delete-media/{field}` (`op_admin_config_delete_media`), `field ∈
  {header, vignette}` → `unlink` dans `config_directory` + `set*Name(null)` + `flush`, réponse JSON
  `{code, message}`. Deux helpers privés `deleteConfigFile()` / `storeConfigFile()`.
- **`ConfigController::edit()`** réécrit : traitement **symétrique** de `headerFile` et `vignetteFile`
  (remplacement = suppression de l'ancien fichier puis `storeConfigFile()`) ; suppression du bloc
  `STEP 1` mort et du champ `isSupprVignette` (retiré de `ConfigType`).
- **`Config::$vignetteName`** rendu nullable (colonne `config.vignette_name` était `NOT NULL`) —
  migration `Version20260909130000` (`ALTER TABLE config CHANGE vignette_name … DEFAULT NULL`).
  `header_name` était déjà nullable.
- **`admin/config/_form.html.twig`** : `delete_url` = `path('op_admin_config_delete_media', {id, field})`
  sur les deux blocs `bloc_insert_image`.
- **`EditConfig.js`** : ajout du handler `[data-action="delete-media"]` — `showDialog(url, …,
  onConfirm)` (API 4 args, un seul écouteur délégué sur `#validModal`, cf.
  `NOTES_BOUTON_ENREGISTRER_HEADER.md` §4) puis `axios.post` + `location.reload()`.

### Correctif complémentaire — bandeau de la page d'accueil

Après upload d'un nouveau bandeau, celui-ci n'apparaissait plus sur la home : `headershow.html.twig`
lisait l'image dans `uploads/images/collections/` alors que tous les autres consommateurs de
`config.headerName` (aperçu de `_form.html.twig`, `webapp/articles/show.html.twig`,
`_blocarticle.html.twig`) et le `config_directory` où les fichiers sont écrits pointent vers
`uploads/images/config/`. L'ancien bandeau existait par hasard dans `collections/` (fichiers hérités),
d'où l'effet « masqué » jusqu'au premier vrai changement d'image. `headershow.html.twig` corrigé vers
`uploads/images/config/`.

---

## Fichiers modifiés

- `templates/admin.html.twig` (retrait du `meta refresh`)
- `src/Controller/Webapp/ArticleController.php` (`editAdmin()` : `$form->has()` avant `$form->get()`)
- `templates/admin/security/login.html.twig` (`data-controller="csrf-protection"`)
- `src/Controller/Admin/ConfigController.php` (§4 : `deleteMedia()`, `edit()` réécrit, helpers)
- `src/Entity/Admin/Config.php` (§4 : `vignetteName` nullable)
- `src/Form/Admin/ConfigType.php` (§4 : retrait de `isSupprVignette`)
- `templates/admin/config/_form.html.twig` (§4 : `delete_url`)
- `templates/admin/config/headershow.html.twig` (§4 : dossier `collections/` → `config/`)
- `assets/js/admin/admin/EditConfig.js` (§4 : handler `delete-media`)

## Fichiers créés

- `NOTES_CORRECTIFS_2026-09.md`
- `migrations/Version20260909130000.php` (§4)

## Déploiement

- Rebuild des assets pour §3 et §4.
- **§4 : `php bin/console doctrine:migrations:migrate`** (colonne `vignette_name` nullable) — sans
  cette migration, la suppression de la vignette renvoie une 500 sur `flush` (la suppression du bandeau
  fonctionne sans, `header_name` étant déjà nullable).
- Rien côté Elasticsearch.
