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

    // Formulaire de recherche instancié dans la navbar (NavbarSearchController)
    const searchForm = document.getElementById('navbar_search_form');
    if (searchForm) {
        // Entrée : recherche complète, rechargement de la liste principale.
        searchForm.addEventListener('submit', function (event) {
            event.preventDefault();
            const params = new URLSearchParams(new FormData(searchForm));
            axios
                .get(searchForm.action + '?' + params.toString())
                .then(function (response) {
                    document.getElementById('liste').innerHTML = response.data.liste;
                    reload();
                })
                .catch(function (error) {
                    console.log(error);
                });
        });

        initLiveSearch(searchForm);
    }

    /**
     * Recherche instantanée Elasticsearch : dès MIN_CHARS caractères saisis, les
     * résultats (titre / thème / établissement) s'affichent dans le panneau
     * #navbar_search_results sous le champ.
     */
    function initLiveSearch(form) {
        const MIN_CHARS = 5;
        const input = form.querySelector('input[type="search"]');
        const panel = document.getElementById('navbar_search_results');
        const liveUrl = form.dataset.liveUrl;

        if (!input || !panel || !liveUrl) return;

        let debounceId;
        let lastQuery = null;
        let requestId = 0;

        function hide() {
            panel.classList.add('hidden');
            panel.innerHTML = '';
            lastQuery = null;
        }

        function show() {
            panel.classList.remove('hidden');
        }

        input.addEventListener('input', function () {
            const q = input.value.trim();
            window.clearTimeout(debounceId);

            if (q.length < MIN_CHARS) {
                hide();
                return;
            }

            debounceId = window.setTimeout(function () {
                if (q === lastQuery) return;
                lastQuery = q;
                const current = ++requestId;

                axios
                    .get(liveUrl, { params: { q: q } })
                    .then(function (response) {
                        if (current !== requestId) return; // réponse obsolète
                        panel.innerHTML = response.data.html || '';
                        show();
                    })
                    .catch(function () {
                        if (current === requestId) hide();
                    });
            }, 250);
        });

        input.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                hide();
                input.blur();
            }
        });

        // Ferme le panneau au clic en dehors du formulaire de recherche.
        document.addEventListener('click', function (e) {
            if (!form.contains(e.target)) hide();
        });
    }
}
