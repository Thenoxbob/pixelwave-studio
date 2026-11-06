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

// ---------------------------------------------------------------------------
// Validation des formulaires dans le navigateur (formulaires marqués data-valider).
// Elle évite un aller-retour au serveur, mais le serveur revérifie toujours tout.
// ---------------------------------------------------------------------------

// mêmes règles que Validator::motDePasseRobuste() côté PHP
function motDePasseRobuste(mdp) {
  return mdp.length >= 10 && mdp.length <= 128
    && /\p{L}/u.test(mdp) && /\d/.test(mdp) && /[^\p{L}\d]/u.test(mdp);
}

// retourne le message d'erreur d'un champ, ou '' s'il est correct
function erreurDuChamp(champ, formulaire) {
  const valeur = champ.type === 'checkbox' ? champ.checked : champ.value.trim();
  const longueur = typeof valeur === 'string' ? [...valeur].length : 0;

  if (champ.required && (valeur === '' || valeur === false)) {
    return champ.type === 'checkbox' ? 'Cette case doit être cochée.' : 'Ce champ est obligatoire.';
  }
  if (valeur === '' || typeof valeur !== 'string') return '';
  if (champ.minLength > 0 && longueur < champ.minLength) return `${champ.minLength} caractères minimum.`;
  if (champ.maxLength > 0 && longueur > champ.maxLength) return `${champ.maxLength} caractères maximum.`;
  if (champ.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(valeur)) return 'Adresse e-mail invalide.';
  if (champ.pattern && !new RegExp(`^(?:${champ.pattern})$`, 'u').test(valeur)) {
    return champ.dataset.messageMotif ?? 'Format incorrect.';
  }
  if (champ.dataset.regle === 'mot-de-passe' && !motDePasseRobuste(champ.value)) {
    return 'Mot de passe trop faible : il manque une des règles indiquées ci-dessus.';
  }
  if (champ.dataset.confirme) {
    const reference = formulaire.elements[champ.dataset.confirme];
    if (reference && reference.value !== champ.value) return 'Les deux mots de passe ne correspondent pas.';
  }
  return '';
}

// affiche ou efface le message sous le champ (le même emplacement que les erreurs du serveur)
function afficherErreur(champ, message) {
  // pour une case à cocher, le message va sous la ligne (comme les erreurs renvoyées par le serveur)
  const caseACocher = champ.closest('.case');
  const conteneur = champ.closest('.champ') ?? caseACocher?.parentElement ?? champ.parentElement;
  let zone = conteneur.querySelector(`#err-${CSS.escape(champ.name)}`);
  if (!zone && message) {
    zone = document.createElement('span');
    zone.className = 'champ__erreur';
    zone.id = `err-${champ.name}`;
    caseACocher ? caseACocher.insertAdjacentElement('afterend', zone) : conteneur.appendChild(zone);
  }
  if (zone) zone.textContent = message;
  if (message) {
    champ.setAttribute('aria-invalid', 'true');
    champ.setAttribute('aria-describedby', zone.id);
  } else {
    champ.removeAttribute('aria-invalid');
  }
}

function validerFormulaire(formulaire) {
  let premierEnErreur = null;
  formulaire.querySelectorAll('input:not([type="hidden"]), textarea, select').forEach((champ) => {
    const message = erreurDuChamp(champ, formulaire);
    afficherErreur(champ, message);
    if (message && !premierEnErreur) premierEnErreur = champ;
  });
  premierEnErreur?.focus();
  return premierEnErreur === null;
}

document.querySelectorAll('form[data-valider]').forEach((formulaire) => {
  formulaire.addEventListener('submit', (evt) => {
    if (!validerFormulaire(formulaire)) evt.preventDefault();
  });
  // on revérifie un champ quand on le quitte, et en direct s'il était déjà en erreur
  formulaire.addEventListener('focusout', (evt) => {
    if (evt.target.name) afficherErreur(evt.target, erreurDuChamp(evt.target, formulaire));
  });
  formulaire.addEventListener('input', (evt) => {
    if (evt.target.getAttribute('aria-invalid') === 'true') {
      afficherErreur(evt.target, erreurDuChamp(evt.target, formulaire));
    }
  });
});

