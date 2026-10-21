# Correctifs divers — septembre 2026

Petits correctifs (à partir du 08/09/2026, §6-7 du 21/10/2026) qui ne relèvent d'aucun des chantiers documentés séparément
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

## 5. Dropdown utilisateur non fonctionnel dans la navbar admin

Symptôme (11/09/2026) : dans la navbar admin, le dropdown sous l'avatar (dernier élément à
droite, représentant l'utilisateur connecté) ne s'ouvrait plus au clic.

Cause : `#menu-button` / `#menu-dropdown` (`templates/admin/include/navbar_admin.html.twig`)
sont un extrait de template Tailwind UI (normalement piloté par Alpine.js/React), collé sans
JS de câblage. Le mécanisme générique de `assets/admin.js` ne gère que les éléments porteurs de
la classe `.dropdown-toggle` (dropdowns « Sites » / « Ressources » / « Paramètres » de la barre
horizontale) — l'avatar n'a pas cette classe, donc aucun écouteur ne se déclenchait. Les 3 liens
du menu (« Votre profil », « Paramètres », « Déconnexion ») pointaient en plus vers `href="#"`,
y compris la déconnexion.

### Correctif

- **`assets/admin.js`** : ajout d'un bloc dédié — clic sur `#menu-button` bascule la classe
  `hidden` de `#menu-dropdown` ; clic en dehors des deux éléments referme le menu. Volontairement
  séparé du mécanisme `.dropdown-toggle` existant (structure DOM différente : le bouton et le
  menu ne sont pas des frères directs ici).
- **`templates/admin/include/navbar_admin.html.twig`** : les 3 liens pointent désormais vers de
  vraies routes — « Paramètres » → `op_admin_user_edit` (fiche du user connecté), « Déconnexion »
  → `op_admin_security_logout`. Le 1er lien (« Votre profil ») a ensuite été repurposé en
  « Administrateurs » → `op_admin_user_list_administrateur`, cf.
  `NOTES_ADMIN_LISTE_ADMINISTRATEURS.md`.

## 6. Archivage d'un article depuis l'espace Établissement

Symptôme (21/10/2026) : sur la liste des articles de l'espace « gestion » d'un établissement
(`op_espetab_articles_byetablissement`, à ne pas confondre avec la liste admin), le bouton
`a.btnArchived` suivait simplement son lien (GET sur une route `POST` uniquement) : ni modale
d'avertissement, ni archivage.

Cause :

1. Le JS de la page était un `<script>` inline dans `articlesbyetablissement.html.twig`, encore écrit
   pour **Bootstrap** (`bootstrap.Modal`, `#ModalWarning`, `#btnPushReq`, `bootstrap.Toast`) — plus
   chargé sur le site, qui est passé aux modales/notifications Tailwind.
2. `assets/js/app/article/IndexArticles.js` (copie de ce script, avec un `openModal()` vide) n'était
   importé nulle part dans `assets/app.js`.
3. La réponse AJAX remplace `#listeArticles`… qui n'existait pas dans le template.
4. `ArticleController::archived()` rend `_listebyetablissement.html.twig` avec les lignes de
   `listArticlesByEtablissement()` (tableaux, pas des entités) **sans** passer par
   `withArticleMediaUrls` : le template retombe alors sur `article_image_url(article)`, typé
   `Article` ⇒ `TypeError` / 500 dès qu'un article de l'établissement a une image.

### Correctif — calqué sur la liste admin (`assets/js/admin/webapp/IndexArticles.js`)

- **`assets/js/app/article/IndexArticles.js`** réécrit : clic sur `a.btnArchived` →
  `showDialog(href, titre, message, onConfirm)` (API 4 args, un seul écouteur délégué sur
  `#validModal`, cf. `NOTES_BOUTON_ENREGISTRER_HEADER.md` §4) ; à la validation, `axios.post(url)`,
  remplacement de `#listeArticles` par `response.data.listeArticles`, `showNotification('success', …)`,
  puis `reload()` pour rebrancher les boutons de la nouvelle liste. Erreur →
  `showNotification('warning', …)`.
