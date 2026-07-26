import axios from 'axios';
import {showNotification, showDialog, hideDialog} from "../../composants/tailwind";
import {player_audio} from "../../composants/fonctions";

export function initNewEditEtablissement(){
    console.log('Bienvenu sur la page d\'edition d\'un établissement.');
    const Textarea = document.getElementById('etablissement_GroupDescription');
    ClassicEditor
        .create(Textarea, {
            toolbar: [ 'heading','bold', 'italic', 'bulletedList', 'numberedList', 'blockQuote', 'indent', 'alignment' ],
        })
        .then(editor => {
            const editable = editor.ui.view.editable.element;

            // Applique les classes du textarea
            if (Textarea.classList.length > 0) {
                editable.classList.add(...Textarea.classList);
            }

            // Convertit rows -> min-height
            const rows = Textarea.getAttribute("rows");
            if (rows) {
                editable.style.minHeight = `${rows * 1.2}em`;
            }
        })
        .catch(error => {
            console.error(error);
        });
    // sélection du div contenant le spinneur
    const spinner = document.getElementById('spinner')

    const button = document.querySelector('button.btn');
    button.addEventListener('click', event => {
        button.classList.remove('d-none')
        console.log('Ok')
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
