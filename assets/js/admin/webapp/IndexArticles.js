import axios from 'axios';
import {showNotification, showDialog, hideDialog} from "../../composants/tailwind";

export function initIndexArticle(){
    console.log('Bienvenue sur la page d\'ajout ou d\'édition d\'un article.');

    function openDialog(e){
        e.preventDefault()
        let url = this.href;
        showDialog(url,'Suppression', 'Vous êtes sur le point de supprimer l\'article.');
    }

    function submitModal(e){
        e.preventDefault()
        let url = this.href;
        hideDialog();
        axios
            .post(url)
            .then(function(response){
                document.getElementById('liste').innerHTML = response.data.liste;
                reload()
            })
            .catch(function(error){
                console.log(error);
            })
    }

    function reload(){
        // Bouton de suppression de la ligne en cours
        document.querySelectorAll('a.openDialog').forEach(function(link){
            link.addEventListener('click', openDialog)
        })
        // Bouton de suppression de la ligne en cours
        document.querySelectorAll('a.submitModal').forEach(function(link){
            link.addEventListener('click', submitModal)
        })
    }

    reload();
}
