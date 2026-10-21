import axios from 'axios';
import {showNotification, showDialog} from "../../composants/tailwind";

/**
 * Liste des articles depuis l'espace Etablissement (route op_espetab_articles_byetablissement).
 * Le bouton .btnArchived ouvre la modale de confirmation ; à la validation, la requête
 * d'archivage est envoyée puis la liste #listeArticles est rafraîchie.
 */
export function initIndexArticle(){
    console.log('Bienvenue sur la liste des articles de l\'établissement');

    function openArchivedDialog(event){
        event.preventDefault()
        showDialog(
            this.href,
            'Archivage de l\'article',
            'Vous êtes sur le point de mettre votre article en archive. Seul le coordinateur du dispositif aura accès à l\'article.<br>Êtes-vous sûr de vouloir continuer ?',
            submitArchived
        );
    }

    function submitArchived(url){
        axios
            .post(url)
            .then(function (response){
                document.getElementById('listeArticles').innerHTML = response.data.listeArticles
                showNotification('success', response.data.message)
                reload()
            })
            .catch(function (error){
                console.log(error)
                showNotification('warning', 'Erreur lors de l\'archivage de l\'article.')
            })
    }

    function reload(){
        // Bouton d'archivage de la ligne en cours
        document.querySelectorAll('a.btnArchived').forEach(function(link){
            link.addEventListener('click', openArchivedDialog)
        })
    }

    reload();
}
