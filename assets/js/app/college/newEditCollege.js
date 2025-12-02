import {player_audio} from "../../composants/fonctions";

export function initNewEditcollege(){
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
    // sélection du div contenant le spinneur
    const spinner = document.getElementById('spinner')

    const button = document.querySelector('button.btn');
    button.addEventListener('click', event => {
        button.classList.remove('d-none')
        console.log('Ok')
    });
}
