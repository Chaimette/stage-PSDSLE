// Navigation responsive avec dropdown "Autre"
(function () {
    const navList = document.getElementById("main-nav");
    if (!navList) return;

    const moreLi = navList.querySelector(".more");
    const moreMenu = document.getElementById("more-menu");
    const candidates = Array.from(navList.querySelectorAll(".nav-item.can-overflow"));

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
            navList.insertBefore(li, moreLi);
            if (hasOverflow()) {
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

// Accordeons page presta
document.addEventListener("DOMContentLoaded", function () {

    function toggleAccordion(header) {
        const item = header.closest(".accordion-item");
        const content = item.querySelector(".accordion-content");
        const isOpen = item.classList.contains("open");

        document.querySelectorAll(".accordion-item").forEach((otherItem) => {
            if (otherItem !== item) {
                otherItem.classList.remove("open");
                const otherContent = otherItem.querySelector(".accordion-content");
                if (otherContent) {
                    otherContent.style.maxHeight = null;
                }
            }
        });

        if (isOpen) {
            item.classList.remove("open");
            content.style.maxHeight = null;
            content.style.padding = '';
        } else {
            item.classList.add("open");
            content.style.padding= "1.5rem";
            content.style.maxHeight = content.scrollHeight + "px";
        }
    }

    document.querySelectorAll(".accordion-header").forEach((header) => {
        header.addEventListener("click", function () {
            toggleAccordion(this);
        });
    });

    document.querySelectorAll(".accordion-header").forEach((header) => {
        header.addEventListener("keydown", function (e) {
            if (e.key === "Enter" || e.key === " ") {
                e.preventDefault();
                toggleAccordion(this);
            }
        });

        // Rendre focusable
        header.setAttribute("tabindex", "0");
        header.setAttribute("role", "button");
        header.setAttribute("aria-expanded", "false");
    });
});
