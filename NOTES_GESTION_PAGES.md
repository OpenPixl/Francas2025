# Gestion des pages & rendu des sections

## Contexte

Lot de modifications du 08/09/2026 sur la brique « Pages » de l'admin
(`webapp/page/*`, contrôleur `PageController`) et sur le rendu des sections côté site :

- slug de page toujours dérivé du titre ;
- bascule **Menu** / **Publié** de la liste des pages remise en JS externe + classes Tailwind ;
- bouton « Mettre à jour » de l'édition en variante bleue ;
- parité du rendu des sections entre la page d'accueil et les pages standard ;
- réécriture du contenu de la page « Nous rejoindre ».

---

## 1. Slug de `Page` dérivé du titre — `PrePersist` + `PreFlush`

`src/Entity/Webapp/Page.php`, `initializeSlug()` :

```php
#[ORM\PrePersist]
#[ORM\PreFlush]          // avant : #[ORM\PreUpdate]
public function initializeSlug() {
    $slugify = new Slugify();
    $this->slug = $slugify->slugify((string) $this->title);   // avant : seulement si slug vide
}
```

- **`PreUpdate` → `PreFlush`** : `PreUpdate` s'exécute *après* le calcul du changeset Doctrine —
  écrire `$this->slug` à ce moment-là n'était pas répercuté en base lors d'une modification.
  `PreFlush` s'exécute avant, l'écriture est donc bien persistée.
- **Le slug est toujours recalculé depuis le titre** (plus de garde `if (empty($this->slug))`).
  L'opération est **idempotente** : titre inchangé ⇒ même slug ⇒ pas de changeset ⇒ aucune requête SQL.
  Il n'y a écriture que si le titre a changé.
- **Conséquence** : renommer une page change son URL (`/{slug}`). Les liens internes construits avec
  `path('op_webapp_page', {slug: …})` suivent automatiquement (slug lu depuis l'entité) ; les liens
  écrits en dur ne suivent pas.
- Alignement d'un lien en dur : `templates/webapp/articles/articleEtablissementSlug.html.twig`,
  fil d'ariane niveau 1 — `slug: 'les_medias_collegiens'` → `slug: 'les-medias-jeunes'` (le Slugify du
  titre actuel produit des tirets, pas des underscores).

## 2. Bascule Menu / Publié en AJAX + classes Tailwind

### `assets/js/admin/admin/IndexPage.js` (nouveau)

```js
export function initIndexPage() {
    document.querySelectorAll('a.js-menu, a.js-publish').forEach(function (link) {
        if (link.dataset.toggleBound) return;   // idempotent
        link.dataset.toggleBound = '1';
        link.addEventListener('click', onToggle);
    });
}
```

Au clic : `axios.get(link.href)` puis inversion en place de l'icône et de sa couleur —
`fa-check-circle` / `text-green-800` (actif) ↔ `fa-times-circle` / `text-red-800` (inactif).
Même principe que la colonne « Publié sur le site » des établissements (`IndexEtablissement.js`).

Câblé dans `assets/admin.js`, `switch (page)` :

```js
case 'op_webapp_page_index':
    initIndexPage();
    break;
```

### `templates/webapp/page/include/_liste.html.twig`

Colonnes Menu / Publié : classes **Bootstrap** `text-success` / `text-danger` → **Tailwind**
`text-green-800` / `text-red-800` (Bootstrap n'est plus chargé dans l'admin).

### `templates/webapp/page/index.html.twig`

Suppression du gros `{% block javascripts %}` inline (≈ 225 lignes) : `window.onload`,
`bootstrap.Toast`, `#toaster`, modale `#Suppr`, ré-attachements manuels d'événements sur `a.jsUp` /
`a.jsDown` / `a.jsPosition` / `a.js-data-suppr`. Code **mort** depuis le passage à Tailwind et la
suppression de Bootstrap ; la bascule Menu/Publié est reprise par `IndexPage.js`.

### Routes backend

Inchangées : `op_webapp_page_menu` / `op_webapp_page_publish` (retour JSON `{code, message}`).

## 3. Bouton « Mettre à jour » (édition d'une page) en variante `primary`

`templates/webapp/page/edit.html.twig` — bloc `btns.edit` (bouton d'entête, cf.
`NOTES_BOUTON_ENREGISTRER_HEADER.md`) : ajout de `'variant': 'primary'`.

`templates/composants/buttons/button_utils.html.twig` supportait déjà `primary`
(`border-blue-700 text-blue-700 hover:bg-blue-700 hover:text-white`) à côté de `danger` — aucune
modification du composant. Le bouton passe simplement du gris neutre au bleu.