- **`assets/app.js`** : import de `initIndexArticle` et `case 'op_espetab_articles_byetablissement'`
  dans l'aiguillage par `data-page`.
- **`templates/webapp/articles/articlesbyetablissement.html.twig`** : suppression du bloc
  `javascripts` inline Bootstrap ; include de la liste enveloppé dans `<div id="listeArticles">`.
- **`ArticleController::archived()`** : `array_map($this->mediaPathResolver->withArticleMediaUrls(...), …)`
  sur la liste renvoyée, comme dans `listArticlesByEtablissement()`.

### Correctif complémentaire — articles archivés réaffichés au rechargement

Après archivage, l'article disparaissait bien de la liste (réponse AJAX) mais **réapparaissait au
rechargement de la page**. Contrôle en base : `article.is_archived = 1` → la requête d'archivage
aboutit, c'est l'affichage initial qui ne filtrait pas.

`articlesByEtablissement()` listait tous les articles de l'établissement via
`findBy(['etablissement' => …])`, archivés compris, alors que la liste renvoyée après archivage
(`listArticlesByEtablissement()`) filtre `isArchived = 0`. Ajout du critère au `findBy` :

```php
$data = $articleRepository->findBy(
    ['etablissement' => $etablissement, 'isArchived' => 0],
    ['updatedAt' => 'DESC']
);
```

Les deux listes (chargement initial et rafraîchissement AJAX) appliquent désormais le même filtre.
NB : un article dont `is_archived` serait `NULL` est masqué dans les deux cas.

## 7. Articles archivés exclus de toutes les listes, tri homogène

Suite du §6 (21/10/2026) : contrôle de toutes les listes d'articles (admin et front) pour qu'elles
excluent les archivés et soient triées par date de modification décroissante.

| Liste | Avant | Après |
|---|---|---|
| Admin `op_webapp_articles_index` (+ rechargement après suppression `op_webapp_article_del`) | archivés inclus, tri `id DESC` | `ArticleRepository::queryByArchived()` : non archivés (onglet par défaut), `updatedAt DESC, id DESC` |
| Dashboard admin (`Admin\DashboardController::index`, 10 derniers) | archivés inclus, tri `id DESC` | filtre `isArchived = false`, `updatedAt DESC, id DESC` |
| Tableau de bord espace établissement (`op_webapp_espetab`, compteur « N publiés ») | archivés comptés, sans tri | `findBy([…, 'isArchived' => 0], ['updatedAt' => 'DESC'])` |
| Carrousel `listFiveArticles()` | archivés inclus | filtre `isArchived = 0` (tri `updatedAt DESC` déjà présent) |
| Recherches Elasticsearch : admin (navbar), suggestions `search-live`, `op_webapp_article_all_by_etablissement`, `op_webapp_articles_bytype` | archivés inclus (champ non indexé) | `addMustNot(Term isArchived = true)` |

Déjà conformes : `allArticles()`, `listArticlesByEtablissement()`, `listArticlesBySection()`,
`listLatestAudioArticles()`, `articleEtablissementSlug()`.

**Elasticsearch** : ajout de `isArchived: { type: boolean }` au mapping de l'index `article`
(`config/packages/fos_elastica.yaml`). Le filtre est un `must_not isArchived = true` (et non un
`filter isArchived = false`) : tant que l'index n'est pas reconstruit, les documents sans le champ
restent visibles au lieu de disparaître tous. Le listener Doctrine réindexe l'article à l'archivage.

### Filtre « Archivés » dans la liste admin

Pour que l'admin garde l'accès aux archivés, la liste `op_webapp_articles_index` a deux onglets
**En ligne / Archivés** (avec compteurs), pilotés par `?archived=1` :

- `ArticleRepository::queryByArchived(bool $archived)` (remplace `queryNotArchived()`).
- `index()` : lit `archived` ; recherche ES → `filter isArchived = true` en vue archivés,
  `must_not` sinon ; compteurs via `count(['isArchived' => …])`.
