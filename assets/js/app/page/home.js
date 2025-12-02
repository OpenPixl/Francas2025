import {carousel} from '../../composants/tailwind'

export function initHomePage(){
    console.log('Bienvenue sur la page d\'accueil du site.')
    const track = document.getElementById("carousel-track");
    carousel(track);
}
