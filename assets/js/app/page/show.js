import axios from 'axios';
import {filters} from "../../composants/filters";

export function initShowPage()
{
    console.log('Bienvenue sur la page d\'affichage')

    const section = document.querySelector('#form_search');
    if (section) {
        const form = section.querySelector('form');
        const button = section.querySelector('button[type="submit"], input[type="submit"]');
        if (form && button) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();

                const method = (form.method || 'post').toLowerCase();
                const formData = new FormData(form);

                axios
                    .post(form.action, formData)
                    .then(response => {
                        const results = document.querySelector('#form_results');
                        if (results && response.data.liste) {
                            results.innerHTML = response.data.liste;
                    }
                }).catch(error => {
                    console.error('Erreur lors de la recherche', error);
                });
            });
        }
    }
    filters()

}
