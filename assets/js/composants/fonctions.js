import TomSelect from "tom-select";
import 'tom-select/dist/css/tom-select.css';
import axios from 'axios';
import {showDialog, showNotification} from "./tailwind";


export function useTomSelect(selector, option) {
    const TsSimple = {
        //plugins: ['remove_button'],
        create: true,
        onItemAdd:function(){
            this.setTextboxValue('');
            this.refreshOptions();
        },
        render:{
            option:function(data,escape){
                return '<div class="d-flex"><span>' + escape(data.data) + '</span></div>';
            },
            item:function(data,escape){
                return '<div>' + escape(data.data) + '</div>';
            }
        }
    };
    const TsMulti = {
        plugins: ['remove_button'],
        create: true,
        onItemAdd:function(){
            this.setTextboxValue('');
            this.refreshOptions();
        },
        render:{
            option:function(data,escape){
                return '<div><span>' + escape(data.data) + '</span></div>';
            },
            item:function(data,escape){
                return '<div>' + escape(data.data) + '</div>';
            }
        }
    };
    console.log(option);

    if (option === 'Simple'){
        initializeTomSelect('.oneChoice', TsSimple);
    }
    else if (option === 'Multi'){
        initializeTomSelect('.multiChoice', TsMulti);
    }

    function initializeTomSelect(selector, options = {}) {
        document.querySelectorAll(selector).forEach(selectElement => {
            new TomSelect(selectElement, options);
        });
    }
}

/**
 * Transforme en liste déroulante « recherche + sélection » (Tom Select) tous les
 * <select> portant l'attribut data-search-select.
 *
 * Prévu pour les listes liées à une entité (choisir un article, une page, une
 * catégorie…) : la frappe au clavier filtre les options, sans création de valeur.
 * L'apparence est alignée sur les autres champs via les classes .ts-styled /
 * .ts-styled-dropdown (cf. assets/styles/admin.css).
 *
 * Idempotent : les <select> déjà initialisés sont ignorés. Appelable sur
 * n'importe quel formulaire d'édition ou de création.
 *
 * @param {ParentNode} root  Racine de recherche (document par défaut).
 */
export function initSearchSelects(root = document) {
    root.querySelectorAll('select[data-search-select]').forEach((select) => {
        if (select.tomselect || select.classList.contains('ts-hidden-accessible')) {
            return;
        }

        const isRequired = select.hasAttribute('required') && !select.disabled;
        const isMultiple = select.multiple;
        const emptyOption = select.querySelector('option[value=""]');
        const placeholder = select.dataset.placeholder
            || (emptyOption ? emptyOption.textContent.trim() : '')
            || (isMultiple ? 'Sélectionner…' : 'Rechercher…');

        // En mode multi : chips avec croix de retrait + vidage global ; pas de
        // limite d'items. En mode simple : bouton d'effacement si non requis.
        const plugins = isMultiple
            ? ['remove_button', 'clear_button']
            : (isRequired ? [] : ['clear_button']);

        try {
            new TomSelect(select, {
                create: false,
                allowEmptyOption: !isRequired,
                maxOptions: null,
                maxItems: isMultiple ? null : 1,
                placeholder: placeholder,
                wrapperClass: 'ts-wrapper ts-styled',
                dropdownClass: 'ts-dropdown ts-styled-dropdown',
                // Le champ est souvent dans un conteneur overflow-hidden : on sort
                // le menu du flux pour éviter qu'il soit rogné.
                dropdownParent: 'body',
                plugins: plugins,
                // Conserve l'ordre des <option> fourni par le serveur.
                sortField: { field: '$order' },
            });
        } catch (e) {
            console.error('[initSearchSelects] échec sur', select, e);
        }
    });
}

/**
 * Câble le formulaire de recherche instancié dans la navbar admin (NavbarSearchController)
 * sur une page de liste : Entrée = recherche complète (rechargement AJAX de la liste),
 * saisie à partir de MIN_CHARS caractères = suggestions instantanées Elasticsearch dans
 * le panneau #navbar_search_results sous le champ.
 *
 * @param {string} listSelector id du conteneur de la liste à remplacer par la réponse AJAX
 * @param {Function} [onListLoaded] rappelé après remplacement de la liste (ex : réattacher des écouteurs)
 */
export function initNavbarSearch(listSelector, onListLoaded) {
    const searchForm = document.getElementById('navbar_search_form');
    if (!searchForm) return;

    searchForm.addEventListener('submit', function (event) {
        event.preventDefault();
        const params = new URLSearchParams(new FormData(searchForm));
        axios
            .get(searchForm.action + '?' + params.toString())
            .then(function (response) {
                document.getElementById(listSelector).innerHTML = response.data.liste;
                if (typeof onListLoaded === 'function') onListLoaded();
            })
            .catch(function (error) {
                console.log(error);
            });
    });

    initNavbarLiveSearch(searchForm);
}

