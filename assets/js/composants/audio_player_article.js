/**
 * Lecteur audio d'un article (une seule piste), visuel calqué sur le lecteur
 * sous la navbar (audio_player_bar.js) : play/pause, progression (clic,
 * glisser souris/tactile), volume et coupure du son.
 *
 * Markup : templates/webapp/composants/audio_player_article.html.twig
 * Plusieurs lecteurs possibles sur une même page : chacun est initialisé.
 * Appelée sur toutes les pages (assets/app.js) ; idempotente.
 */
export function initAudioPlayersArticle(root = document) {
    root.querySelectorAll('.audio-player-article').forEach(initPlayer);
}

function initPlayer(player) {
    const audio = player.querySelector('.player-audio');
    if (!audio || player.dataset.playerReady) return;
    player.dataset.playerReady = '1';

    const playBtn = player.querySelector('.control-play');
    const iconPlay = player.querySelector('.player-ico-play');
    const iconPause = player.querySelector('.player-ico-pause');
    const iconLoader = player.querySelector('.player-ico-loader');
    const currentEl = player.querySelector('.player-current');
    const durationEl = player.querySelector('.player-duration');
    const rail = player.querySelector('.player-rail');
    const played = player.querySelector('.player-played');
    const thumb = player.querySelector('.player-thumb');
    const volume = player.querySelector('.player-volume');
    const muteBtn = player.querySelector('.control-mute');

    let dragging = false;
    let railRect = null;
    let lastVolume = 1;

    const fmt = (s) => {
        if (!isFinite(s) || s < 0) return '0:00';
        const m = Math.floor(s / 60);
        const ss = String(Math.floor(s % 60)).padStart(2, '0');
        return `${m}:${ss}`;
    };

    const setIcon = (state) => {
        // state: 'play' | 'pause' | 'loading'
        iconPlay.classList.toggle('hidden', state !== 'play');
        iconPause.classList.toggle('hidden', state !== 'pause');
        iconLoader.classList.toggle('hidden', state !== 'loading');
        playBtn.setAttribute('aria-label', state === 'pause' ? 'Pause' : 'Lecture');
    };

    const setProgress = (pct) => {
        played.style.width = pct * 100 + '%';
        thumb.style.left = pct * 100 + '%';
    };

    // ---- Lecture ----
    playBtn.addEventListener('click', () => {
        if (audio.paused) {
            audio.play().catch(() => setIcon('play'));
        } else {
            audio.pause();
        }
    });

    audio.addEventListener('play', () => {
        setIcon('pause');
        // Un seul son à la fois : met en pause les autres lecteurs de la page
        // (dont le lecteur sous la navbar).
        document.querySelectorAll('audio').forEach((other) => {
            if (other !== audio && !other.paused) other.pause();
        });
    });
    audio.addEventListener('playing', () => setIcon('pause'));
    audio.addEventListener('pause', () => setIcon('play'));
    audio.addEventListener('waiting', () => setIcon('loading'));
    audio.addEventListener('canplay', () => setIcon(audio.paused ? 'play' : 'pause'));
    audio.addEventListener('ended', () => {
        setIcon('play');
        setProgress(0);
        currentEl.textContent = '0:00';
    });

    audio.addEventListener('loadedmetadata', () => {
        durationEl.textContent = fmt(audio.duration);
    });

    audio.addEventListener('timeupdate', () => {
        if (dragging || !isFinite(audio.duration) || audio.duration <= 0) return;
        setProgress(audio.currentTime / audio.duration);
        currentEl.textContent = fmt(audio.currentTime);
    });

    // ---- Barre de progression ----
    const seekToClientX = (clientX) => {
        if (!railRect || !isFinite(audio.duration) || audio.duration <= 0) return;
        const pct = Math.max(0, Math.min(1, (clientX - railRect.left) / railRect.width));
        audio.currentTime = pct * audio.duration;
        setProgress(pct);
        currentEl.textContent = fmt(audio.currentTime);
    };

    rail.addEventListener('click', (e) => {
        railRect = rail.getBoundingClientRect();
        seekToClientX(e.clientX);
    });

    thumb.addEventListener('mousedown', (e) => {
        dragging = true;
        railRect = rail.getBoundingClientRect();
        e.preventDefault();
    });
    window.addEventListener('mousemove', (e) => {
        if (dragging) seekToClientX(e.clientX);
    });
    window.addEventListener('mouseup', () => {
        dragging = false;
    });

    thumb.addEventListener('touchstart', () => {
        dragging = true;
        railRect = rail.getBoundingClientRect();
    }, { passive: true });
    window.addEventListener('touchmove', (e) => {
        if (dragging) seekToClientX(e.touches[0].clientX);
    }, { passive: true });
    window.addEventListener('touchend', () => {
        dragging = false;
    });

    // ---- Volume ----
    const syncMuteIcon = () => {
        if (!muteBtn) return;
        const i = muteBtn.querySelector('i');
        if (!i) return;
        const silent = audio.muted || audio.volume === 0;
        i.classList.toggle('fa-volume-high', !silent);
        i.classList.toggle('fa-volume-xmark', silent);
    };

    if (volume) {
        volume.addEventListener('input', (e) => {
            audio.volume = parseFloat(e.target.value);
            audio.muted = audio.volume === 0;
            syncMuteIcon();
        });
    }

    if (muteBtn) {
        muteBtn.addEventListener('click', () => {
            if (audio.muted || audio.volume === 0) {
                audio.muted = false;
                audio.volume = lastVolume || 1;
                if (volume) volume.value = audio.volume;
            } else {
                lastVolume = audio.volume;
                audio.muted = true;
                if (volume) volume.value = 0;
            }
            syncMuteIcon();
        });
    }

    setIcon('play');
    syncMuteIcon();
}
