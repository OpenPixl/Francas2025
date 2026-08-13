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
}
