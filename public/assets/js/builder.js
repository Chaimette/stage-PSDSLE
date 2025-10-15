let newSec = 0,
    newPresta = 0,
    newTarif = 0;

// Helpers
// $ / $$ pour querySelector / querySelectorAll
const $ = (sel, root = document) => root.querySelector(sel);
const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

function slugify(str) {
    return (str || "")
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/(^-|-$)/g, "")
    .slice(0, 100);
}

function openParentsDetails(el) {
    for (let p = el?.parentElement; p; p = p.parentElement) {
        if (p.tagName?.toLowerCase() === "details") p.open = true;
    }
}

function nextOrder(inputs) {
    let max = -1;
    inputs.forEach((inp) => {
        const block = inp.closest(".admin-section,.admin-prestation,.admin-tarif");
        const del = block?.querySelector('input[type="checkbox"][name*="[delete]"]')?.checked;
        if (del) return;

        const v = inp.value.trim();
        if (v !== "" && !isNaN(v)) max = Math.max(max, Number(v));
    });
    return max + 1;
}

// Désactiver tout un bloc si delete coché (évite validations & focus)
function setDisabledWithin(container, disabled) {
    $$("input,select,textarea,button", container).forEach((el) => {
        if (el.type === "checkbox" && /\[delete\]/.test(el.name)) return;

        el.disabled = !!disabled;
    });
    container.style.opacity = disabled ? 0.5 : "";
    container.style.filter = disabled ? "grayscale(.4)" : "";
}

// toggle delete disables block
document.addEventListener("change", (e) => {
    const chk = e.target;
    if (!(chk instanceof HTMLInputElement)) return;

    if (chk.type !== "checkbox" || !/\[delete\]/.test(chk.name)) return;

    const block = chk.closest(
        ".admin-tarif, details.admin-prestation, details.admin-section, .card.prestation, .card.tarif"
    );
    if (block) setDisabledWithin(block, chk.checked);
});

// Auto-slug section
document.addEventListener("input", (e) => {
    const el = e.target;
    if (!(el instanceof HTMLInputElement || el instanceof HTMLTextAreaElement)) return;

    const m = el.name && el.name.match(/^sections\[(.+?)\]\[nom\]$/);
    if (!m) return;

    const secId = m[1];
    const slugInput = document.querySelector(`input[name="sections[${secId}][slug]"]`);
    if (slugInput) {
        slugInput.value = slugify(el.value);
    }
});

// remplissage auto ordre si vide au blur (max+1 dans le scope)
// on sort si âs un champ texte/num, ou si ce n'est pas un champ qui gère les ordres d'affichage
document.addEventListener(
    "blur",
    (e) => {
        const inp = e.target;
        if (!(inp instanceof HTMLInputElement) || !inp.classList.contains("order-input")) return;

        if (inp.value.trim() !== "") return;

        let scope = [];
        const section = inp.closest(".admin-section");
        const presta = inp.closest(".admin-prestation");

        if (inp.name.startsWith("sections[")) {
            scope = $$('.admin-section > summary .order-input[name^="sections["]', section?.parentElement || document);
        } else if (inp.name.startsWith("prestations[")) {
            scope = $$('.admin-prestation > summary .order-input[name^="prestations["]', section || document);
        } else if (inp.name.startsWith("tarifs[")) {
            scope = $$('.admin-tarif .order-input[name^="tarifs["]', presta || document);
        }
        inp.value = nextOrder(scope);
    },
    true
);

function addSection() {
    const id = "new" + ++newSec;
    const wrap = $("#sectionsWrap");
    const tpl = `
  <details class="admin-section" open data-id="${id}">
    <summary>
      <span class="section-title">Section</span>
      <input class="inline-input" name="sections[${id}][nom]" placeholder="Nom" required>
      <input class="inline-input slug" name="sections[${id}][slug]" placeholder="slug" required>
      <label class="stack mini"><span>Ordre</span><input class="inline-input sm order-input" type="number" name="sections[${id}][ordre_affichage]" min="1" ></label>
      <label class="check"><input type="checkbox" name="sections[${id}][actif]" checked> Actif</label>
      <label class="danger"><input type="checkbox" name="sections[${id}][delete]"> Supprimer</label>
      <button type="button" class="btn btn-cancel" onclick="cancelBlock(this)"> Annuler</button>
    </summary>
    <div class="section-body">
      <label class="stack"><span>Description</span><textarea name="sections[${id}][description]" rows="2"></textarea></label>
      <label class="stack"><span>Méta description</span><input name="sections[${id}][meta_description]"></label>
      <div class="section-actions">
        <button type="button" class="btn btn-soft" onclick="addPrestation('${id}')">+ Ajouter une prestation</button>
        <button type="button" class="btn btn-cancel" onclick="cancelBlock(this)"> Annuler</button>
      </div>
      <div class="prestations" id="prestations-${id}"></div>
    </div>
  </details>`;
    wrap.insertAdjacentHTML("beforeend", tpl);
}

