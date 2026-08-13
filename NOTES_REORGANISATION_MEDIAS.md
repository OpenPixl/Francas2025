# Réorganisation du stockage des médias (établissements + articles)

## Contexte

Tous les logos/bandeaux d'établissements atterrissaient dans un seul dossier plat
(`public/uploads/images/etablissements/`) et toutes les images/documents d'articles dans un autre
(`public/uploads/images/articles/`) — établissements mélangés entre eux, articles mêlés à leurs pièces
jointes (audio/vidéo/pdf), noms de fichiers illisibles (`slug-original-uniqid.ext`). L'objectif : ranger
chaque fichier sous son propriétaire réel (établissement, ou administrateur auteur si l'article n'est lié
à aucun établissement) puis par type de média, avec un nom de fichier lisible et déterministe.

```
public/uploads/etablissements/{etablissement_id}/images/     ← logo + bandeau de l'établissement
public/uploads/etablissements/{etablissement_id}/articles/   ← images des articles de cet établissement
public/uploads/etablissements/{etablissement_id}/audios/     ← pièce jointe audio (support=1)
public/uploads/etablissements/{etablissement_id}/videos/     ← pièce jointe vidéo (support=2)
public/uploads/etablissements/{etablissement_id}/docs/       ← pièce jointe pdf/document (support=3)

public/uploads/admins/{user_id}/articles/                    ← images des articles rédigés par un admin (pas d'établissement)
public/uploads/admins/{user_id}/audios|videos|docs/          ← idem, pièces jointes
```

Nommage des fichiers, déterministe :
- Établissement : `{slug du nom}_bandeau.{ext}` / `{slug du nom}_avatar.{ext}` (ex. `pays-des-luys_bandeau.jpg`)
  — inchangé, conservé volontairement tel quel (voir §7bis).
- Article : `{id}_article.{ext}` pour l'image, `{id}_audio`/`{id}_video`/`{id}_doc.{ext}` selon le support pour
  la pièce jointe (ex. `69_article.jpg`, `69_doc.docx`). Pas de slug du titre : l'ID suffit à garantir
  l'unicité dans le dossier partagé entre tous les articles du même propriétaire, et évite des noms de
  fichiers à rallonge pour les titres longs (voir §7bis — ce point a changé après la migration initiale).

Ce chantier s'est fait en trois temps : d'abord la réorganisation en dossiers imbriqués (structure ci-dessus,
noms de fichiers encore aléatoires), validée et déployée ; puis, sur demande explicite, le renommage des
fichiers déjà migrés vers une convention lisible incluant le slug du titre côté article ; enfin, toujours sur
demande explicite, la simplification de cette convention côté article pour retirer le slug (§7bis).

---

## 1. Service central : `App\Service\MediaPathResolver` (`src/Service/MediaPathResolver.php`)

Trois familles de méthodes :
- **`*Dir()`** → chemin absolu disque, utilisé par les contrôleurs avant `move()`/`unlink()`. Crée le dossier
  (`Filesystem::mkdir()`, idempotent) s'il n'existe pas encore.
- **`*Url()`** → chemin public relatif (ex. `/uploads/etablissements/42/images/pays-des-luys_avatar.png`),
  pure construction de chaîne, aucun accès disque — utilisé par l'extension Twig, enveloppé dans `asset()`
  côté template.
- **`*Filename()`** → construit le nom de fichier déterministe à partir du slug de l'entité (Symfony
  `SluggerInterface` pour l'établissement, slug déjà persistant sur `Article`) et d'un suffixe fixe selon
  le type de média.

Méthodes bas niveau (par ID, réutilisables depuis les tableaux Elastica/DQL, notamment dans
`withArticleMediaUrls()` — voir §4) : `etablissementImageDir/Url(int $etablissementId, ?string $filename)`,
`articleImageDir/Url(?int $etablissementId, int $authorId, ?string $filename)`,
`resolveDocSubfolder(?int $supportId, ?string $filenameHint)` (id → `audios`/`videos`/`docs`, repli sur
l'extension du fichier si le support est absent ou vaut « Aucun » (id `0`), défaut `docs` en dernier
recours), `articleDocDir/Url(...)`.

