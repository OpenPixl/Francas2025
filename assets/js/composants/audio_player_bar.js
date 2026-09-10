/**
 * Lecteur audio affiché sous la navbar (zone paramétrable par page).
 *
 * Joue les 5 dernières publications « audio » : une barre compacte
 * (play/pause, pochette, titre, progression, volume) + un sélecteur listant
 * les pistes. Lecture HTML5 inline, aucun changement de page.
 *
 * Markup : templates/webapp/composants/audio_player_bar.html.twig
 */
export function initAudioPlayerBar() {
    const bar = document.getElementById('audio-player-bar');
    if (!bar) return;

    const audio = bar.querySelector('.player-audio');
    const items = Array.from(bar.querySelectorAll('.playlist-item'));
    if (!audio || items.length === 0) return;

    const playBtn = bar.querySelector('.control-play');
    const iconPlay = bar.querySelector('.player-ico-play');
    const iconPause = bar.querySelector('.player-ico-pause');
    const iconLoader = bar.querySelector('.player-ico-loader');
    const cover = bar.querySelector('.media-cover');
    const coverFallback = bar.querySelector('.media-cover-fallback');
    const titleEl = bar.querySelector('.player-title');
    const artistEl = bar.querySelector('.player-artist');
    const currentEl = bar.querySelector('.player-current');
    const durationEl = bar.querySelector('.player-duration');
    const rail = bar.querySelector('.player-rail');
    const played = bar.querySelector('.player-played');
    const thumb = bar.querySelector('.player-thumb');
    const volume = bar.querySelector('.player-volume');
    const muteBtn = bar.querySelector('.control-mute');
    const playlistToggle = bar.querySelector('.playlist-toggle');
    const playlistList = bar.querySelector('.playlist-list');

    let currentIndex = -1;
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
    };

    const setCover = (src) => {
        if (!cover) return;
        if (src) {
            cover.src = src;
            cover.hidden = false;
            if (coverFallback) coverFallback.classList.add('hidden');
        } else {
            cover.removeAttribute('src');
            cover.hidden = true;
            if (coverFallback) coverFallback.classList.remove('hidden');
        }
    };

    const highlight = (index) => {
        items.forEach((el, i) => {
            el.classList.toggle('bg-slate-100', i === index);
            el.classList.toggle('font-semibold', i === index);
        });
    };

    const loadTrack = (index, autoplay) => {
        const item = items[index];
        if (!item) return;
        currentIndex = index;

        audio.src = item.dataset.src || '';
        titleEl.textContent = item.dataset.title || '—';
        artistEl.textContent = item.dataset.artist || '';
        setCover(item.dataset.cover || '');
        played.style.width = '0%';
        thumb.style.left = '0%';
        currentEl.textContent = '0:00';
        durationEl.textContent = '0:00';
        highlight(index);

        if (autoplay) {
            audio.play().catch(() => setIcon('play'));
        }
    };

    // ---- Lecture ----
    playBtn.addEventListener('click', () => {
        if (currentIndex === -1) loadTrack(0, false);
        if (audio.paused) {
            audio.play().catch(() => setIcon('play'));
        } else {
            audio.pause();
        }
    });

    audio.addEventListener('play', () => setIcon('pause'));
    audio.addEventListener('playing', () => setIcon('pause'));
    audio.addEventListener('pause', () => setIcon('play'));
    audio.addEventListener('waiting', () => setIcon('loading'));
    // Fin de mise en mémoire tampon : on retire le spinner.
    audio.addEventListener('canplay', () => setIcon(audio.paused ? 'play' : 'pause'));
    audio.addEventListener('ended', () => {
        if (currentIndex + 1 < items.length) {
            loadTrack(currentIndex + 1, true);
        } else {
            setIcon('play');
        }
    });

    audio.addEventListener('loadedmetadata', () => {
        durationEl.textContent = fmt(audio.duration);
    });

    audio.addEventListener('timeupdate', () => {
        if (dragging || !isFinite(audio.duration) || audio.duration <= 0) return;
        const pct = (audio.currentTime / audio.duration) * 100;
        played.style.width = pct + '%';
        thumb.style.left = pct + '%';
        currentEl.textContent = fmt(audio.currentTime);
    });

    // ---- Barre de progression ----
    const seekToClientX = (clientX) => {
        if (!railRect || !isFinite(audio.duration) || audio.duration <= 0) return;
        const pct = Math.max(0, Math.min(1, (clientX - railRect.left) / railRect.width));
        audio.currentTime = pct * audio.duration;
        played.style.width = pct * 100 + '%';
        thumb.style.left = pct * 100 + '%';
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

    // ---- Volume ----
    if (volume) {
        volume.addEventListener('input', (e) => {
            audio.volume = parseFloat(e.target.value);
            audio.muted = audio.volume === 0;
            syncMuteIcon();
        });
    }

    const syncMuteIcon = () => {
        if (!muteBtn) return;
        const i = muteBtn.querySelector('i');
        if (!i) return;
        const silent = audio.muted || audio.volume === 0;
        i.classList.toggle('fa-volume-high', !silent);
        i.classList.toggle('fa-volume-xmark', silent);
    };

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

    // ---- Sélecteur de pistes ----
    const closePlaylist = () => {
        playlistList.classList.add('hidden');
        playlistToggle.setAttribute('aria-expanded', 'false');
    };
    const togglePlaylist = () => {
        const open = playlistList.classList.toggle('hidden') === false;
        playlistToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    if (playlistToggle && playlistList) {
        playlistToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            togglePlaylist();
        });
        document.addEventListener('click', (e) => {
            if (!playlistList.classList.contains('hidden') && !bar.contains(e.target)) {
                closePlaylist();
            }
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closePlaylist();
        });
    }

    items.forEach((item, index) => {
        item.addEventListener('click', () => {
            loadTrack(index, true);
            closePlaylist();
        });
    });

    // ---- Démarrage : 1re piste chargée, sans lecture ----
    loadTrack(0, false);
    setIcon('play');
    syncMuteIcon();
}
