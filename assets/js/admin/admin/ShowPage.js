import axios from 'axios';
import {showDialog, hideDialog} from "../../composants/tailwind";

export function initShowPage(){
    console.log('Bienvenu sur la page dédiés aux sections dans une page.');

    const addSection = document.getElementById('btnAddSection');

    // Fonction OnClickBtnMenu - Mettre en page d'accueil la section sélectionnée
    function onClickBtnStar(event){
        event.preventDefault();
        const url = this.href;                                          // variable qui récupère l'url inclus dans le "this"
        const icone = this.querySelector('i');                          // variable qui sélectionne l'élément balise <i></i>
        axios
            .post(url)
            .then(function(response) {
                if(icone.classList.contains('far')) {
                    icone.classList.replace('far', 'fas');
                    icone.classList.replace('text-danger', 'text-success');
                }
                else {
                    icone.classList.replace('fas', 'far');
                    icone.classList.replace('text-success', 'text-danger');
                }
            });
    }

    // Bloc d'ajout d'une nouvelle section à la page
    addSection.addEventListener("click", function(event){
        event.preventDefault();
        const url = this.href;
        axios
            .get(url)
            .then(function(response)
            {
                document.getElementById('liste').innerHTML = response.data.liste;
                reload();
            })
    })

    // Ouvre la modale de confirmation avant suppression de la ligne
    function openDialog(event){
        event.preventDefault();
        const url = this.href;
        showDialog(url, 'Suppression', 'Vous êtes sur le point de supprimer cette section.');
    }

    // Validation de la suppression depuis la modale
    function submitModal(event){
        event.preventDefault();
        const url = this.href;
        hideDialog();
        axios
            .post(url)
            .then(function(response)
            {
                document.getElementById('liste').innerHTML = response.data.liste;
                reload();
            })
            .catch(function(error){
                console.log(error);
            })
    }

    // Bloc de déplacement UP or DOWN d'une section
    function onClickBtnPosition(event)
    {
        event.preventDefault();
        const id = this.id;                                                             // correspond au futur placement de la section
        const icone = this.querySelector('i');
        let level;
        if(icone.classList.contains('fa-long-arrow-alt-up')) {
            level = 'up';
        }else{
            level = 'down';
        }
        const url = '/webapp/section/position/' + id + '/' + level;
        axios
            .get(url)
            .then(function(response){
                document.getElementById('liste').innerHTML = response.data.liste;
                reload();
            })
    }

    // --------------------------------------------------------------------------
    // Bloc de (ré)attachement des évènements sur les lignes de la liste
    // appelé au chargement puis après chaque rafraîchissement AJAX de #liste
    // --------------------------------------------------------------------------
    function reload(){
        // Bouton de mise en vedette d'une section
        document.querySelectorAll('a.js-star').forEach(function (link){
            link.addEventListener('click', onClickBtnStar);
        })

        // Bouton d'ouverture de la modale de suppression
        document.querySelectorAll('a.openModal').forEach(function(link){
            link.addEventListener('click', openDialog);
        })

        // Flèches de déplacement UP/DOWN
        document.querySelectorAll('a.jsPosition').forEach(function (link){
            link.addEventListener('click', onClickBtnPosition);
        })
    }

    reload();

    // Bouton "Valider" de la modale : élément statique, hors de #liste,
    // n'a donc besoin d'être branché qu'une seule fois.
    document.querySelectorAll('a.submitModal').forEach(function(link){
        link.addEventListener('click', submitModal);
    })
}
