# Hero d'introduction (pages + accueil) & logo du site

## Contexte (09/09/2026)

L'entité `Page` porte déjà `intro` (HTML CKEditor) + `isIntroShow` + `isTitleShow`, éditables dans la
gestion des pages. L'affichage front se limitait à un `<h1>` + un `<p>{{ page.intro|raw }}</p>` bruts,
et la page d'accueil n'affichait aucune intro.

Objectif : un **composant unique** rendant, en mode « HERO » Tailwind (aligné à gauche, pleine largeur,
**sans bouton**, logo du site à droite), le titre de la page + l'intro — réutilisé sur les pages
classiques (sous condition `isIntroShow`) et sur l'accueil.

---

## 1. Composant `templates/components/HeroIntro.html.twig`

Twig Component **anonyme** (`symfony/ux-twig-component` v3, `anonymous_template_directory: components/`),
appelé `<twig:HeroIntro …>`. Sur le modèle des composants livrés `Kbd` / `KbdGroup`, mais **sans**
`|tailwind_merge` (filtre non installé dans ce projet — `Kbd.html.twig` casse d'ailleurs `lint:twig`,
scaffolding `ux-toolkit` inutilisé).

Props (`{% props %}`) :

| Prop | Rôle |
|---|---|
| `title` | titre `<h1>` ; `null`/`''` → pas de titre |
| `intro` | HTML de l'intro, rendu `|raw` |
| `image` | URL de l'**image d'illustration de la page** — prioritaire sur `logo` |
| `logo` | URL du logo du site, utilisé seulement si `image` est vide |
| `media_alt` | `alt` de l'image / du logo (défaut `''`) |

`{% set media = image ?: logo %}` : le visuel de droite est l'image de la page si elle existe, sinon le
logo du site, sinon rien. Rendu différent selon le cas — image : `w-64/80 rounded-lg object-cover` ;
logo : `h-20/28/32 object-contain`.

- Bloc `max-w-7xl` aligné à gauche, `<h1>` `text-3xl sm:text-4xl` + filet `h-px w-24 bg-[#d84519]`.
- L'intro est dans un conteneur `max-w-3xl` stylé via **variantes Tailwind v4 `[&_h2]:…` / `[&_p]:…`
  / `[&_a]:…` / `[&_blockquote]:…`** : le projet n'a **pas** le plugin `@tailwindcss/typography`
  (`prose` ne produit aucune règle — vérifié dans `public/build/app.css`), on ne peut donc pas s'appuyer
  dessus.
- Logo `<img>` `shrink-0`, `h-20 sm:h-28 lg:h-32`, `object-contain`, masqué implicitement si `logo`
  est `null`.
- Aucun bouton / CTA (demande explicite).

## 2. Pages classiques — `templates/webapp/page/page.html.twig`

```twig
{% set site_logo = site_logo_path() ? asset(site_logo_path()) : null %}

{% if page.isIntroShow == 1 %}
    <twig:HeroIntro
        :title="page.isTitleShow == 1 ? page.title : null"
        :intro="page.intro"
        :logo="site_logo"
        :logo_alt="site_config() ? site_config().name : ''" />
{% elseif page.isTitleShow == 1 %}
    {# titre seul : ancien rendu conservé #}
    <section class="mx-auto mt-8 max-w-7xl …"><h1 …>{{ page.title|upper }}</h1></section>
{% endif %}
```

- `isIntroShow` pilote le hero ; le titre y est intégré seulement si `isTitleShow`.
- `isTitleShow` **sans** `isIntroShow` : on garde l'ancien `<h1>` souligné isolé (pas de régression sur
  les pages « titre seul »).

## 3. Page d'accueil — page dédiée (slug figé)

`DashboardController::HomePage()` :

```php
private const HOMEPAGE_SLUG = 'radio-francas-40';
…
$homePage = $em->getRepository(Page::class)->findOneBy(['slug' => self::HOMEPAGE_SLUG, 'isPublish' => 1]);
return $this->render('webapp/public/index.html.twig', [..., 'homePage' => $homePage]);
```

- La page **id 4 « Radio Francas 40 »** (slug `radio-francas-40`, `is_menu = 0`, `isIntroShow = 1`)
  alimente le hero : titre + intro + image d'illustration.
- Slug **figé dans la constante** : le slug étant dérivé du titre (`Page::initializeSlug()`, cf.
  `NOTES_GESTION_PAGES.md` §1), renommer cette page impose de mettre à jour `HOMEPAGE_SLUG`. La page
  doit rester publiée et hors menu.