- `DelEvent()` : relit `archived` pour recharger la bonne liste après suppression ; le lien de
  suppression de `_liste.html.twig` transmet `archived=1` quand le filtre est actif.
- `assets/js/admin/webapp/IndexArticles.js` : en vue archivés, ajoute un `<input hidden name="archived">`
  au formulaire de recherche de la navbar (hors du préfixe `navbar_article_search[…]`, donc ignoré
  par la validation du formulaire).
- La pagination KNP conserve le paramètre automatiquement.

Limite : les suggestions instantanées (`search-live`) n'affichent toujours que les articles en
ligne.

### Désarchivage depuis l'onglet « Archivés »

- **`ArticleController::unarchived()`** : `POST /webapp/article/unarchived/{id}/{page}`
  (`op_webapp_article_unarchived`) → `setIsArchived(false)`, réponse JSON `{code, message, liste,
  countOnline, countArchived}` avec la liste des archivés rechargée. Le listener Elastica réindexe
  l'article.
- **`_liste.html.twig`** : icône `fa-box-open` affichée seulement si `archived`, classe `openDialog`
  avec `data-dialog-title` / `data-dialog-message`.
- **`IndexArticles.js` (admin)** : `openDialog()` lit le titre/message du bouton (suppression par
  défaut) ; `submitModal()` met à jour les compteurs des onglets (`#countOnline`, `#countArchived`,
  renvoyés aussi par `DelEvent()`) et affiche le message en notification.
- **Bug corrigé au passage** : l'écouteur `submitModal` du bouton « Valider » de la modale (hors de
  `#liste`) était rebranché à chaque `reload()` → après une première action AJAX, la confirmation
  suivante envoyait la requête **en double** (2ᵉ suppression en 404). Il est désormais branché une
  seule fois.

Non traité : `listArticlesByEtablissements()` (tri `id ASC`) n'est appelée nulle part (code mort).

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
- `assets/admin.js` (§5 : câblage du dropdown utilisateur)
- `templates/admin/include/navbar_admin.html.twig` (§5 : liens du dropdown utilisateur)
- `assets/js/app/article/IndexArticles.js` (§6 : modale + requête d'archivage)
- `assets/app.js` (§6 : branchement sur `op_espetab_articles_byetablissement`)
- `templates/webapp/articles/articlesbyetablissement.html.twig` (§6 : retrait script Bootstrap, `#listeArticles`)
- `src/Controller/Webapp/ArticleController.php` (§6 : `archived()` → `withArticleMediaUrls` ; `articlesByEtablissement()` → filtre `isArchived = 0` ; §7 : filtres ES + `queryByArchived()`, filtre admin)
- `src/Repository/Webapp/ArticleRepository.php` (§7 : `queryByArchived()`, filtre dans `listFiveArticles()`)
- `src/Controller/Admin/DashboardController.php` (§7)
- `src/Controller/App/DashboardController.php` (§7)
- `config/packages/fos_elastica.yaml` (§7 : champ `isArchived`)
- `templates/webapp/articles/index.html.twig` (§7 : onglets En ligne / Archivés)
- `templates/webapp/articles/include/_liste.html.twig` (§7 : `archived` sur le lien de suppression, bouton de désarchivage)
- `assets/js/admin/webapp/IndexArticles.js` (§7 : `archived` transmis à la recherche navbar, désarchivage, écouteur `submitModal` unique)

## Fichiers créés

- `NOTES_CORRECTIFS_2026-09.md`
- `migrations/Version20260909130000.php` (§4)

## Déploiement

- Rebuild des assets pour §3, §4 et §6 (`npm run build`).
- **§4 : `php bin/console doctrine:migrations:migrate`** (colonne `vignette_name` nullable) — sans
  cette migration, la suppression de la vignette renvoie une 500 sur `flush` (la suppression du bandeau
  fonctionne sans, `header_name` étant déjà nullable).
- **§7 : `php bin/console fos:elastica:populate --index=article`** (nouveau champ `isArchived`) — sans
  cela les archivés restent visibles dans les listes issues d'une recherche ES.
- Vidage du cache (`cache:clear`) pour §6/§7.