Wrappers entité (contrôleurs + extension Twig) : `etablissementLogoDir/Url/Filename(Etablissement)`,
`etablissementHeaderDir/Url/Filename(Etablissement)`, `articleImageDirFor/UrlFor/Filename(Article)`,
`articleDocDirFor/UrlFor/Filename(Article)`, et `withArticleMediaUrls(?array $row): ?array` (ajoute
`imageUrl`/`docUrl`/`logoUrl` à une ligne d'article projetée en tableau — voir §4).

Mapping support→dossier basé sur l'**ID** (1=audios, 2=videos, 3=docs) — même convention que
`getDocConstraintsBySupportId()` déjà présente dans `ArticlesType`/`Articles2Type`.

## 2. `config/services.yaml`

```yaml
etablissements_media_directory: '%kernel.project_dir%/public/uploads/etablissements'
admins_media_directory: '%kernel.project_dir%/public/uploads/admins'
```

`article_directory`/`etablissement_directory` (anciens chemins plats) conservés tels quels — nécessaires
comme dossiers **source** pour la commande de migration (§6), à retirer dans un futur nettoyage.

`MediaPathResolver` câblé avec ces deux paramètres ; `EtablissementController` et `ArticleController`
reçoivent le service en second argument (même modèle que les blocs Elastica déjà présents) ;
`RenameMediaToSlugCommand` (§7) n'a besoin d'aucun paramètre scalaire, entièrement autowiré.

## 3. `EtablissementController` — 6 méthodes concernées

`newEtablissementAdmin`, `new`, `edit`, `editEtablissementAdmin`, `deleteMedia`, `delete`.

**Point structurel pour `new`/`newEtablissementAdmin`** : l'entité n'a pas encore d'ID au moment de
l'upload — il faut `persist()` + `flush()` d'abord pour obtenir l'ID (nécessaire pour le dossier ET pour le
slug du nom, déjà connu dès la saisie du formulaire), **puis** déplacer les fichiers, puis un second
`flush()` pour enregistrer les noms de fichiers.