- `webapp/public/index.html.twig`, en tête de `{% block main %}` :

  ```twig
  {% if homePage is defined and homePage and homePage.isIntroShow == 1 %}
      {% set site_logo = site_logo_path() ? asset(site_logo_path()) : null %}
      {% set home_image = homePage.image ? asset('uploads/images/pages/' ~ homePage.image) : null %}
      <twig:HeroIntro :title="homePage.isTitleShow == 1 ? homePage.title : null"
                      :intro="homePage.intro" :image="home_image" :logo="site_logo"
                      :media_alt="home_image ? homePage.title : config.name" />
  {% endif %}
  ```

- Si la page est absente / dépubliée → `homePage` null → hero absent, pas d'erreur.

> **Historique** : la constante valait `'accueil'` (convention `PageRepository::ListMenu()` qui exclut
> `title = 'accueil'` du menu), mais aucune page n'ayant ce slug le hero de la home ne s'affichait
> jamais. Repointée sur la page réellement utilisée.

## 3bis. Image d'illustration par page

Nouvelle colonne **`page.image`** (`VARCHAR(255) NULL`, migration `Version20260909140000`), fichiers
dans `public/uploads/images/pages/` (paramètre `page_directory`).

- `Page::getImage()` / `setImage(?string)`.
- `PageType` : champ `imageFile` (`FileType` non mappé, png/jpg), rendu dans
  `webapp/page/_form.html.twig` via `bloc_insert_image.html.twig` (`delete_url` →
  `op_webapp_page_delete_media`).
- `PageController` :
  - `new()` / `newPosition()` / `edit()` : `imageFile` renseigné → `storePageImage()` (helper privé,
    même principe que `ConfigController::storeConfigFile()`), `edit()` supprime d'abord l'ancien
    fichier.
  - **`deleteMedia()`** : `POST /webapp/page/{id}/delete-media` (`op_webapp_page_delete_media`) →
    `deletePageImage()` + `setImage(null)` + `flush`, réponse JSON `{code, message}`.
- `assets/js/admin/admin/NewEditPage.js` : handler `[data-action="delete-media"]`
  (`showDialog(..., onConfirm)` + `axios.post` + reload), comme `EditConfig.js`.
- Affichage : **uniquement dans le hero** (donc page avec `isIntroShow = 1`), à droite, prioritaire
  sur le logo du site. Une page avec une image mais sans intro affichée ne montre pas l'image.

## 4. Logo du site — nouveau média géré dans les Paramètres

La colonne `config.logo_name` (déjà `VARCHAR(255) NULL`, `setLogoName(?string)` déjà nullable)
n'était pilotée par aucun formulaire. Ajout du média « Logo du site », géré exactement comme le bandeau
et la vignette (cf. `NOTES_CORRECTIFS_2026-09.md` §4) :

- `ConfigType` : champ `logoFile` (`FileType` non mappé, png/jpg).
- `admin/config/_form.html.twig` : 3ᵉ bloc `bloc_insert_image` « Logo du site (navbar + accueil) »,
  `delete_url` avec `field: 'logo'`.
- `ConfigController::edit()` : traitement de `logoFile` (`deleteConfigFile()` + `storeConfigFile()`).
- `ConfigController::deleteMedia()` : `field` accepte désormais `header | vignette | logo`.
- Pas de migration.

### `site_logo_path()` — helper Twig tolérant

`SiteContext::getLogoWebPath()` (exposé en fonction Twig `site_logo_path()` via
`SiteContextExtension`) : renvoie `uploads/images/config/<logoName>` **seulement si le fichier existe**
sur le disque (`is_file(%config_directory%/…)`), sinon `null`.

Raison : les médias de la config sont saisis dans l'admin et une ancienne valeur de `logo_name` peut
pointer sur un fichier absent (héritage : fichiers dans `public/uploads/images/collections/`, alors que
les uploads vont maintenant dans `…/config/`). Sans cette garde, la navbar et le hero afficheraient une
image cassée.

### Navbar — `templates/webapp/page/listmenu.html.twig`

Le logo codé en dur (`asset('/uploads/images/fixes/logo-cc.png')`) devient :

```twig
<div class="flex shrink-0 items-center">
    <img class="h-10 w-auto max-h-12 object-contain"
         src="{{ site_logo_path() ? asset(site_logo_path()) : asset('/uploads/images/fixes/logo-cc.png') }}"
         alt="{{ config is defined and config ? config.name : '' }}">
</div>
```

