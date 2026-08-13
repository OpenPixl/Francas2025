import {bindHeaderSaveButton, bindHeaderDeleteButton} from "../../composants/fonctions";

export function initNewEditArticle(){
    bindHeaderSaveButton();
    bindHeaderDeleteButton();

    ClassicEditor
        .create(document.querySelector('#articles_content'), {
            toolbar: [ 'heading','bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'indent', 'alignment' ],
            height: 50
        })
        .catch(error => {
            console.error(error);
        });


}