function initNavbarLiveSearch(form) {
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

/**
 * Le bouton "Enregistrer"/"Mettre à jour" du header (sous la navbar) est en dehors du <form> :
 * il ne peut donc pas déclencher nativement la soumission. On le relie ici au <form> de la page.
 */
export function bindHeaderSaveButton() {
    const saveBtn = document.getElementById('btn-header-save');
    const form = document.querySelector('#content form');

    if (!saveBtn || !form) return;

    saveBtn.addEventListener('click', function (e) {
        e.preventDefault();
        form.requestSubmit();
    });
}

/**
 * Le bouton "Supprimer" du header (sous la navbar) déclenche la modale de confirmation
 * existante puis, une fois validée, appelle l'action de suppression de l'entité avec le
 * bon verbe HTTP et le token CSRF, avant de rediriger vers la page indiquée.
 */
export function bindHeaderDeleteButton() {
    const delBtn = document.getElementById('btn-header-delete');
    if (!delBtn) return;

    const url = delBtn.dataset.deleteUrl;
    const method = (delBtn.dataset.deleteMethod || 'DELETE').toUpperCase();
    const token = delBtn.dataset.csrfToken;
    const redirectUrl = delBtn.dataset.redirectUrl;
    const message = delBtn.dataset.confirmMessage || 'Voulez-vous vraiment supprimer cet élément ? Cette action est irréversible.';

    if (!url) return;

    delBtn.addEventListener('click', function (e) {
        e.preventDefault();
        showDialog(url, 'Suppression', message, function (confirmedUrl) {
            axios
                .request({
                    url: confirmedUrl,
                    method: method,
                    data: new URLSearchParams({ _token: token || '' }),
                })
                .then(function () {
                    if (redirectUrl) {
                        window.location.href = redirectUrl;
                    }
                })
                .catch(function () {
                    showNotification('warning', 'Erreur lors de la suppression.');
                });
        });
    });
}

export function removeOptions(selectElement) {
    for (let i = selectElement.options.length - 1; i >= 0; i -= 1) {
        selectElement.remove(i);
    }
}

export function zipcode(zipcodeInput, communeInput, select) {
    zipcodeInput.addEventListener('input', () => {
        if (zipcodeInput.value.length !== 5) return;

        axios
            .get(`https://apicarto.ign.fr/api/codes-postaux/communes/${zipcodeInput.value}`)
            .then(({ data }) => {
                removeOptions(select);

                data.forEach((el, idx) => {
                    const label = `${el.nomCommune.toUpperCase()} (${el.codePostal})`;
                    const opt = new Option(label, label, idx === 0, idx === 0);
                    select.options.add(opt);
                });

                if (data.length) {
                    zipcodeInput.value = data[0].codePostal;
                    communeInput.value = data[0].nomCommune.toUpperCase();
                }
            })
            .catch(() => alert('Pas de commune pour ce code postal'));
    });
}

export function change_selectcity(zipcode, commune, select){
    let regex = /^(.+) \((\d+)\)$/;
    let select_value = select.options[select.selectedIndex].text;
    const match = select_value.match(regex);
    zipcode.value = match[2];
    commune.value = match[1].toUpperCase();
}

export function formatDate(dateInput) {
    dateInput.addEventListener('input', function (e) {
        let value = e.target.value.replace(/\D/g, ''); // supprime tout sauf chiffres
        if (value.length > 8) value = value.substring(0, 8); // max 8 chiffres
        let formatted = '';
        if (value.length > 0) {
            formatted += value.substring(0, 2);
        }
        if (value.length > 2) {
            formatted += '/' + value.substring(2, 4);
        }
        if (value.length > 4) {
            formatted += '/' + value.substring(4, 8);
        }
        // Appliquer le format
        e.target.value = formatted;
    });
    dateInput.addEventListener('paste', function (e) {
        e.preventDefault();
        const paste = (e.clipboardData || window.clipboardData).getData('text');
        const digits = paste.replace(/\D/g, '').substring(0, 8);
        let formatted = '';
        if (digits.length >= 2) formatted += digits.substring(0, 2);
        if (digits.length >= 4) formatted += '/' + digits.substring(2, 4);
        if (digits.length > 4) formatted += '/' + digits.substring(4, 8);
        e.target.value = formatted;
    });
}

export function formatTel(telInput) {
    telInput.addEventListener('input', function (e) {
        let value = e.target.value.replace(/\D/g, ''); // supprime tout sauf chiffres
        if (value.length > 8) value = value.substring(0, 10); // max 8 chiffres
        let formatted = '';
        if (value.length > 0) {
            formatted += value.substring(0, 2);
        }
        if (value.length > 2) {
            formatted += ' ' + value.substring(2, 4);
        }
        if (value.length > 4) {
            formatted += ' ' + value.substring(4, 6);
        }
        if (value.length > 6) {
            formatted += ' ' + value.substring(6, 8);
        }
        if (value.length > 8) {
            formatted += ' ' + value.substring(8, 10);
        }
        // Appliquer le format
        e.target.value = formatted;
    });
    telInput.addEventListener('paste', function (e) {
        e.preventDefault();
        const paste = (e.clipboardData || window.clipboardData).getData('text');
        const digits = paste.replace(/\D/g, '').substring(0, 10);
        let formatted = '';
        if (digits.length >= 2) formatted += digits.substring(0, 2);
        if (digits.length >= 4) formatted += ' ' + digits.substring(2, 4);
        if (digits.length >= 6) formatted += ' ' + digits.substring(4, 6);
        if (digits.length >= 8) formatted += ' ' + digits.substring(6, 8);
        if (digits.length >= 10) formatted += ' ' + digits.substring(8, 10);
        e.target.value = formatted;
    });
}
