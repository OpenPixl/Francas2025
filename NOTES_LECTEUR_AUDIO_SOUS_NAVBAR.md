# Zone paramétrable sous la navbar : lecteur audio ou bannière

## Contexte (10/09/2026)

La page d'accueil affichait une **bannière** (`admin/config/headershow.html.twig`) *au-dessus* de la
navbar, via le `{% block header %}` de `base.html.twig` conditionné à la route
`op_webapp_public_homepage`.

Le client veut :

- **supprimer** ce header au-dessus de la navbar ;
- pouvoir afficher, **directement sous la navbar** et **au choix par page**, soit un **lecteur des
  5 dernières publications audio**, soit la **bannière**, soit **rien** ;
- ce réglage se fait dans la **gestion des pages** (checkbox « ne rien afficher » + liste de choix).

Le lecteur s'inspire du player RadioKing, **simplifié** : pas de vote / partage / téléchargement /
lien HD. Icônes Font Awesome (kit déjà chargé dans `base.html.twig`).

---

## 1. Entité `Page` + migration

`src/Entity/Webapp/Page.php` — deux colonnes :

| Champ | Type | Rôle |
|---|---|---|
| `underNavHidden` | `boolean` nullable (défaut entité `true`) | `true`/`null` → rien ; `false` → afficher |
| `underNavType` | `string(20)` nullable | `'player'` ou `'banner'` |

Migration `Version20260910120000` : `ALTER TABLE page ADD under_nav_hidden TINYINT(1) DEFAULT NULL,
ADD under_nav_type VARCHAR(20) DEFAULT NULL`.

> Colonne **sans défaut SQL** : les pages existantes restent à `NULL` (traité comme masqué), les
> pages créées ensuite prennent le défaut de l'entité (`true`). Aucun changement visible tant qu'une
> page n'est pas explicitement configurée.

## 2. Formulaire de gestion des pages

- `src/Form/Webapp/PageType.php` : `underNavHidden` (CheckboxType) + `underNavType` (ChoiceType,
  `player` / `banner`, `placeholder` « Choisir… »).
- `templates/webapp/page/_form.html.twig` : bloc « Zone sous la navbar » dans la colonne « Options de
  la page » + petit `<script>` inline qui grise la liste de choix quand « Ne rien afficher » est coché.

## 3. Affichage front

`base.html.twig` :

```twig
<header>{% block header %}{% endblock %}</header>   {# vidé : plus de bannière au-dessus de la navbar #}
{% block mainnav %} … {% endblock %}
{% block undernav %}{% endblock %}                  {# nouveau, juste sous la navbar #}
```

Partiel unique `templates/webapp/page/_undernav_zone.html.twig` (param : `undernav_page` = entité
`Page`) : selon `underNavHidden` / `underNavType`, inclut le lecteur ou `headershow.html.twig`.

Le bloc `undernav` est surchargé dans :

| Template | Page utilisée |
|---|---|
| `webapp/public/index.html.twig` | `homePage` (slug `radio-francas-40`, cf. `DashboardController::HOMEPAGE_SLUG`) |
| `webapp/page/page.html.twig` | `page` (pages de menu classiques) |

> **La bannière d'accueil dépend désormais de la config de la page `radio-francas-40`** : pour la
> réafficher, éditer cette page → décocher « Ne rien afficher » → choisir « Bannière du site ».
> (`config.isHeaderShow` + `config.headerName` restent la source de l'image.)

## 4. Le lecteur

- `templates/webapp/composants/audio_player_bar.html.twig` : barre pleine largeur (`bg-slate-900`,
  accents `#d84519`), play/pause, pochette, titre + artiste (établissement), barre de progression,
  volume/mute, et un **sélecteur** listant les 5 pistes. `<audio>` HTML5 inline, aucun changement de
  page. Si aucune publication audio : bandeau discret « Aucune publication audio disponible ».
- Source des pistes : `ArticleRepository::listLatestAudioArticles(5)` — Articles `support = Audio`
  (id 1), `isArchived = 0`, `doc` non vide, triés `createdAt DESC`.
- Exposé au template par `App\Twig\AudioPlayerExtension` → fonction `latest_audio_articles(max = 5)`.
- JS : `assets/js/composants/audio_player_bar.js` (`initAudioPlayerBar`), câblé dans `assets/app.js`
  via `onReady` (Turbo Drive désactivé — cf. `NOTES_TOMSELECT_ET_TURBO.md`). S'auto-ignore si
  `#audio-player-bar` absent. Lecture piste par piste, enchaînement auto en fin de piste.

---

## Déploiement prod

```
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console cache:clear            # nouvelles routes/templates (cf. NOTES_CORRECTIFS_2026-09)
yarn encore production
```

Puis, côté admin : configurer la zone sous la navbar sur la page d'accueil (`radio-francas-40`) et
sur les pages voulues.
