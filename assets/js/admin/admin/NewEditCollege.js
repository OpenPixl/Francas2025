export function initNewEditCollege(){
    ClassicEditor
        .create(document.querySelector('#college_edit_GroupDescription'), {
            toolbar: [ 'heading','bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'indent', 'alignment' ],
            height: 50
        })
        .catch(error => {
            console.error(error);
        });
}
