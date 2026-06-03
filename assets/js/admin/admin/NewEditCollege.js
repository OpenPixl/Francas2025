import axios from 'axios';
import {showNotification, showDialog, hideDialog} from "../../composants/tailwind";
import {zipcode, change_selectcity} from "../../composants/fonctions";

export function initNewEditCollege(){
    console.log('Bienvenu sur la page d\'edition d\'un college.');
    const Textarea = document.getElementById('college_GroupDescription');
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

    const zipcode_input = document.getElementById('college_zipcode');
    const commune_input = document.getElementById('college_city');
    const commune_select = document.getElementById('selectcity');

    if (commune_input) {
        zipcode_input.addEventListener('input', function (event) {
            zipcode(zipcode_input, commune_input, commune_select);
        });
        commune_select.addEventListener('change', function (event) {
            change_selectcity(zipcode_input, commune_input, commune_select);
        });
    }

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
