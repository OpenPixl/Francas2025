import {
    formatDate,
    useTomSelect,
    initializeTinyMCE
} from "../../composants/fonctions"

export function initNewEditMessage(){

    console.log('Bienvenu sur la page d\'edition d\'un message.');

    const Textarea = document.getElementById('message_content');
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

    // ajout d'un select multiple
    useTomSelect('.multiChoice', 'Multi');


}
