import {player_audio} from '../../composants/fonctions';

export function initArticleIndex() {

    const audio = document.getElementById("audio");
    const playBtn = document.getElementById("playBtn");
    const played = document.getElementById("played");
    const rail = document.getElementById("rail");
    const thumb = document.getElementById("thumb");
    const current = document.getElementById("currentTime");
    const duration = document.getElementById("duration");
    const volume = document.getElementById("volume");
    const titleEl = document.getElementById("mp3Title");

    let drag = false;
    let railRect = null;

    player_audio(audio, playBtn, played, rail, thumb, current, duration, volume, titleEl, drag, railRect);
}
