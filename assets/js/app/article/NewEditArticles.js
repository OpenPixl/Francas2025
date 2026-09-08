import axios from 'axios';
import {showNotification, showDialog, hideDialog} from "../../composants/tailwind";
import {richTextConfig} from "../../composants/ckeditor";

export function initNewEditArticle(){
    console.log('Bienvenue sur la page d\'ajout ou d\'édition d\'un article par les collèges');
    ClassicEditor
        .create(document.querySelector('#articles2_content'), {
            ...richTextConfig,
            height: 50
        })
        .catch(error => {
            console.error(error);
        });

    // Suppression d'un média (logo ou bandeau)
    document.querySelectorAll('[data-action="delete-media"]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const url = btn.dataset.deleteUrl;
            if (!url) return;
            showDialog(url, 'Suppression', 'Voulez-vous supprimer ce fichier ? Cette action est irréversible.');
        });
    });

    const validModal = document.getElementById('validModal');
    if (validModal) {
        validModal.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.href;
            hideDialog();
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
    }
}
