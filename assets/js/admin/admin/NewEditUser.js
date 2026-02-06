import {zipcode, change_selectcity} from "../../composants/fonctions";

export function initNewEditUser(){
    console.log('Bienvenu sur la page d\'édition d\'un utilisateur.');

    const zipcode_input = document.getElementById('user_zipcode');
    const commune_input = document.getElementById('user_city');
    const commune_select = document.getElementById('selectcity');

    if (commune_input) {
        zipcode_input.addEventListener('input', function (event) {
            zipcode(zipcode_input, commune_input, commune_select);
        });
        commune_select.addEventListener('change', function (event) {
            change_selectcity(zipcode_input, commune_input, commune_select);
        });
    }
}
