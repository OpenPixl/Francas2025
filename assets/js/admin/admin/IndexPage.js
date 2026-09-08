import axios from 'axios';

/**
 * Page « Gestion des pages » : bascule Menu / Publié via des liens AJAX.
 *
 * Même principe que la colonne « Publié sur le site » des établissements
 * (cf. IndexEtablissement.js) : au clic on appelle la route, puis on inverse
 * l'icône et sa couleur Tailwind — vert (fa-check-circle / text-green-800) si
 * actif, rouge (fa-times-circle / text-red-800) sinon.
 *
 * Routes : op_webapp_page_menu, op_webapp_page_publish (retour JSON {code,message}).
 */
export function initIndexPage() {
    document.querySelectorAll('a.js-menu, a.js-publish').forEach(function (link) {
        if (link.dataset.toggleBound) return;
        link.dataset.toggleBound = '1';
        link.addEventListener('click', onToggle);
    });
}

function onToggle(event) {
    event.preventDefault();
    const link = event.currentTarget;
    const icon = link.querySelector('i');
    if (!icon) return;

    axios
        .get(link.href)
        .then(function () {
            if (icon.classList.contains('fa-check-circle')) {
                icon.classList.replace('fa-check-circle', 'fa-times-circle');
                icon.classList.replace('text-green-800', 'text-red-800');
            } else {
                icon.classList.replace('fa-times-circle', 'fa-check-circle');
                icon.classList.replace('text-red-800', 'text-green-800');
            }
        })
        .catch(function (error) {
            console.error(error);
        });
}
