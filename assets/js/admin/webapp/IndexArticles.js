import axios from 'axios';
import {showNotification, showDialog, hideDialog} from "../../composants/tailwind";
import {initNavbarSearch} from "../../composants/fonctions";

export function initIndexArticle(){
    console.log('Bienvenue sur la page d\'ajout ou d\'édition d\'un article.');

    function openDialog(e){
        e.preventDefault()
        let url = this.href;
        // Titre et message propres au bouton (désarchivage), suppression par défaut
        showDialog(
            url,
            this.dataset.dialogTitle || 'Suppression',
            this.dataset.dialogMessage || 'Vous êtes sur le point de supprimer l\'article.'
        );
    }

    function submitModal(e){
        e.preventDefault()
        let url = this.href;
        hideDialog();
        axios
            .post(url)
            .then(function(response){
                document.getElementById('liste').innerHTML = response.data.liste;
                // Compteurs des onglets En ligne / Archivés
                ['countOnline', 'countArchived'].forEach(function(id){
                    const counter = document.getElementById(id);
                    if (counter && response.data[id] !== undefined) counter.textContent = response.data[id];
                })
                if (response.data.message) showNotification('success', response.data.message);
                reload()
            })
            .catch(function(error){
                console.log(error);
            })
    }

    function reload(){
        // Boutons de suppression / désarchivage de la ligne en cours
        document.querySelectorAll('a.openDialog').forEach(function(link){
            link.addEventListener('click', openDialog)
        })
    }

    // Bouton « Valider » de la modale : hors de #liste, branché une seule fois
    // (le rebrancher dans reload() empilait les écouteurs → requête envoyée en double)
    document.querySelectorAll('a.submitModal').forEach(function(link){
        link.addEventListener('click', submitModal)
    })

    reload();

    // Filtre « Archivés » actif (?archived=1) : transmis à la recherche de la navbar
    if (new URLSearchParams(window.location.search).get('archived') === '1') {
        const searchForm = document.getElementById('navbar_search_form');
        if (searchForm) {
            const archivedInput = document.createElement('input');
            archivedInput.type = 'hidden';
            archivedInput.name = 'archived';
            archivedInput.value = '1';
            searchForm.appendChild(archivedInput);
        }
    }

    // Formulaire de recherche instancié dans la navbar (NavbarSearchController)
    initNavbarSearch('liste', reload);
}
