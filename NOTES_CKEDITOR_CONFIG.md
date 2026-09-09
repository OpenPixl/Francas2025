# CKEditor 5 — configuration partagée des champs de contenu

## Contexte (08/09/2026)

Trois éditeurs de contenu éditorial (articles admin, articles collèges, pages) instanciaient chacun
`ClassicEditor.create(...)` avec une `toolbar` recopiée à l'identique et **aucune** contrainte sur le
HTML produit : le menu « Titre » proposait `<h1>` (en conflit avec le titre de page/article), et les
liens n'avaient jamais `target` / `rel`.

Objectif : une config unique, réutilisable, produisant un HTML sémantique correct pour le
référencement.

---

## `assets/js/composants/ckeditor.js` (nouveau)

Exporte une constante `richTextConfig` :

```js
export const richTextConfig = {
    toolbar: [
        'heading', 'bold', 'italic', 'link',
        'bulletedList', 'numberedList', 'blockQuote', 'indent', 'alignment',
    ],
    heading: {
        options: [
            { model: 'paragraph', title: 'Paragraphe', class: 'ck-heading_paragraph' },
            { model: 'heading1', view: 'h2', title: 'Titre 2', class: 'ck-heading_heading2' },
            { model: 'heading2', view: 'h3', title: 'Titre 3', class: 'ck-heading_heading3' },
            { model: 'heading3', view: 'h4', title: 'Titre 4', class: 'ck-heading_heading4' },
        ],
    },
    link: {
        decorators: {
            externalLink: {
                mode: 'manual',
                label: 'Lien externe (nouvel onglet, nofollow)',
                defaultValue: false,
                attributes: { target: '_blank', rel: 'noopener noreferrer nofollow' },
            },
        },
    },
};
```

- **Titres limités à `<h2>` / `<h3>` / `<h4>`** : le `model` interne `heading1` est mappé sur la vue
  `<h2>`, etc. Le `<h1>` reste réservé au titre de la page / de l'article, jamais éditable dans le
  corps.
- **Lien externe** : case à cocher manuelle dans la bulle d'édition du lien (pas dans la barre
  d'outils) qui ajoute `target="_blank"` + `rel="noopener noreferrer nofollow"`.
- Le **gras** produit déjà `<strong>` et l'italique `<i>` (comportement par défaut de CKEditor 5) —
  inchangé.
- **Build CDN « classic » 22.0.0 conservé** : montée de version écartée pour ce chantier. Seuls les
  plugins déjà inclus sont utilisables ; pour `<em>`, `<code>`, `<abbr>` ou des attributs libres il
  faudrait un build personnalisé / une version récente avec *General HTML Support*.

## Application

Dans chaque fichier, `import {richTextConfig}` puis étalement dans les options
(`{ ...richTextConfig, height: 50 }`), en remplacement de la `toolbar` inline :

- `assets/js/admin/admin/NewEditPage.js` — éditeur `#page_intro`
- `assets/js/admin/webapp/NewEditArticles.js` — éditeur `#articles_content` (articles admin)
- `assets/js/app/article/NewEditArticles.js` — éditeur `#articles2_content` (articles collèges)

---

## Fichiers créés

- `NOTES_CKEDITOR_CONFIG.md`
- `assets/js/composants/ckeditor.js`

## Fichiers modifiés

- `assets/js/admin/admin/NewEditPage.js`
- `assets/js/admin/webapp/NewEditArticles.js`
- `assets/js/app/article/NewEditArticles.js`

## Déploiement

- Rebuild des assets. Aucun impact base / Elasticsearch.
- Contenu déjà saisi non modifié rétroactivement (un `<h1>` déjà enregistré dans un article reste tel
  quel jusqu'à réédition).
