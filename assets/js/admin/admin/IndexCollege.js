import axios from 'axios';

export function initIndexCollege(){
    console.log('Bienvenue sur la page de gestions des établissements.')

    // active ou désactive l'utilisateur sélectionné de la plateforme
    function onClickBtnPublish(event){
        event.preventDefault();
        const url = this.href;                                          // variable qui récupère l'url inclus dans le "this"
        const icone = this.querySelector('i');
        axios
            .get(url)
            .then(function(response) {
                if(icone.classList.contains('fa-check-circle')) {
                    icone.classList.replace('fa-check-circle', 'fa-times-circle');
                    icone.classList.replace('text-green-800', 'text-red-800');
                }
                else {
                    icone.classList.replace('fa-times-circle', 'fa-check-circle');
                    icone.classList.replace('text-red-800', 'text-green-800');
                }
            });
    }

    function reload(){
        // Evènement sur le bouton js-verified
        document.querySelectorAll('a.isPublish').forEach(function (link){
            link.addEventListener('click', onClickBtnPublish);
        })
    }

    reload();
}