// Compteur de caractères sous les zones de texte marquées data-compteur
document.querySelectorAll('textarea[data-compteur]').forEach((zone) => {
  const compteur = document.createElement('span');
  compteur.className = 'champ__aide';
  compteur.setAttribute('aria-live', 'polite');
  zone.insertAdjacentElement('afterend', compteur);
  const maj = () => {
    const n = [...zone.value.trim()].length;
    const manque = zone.minLength - n;
    compteur.textContent = manque > 0
      ? `${n} / ${zone.maxLength} caractères (encore ${manque} au minimum)`
      : `${n} / ${zone.maxLength} caractères`;
  };
  zone.addEventListener('input', maj);
  maj();
});

// ---------------------------------------------------------------------------
// Bibliothèque : révéler une clé avec l'API REST, puis la copier
// ---------------------------------------------------------------------------

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

function boutonCopier() {
  const b = document.createElement('button');
  b.type = 'button';
  b.className = 'btn btn--contour btn--petit';
  b.dataset.action = 'copier';
  b.textContent = 'Copier';
  return b;
}

// message d'erreur affiché dans le bloc de la clé
function messageBloc(bloc, texte) {
  let zone = bloc.querySelector('.bloc-cle__erreur');
  if (!zone) {
    zone = document.createElement('p');
    zone.className = 'bloc-cle__erreur';
    zone.setAttribute('role', 'alert');
    bloc.appendChild(zone);
  }
  zone.textContent = texte;
}

// traduit une réponse d'erreur de l'API en message compréhensible
function messageErreurApi(statut, donnees) {
  if (statut === 401) return 'Ta session a expiré. Recharge la page et reconnecte-toi.';
  if (statut === 403) return 'Session expirée ou action refusée. Recharge la page puis réessaie.';
  if (statut === 404) return "Cette clé est introuvable dans ta bibliothèque.";
  return donnees?.erreur?.message ?? `Erreur inattendue du serveur (code ${statut}). Réessaie dans un instant.`;
}

async function revelerCle(bouton, bloc) {
  const texteInitial = bouton.textContent;
  bouton.disabled = true;
  bouton.textContent = 'Affichage…';
  bloc.setAttribute('aria-busy', 'true');
  try {
    const rep = await fetch(`/api/v1/moi/cles/${bloc.dataset.cle}/revelation`, {
      method: 'POST',
      headers: { 'X-CSRF-Token': csrf, Accept: 'application/json' },
      credentials: 'same-origin',
    });
    const donnees = await rep.json().catch(() => null);   // une page d'erreur HTML n'est pas du JSON
    if (!rep.ok || !donnees?.cle) {
      throw new Error(messageErreurApi(rep.status, donnees));
    }
    bloc.querySelector('.bloc-cle__code').textContent = donnees.cle;
    bloc.querySelector('.bloc-cle__form').replaceWith(boutonCopier());
    bloc.querySelector('.bloc-cle__erreur')?.remove();
  } catch (e) {
    // TypeError = le serveur n'a pas pu être joint (réseau coupé)
    messageBloc(bloc, e instanceof TypeError ? 'Connexion impossible. Vérifie ta connexion internet puis réessaie.' : e.message);
    bouton.disabled = false;
    bouton.textContent = texteInitial;
  } finally {
    bloc.removeAttribute('aria-busy');
  }
}

document.addEventListener('click', (evt) => {
  const bouton = evt.target.closest('[data-action]');
  const bloc = bouton?.closest('.bloc-cle');
  if (!bloc) return;
  if (bouton.dataset.action === 'copier') {
    copier(bloc.querySelector('.bloc-cle__code').textContent.trim(), bouton);
  }
  if (bouton.dataset.action === 'reveler') {
    evt.preventDefault();
    revelerCle(bouton, bloc);
  }
});

// Jeton API affiché une seule fois : clic pour copier
document.querySelectorAll('[data-copiable]').forEach((el) => {
  el.title = 'Cliquer pour copier';
  el.addEventListener('click', () => navigator.clipboard?.writeText(el.textContent.trim()));
});
