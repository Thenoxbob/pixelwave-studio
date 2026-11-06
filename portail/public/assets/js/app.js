// Portail PixelWave : améliorations progressives (le site reste utilisable sans JavaScript)
'use strict';

const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

// Menu mobile
const menuBouton = document.querySelector('.menu-bouton');
menuBouton?.addEventListener('click', () => {
  const ouvert = menuBouton.getAttribute('aria-expanded') === 'true';
  menuBouton.setAttribute('aria-expanded', String(!ouvert));
  document.querySelector('.nav')?.classList.toggle('nav--ouverte', !ouvert);
});

// Boutique : les filtres s'appliquent dès qu'on les change
const filtres = document.querySelector('form[data-auto-submit]');
if (filtres) {
  const envoyer = () => {
    filtres.querySelector('[data-type-courant]')?.removeAttribute('disabled');
    filtres.requestSubmit();
  };
  filtres.querySelectorAll('select').forEach((s) => s.addEventListener('change', envoyer));
  let minuterie;
  filtres.querySelector('input[type="search"]')?.addEventListener('input', () => {
    clearTimeout(minuterie);
    minuterie = setTimeout(envoyer, 400);
  });
}

// Copie dans le presse-papiers avec retour visuel
async function copier(texte, bouton) {
  try {
    await navigator.clipboard.writeText(texte);
    const ancien = bouton.textContent;
    bouton.textContent = 'Copiée !';
    setTimeout(() => { bouton.textContent = ancien; }, 1800);
  } catch {
    window.prompt('Copie la clé :', texte);
  }
}

function boutonCopier(bloc) {
  const b = document.createElement('button');
  b.type = 'button';
  b.className = 'btn btn--contour btn--petit';
  b.dataset.action = 'copier';
  b.textContent = 'Copier';
  return b;
}

// Bibliothèque : révéler une clé via l'API REST, puis la copier
document.addEventListener('click', async (evt) => {
  const bouton = evt.target.closest('[data-action]');
  if (!bouton) return;
  const bloc = bouton.closest('.bloc-cle');

  if (bouton.dataset.action === 'copier' && bloc) {
    copier(bloc.querySelector('.bloc-cle__code').textContent.trim(), bouton);
  }

  if (bouton.dataset.action === 'reveler' && bloc) {
    evt.preventDefault();
    bouton.disabled = true;
    try {
      const rep = await fetch(`/api/v1/moi/cles/${bloc.dataset.cle}/revelation`, {
        method: 'POST',
        headers: { 'X-CSRF-Token': csrf, Accept: 'application/json' },
        credentials: 'same-origin',
      });
      const donnees = await rep.json();
      if (!rep.ok) throw new Error(donnees.erreur?.message ?? 'Erreur');
      bloc.querySelector('.bloc-cle__code').textContent = donnees.cle;
      bloc.querySelector('.bloc-cle__form').replaceWith(boutonCopier(bloc));
    } catch (e) {
      bouton.disabled = false;
      bouton.closest('form').submit();   // repli : version sans JavaScript
    }
  }
});

// Jeton API affiché une seule fois : clic pour copier
document.querySelectorAll('[data-copiable]').forEach((el) => {
  el.title = 'Cliquer pour copier';
  el.addEventListener('click', () => navigator.clipboard?.writeText(el.textContent.trim()));
});
