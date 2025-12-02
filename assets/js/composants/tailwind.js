// Dropdown
export function initDropdowns() {
    document.querySelectorAll('.dropdown-toggle').forEach(button => {
        button.addEventListener('click', function (e) {
            e.stopPropagation();

            const dropdown = this.closest('.dropdown');
            const menu = dropdown.querySelector('.dropdown-menu');

            // Fermer tous les autres
            document.querySelectorAll('.dropdown-menu').forEach(m => {
                if (m !== menu) m.classList.add('hidden');
            });

            menu.classList.toggle('hidden');
        });
    });

    // Clic extérieur
    document.addEventListener('click', () => {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.classList.add('hidden');
        });
    });

    // Échap pour fermer
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.classList.add('hidden');
            });
        }
    });
}

// Module Notification/Toast
export function showNotification(state, message, delay = 3000) {
    const notification = document.getElementById("notification");
    const closeBtn = document.getElementById("closeNotification");

    console.log(message);

    if (state === 'success') {
        notification.classList.add('border', 'border-green-300');
        // changer le texte si besoin
        notification.querySelector("#title").textContent = "Réussite";
    }
    if (state === 'warning') {
        // changer le texte si besoin
        notification.querySelector("#title").textContent = "Attention";
        notification.classList.add('border', 'border-orange-300');
    }

    // changer le texte si besoin
    notification.querySelector("#message").textContent = message;

    // afficher avec transition
    notification.classList.remove("hidden");
    setTimeout(() => {
        notification.classList.remove("opacity-0", "translate-y-2", "sm:translate-x-2", 'border', 'border-orange-300');
        notification.classList.add("opacity-100", "translate-y-0", "sm:translate-x-0");
    }, 50);

    // auto-fermeture
    setTimeout(() => hideNotification(), delay);
}

export function hideNotification() {
    notification.classList.remove("opacity-100", "translate-y-0", "sm:translate-x-0", 'border', 'border-orange-300');
    notification.classList.add("opacity-0", "translate-y-2", "sm:translate-x-2");

    // attendre la fin de la transition avant de cacher complètement
    setTimeout(() => {
        notification.classList.add("hidden");
    }, 3000);
}

// Module Dialog/Modal

export function showDialog(message = "Successfully saved!", delay = 3000) {
    let dialog = document.getElementById("dialog");
    //const closeBtn = document.getElementById("closeNotification");

    // changer le texte si besoin
    //notification.querySelector("p.font-medium").textContent = message;

    // afficher avec transition
    dialog.classList.remove("hidden");
    setTimeout(() => {
        dialog.classList.remove("opacity-0", "translate-y-2", "sm:translate-x-2");
        dialog.classList.add("opacity-100", "translate-y-0", "sm:translate-x-0");
    }, 50);

    // auto-fermeture
    setTimeout(() => hideNotification(), delay);
}

export function hideDialog() {
    let dialog = document.getElementById("dialog");

    dialog.classList.remove("opacity-100", "translate-y-0", "sm:translate-x-0");
    dialog.classList.add("opacity-0", "translate-y-2", "sm:translate-x-2");

    dialog.querySelector('#modal_body_text').innerHTML = "<p class=\"text-sm font-normal text-slate-700\">Présentation d'un contenu selon le besoin de la\n" +
        "modale</p>"
    ;

    // attendre la fin de la transition avant de cacher complètement
    setTimeout(() => {
        dialog.classList.add("hidden");
    }, 300);
}

// carousel
export function carousel(track){

    const carousel = track.parentElement;
    const bars = document.querySelectorAll(".bar");
    const delay = 6000; // 6 secondes

    let index = 0;
    let autoplayInterval = null;

    function goToSlide(i) {
        index = i;
        track.style.transform = `translateX(-${i * 100}%)`;

        bars.forEach((b, k) => {
            b.classList.toggle("bg-white", k === i);
            b.classList.toggle("bg-white/40", k !== i);
        });
    }

    function startAutoplay() {
        autoplayInterval = setInterval(() => {
            index = (index + 1) % bars.length;
            goToSlide(index);
        }, delay);
    }

    function stopAutoplay() {
        clearInterval(autoplayInterval);
    }

    bars.forEach((bar, i) => {
        bar.addEventListener("click", () => goToSlide(i));
    });

    carousel.addEventListener("mouseenter", stopAutoplay);
    carousel.addEventListener("mouseleave", startAutoplay);

    // Initialisation
    goToSlide(0);
    startAutoplay();
}