## 4. Rendu des sections : parité `listsections` ↔ `_onesection`

Deux gabarits rendent les sections d'une page :

| Template | Utilisé par |
|---|---|
| `templates/webapp/section/include/_onesection.html.twig` | page d'accueil |
| `templates/webapp/section/listsections.html.twig` | pages standard (`SectionController::ListAllSections`) |

`listsections.html.twig` était en retard sur `_onesection.html.twig` :

- **Titre + description de section** : ajout des blocs `{% if section.isShowtitle == 1 %}` (→ `<h2>`
  `section.name|upper`) et `{% if section.isShowdescription == 1 %}` (→ `section.descriptif|raw`),
  même markup que `_onesection`.
- **`ONE_ARTICLE_COMPLETE`** : ajout de la branche manquante →
  `render(controller('App\\Controller\\Webapp\\ArticleController::ArticlesCompleteBySection', {'idsection': section.id}))`.
- **Doublon corrigé** : la 2ᵉ branche `{% elseif section.content == 'ALL_RESSOURCES' %}` (jamais
  atteinte) → `ONE_RESSOURCES`.

`_onesection.html.twig` : les branches ressources pointaient vers
`App\Controller\Webapp\RessourcesController` (**classe inexistante**) → corrigées en
`App\Controller\App\RessourcesController` (`sectionListAll`, `sectionlistOneCategory`).

## 5. Contenu — page contact « Nous contacter » → « Nous rejoindre »

- `templates/webapp/public/contact.html.twig` : titre `<h2>` « Nous contacter » → « Nous rejoindre »,
  réécriture des deux paragraphes d'accroche autour de **Radio Francas 40** (média animé avec des
  groupes d'enfants / de jeunes / d'élèves) au lieu du dispositif « Collégiens-Citoyens ». Correction
  d'une balise `</div>` en trop dans le bloc.
- `templates/webapp/page/listmenu.html.twig` : libellé du lien de navigation « CONTACT » →
  « NOUS REJOINDRE » (route `op_webapp_public_contactpage` inchangée).

## 6. Nettoyage debug (accueil)

- `src/Controller/Webapp/PageController.php` : suppression d'un `//dd($config)` commenté dans
  `listMenu()`.
- `templates/webapp/page/listmenu.html.twig` : retrait d'un « ok » de debug affiché dans la navbar.
- `config/packages/webpack_encore.yaml` : activation de `data-turbo-track: reload` sur
  `script_attributes` / `link_attributes` (rendu **inerte** peu après par la désactivation de Turbo
  Drive — voir `NOTES_TOMSELECT_ET_TURBO.md`, §4).

---

## Fichiers créés

- `NOTES_GESTION_PAGES.md`
- `assets/js/admin/admin/IndexPage.js`

## Fichiers modifiés

- `src/Entity/Webapp/Page.php` (`initializeSlug()` : `PreUpdate` → `PreFlush`, recalcul systématique)
- `src/Controller/Webapp/PageController.php` (nettoyage `dd`)
- `assets/admin.js` (`case op_webapp_page_index` → `initIndexPage()`)
- `templates/webapp/page/index.html.twig` (suppression du `<script>` inline obsolète)
- `templates/webapp/page/include/_liste.html.twig` (classes Bootstrap → Tailwind)
- `templates/webapp/page/edit.html.twig` (`variant: 'primary'`)
- `templates/webapp/page/listmenu.html.twig` (libellé nav + retrait debug)
- `templates/webapp/articles/articleEtablissementSlug.html.twig` (slug du fil d'ariane)
- `templates/webapp/section/listsections.html.twig` (titre/description, `ONE_ARTICLE_COMPLETE`, doublon)
- `templates/webapp/section/include/_onesection.html.twig` (namespace `RessourcesController`)
- `templates/webapp/public/contact.html.twig` (contenu « Nous rejoindre »)
- `config/packages/webpack_encore.yaml` (`data-turbo-track: reload`)

## Déploiement

- Rebuild des assets (nouveau `IndexPage.js`, imports `admin.js`).
- **Migration** : `initializeSlug()` recalcule le slug de chaque page **au premier `flush`** qui la
  touche. Aucun script de reprise, mais toute page dont le titre contenait un slug divergent (p. ex.
  underscores) verra son URL changer dès sa prochaine sauvegarde en admin. Vérifier les liens en dur
  éventuels côté templates.
- Rien côté Elasticsearch.
