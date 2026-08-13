import {bindHeaderSaveButton, bindHeaderDeleteButton} from "../../composants/fonctions";

export function initNewEditPage(){
    bindHeaderSaveButton();
    bindHeaderDeleteButton();

    ClassicEditor
        .create(document.querySelector('#page_intro'), {
            toolbar: [ 'heading','bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'indent', 'alignment' ],
            height: 50
        })
        .catch(error => {
            console.error(error);
        });
}
