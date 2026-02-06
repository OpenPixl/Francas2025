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
}
