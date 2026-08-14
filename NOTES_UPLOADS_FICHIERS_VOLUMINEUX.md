# Autoriser les uploads volumineux (déploiement FrankenPHP)

## Contexte

L'image `dunglas/frankenphp` utilisée en prod/dev (`Dockerfile`) ne portait aucune configuration PHP
custom : pas de `php.ini`, pas de `conf.d/`, pas de `Caddyfile`. Les valeurs par défaut de PHP
(`upload_max_filesize=2M`, `post_max_size=8M`) rendaient impossibles la plupart des uploads du site —
y compris ceux déjà annoncés dans le code (bandeaux, avatars, pièces jointes d'articles).

Trois couches à aligner pour qu'un upload aboutisse : **PHP** (`upload_max_filesize`/`post_max_size`,
sinon le fichier est tronqué/rejeté avant même d'atteindre Symfony) → **Symfony** (contrainte `File`
`maxSize` sur chaque champ `FileType`, sinon rejet en validation de formulaire) → **code métier**
(aucune limite supplémentaire trouvée dans les contrôleurs `EtablissementController`/`ConfigController`).

FrankenPHP (Caddy) n'impose de son côté aucune limite de taille de corps de requête par défaut : c'est
bien PHP qui gouverne ici, pas de `Caddyfile` à ajouter.

---

## 1. Config PHP : `docker/php/conf.d/uploads.ini` (nouveau)

```ini
; Autorise les uploads volumineux (formulaires, médias, imports)
; Plafond calé sur le support "vidéo" d'ArticlesType (~390 Mo, cf. src/Form/Webapp/ArticlesType.php)
upload_max_filesize = 400M
post_max_size = 420M
memory_limit = 512M
max_execution_time = 600
max_input_time = 600
max_file_uploads = 20
```

- `post_max_size` > `upload_max_filesize` (marge pour les autres champs du formulaire).
- `memory_limit` relevé à 512M : nécessaire au traitement d'images volumineuses avec `gd`.
- `max_execution_time`/`max_input_time` à 600s : couvre les uploads vidéo sur connexion lente.
- Plafond global fixé à 400 Mo pour couvrir le cas le plus haut du code existant (vidéo, voir §3).

Chargé automatiquement par PHP au démarrage (dossier `conf.d/` scanné par défaut dans l'image
FrankenPHP), via l'ajout suivant dans `Dockerfile` :

```dockerfile
RUN install-php-extensions intl opcache gd zip pdo_pgsql pgsql curl xml mbstring json

# Autorise les uploads volumineux (voir docker/php/conf.d/uploads.ini)
COPY docker/php/conf.d/uploads.ini /usr/local/etc/php/conf.d/uploads.ini
```

**Rebuild obligatoire** après ce changement :
```bash
docker compose build php
docker compose up -d
```

---

## 2. Contraintes Symfony (`Assert\File` / `maxSize`)

La plupart des formulaires limitaient déjà les fichiers à `'maxSize' => '10000k'` (~9,77 Mo), bien en
dessous du nouveau plafond PHP. Remonté à `'100000k'` (~100 Mo) pour rester cohérent avec la config PHP,
sur les champs suivants :

| Formulaire | Champ(s) |
|---|---|
| `src/Form/Admin/userType.php`, `userEditType.php` | `avatarFile` |
| `src/Form/Admin/EtablissementType.php`, `EtablissementEditType.php` | `headerFile`, `logoFile` |
| `src/Form/Admin/ConfigType.php` | `headerFile`, `vignetteFile` |
| `src/Form/Webapp/RessourcesType.php` | `imageFile`, `docFile` |
| `src/Form/Webapp/ArticlesType.php`, `Articles2Type.php` | `imageFile`, `docFile` (par défaut + cas "document", support id 3) |

## 3. Cas particulier : pièce jointe d'article selon le support

Dans `ArticlesType`/`Articles2Type`, le champ `docFile` a une contrainte de taille qui dépend du
`support` sélectionné (`getDocConstraintsBySupportId()`) — inchangée, déjà cohérente avec le nouveau
plafond PHP de 400 Mo :

| Support (id) | Formats acceptés | Taille max |
|---|---|---|
| Audio (1) | mp3, wav | 200 000 k (~195 Mo) |
| Vidéo (2) | mp4, mpeg | 400 000 k (~390 Mo) |
| Document (3) | pdf, doc, docx | 100 000 k (~100 Mo, aligné §2) |

---

## 4. Nettoyage VichUploader

Le bundle `vich/uploader-bundle` n'est plus installé (absent de `composer.json`/`composer.lock`), les
uploads sont gérés « à la main » dans les contrôleurs. Des résidus inertes traînaient encore dans le
code (imports et annotations jamais lus sans le bundle, aucun impact fonctionnel mais dette morte) :

- **Formulaires** — imports inutilisés retirés : `use Vich\UploaderBundle\Form\Type\VichImageType;`
  (`EtablissementType`, `EtablissementEditType`, `ConfigType`) et `VichFileType`/`VichImageType`
  (`RessourcesType`).
- **Entités** — docblocks `@Vich\Uploadable()` et imports `Vich\UploaderBundle\Mapping\Annotation`
  / `Vich\UploaderBundle\Entity\File` retirés de `Article`, `Theme`, `Support`, `Ressources`. Les champs
  `imageName`/`imageSize`/`doc` restent des colonnes Doctrine classiques, sans mapping Vich.

Plus aucune occurrence de `Vich` dans `src/` après nettoyage.

---

## Récapitulatif des plafonds effectifs

| Niveau | Valeur |
|---|---|
| PHP `upload_max_filesize` | 400 Mo |
| PHP `post_max_size` | 420 Mo |
| Symfony — champs génériques (avatar, logo, bandeau, image/doc article ou ressource) | 100 Mo |
| Symfony — pièce jointe article, audio | 195 Mo |
| Symfony — pièce jointe article, vidéo | 390 Mo |
