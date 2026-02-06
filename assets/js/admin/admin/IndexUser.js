import axios from 'axios';

export function initIndexUser(){
    console.log('Bienvenu sur la page d\'index des utilisateurs.');

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

    // function Suppression de la ligne en cours
    function onClickDelEvent(event){
        //event.preventDefault()
        const id = document.getElementById('recipient-name').value
        axios
            .post('/admin/user/del/'+ id)
            .then(function(response)
            {
                const liste = document.getElementById('liste').innerHTML;
                console.log(liste);

                // Bouton de suppression de la ligne en cours
                document.querySelectorAll('a.js-data-suppr').forEach(function(link){
                    link.addEventListener('click', onClickDelEvent)
                })

                var option = {
                    animation : true,
                    autohide: true,
                    delay : 3000,
                };

                var toastHTMLElement = document.getElementById("toaster");
                var message = response.data.message;
                var toastBody = toastHTMLElement.querySelector('.toast-body') // selection de l'élément possédant le message
                toastBody.textContent = message;
                var toastElement = new bootstrap.Toast(toastHTMLElement, option);
                toastElement.show();
            })
            .catch(function(error){
                console.log(error);
            })
    }

    function reload(){

        // Evènement sur le bouton js-verified
        document.querySelectorAll('a.isPublish').forEach(function (link){
            link.addEventListener('click', onClickBtnPublish);
        })

        // Bouton de suppression de la ligne en cours
        document.querySelectorAll('a.js-data-suppr').forEach(function(link){
            link.addEventListener('click', onClickDelEvent)
        })
    }

    reload();

}
