export function initNewEditArticle(){
    console.log('Bienvenue sur la page d\'ajout ou d\'édition d\'un article par les collèges');
    ClassicEditor
        .create(document.querySelector('#articles2_content'), {
            toolbar: [ 'heading','bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'indent', 'alignment' ],
            height: 50
        })
        .catch(error => {
            console.error(error);
        });
}
