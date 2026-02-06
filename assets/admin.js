/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/admin.css';

import { initDropdowns } from './js/composants/tailwind.js';
import { initDashboardIndex } from './js/admin/dashboard/index';
import { initNewEditArticle } from "./js/admin/webapp/NewEditArticles";
import { initNewEditPage } from "./js/admin/admin/NewEditPage";
import { initNewEditCollege } from "./js/admin/admin/NewEditCollege";
import { initEditConfig } from "./js/admin/admin/EditConfig";
import { initIndexCollege } from "./js/admin/admin/IndexCollege";
import { initNewEditUser } from "./js/admin/admin/NewEditUser";
import { initIndexUser } from "./js/admin/admin/IndexUser";

document.addEventListener('DOMContentLoaded', () => {
    // Select all dropdown toggle buttons
    const dropdownToggles = document.querySelectorAll(".dropdown-toggle")
    const page = document.body.dataset.page;

    switch (page) {
        case 'op_admin_config_edit':
            initEditConfig();
            break;
        case 'op_admin_college_new':
        case 'op_admin_college_edit':
            initNewEditCollege();
            break;
        case 'op_admin_college_index':
            initIndexCollege();
            break;
        case 'op_admin_user_index':
            initIndexUser();
            break;
        case 'op_admin_user_new':
        case 'op_admin_user_edit':
            initNewEditUser();
            break
        case 'op_webapp_articles_new_admin':
        case 'op_webapp_articles_edit_admin':
            initNewEditArticle();
            break;
        case 'op_webapp_page_new':
        case 'op_webapp_page_edit':
            initNewEditPage();
            break;
        default:
            console.log('Page non reconnue ou pas de JS spécifique');
    }

    dropdownToggles.forEach((toggle) => {
        toggle.addEventListener("click", () => {
            // Find the next sibling element which is the dropdown menu
            const dropdownMenu = toggle.nextElementSibling

            // Toggle the 'hidden' class to show or hide the dropdown menu
            if (dropdownMenu.classList.contains("hidden")) {
                // Hide any open dropdown menus before showing the new one
                document.querySelectorAll(".dropdown-menu").forEach((menu) => {
                    menu.classList.add("hidden")
                })

                dropdownMenu.classList.remove("hidden")
            } else {
                dropdownMenu.classList.add("hidden")
            }
        })
    })

    // Clicking outside of an open dropdown menu closes it
    window.addEventListener("click", function (e) {
        if (!e.target.matches(".dropdown-toggle")) {
            document.querySelectorAll(".dropdown-menu").forEach((menu) => {
                if (!menu.contains(e.target)) {
                    menu.classList.add("hidden")
                }
            })
        }
    })

    // Mobile menu toggle

    const mobileMenuButton = document.querySelector('.mobile-menu-button')
    const mobileMenu = document.querySelector('.navigation-menu')

    mobileMenuButton.addEventListener('click', () => {
        mobileMenu.classList.toggle('hidden')
    })
});
