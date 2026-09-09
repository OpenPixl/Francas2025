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

---

## Fichiers modifiés

- `templates/admin.html.twig` (retrait du `meta refresh`)
- `src/Controller/Webapp/ArticleController.php` (`editAdmin()` : `$form->has()` avant `$form->get()`)
- `templates/admin/security/login.html.twig` (`data-controller="csrf-protection"`)

## Fichiers créés

- `NOTES_CORRECTIFS_2026-09.md`

## Déploiement

- Rebuild des assets pour §3 (dépend du contrôleur Stimulus `csrf-protection`, déjà présent).
- Rien côté base ni Elasticsearch.
