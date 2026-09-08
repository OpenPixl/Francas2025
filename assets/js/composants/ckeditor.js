/**
 * Configuration partagée de CKEditor 5 pour les champs de contenu éditorial
 * (articles, pages) — HTML sémantique favorable au référencement.
 *
 * - Le gras produit déjà <strong> (comportement par défaut de CKEditor 5),
 *   l'italique <i>.
 * - Titres limités à <h2> / <h3> / <h4> : le <h1> reste réservé au titre de
 *   la page / de l'article.
 * - Liens : bascule manuelle « lien externe » qui ajoute
 *   target="_blank" + rel="noopener noreferrer nofollow" (proposée dans la
 *   bulle d'édition du lien, pas dans la barre d'outils).
 *
 * Build CDN « classic » 22.0.0 : seuls les plugins déjà inclus sont
 * utilisables. Pour <em>, <code>, <abbr>… ou des attributs libres, il faut
 * un build personnalisé / une version récente avec General HTML Support.
 *
 * Usage : ClassicEditor.create(el, { ...richTextConfig })
 */
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
                attributes: {
                    target: '_blank',
                    rel: 'noopener noreferrer nofollow',
                },
            },
        },
    },
};
