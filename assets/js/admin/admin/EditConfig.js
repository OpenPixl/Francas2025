import axios from 'axios';
import {showNotification, showDialog} from "../../composants/tailwind";
import {bindHeaderSaveButton} from "../../composants/fonctions";

export function initEditConfig(){
    bindHeaderSaveButton();

    ClassicEditor
        .create(document.querySelector('#config_description'), {
            toolbar: [ 'heading','bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'indent', 'alignment' ],
            height: 50
        })
        .catch(error => {
            console.error(error);
        });

    // Suppression d'un média du site (bandeau ou vignette) — bouton
    // data-action="delete-media" du composant bloc_insert_image.html.twig.
    // Même principe que la page d'édition d'un établissement.
    document.querySelectorAll('[data-action="delete-media"]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const url = btn.dataset.deleteUrl;
            if (!url) return;
            showDialog(url, 'Suppression', 'Voulez-vous supprimer ce fichier ? Cette action est irréversible.', function(url) {
                axios
                    .post(url)
                    .then(function(response) {
                        showNotification('success', response.data.message);
                        setTimeout(() => location.reload(), 1200);
                    })
                    .catch(function() {
                        showNotification('warning', 'Erreur lors de la suppression.');
                    });
            });
        });
    });
}
