import axios from 'axios';
import {bindHeaderSaveButton, bindHeaderDeleteButton} from "../../composants/fonctions";
import {richTextConfig} from "../../composants/ckeditor";
import {showNotification, showDialog} from "../../composants/tailwind";

export function initNewEditPage(){
    bindHeaderSaveButton();
    bindHeaderDeleteButton();

    ClassicEditor
        .create(document.querySelector('#page_intro'), {
            ...richTextConfig,
            height: 50
        })
        .catch(error => {
            console.error(error);
        });

    // Suppression de l'image d'illustration (bouton data-action="delete-media"
    // du composant bloc_insert_image.html.twig) — même principe que EditConfig.js.
    document.querySelectorAll('[data-action="delete-media"]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const url = btn.dataset.deleteUrl;
            if (!url) return;
            showDialog(url, 'Suppression', 'Voulez-vous supprimer cette image ? Cette action est irréversible.', function(url) {
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