function addPrestation(sectionId) {
    const id = "newP" + ++newPresta;
    const wrap = document.getElementById("prestations-" + sectionId);
    const tpl = `
  <details class="admin-prestation" open data-id="${id}">
    <summary>
      <span class="presta-title">Prestation</span>
      <input type="hidden" name="prestations[${id}][section_id]" value="${sectionId}">
      <input class="inline-input" name="prestations[${id}][nom]" placeholder="Nom" required>
      <label class="stack mini"><span>Ordre</span><input class="inline-input sm order-input" type="number" name="prestations[${id}][ordre_affichage]" value="0" min="0" required></label>
      <label class="check"><input type="checkbox" name="prestations[${id}][actif]" checked> Actif</label>
      <label class="danger"><input type="checkbox" name="prestations[${id}][delete]"> Supprimer</label>
      <button type="button" class="btn btn-cancel" onclick="cancelBlock(this)"> Annuler</button>
    </summary>
    <div class="presta-body">
      <label class="stack"><span>Description</span><textarea name="prestations[${id}][description]" rows="2"></textarea></label>
      <div class="presta-actions">
        <button type="button" class="btn btn-soft" onclick="addTarif('${id}')">+ Ajouter un tarif</button>
      </div>
      <div class="tarifs" id="tarifs-${id}"></div>
    </div>
  </details>`;
    wrap.insertAdjacentHTML("beforeend", tpl);
}

function addTarif(prestationId) {
    const id = "newT" + ++newTarif;
    const wrap = document.getElementById("tarifs-" + prestationId);
    const tpl = `
  <div class="admin-tarif" data-id="${id}">
    <input type="hidden" name="tarifs[${id}][prestation_id]" value="${prestationId}">
    <input class="inline-input sm" name="tarifs[${id}][duree]" placeholder="Durée">
    <input class="inline-input sm" name="tarifs[${id}][nb_seances]" placeholder="Nb séances">
    <input class="inline-input sm" type="number" step="0.01" name="tarifs[${id}][prix]" value="0" placeholder="Prix">
    <label class="stack mini"><span>Ordre</span><input class="inline-input sm order-input" type="number" name="tarifs[${id}][ordre_affichage]" value="0" min="0" required></label>
    <label class="danger"><input type="checkbox" name="tarifs[${id}][delete]"> Supprimer</label>
    <button type="button" class="btn btn-cancel" onclick="cancelBlock(this)"> Annuler</button>
  </div>`;
    wrap.insertAdjacentHTML("beforeend", tpl);
}

// Annuler: supprime si "new*", sinon coche delete + grise + replie
function cancelBlock(btn) {
    const card = btn.closest(
        ".admin-tarif, details.admin-prestation, details.admin-section, .card.prestation, .card.tarif"
    );
    if (!card) return;

    const isNew = (card.dataset.id || "").startsWith("new");
    if (isNew) {
        card.remove();
        return;
    }
    const del = card.querySelector('input[type="checkbox"][name*="[delete]"]');
    if (del) {
        del.checked = true;
        setDisabledWithin(card, true);
        if (card.tagName?.toLowerCase() === "details") card.open = false;
    }
}

// Validation et submit
const form = document.getElementById("builderForm");

form.addEventListener("submit", (e) => {
    let sInputs = $$('.admin-section > summary .order-input[name^="sections["]');
    const autoS = nextOrder(sInputs);
    sInputs.forEach((inp) => {
        if (inp.value.trim() === "") inp.value = autoS;
    });

    // Prestations (par section)
    $$(".admin-section").forEach((sec) => {
        const pInputs = $$('.admin-prestation > summary .order-input[name^="prestations["]', sec);
        const autoP = nextOrder(pInputs);
        pInputs.forEach((inp) => {
            if (inp.value.trim() === "") inp.value = autoP;
        });
    });

    // Tarifs (par prestation)
    $$(".admin-prestation").forEach((pre) => {
        const tInputs = $$('.admin-tarif .order-input[name^="tarifs["]', pre);
        const autoT = nextOrder(tInputs);
        tInputs.forEach((inp) => {
            if (inp.value.trim() === "") inp.value = autoT;
        });
    });

    // Validation native HTML5 + ouverture du 1er accordéon invalide
    if (!form.checkValidity()) {
        e.preventDefault();
        const invalid = form.querySelector(":invalid");
        if (invalid) {
            openParentsDetails(invalid);
            requestAnimationFrame(() => {
                invalid.reportValidity();
                invalid.focus({preventScroll: true});
            });
        }
    }
});