Nommage : chaque bloc d'upload est passé de ~10 lignes (`pathinfo()` + `slug()` + `uniqid()` +
`guessExtension()`) à un seul appel `$this->mediaPathResolver->etablissementHeaderFilename($etablissement, $headerFile->guessExtension())`.
Le paramètre `SluggerInterface $slugger`, devenu inutile dans les 4 méthodes concernées, a été retiré des
signatures (et l'import associé).

## 4. `ArticleController` — 5 méthodes + 7 méthodes de listing

`new`, `newAdmin`, `edit`, `editAdmin`, `deleteMedia` : même principe (persist avant upload pour `new`/`newAdmin`,
nommage via `articleImageFilename()`/`articleDocFilename()`, `SluggerInterface` retiré).

**Correction d'une race condition** dans `editAdmin` : si on change le support (ex. audio→vidéo) **et**
qu'on coche « supprimer le doc » dans le même envoi, l'ancien code cherchait à supprimer le fichier au
**nouveau** sous-dossier (vidéo) alors qu'il est encore dans l'**ancien** (audio). Corrigé en capturant
`$oldSupportId = $article->getSupport()?->getId();` **avant** `$form->handleRequest($request)`.

**`withArticleMediaUrls()`** : les 7 méthodes de listing (`listAllArticles`, `listArticlesByType`,
`listArticlesBySection`, `ArticlesCompleteBySection`, `listArticlesBySectionOther`,
`listArticlesByEtablissement`, `listArticlesByPageEtablissement`, `listFiveArticles`,
`articleEtablissementSlug`) manipulent des **tableaux** issus de requêtes DQL partielles ou d'Elastica, pas
des entités — l'extension Twig ne peut pas s'y brancher directement. Cette méthode du resolver (déplacée
depuis un premier jet local à `ArticleController`, car **`EtablissementController::show2()` construit
lui aussi sa propre liste d'articles** de façon indépendante) ajoute `imageUrl`/`docUrl`/`logoUrl` en
s'adaptant aux différents noms de clés selon la méthode de repository d'origine
(`idetablissement`/`idEtablissement`, `logoName`/`logoNameEtablissement`/`logoEtablissement`).

## 5. Templates (~19 fichiers)

Remplacement mécanique `asset('/uploads/.../' ~ x.champ)` → `asset(xxx_url(x))` (entités) ou
`asset(x.imageUrl)` (tableaux déjà enrichis par le contrôleur, §4). Deux appels `vich_uploader_asset()`
cassés corrigés au passage (`listarticlesbysectionother.html.twig`, `_headeretablissement.html.twig` —
ce dernier n'étant référencé nulle part dans le code, corrigé par précaution sans certitude d'usage réel).
`bloc_insert_image.html.twig` (composant de formulaire partagé, 4 points d'appel) : deux nouvelles
fonctions Twig `*_url_prefix()` ajoutées pour lui fournir le préfixe de dossier attendu par sa logique
interne (`url_file ~ entity_name`), sans changer le composant lui-même — corrige au passage un bug
préexistant où le champ image d'un article pointait vers le dossier des établissements.

## 6. Commande de migration des dossiers : `app:media:migrate-to-nested-storage`

`src/Command/MigrateMediaToNestedStorageCommand.php`. Jamais destructif par défaut :
- **Sans option** : dry-run, rapport uniquement.
- **`--apply`** : déplace réellement les fichiers (copy + vérification de taille + suppression de
  l'original — plus sûr qu'un `rename()` sur une opération de 15 Go).
- **`--apply --delete-orphans`** : supprime en plus les fichiers physiques non réclamés par une ligne en
  base (`--delete-orphans` seul ne fait rien).

Exécutée : **1046 fichiers déplacés** (24 logos, 25 bandeaux, 466 images d'articles, 531 pièces jointes),
22 références en base pointant vers des fichiers déjà manquants avant la migration (ignorées sans erreur),
12 cas de support absent/« Aucun » résolus par extension. **222 fichiers orphelins** détectés dans les
anciens dossiers plats (30 établissements + 192 articles — cohérent avec le fait que les méthodes `edit`
d'`ArticleController` ne nettoyaient jamais l'ancien fichier lors d'un remplacement), **non supprimés** —
laissés en place pour revue manuelle avant un futur `--delete-orphans`.

## 7. Commande de renommage : `app:media:rename-to-slug`

`src/Command/RenameMediaToSlugCommand.php`. Renomme les fichiers déjà migrés vers la convention lisible et
met à jour la colonne correspondante en base, dans la même transaction logique (renommage physique **et**
`flush()` par lot). Même principe de sécurité que la commande de migration :
- **Sans option** : dry-run, liste chaque renommage prévu.
- **`--apply`** : renomme réellement et sauvegarde en base.
- Idempotent : si le fichier porte déjà le bon nom, ignoré silencieusement (`déjà au bon nom`) ; si un
  fichier cible existe déjà sous un autre nom (collision), l'entrée est signalée et ignorée plutôt
  qu'écrasée.

Exécutée : **1046 fichiers renommés** (0 collision, 22 fichiers introuvables ignorés — même écart que §6),
base de données mise à jour en parallèle. Une seconde exécution en dry-run confirme l'idempotence
(0 à renommer, 1046 déjà corrects).

## 7bis. Simplification du nommage des articles (retrait du slug) + aperçu image formulaire admin article

Deux demandes complémentaires, traitées ensemble :

**Aperçu de l'image chargée, formulaire admin article** — `EtablissementController` (vue admin) affichait déjà
un aperçu de l'image chargée sur `edit`, mais `templates/webapp/articles/_form2.html.twig` (utilisé par
`newadmin.html.twig`/`edit_admin.html.twig`, partie admin d'`ArticleController`) ne passait pas `url_file` à
l'inclusion de `bloc_insert_image.html.twig` pour le champ image — l'aperçu retombait donc sur une icône SVG
générique au lieu de l'image réellement uploadée. Corrigé en ajoutant
`'url_file': article.id ? article_image_url_prefix(article) : ''` (même principe que côté établissement),
**uniquement sur le champ image** — le champ pièce jointe (doc/audio/vidéo), volontairement hors périmètre,
n'a pas été touché. Petit ajustement visuel au passage dans le composant partagé
`bloc_insert_image.html.twig` (`px-2` sur le conteneur du nom de fichier, pour l'alignement une fois l'aperçu
image affiché à côté).

**Retrait du slug dans le nom de fichier des articles** — demande explicite : les noms de fichiers d'articles
générés en §7 (`{slug de l'article}-{id}_article.{ext}`) pouvaient devenir très longs pour un titre verbeux.
`MediaPathResolver::articleMediaFilename()` simplifié pour ne garder que `{id}_{suffixe}.{ext}` (le slug de
titre est retiré ; l'ID seul suffit à l'unicité, voir Contexte). **Le nommage établissement n'a volontairement
pas été touché** (garde le slug du nom, `etablissementMediaFilename()` inchangée) — demande explicite de
l'utilisateur.

Comme `RenameMediaToSlugCommand` (§7) est entièrement piloté par ce que le resolver calcule, aucune nouvelle
commande n'a été nécessaire : une seconde exécution a suffi pour aligner les fichiers déjà migrés sur la
nouvelle convention. Exécutée en dry-run puis `--apply` : **997 fichiers renommés** (tous côté article), **49
fichiers déjà corrects** (= exactement le nombre de logos + bandeaux d'établissements, confirmant qu'aucun
fichier établissement n'a été touché), 22 fichiers introuvables ignorés (même écart préexistant que §6/§7),
**0 collision** (attendu : des ID numériques purs ne peuvent pas entrer en collision). Vérifié : fichier
physique renommé (ex. `129_article.jpg`, `69_doc.docx`), colonne DB de l'article #69 synchronisée
(`image_name = 69_article.jpg`), établissement #1 confirmé inchangé (`pays-des-luys_avatar.png`), nouveau
dry-run confirmant l'idempotence, page `/webapp/articles/69` re-testée (200, bon chemin dans le HTML rendu).
Les futurs uploads suivent automatiquement cette même convention, le resolver étant la seule source de vérité
du nommage.

## Bugs corrigés en cours de route (découverts en testant, hors du périmètre initial)

- `EtablissementController::show2()` construisait sa propre liste d'articles indépendamment
  d'`ArticleController` — repéré seulement en testant la page réelle (`Key "imageUrl" ... does not exist`).
- `ArticleRepository::listArticlesBySection()` et `articleEtablissementSlug()` sélectionnaient
  `a.author as idauthor` sans jointure — DQL invalide (« Must be a StateFieldPathExpression »), corrigé en
  ajoutant `leftJoin('a.author', 'au')` et en sélectionnant `au.id`.
- `Article::getAuthor()` pouvait être `null` sur des données existantes (hypothèse « toujours renseigné »
  invalidée en dry-run réel) — rendu défensif (`?->getId() ?? 0`) partout dans le resolver.
- `ArticleController::listArticlesByPageEtablissement()` passait un tableau brut à
  `knp_pagination_render()`, qui attend un objet de pagination — cassé indépendamment de ce chantier,
  corrigé en ajoutant l'appel à `PaginatorInterface::paginate()` manquant.

## Fichiers créés

- `src/Service/MediaPathResolver.php`
- `src/Twig/MediaPathExtension.php`
- `src/Command/MigrateMediaToNestedStorageCommand.php`
- `src/Command/RenameMediaToSlugCommand.php`
- `NOTES_REORGANISATION_MEDIAS.md`

## Fichiers modifiés

- `config/services.yaml`
- `src/Controller/Admin/EtablissementController.php`
- `src/Controller/Webapp/ArticleController.php`
- `src/Repository/Webapp/ArticleRepository.php`
- ~19 templates (voir §5) : notamment `webapp/articles/show.html.twig`,
  `webapp/articles/include/_blocarticle.html.twig`, `webapp/articles/include/_listesearch.html.twig`,
  `webapp/articles/listarticlesbytype.html.twig`, `webapp/articles/articleEtablissementSlug.html.twig`,
  `webapp/articles/listarticlecompletebysection.html.twig`, `admin/etablissement/include/banner_etablissement.html.twig`,
  `admin/etablissement/show.html.twig`, `admin/etablissement/include/_listesearch.html.twig`,
  `admin/etablissement/listetablissementsbytype.html.twig`, `composants/modules/carrousel.html.twig`,
  `espace_etablissement/dashboard/_header_etablissement.html.twig`, formulaires `_form.html.twig`
  (établissement et article), `webapp/articles/_form2.html.twig` (aperçu image, formulaire admin article — §7bis),
  `composants/forms/blocs/bloc_insert_image.html.twig` (ajustement visuel mineur — §7bis)

## Vérifications effectuées

- `php -l` sur tous les fichiers PHP modifiés/créés — OK.
- `php bin/console lint:twig templates/` — 207 fichiers valides, les 6 erreurs restantes sont préexistantes
  et hors périmètre (`vich_uploader_asset` non enregistré comme fonction Twig dans ce projet, sur des
  entités non concernées : favicon, ressources, support, thème, avatar utilisateur).
- `php bin/console cache:clear` après chaque changement de câblage de service — OK à chaque étape.
- Migration (§6) : dry-run puis `--apply`, recoupement des compteurs avec des requêtes SQL directes
  (25 logos, 35 bandeaux, 477 images, 531 docs en base), nouveau dry-run après `--apply` confirmant 0 ligne
  restant à migrer.
- Renommage (§7) : dry-run puis `--apply`, vérification croisée fichier physique ↔ colonne en base sur
  plusieurs entités, dry-run de contrôle confirmant l'idempotence.
- Test de rendu sur une dizaine de routes distinctes après migration **et** après renommage (établissement,
  article seul, recherche articles, listing par section pour les 3 types de contenu ONE_ARTICLE/
  FIVE_ARTICLES/OTHER_CONTENT, listing par établissement, carrousel) — chacune vérifiée deux fois (avant/
  après renommage), tous les `<img src>`/`<audio>`/`<video>` résolvant vers le nouveau chemin, aucune
  erreur Twig « does not exist » restante une fois les bugs ci-dessus corrigés.
