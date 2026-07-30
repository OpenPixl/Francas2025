# Renommage `College` → `Etablissement`

## Contexte

La plateforme publiait initialement des articles rédigés par des collégiens, gérés via l'entité `College`. Le dispositif s'ouvre désormais aux centres de loisirs, espaces jeunes et autres structures accueillant des jeunes. Cette évolution nécessite :

1. Le renommage complet de l'entité `College` en `Etablissement` (table, colonnes de clé étrangère, routes, contrôleurs, formulaires, templates, JS, config, rôle de sécurité, dossier d'upload), en préservant toutes les données existantes.
2. L'ajout d'un champ **type d'établissement**, sous forme d'une nouvelle entité de référence `TypeEtablissement` (Collège / Centre de loisirs / Espace jeunes / Autre).

Les 44 établissements existants ont été automatiquement rattachés au type **Collège** lors de la migration.

---

## Base de données

### Migrations créées (`migrations/`)

| Fichier | Contenu |
|---|---|
| `Version20260726085717.php` | Renommage table `college`→`etablissement` (+ colonnes `college_email`/`college_phone`→`etablissement_email`/`etablissement_phone`), création de `type_etablissement` + seed des 4 types, ajout de `etablissement.type_etablissement_id` (backfill vers "Collège"), renommage des colonnes FK sur `ressources`, `user`, `section`, `article` (avec gestion de la contrainte FK réelle), renommage de la table de jointure `college_section`→`etablissement_section`, migration des rôles JSON `ROLE_COLLEGE`→`ROLE_ETABLISSEMENT`, migration des constantes `Section::content` `ONE_COLLEGE`/`ALL_COLLEGES`→`ONE_ETABLISSEMENT`/`ALL_ETABLISSEMENTS`. |
| `Version20260726085952.php` | Renommage cosmétique de deux index (`article`, `etablissement`) pour suivre la convention de nommage Doctrine. |
| `Version20260726090236.php` | Renommage de la valeur `user.typeuser` de `'college'` vers `'etablissement'`. |

