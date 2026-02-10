export function initIndexArticle(){
    console.log('Bienvenue sur la page d\'ajout ou d\'édition d\'un article par les collèges');

    function openModal(){}

    function reload(){
        document.querySelectorAll('.btnArchived').forEach(function(link){
            link.addEventListener('click', event => {
                event.preventDefault();
                openModal
            })
        })
    }

    reload();
}
