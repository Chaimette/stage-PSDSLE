// Navigation responsive avec dropdown "Autre" et burger menu
(function () {
    const navList = document.getElementById("main-nav");
    if (!navList) return;

    const moreLi = navList.querySelector(".more");
    const moreMenu = document.getElementById("more-menu");
    const soinsDd = navList.querySelector(".nav-item.dropdown:not(.more)");
    
    // Sélectionner tous les éléments qui peuvent déborder
    const candidates = Array.from(navList.querySelectorAll(".nav-item.can-overflow, .nav-item.dropdown:not(.more)"));

    let isFirstRender = true;
    let isMobileView = false;

    // On indexe les éléments pour le tri
    candidates.forEach((li, idx) => (li.dataset.index = String(idx)));

    // Créer le burger menu
    const burgerBtn = document.createElement("button");
    burgerBtn.className = "burger-menu";
    burgerBtn.setAttribute("aria-label", "Menu");
    burgerBtn.setAttribute("aria-expanded", "false");
    burgerBtn.innerHTML = `
        <span class="burger-line"></span>
        <span class="burger-line"></span>
        <span class="burger-line"></span>
    `;

    const mobileMenu = document.createElement("div");
    mobileMenu.className = "mobile-sidebar";
    mobileMenu.innerHTML = '<ul class="mobile-menu-list"></ul>';
    
    const nav = navList.closest(".nav");
    nav.appendChild(burgerBtn);
    document.body.appendChild(mobileMenu);

    // Gestion des dropdowns (desktop)
    function closeAllDropdowns(except = null) {
        navList.querySelectorAll(".dropdown.open").forEach((li) => {
            if (li !== except) {
                li.classList.remove("open");
                li.querySelector(".dropdown-toggle")?.setAttribute("aria-expanded", "false");
            }
        });
    }

    // Events pour les dropdowns (desktop)
    navList.addEventListener("click", (e) => {
        if (isMobileView) return;
        
        const btn = e.target.closest(".dropdown-toggle");
        if (!btn) return;

        const li = btn.closest(".dropdown");
        const isOpen = li.classList.toggle("open");
        btn.setAttribute("aria-expanded", isOpen ? "true" : "false");

        if (isOpen) closeAllDropdowns(li);
        e.stopPropagation();
    });

    document.addEventListener("click", () => {
        if (!isMobileView) closeAllDropdowns();
    });
    
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") {
            closeAllDropdowns();
            closeMobileMenu();
        }
    });

    // Burger menu
    function toggleMobileMenu() {
        const isOpen = mobileMenu.classList.toggle("open");
        burgerBtn.classList.toggle("open");
        burgerBtn.setAttribute("aria-expanded", isOpen ? "true" : "false");
        document.body.style.overflow = isOpen ? "hidden" : "";
    }

    function closeMobileMenu() {
        mobileMenu.classList.remove("open");
        burgerBtn.classList.remove("open");
        burgerBtn.setAttribute("aria-expanded", "false");
        document.body.style.overflow = "";
    }

    burgerBtn.addEventListener("click", toggleMobileMenu);
    mobileMenu.addEventListener("click", (e) => {
        if (e.target === mobileMenu) closeMobileMenu();
    });

    // Construire le menu mobile
    function buildMobileMenu() {
        const mobileList = mobileMenu.querySelector(".mobile-menu-list");
        mobileList.innerHTML = "";

        // Récupérer tous les liens
        const allLinks = [];
        
        candidates.forEach((li) => {
            if (li.classList.contains("dropdown") && !li.classList.contains("more")) {
                // Décomposer le dropdown "Soins"
                const subMenu = li.querySelector(".dropdown-menu");
                if (subMenu) {
                    subMenu.querySelectorAll("a").forEach((a) => {
                        allLinks.push({ href: a.href, text: a.textContent.trim() });
                    });
                }
            } else if (li.parentElement === navList || li.parentElement === moreMenu) {
                // Liens simples
                const link = li.querySelector("a");
                if (link) {
                    allLinks.push({ href: link.href, text: link.textContent.trim() });
                }
            }
        });

        // Ajouter les liens du menu "Autre" s'ils existent
        Array.from(moreMenu.children).forEach((li) => {
            if (li.classList.contains("dropdown") && !li.classList.contains("more")) {
                const subMenu = li.querySelector(".dropdown-menu");
                if (subMenu) {
                    subMenu.querySelectorAll("a").forEach((a) => {
                        allLinks.push({ href: a.href, text: a.textContent.trim() });
                    });
                }
            } else {
                const link = li.querySelector("a");
                if (link) {
                    allLinks.push({ href: link.href, text: link.textContent.trim() });
                }
            }
        });

        // Créer les éléments du menu mobile
        allLinks.forEach((linkData) => {
            const li = document.createElement("li");
            const a = document.createElement("a");
            a.href = linkData.href;
            a.textContent = linkData.text;
            a.addEventListener("click", closeMobileMenu);
            li.appendChild(a);
            mobileList.appendChild(li);
        });
    }

    // Gestion du dropdown "Soins"
    function expandSoinsInMore() {
        if (!soinsDd || soinsDd.parentElement !== moreMenu) return;

        const subMenu = soinsDd.querySelector(".dropdown-menu");
        if (!subMenu) return;

        const links = Array.from(subMenu.querySelectorAll("a"));
        const soinsIndex = Number(soinsDd.dataset.index);

        links.forEach((a, i) => {
            const li = document.createElement("li");
            li.className = "nav-item soins-sub";
            li.dataset.index = `${soinsIndex}.${i}`;
            const newLink = a.cloneNode(true);
            li.appendChild(newLink);
            moreMenu.insertBefore(li, soinsDd.nextSibling);
        });

        soinsDd.style.display = "none";
    }

    function collapseSoinsFromMore() {
        const soinsSubs = moreMenu.querySelectorAll(".nav-item.soins-sub");
        if (soinsSubs.length === 0) return;

        soinsSubs.forEach((li) => li.remove());
        
        if (soinsDd && soinsDd.parentElement === moreMenu) {
            soinsDd.style.display = "";
        }
    }

    // Fonctions de gestion du overflow (desktop)
    function hasOverflow() {
        return navList.scrollWidth > navList.clientWidth + 1;
    }

    function updateMoreVisibility() {
        moreLi.hidden = moreMenu.children.length === 0 || Array.from(moreMenu.children).every(li => li.style.display === "none");
    }

    function moveToMore() {
        const visible = candidates.filter((li) => li.parentElement === navList);
        if (visible.length === 0) return false;

        visible.sort((a, b) => Number(b.dataset.index) - Number(a.dataset.index));
        const li = visible[0];
        moreMenu.insertBefore(li, moreMenu.firstChild);

        // Si c'est le dropdown "Soins", le décomposer
        if (li === soinsDd) {
            expandSoinsInMore();
        }

        return true;
    }

    function restoreFromMore() {
        if (moreMenu.children.length === 0) return;

        // D'abord, recomposer "Soins" s'il est décomposé
        collapseSoinsFromMore();

        const pool = Array.from(moreMenu.children).filter(li => li.style.display !== "none");
        pool.sort((a, b) => Number(a.dataset.index) - Number(b.dataset.index));

        for (const li of pool) {
            const targetIndex = Number(li.dataset.index);
            let insertBefore = moreLi;
            
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
                if (li === soinsDd) {
                    expandSoinsInMore();
                }
                break;
            }
        }
    }

    function rebalanceNav() {
        if (isMobileView) return;

        const wasHidden = moreLi.hidden;
        if (wasHidden) moreLi.hidden = false;

        restoreFromMore();

        let guard = 100;
        while (hasOverflow() && guard-- > 0) {
            if (!moveToMore()) break;
        }

        updateMoreVisibility();

        if (wasHidden && (moreMenu.children.length === 0 || Array.from(moreMenu.children).every(li => li.style.display === "none"))) {
            moreLi.hidden = true;
        }

        if (isFirstRender) {
            navList.style.visibility = "visible";
            isFirstRender = false;
        }
    }

    // Gestion responsive
    function handleResize() {
        const wasMobile = isMobileView;
        isMobileView = window.innerWidth <= 900;

        if (isMobileView !== wasMobile) {
            if (isMobileView) {
                // Passer en mode mobile
                navList.style.display = "none";
                burgerBtn.style.display = "flex";
                buildMobileMenu();
                closeMobileMenu();
            } else {
                // Passer en mode desktop
                navList.style.display = "flex";
                burgerBtn.style.display = "none";
                closeMobileMenu();
                rebalanceNav();
            }
        } else if (!isMobileView) {
            rebalanceNav();
        }
    }

    const scheduleResize = () => requestAnimationFrame(handleResize);

    window.addEventListener("resize", scheduleResize, { passive: true });

    if ("ResizeObserver" in window) {
        new ResizeObserver(scheduleResize).observe(navList);
    }

    // Initialisation
    document.addEventListener("DOMContentLoaded", handleResize);
    window.addEventListener("load", handleResize);

    if (document.fonts?.ready) {
        document.fonts.ready.then(handleResize).catch(() => {});
    }

    handleResize();
})();