**Important** : le dossier `/migrations/` était déclaré dans `.gitignore` (recette Symfony par défaut, jamais utilisé jusqu'ici) — il a été retiré du `.gitignore`, sinon ces migrations n'auraient jamais été commitées ni déployables.

### Données vérifiées après migration

| Vérification | Résultat |
|---|---|
| Nombre d'établissements | 44 (préservés) |
| Établissements rattachés au type "Collège" | 44 |
| Comptes avec `ROLE_ETABLISSEMENT` | 38 |
| Comptes avec `ROLE_COLLEGE` restants | 0 |
| Comptes `typeuser = 'etablissement'` | 35 |
| Articles liés à un établissement | 598 (préservés) |

La dérive de schéma restante détectée par `doctrine:schema:validate` est **préexistante** au renommage (plusieurs contraintes FK n'ont jamais été créées en base sur ce projet, y compris avant ce changement) — non corrigée car hors périmètre.

---

## Convention de nommage appliquée

| Élément | Ancien | Nouveau |
|---|---|---|
| Entité / table | `College` / `college` | `Etablissement` / `etablissement` |
| Repository / Contrôleur / Formulaires | `CollegeRepository`, `CollegeController`, `CollegeType`, `CollegeEditType` | `EtablissementRepository`, `EtablissementController`, `EtablissementType`, `EtablissementEditType` |
| Colonnes spécifiques | `college_email`, `college_phone` | `etablissement_email`, `etablissement_phone` |
| Clés étrangères | `article.college_id`, `ressources.college_id`, `user.college_id`, `section.single_college_id` | `*.etablissement_id`, `section.single_etablissement_id` |
| Table de jointure | `college_section` | `etablissement_section` |
| Rôle de sécurité | `ROLE_COLLEGE` | `ROLE_ETABLISSEMENT` |
| Valeur `user.typeuser` | `college` | `etablissement` |
| Paramètre / dossier upload | `college_directory` → `public/uploads/images/colleges/` | `etablissement_directory` → `public/uploads/images/etablissements/` |
| Filtres LiipImagine | `thumb_bandeau_college`, `thumb_logo_college`, `thumb_card_college` | `thumb_bandeau_etablissement`, `thumb_logo_etablissement`, `thumb_card_etablissement` |
| Préfixes de routes | `op_admin_college_*`, `op_webapp_college_*`, `op_espcoll_*` | `op_admin_etablissement_*`, `op_webapp_etablissement_*`, `op_espetab_*` |
| Dossiers de templates | `templates/admin/college/`, `templates/espacecollege/` | `templates/admin/etablissement/`, `templates/espace_etablissement/` |
| Dossiers/fichiers JS | `assets/js/app/college/`, `IndexCollege.js`, `NewEditCollege.js` | `assets/js/app/etablissement/`, `IndexEtablissement.js`, `NewEditEtablissement.js` |
| Constantes `Section` | `ONE_COLLEGE`, `ALL_COLLEGES` | `ONE_ETABLISSEMENT`, `ALL_ETABLISSEMENTS` |

### Nouvelle entité `TypeEtablissement`

- `App\Entity\Admin\TypeEtablissement` / table `type_etablissement` / colonnes `id`, `libelle`.
- `App\Repository\Admin\TypeEtablissementRepository`.
- Relation `ManyToOne` non nullable depuis `Etablissement::$typeEtablissement`.
- Champ ajouté dans les formulaires `EtablissementType` / `EtablissementEditType` (sélection du type via `EntityType`).
- Pas de CRUD admin dédié dans ce lot (les 4 valeurs sont seedées par migration).

---

## Fichiers créés

- `src/Entity/Admin/TypeEtablissement.php`
- `src/Repository/Admin/TypeEtablissementRepository.php`
- `src/Command/MoveEtablissementUploadsCommand.php` (commande `app:etablissement:move-uploads`)
- `migrations/Version20260726085717.php`, `Version20260726085952.php`, `Version20260726090236.php`

## Fichiers renommés (extraits, liste non exhaustive)

- `src/Entity/Admin/College.php` → `Etablissement.php`
- `src/Repository/Admin/CollegeRepository.php` → `EtablissementRepository.php`
- `src/Controller/Admin/CollegeController.php` → `EtablissementController.php`
- `src/Form/Admin/CollegeType.php` / `CollegeEditType.php` → `EtablissementType.php` / `EtablissementEditType.php`
- `templates/admin/college/` → `templates/admin/etablissement/` (13 fichiers)
- `templates/espacecollege/` → `templates/espace_etablissement/` (7 fichiers)
- `templates/webapp/articles/articleCollegeSlug.html.twig` → `articleEtablissementSlug.html.twig` (+ 5 autres fichiers du dossier)
- `templates/webapp/ressources/newressourcebycollege.html.twig` → `newressourcebyetablissement.html.twig`
- `assets/js/app/college/` → `assets/js/app/etablissement/`
- `assets/js/admin/admin/IndexCollege.js` / `NewEditCollege.js` → `IndexEtablissement.js` / `NewEditEtablissement.js`

## Fichiers modifiés en contenu (principaux)

**Entités** : `User.php`, `Article.php`, `Section.php`, `Ressources.php` (relations renommées).

**Contrôleurs** : `ConfigController`, `DashboardController` (Admin + App), `resettingController`, `userController`, `ArticleController`, `MessageController`, `RessourcesController`.

**Repositories** : `UserRepository`, `ArticleRepository`.

**Formulaires** : `userType`, `userEditType`, `SectionType`.

**Config** : `config/services.yaml`, `config/packages/security.yaml`, `config/packages/liip_imagine.yaml`.

**Assets** : `assets/app.js`, `assets/admin.js`, `assets/styles/app.css`.

**Templates** : ~20 fichiers modifiés (variables Twig, chemins d'include, routes, textes affichés) en plus des fichiers renommés.

---

## Décisions de traduction / grammaire

- Textes visibles utilisateur : "collège" → "établissement" avec gestion de l'élision française (ex. "ce collège" → "cet établissement", "le collège" → "l'établissement").
- Le terme **"collégien(s)"** (désignant les élèves rédacteurs, pas l'établissement) a été conservé tel quel dans les éléments de marque ("Collégiens-Citoyens", lien wiki externe) et généralisé en **"jeunes"** dans les textes descriptifs génériques (ex. "articles produits par le groupe de jeunes").

---

## Actions manuelles restantes

Sur chaque environnement où des fichiers ont réellement été uploadés (poste de développement avec les vraies images, staging, production), **après déploiement du code et de la migration** :

```bash
php bin/console app:etablissement:move-uploads
```

Cette commande déplace les fichiers physiques de `public/uploads/images/colleges/` vers `public/uploads/images/etablissements/` (idempotente, avec logs).

---

## Vérifications effectuées

- `php bin/console doctrine:schema:validate` — mapping ORM correct.
- `php bin/console doctrine:migrations:status` — 3 migrations exécutées.
- `php bin/console debug:router` — toutes les nouvelles routes `*etablissement*` actives.
- `php bin/console lint:twig templates` — 202 fichiers valides (8 erreurs préexistantes, non liées au renommage).
- `npm run dev` (Webpack Encore) — compilation réussie.
- Requêtes SQL de contrôle post-migration (voir tableau ci-dessus).
