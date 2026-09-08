import './stimulus_bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';
import { initHomePage } from "./js/app/page/home";
import { initArticleIndex } from "./js/app/etablissement/article";
import { initShowRessource } from "./js/app/ressources/show";
import { initShowPage } from "./js/app/page/show";
import { initNewEditEtablissement } from "./js/app/etablissement/newEditEtablissement";
import { initNewEditMessage } from "./js/admin/webapp/NewEditMessage";
import { initNewEditArticle } from "./js/app/article/NewEditArticles";
import { initIndexAdminArticle } from "./js/admin/webapp/IndexArticles";
import { initSearchSelects } from "./js/composants/fonctions";


/**
 * Exécute callback quand le DOM est prêt (ou tout de suite s'il l'est déjà).
 * Remplace l'ancien écouteur `turbo:load` : Turbo Drive est désactivé, chaque
 * navigation est un chargement de page complet.
 */
function onReady(callback) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', callback);
    } else {
        callback();
    }
}

onReady(() => {

    // Listes déroulantes « recherche + sélection » (data-search-select).
    initSearchSelects();

    // Affectation du JS selon la page
    const page = document.body.dataset.page;
    switch (page) {
        case 'op_webapp_articles_articleSlug':
        case 'op_webapp_articles_show':
            initArticleIndex();
            break;
        case 'op_webapp_ressources_ressourceshow':
            initShowRessource();
            break;
        case 'op_webapp_page':
        case 'op_webapp_page_slug':
        case 'op_webapp_page_display':
            initShowPage();
            break;
        case 'op_webapp_public_homepage':
            initHomePage();
            break;
        case 'op_espetab_etablissement_edit':
            initNewEditEtablissement();
            break;
        case 'op_webapp_articles_new':
        case 'op_webapp_articles_edit':
            initNewEditArticle();
            break;
        case 'op_webapp_message_new':
        case 'op_webapp_message_edit':
            initNewEditMessage();
            break;

        default:
            console.log('Page non reconnue ou pas de JS spécifique');
    }
})
