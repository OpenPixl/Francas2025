export function initShowRessource() {
    (function () {

        const v = document.getElementById("video");
        const play = document.getElementById("videoPlay");
        const current = document.getElementById("videoCurrent");
        const duration = document.getElementById("videoDuration");
        const rail = document.getElementById("videoRail");
        const played = document.getElementById("videoPlayed");
        const thumb = document.getElementById("videoThumb");
        const volume = document.getElementById("videoVolume");
        const fullscreen = document.getElementById("videoFullscreen");

        let dragging = false;
        let railRect = null;

        function fmt(t) {
            if (!isFinite(t)) return "0:00";
            const m = Math.floor(t / 60);
            const s = String(Math.floor(t % 60)).padStart(2, "0");
            return `${m}:${s}`;
        }

        v.addEventListener("loadedmetadata", () => {
            duration.textContent = fmt(v.duration);
        });

        v.addEventListener("timeupdate", () => {
            if (!dragging) {
                const pct = v.currentTime / v.duration * 100;
                played.style.width = pct + "%";
                thumb.style.left = pct + "%";
                current.textContent = fmt(v.currentTime);
            }
        });

        play.addEventListener("click", () => {
            if (v.paused) {
                v.play();
                play.textContent = "❚❚";
            } else {
                v.pause();
                play.textContent = "►";
            }
        });

        volume.addEventListener("input", () => {
            v.volume = volume.value;
        });

        rail.addEventListener("click", (e) => {
            railRect = rail.getBoundingClientRect();
            const pct = (e.clientX - railRect.left) / railRect.width;
            v.currentTime = pct * v.duration;
        });

        thumb.addEventListener("mousedown", (e) => {
            dragging = true;
            railRect = rail.getBoundingClientRect();
            e.preventDefault();
        });

        window.addEventListener("mousemove", (e) => {
            if (!dragging) return;
            const pct = Math.max(0, Math.min(1, (e.clientX - railRect.left) / railRect.width));
            played.style.width = pct * 100 + "%";
            thumb.style.left = pct * 100 + "%";
        });

        window.addEventListener("mouseup", (e) => {
            if (!dragging) return;
            const pct = Math.max(0, Math.min(1, (e.clientX - railRect.left) / railRect.width));
            v.currentTime = pct * v.duration;
            dragging = false;
        });

        // Fullscreen
        fullscreen.addEventListener("click", () => {
            if (!document.fullscreenElement) {
                v.requestFullscreen();
            } else {
                document.exitFullscreen();
            }
        });

    })();
}