- Le conteneur garde exactement les classes d'origine (`flex shrink-0 items-center`) : la rangée est
  déjà figée à `h-16` (`items-center`) et l'image est bornée par `h-10` / `max-h-12` (< 64 px), donc un
  logo de n'importe quelle taille ne fait pas varier la hauteur de la navbar.
- ⚠️ Ne **pas** ajouter `h-16` sur ce conteneur : le wrapper parent est `sm:items-stretch`, un enfant
  à `h-16` l'étire à 64 px et pousse les liens du menu (bloc `sm:block`, sans centrage vertical) en
  haut de la barre au lieu du centre.
- Fallback sur `logo-cc.png` tant qu'aucun logo de site n'est uploadé.

---

## Fichiers créés

- `NOTES_HERO_INTRO.md`
- `templates/components/HeroIntro.html.twig`
- `migrations/Version20260909140000.php` (colonne `page.image`)

## Fichiers modifiés

- `templates/webapp/page/page.html.twig` (hero sous condition `isIntroShow`, prop `image`)
- `templates/webapp/public/index.html.twig` (hero de la home depuis `homePage`, prop `image`)
- `templates/webapp/page/listmenu.html.twig` (logo depuis la config, hauteur préservée)
- `templates/webapp/page/_form.html.twig` (bloc « Image d'illustration »)
- `templates/admin/config/_form.html.twig` (3ᵉ bloc média « Logo du site »)
- `src/Controller/App/DashboardController.php` (`HOMEPAGE_SLUG = 'radio-francas-40'`, chargement `homePage`)
- `src/Controller/Webapp/PageController.php` (`imageFile` dans new/newPosition/edit, `deleteMedia()`, helpers)
- `src/Entity/Webapp/Page.php` (`image` + accesseurs)
- `src/Form/Webapp/PageType.php` (champ `imageFile`)
- `src/Service/SiteContext.php` (`getLogoWebPath()`, injection `%config_directory%`)
- `src/Twig/SiteContextExtension.php` (fonction `site_logo_path()`)
- `src/Form/Admin/ConfigType.php` (champ `logoFile`)
- `src/Controller/Admin/ConfigController.php` (`edit()` + `deleteMedia()` : cas `logo`)
- `config/services.yaml` (paramètre `page_directory`)

## Déploiement

- **`php bin/console doctrine:migrations:migrate`** (colonne `page.image`).
- Rebuild des assets (classes Tailwind du composant + `NewEditPage.js`).
- `mkdir -p public/uploads/images/pages` (créé automatiquement au 1ᵉʳ upload, mais à prévoir).
- La page **id 4 `radio-francas-40`** doit rester publiée et hors menu pour alimenter le hero de la
  home (sinon adapter `HOMEPAGE_SLUG`).
- Uploader le logo du site dans **Paramètres** (sinon navbar = `logo-cc.png`, hero sans visuel si la
  page n'a pas non plus d'image).

## Vérifications effectuées

- `php -l`, `bin/console lint:container`, `doctrine:schema:validate` (mapping OK ; le diff
  `schema:update` ne montre que des FK manquantes **préexistantes**, aucune sur `page.image`).
- `lint:twig` : OK (seul `components/Kbd.html.twig` — scaffolding `ux-toolkit` préexistant, filtre
  `tailwind_merge` absent — est en erreur, non lié).
- `doctrine:migrations:migrate` : `Version20260909140000` appliquée en dev.
- `yarn dev` : build OK (`app.css` → 210 KiB, `admin.js` → 139 KiB avec le handler `NewEditPage.js`).
- `router:match` : `POST /webapp/page/4/delete-media` → `op_webapp_page_delete_media` ;
  `GET /webapp/page/radio-francas-40` → `op_webapp_page_display` (inchangé).
- Rendu réel (serveur dev) :
  - `/home` → 200, **hero affiché** depuis la page 4 (intro « …webradio landaise… »).
  - `/app/radio-francas-40` (`isTitleShow=0`) → hero sans `<h1>`, intro rendue.
  - `/app/semaine-de-la-presse` (`isTitleShow=1`) → `<h1>Semaine de la presse</h1>` + filet + intro.
  - `/app/collegiens-citoyens` → hero + `<p>testt</p>`.
  - `/webapp/page/new/` et `/webapp/page/4/edit` → 200, champ « Image d'illustration » présent
    (`name="page[imageFile]"`) ; pas de bouton supprimer tant qu'aucune image (page 4 sans `image`).
- Pas encore testé bout en bout : upload réel d'une image de page + clic « supprimer » (CSRF), mais
  la mécanique est identique à celle des médias de `Config` (déjà validée, cf.
  `NOTES_CORRECTIFS_2026-09.md` §4).
