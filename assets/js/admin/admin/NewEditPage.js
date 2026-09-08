import {bindHeaderSaveButton, bindHeaderDeleteButton} from "../../composants/fonctions";
import {richTextConfig} from "../../composants/ckeditor";

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
}
