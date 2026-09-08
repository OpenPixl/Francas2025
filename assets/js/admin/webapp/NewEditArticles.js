import {bindHeaderSaveButton, bindHeaderDeleteButton} from "../../composants/fonctions";
import {richTextConfig} from "../../composants/ckeditor";

export function initNewEditArticle(){
    bindHeaderSaveButton();
    bindHeaderDeleteButton();

    ClassicEditor
        .create(document.querySelector('#articles_content'), {
            ...richTextConfig,
            height: 50
        })
        .catch(error => {
            console.error(error);
        });


}
