/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';
import { initHomePage } from "./js/app/page/home";
import { initArticleIndex } from "./js/app/college/article";
import { initShowRessource } from "./js/app/ressources/show";
import { initShowPage } from "./js/app/page/show";
import { initNewEditcollege } from "./js/app/college/newEditCollege";
import { initNewEditMessage } from "./js/admin/webapp/NewEditMessage";
import { initNewEditArticle } from "./js/app/article/NewEditArticles";


document.addEventListener('DOMContentLoaded', () => {

    // Affectation du JS selon la page
    const page = document.body.dataset.page;
    switch (page) {
        case 'op_webapp_articles_articleSlug':
            initArticleIndex();
            break;
        case 'op_webapp_ressources_ressourceshow':
            initShowRessource();
            break;
        case 'op_webapp_page':
            initShowPage();
            break;
        case 'op_webapp_public_homepage':
            initHomePage();
            break;
        case 'op_espcoll_college_edit':
            initNewEditcollege();
            break;
        case 'op_webapp_articles_new':
        case 'op_webapp_articles_edit':
            initNewEditArticle();
            break
        case 'op_webapp_message_new':
        case 'op_webapp_message_edit':
            initNewEditMessage();
            break;

        default:
            console.log('Page non reconnue ou pas de JS spécifique');
    }
})
