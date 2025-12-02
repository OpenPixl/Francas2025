export function initNewEditArticle(){
    ClassicEditor
        .create(document.querySelector('#articles_content'), {
            toolbar: [ 'heading','bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'indent', 'alignment' ],
            height: 50
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
