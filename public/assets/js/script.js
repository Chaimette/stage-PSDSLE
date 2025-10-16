// Navigation responsive avec dropdown "Autre"
(function () {
    const navList = document.getElementById("main-nav");
    if (!navList) return;

    const moreLi = navList.querySelector(".more");
    const moreMenu = document.getElementById("more-menu");
    
    // Sélectionner tous les éléments qui peuvent déborder (y compris les dropdowns)
    const candidates = Array.from(navList.querySelectorAll(".nav-item.can-overflow, .nav-item.dropdown:not(.more)"));

    let isFirstRender = true;

    // On indexe les éléments pour le tri
    candidates.forEach((li, idx) => (li.dataset.index = String(idx)));

    // Gestion des dropdowns
    function closeAllDropdowns(except = null) {
        navList.querySelectorAll(".dropdown.open").forEach((li) => {
            if (li !== except) {
                li.classList.remove("open");
                li.querySelector(".dropdown-toggle")?.setAttribute("aria-expanded", "false");
            }
        });
    }

    // Events pour les dropdowns
    navList.addEventListener("click", (e) => {
        const btn = e.target.closest(".dropdown-toggle");
        if (!btn) return;

        const li = btn.closest(".dropdown");
        const isOpen = li.classList.toggle("open");
        btn.setAttribute("aria-expanded", isOpen ? "true" : "false");

        if (isOpen) closeAllDropdowns(li);
        e.stopPropagation();
    });

    document.addEventListener("click", () => closeAllDropdowns());
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") closeAllDropdowns();
    });

    // Fonctions de gestion du overflow
    function hasOverflow() {
        return navList.scrollWidth > navList.clientWidth + 1;
    }

    function updateMoreVisibility() {
        moreLi.hidden = moreMenu.children.length === 0;
    }

    function moveToMore() {
        const visible = candidates.filter((li) => li.parentElement === navList);
        if (visible.length === 0) return false;

        // Prendre le dernier élément visible (ordre inverse)
        visible.sort((a, b) => Number(b.dataset.index) - Number(a.dataset.index));
        const li = visible[0];
        moreMenu.insertBefore(li, moreMenu.firstChild);
        return true;
    }

    function restoreFromMore() {
        if (moreMenu.children.length === 0) return;

        const pool = Array.from(moreMenu.children).sort((a, b) => Number(a.dataset.index) - Number(b.dataset.index));

        for (const li of pool) {
            // Trouver la bonne position dans la nav en fonction de l'index
            const targetIndex = Number(li.dataset.index);
            let insertBefore = moreLi;
            
            // Chercher le premier élément visible avec un index supérieur
            for (const candidate of candidates) {
                if (candidate.parentElement === navList && Number(candidate.dataset.index) > targetIndex) {
                    insertBefore = candidate;
                    break;
                }
            }
            
            navList.insertBefore(li, insertBefore);
            
            if (hasOverflow()) {
                // Remettre dans "Autre" si ça déborde
                moreMenu.insertBefore(li, moreMenu.firstChild);
                break;
            }
        }
    }

    function rebalanceNav() {
        const wasHidden = moreLi.hidden;
        if (wasHidden) moreLi.hidden = false;

        // Essayer de restaurer des éléments depuis "Autre"
        restoreFromMore();

        // Déplacer vers "Autre" si overflow
        let guard = 100;
        while (hasOverflow() && guard-- > 0) {
            if (!moveToMore()) break;
        }

        updateMoreVisibility();

        // Cacher "Autre" si vide et pas visible initialement
        if (wasHidden && moreMenu.children.length === 0) {
            moreLi.hidden = true;
        }

        // Rendre visible après le premier calcul
        if (isFirstRender) {
            navList.style.visibility = "visible";
            isFirstRender = false;
        }
    }

    // Optimiser les recalculs avec RAF
    const scheduleRebalance = () => requestAnimationFrame(rebalanceNav);

    // Events de redimensionnement
    window.addEventListener("resize", scheduleRebalance, {passive: true});

    if ("ResizeObserver" in window) {
        new ResizeObserver(scheduleRebalance).observe(navList);
    }

    // Initialisation
    document.addEventListener("DOMContentLoaded", scheduleRebalance);
    window.addEventListener("load", scheduleRebalance);

    if (document.fonts?.ready) {
        document.fonts.ready.then(scheduleRebalance).catch(() => {});
    }

    // Premier calcul immédiat
    rebalanceNav();
})();