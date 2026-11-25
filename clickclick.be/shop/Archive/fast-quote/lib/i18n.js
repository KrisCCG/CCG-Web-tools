// Tiny i18n — EN / FR / NL.
// Strings keyed by dotted id. Use t('key', {vars}) programmatically,
// or mark elements with data-i18n="key" to have applyTranslations() set their text.

const LANGS = ['en', 'fr', 'nl'];

const STRINGS = {
  en: {
    'nav.services':      'Services',
    'nav.shop':          'Shop',

    'hero.title':        'Print quote in',
    'hero.seconds':      'seconds',
    'hero.tagline':      'Brussels print shop. Real prices — pay online — pick up or we deliver by bike in Brussels.',

    'step1.title':       'What do you want to',
    'step1.titleEm':     'print?',
    'tile.flyer':        'Flyer',
    'tile.flyer.desc':   'Party, event, pop-up.',
    'tile.card':         'Card',
    'tile.card.desc':    'Business, wedding, invite.',
    'tile.poster':       'Poster',
    'tile.poster.desc':  'Festival, gallery, club.',
    'tile.poster.tag':   'A4 · A3 · larger on request',

    'step2.title':       'How many',
    'step2.titleEm':     'copies?',

    'step3.title':       'Size &',
    'step3.titleEm':     'paper',
    'label.size':        'Size',
    'label.paper':       'Paper',
    'label.sides':       'Print sides',
    'sides.recto':       'Recto only',
    'sides.rectoVerso':  'Recto & Verso',
    'express.title':     'Express · ready in 48 hours',
    'express.sub':       '+35% — for when you needed it yesterday',

    'step4.title':       'Your',
    'step4.titleEm':     'details',
    'ph.email':          'Email',
    'ph.name':           'Full name',
    'ph.phone':          'Phone (optional)',
    'form.isCompany':    'This order is for a company',
    'ph.company':        'Company name',
    'ph.vat':            'VAT number — e.g. BE0732.984.161',
    'label.address':     'Address',
    'ph.street':         'Street + number',
    'ph.zip':            'Zip',
    'ph.city':           'City',
    'label.delivery':    'Delivery',
    'deliver.pickup':    'Pick-up — Free',
    'deliver.pickupSub': 'Aalststraat 7-11, 1000 Brussels · Mon–Fri 9–12:30 & 14–18h',
    'deliver.ship':      'Delivery — €15',
    'deliver.shipSub':   'DPD Home or by bike in Brussels',
    'form.diffShip':     'Ship to a different address',
    'ph.shipStreet':     'Shipping street + number',
    'ph.shipZip':        'Shipping zip',
    'ph.shipCity':       'Shipping city',

    'step5.title':       'Rules',
    'rules.receipt':     'You will receive a receipt by email after payment.',
    'rules.receiptCo':   'You will receive a receipt and invoice by email after payment.',
    'rules.upload':      '📁 After payment, you\u2019ll get a secure link to upload your artwork — PDF, JPG, PNG or TIFF.',
    'rules.bleed':       '⊞ Please add 3 mm bleed to your files so your print gets a perfect cut.',

    'quote.title':       'Your quote',
    'quote.product':     'Product',
    'quote.format':      'Format',
    'quote.paper':       'Paper',
    'quote.sides':       'Sides',
    'quote.quantity':    'Quantity',
    'quote.delivery':    'Delivery',
    'quote.total':       'Total incl. VAT',
    'quote.perPiece':    'per piece',
    'quote.deliveryPickup': 'Pick-up · Free',
    'quote.deliveryShip':   'DPD / bike · €15',
    'ready.pickup':      'Ready for pick-up by',
    'ready.ship':        'Ready for delivery on',
    'ready.days':        '{n} working days',
    'ready.express':     '48 hours · express',

    'btn.pay':           'Pay €{amount}',
    'btn.order':         'Order online',
    'btn.fillRequired':  'Fill in the required fields',
    'secure':            'Bancontact · Apple Pay · Secure checkout',

    'product.flyer':     'Flyer',
    'product.card':      'Card',
    'product.poster':    'Poster',

    'bespoke.title':     'Need something',
    'bespoke.titleEm':   'bespoke?',
    'bespoke.desc':      'Die-cut shapes, unusual sizes, coloured paper — let\u2019s talk.',
    'bespoke.btn':       '✉ Email us',

    'footer.addr':       'Click Click Graphics · Aalststraat 7-11, 1000 Brussels · BE 0732.984.161',
    'footer.privacy':    'Privacy-friendly · No cookies · No tracking',
  },

  fr: {
    'nav.services':      'Services',
    'nav.shop':          'Boutique',

    'hero.title':        'Devis d\u2019impression en',
    'hero.seconds':      'secondes',
    'hero.tagline':      'Imprimerie bruxelloise. Prix réels — paiement en ligne — retrait ou livraison à vélo à Bruxelles.',

    'step1.title':       'Que voulez-vous',
    'step1.titleEm':     'imprimer\u00A0?',
    'tile.flyer':        'Flyer',
    'tile.flyer.desc':   'Fête, événement, pop-up.',
    'tile.card':         'Carte',
    'tile.card.desc':    'Business, mariage, invitation.',
    'tile.poster':       'Affiche',
    'tile.poster.desc':  'Festival, galerie, club.',
    'tile.poster.tag':   'A4 · A3 · plus grand sur demande',

    'step2.title':       'Combien',
    'step2.titleEm':     'd\u2019exemplaires\u00A0?',

    'step3.title':       'Format &',
    'step3.titleEm':     'papier',
    'label.size':        'Format',
    'label.paper':       'Papier',
    'label.sides':       'Impression',
    'sides.recto':       'Recto seul',
    'sides.rectoVerso':  'Recto & Verso',
    'express.title':     'Express · prêt en 48 heures',
    'express.sub':       '+35% — pour quand c\u2019était pour hier',

    'step4.title':       'Vos',
    'step4.titleEm':     'coordonnées',
    'ph.email':          'E-mail',
    'ph.name':           'Nom complet',
    'ph.phone':          'Téléphone (facultatif)',
    'form.isCompany':    'Cette commande est pour une société',
    'ph.company':        'Nom de société',
    'ph.vat':            'Numéro de TVA — ex. BE0732.984.161',
    'label.address':     'Adresse',
    'ph.street':         'Rue + numéro',
    'ph.zip':            'Code postal',
    'ph.city':           'Ville',
    'label.delivery':    'Livraison',
    'deliver.pickup':    'Retrait — Gratuit',
    'deliver.pickupSub': 'Aalststraat 7-11, 1000 Bruxelles · Lun–Ven 9h–12h30 & 14h–18h',
    'deliver.ship':      'Livraison — 15\u00A0€',
    'deliver.shipSub':   'DPD Home ou à vélo dans Bruxelles',
    'form.diffShip':     'Livrer à une autre adresse',
    'ph.shipStreet':     'Rue + numéro de livraison',
    'ph.shipZip':        'Code postal de livraison',
    'ph.shipCity':       'Ville de livraison',

    'step5.title':       'Règles',
    'rules.receipt':     'Vous recevrez un reçu par e-mail après le paiement.',
    'rules.receiptCo':   'Vous recevrez un reçu et une facture par e-mail après le paiement.',
    'rules.upload':      '📁 Après le paiement, vous recevrez un lien sécurisé pour envoyer vos fichiers — PDF, JPG, PNG ou TIFF.',
    'rules.bleed':       '⊞ Ajoutez 3 mm de fond perdu à vos fichiers pour une découpe parfaite.',

    'quote.title':       'Votre devis',
    'quote.product':     'Produit',
    'quote.format':      'Format',
    'quote.paper':       'Papier',
    'quote.sides':       'Impression',
    'quote.quantity':    'Quantité',
    'quote.delivery':    'Livraison',
    'quote.total':       'Total TVAC',
    'quote.perPiece':    'par pièce',
    'quote.deliveryPickup': 'Retrait · Gratuit',
    'quote.deliveryShip':   'DPD / vélo · 15\u00A0€',
    'ready.pickup':      'Prêt au retrait le',
    'ready.ship':        'Livraison le',
    'ready.days':        '{n} jours ouvrables',
    'ready.express':     '48 heures · express',

    'btn.pay':           'Payer {amount}\u00A0€',
    'btn.order':         'Commander',
    'btn.fillRequired':  'Remplissez les champs requis',
    'secure':            'Bancontact · Apple Pay · Paiement sécurisé',

    'product.flyer':     'Flyer',
    'product.card':      'Carte',
    'product.poster':    'Affiche',

    'bespoke.title':     'Besoin de quelque chose de',
    'bespoke.titleEm':   'spécial\u00A0?',
    'bespoke.desc':      'Formes découpées, formats inhabituels, papier coloré — parlons-en.',
    'bespoke.btn':       '✉ Écrivez-nous',

    'footer.addr':       'Click Click Graphics · Aalststraat 7-11, 1000 Bruxelles · BE 0732.984.161',
    'footer.privacy':    'Respect de la vie privée · Pas de cookies · Pas de tracking',
  },

  nl: {
    'nav.services':      'Diensten',
    'nav.shop':          'Shop',

    'hero.title':        'Printofferte in',
    'hero.seconds':      'seconden',
    'hero.tagline':      'Brusselse drukkerij. Echte prijzen — online betalen — afhalen of bezorging per fiets in Brussel.',

    'step1.title':       'Wat wil je',
    'step1.titleEm':     'drukken?',
    'tile.flyer':        'Flyer',
    'tile.flyer.desc':   'Feest, event, pop-up.',
    'tile.card':         'Kaart',
    'tile.card.desc':    'Zakelijk, huwelijk, uitnodiging.',
    'tile.poster':       'Poster',
    'tile.poster.desc':  'Festival, galerij, club.',
    'tile.poster.tag':   'A4 · A3 · groter op aanvraag',

    'step2.title':       'Hoeveel',
    'step2.titleEm':     'exemplaren?',

    'step3.title':       'Formaat &',
    'step3.titleEm':     'papier',
    'label.size':        'Formaat',
    'label.paper':       'Papier',
    'label.sides':       'Druk',
    'sides.recto':       'Enkelzijdig',
    'sides.rectoVerso':  'Dubbelzijdig',
    'express.title':     'Express · klaar in 48 uur',
    'express.sub':       '+35% — voor als het gisteren al klaar moest zijn',

    'step4.title':       'Jouw',
    'step4.titleEm':     'gegevens',
    'ph.email':          'E-mail',
    'ph.name':           'Volledige naam',
    'ph.phone':          'Telefoon (optioneel)',
    'form.isCompany':    'Deze bestelling is voor een bedrijf',
    'ph.company':        'Bedrijfsnaam',
    'ph.vat':            'BTW-nummer — bv. BE0732.984.161',
    'label.address':     'Adres',
    'ph.street':         'Straat + nummer',
    'ph.zip':            'Postcode',
    'ph.city':           'Stad',
    'label.delivery':    'Levering',
    'deliver.pickup':    'Afhalen — Gratis',
    'deliver.pickupSub': 'Aalststraat 7-11, 1000 Brussel · Ma–Vr 9–12u30 & 14–18u',
    'deliver.ship':      'Levering — € 15',
    'deliver.shipSub':   'DPD Home of per fiets in Brussel',
    'form.diffShip':     'Leveren op een ander adres',
    'ph.shipStreet':     'Straat + nummer levering',
    'ph.shipZip':        'Postcode levering',
    'ph.shipCity':       'Stad levering',

    'step5.title':       'Regels',
    'rules.receipt':     'Je ontvangt een ontvangstbewijs per e-mail na de betaling.',
    'rules.receiptCo':   'Je ontvangt een ontvangstbewijs en factuur per e-mail na de betaling.',
    'rules.upload':      '📁 Na de betaling krijg je een beveiligde link om je bestanden te uploaden — PDF, JPG, PNG of TIFF.',
    'rules.bleed':       '⊞ Voeg 3 mm afloop toe aan je bestanden voor een perfecte snede.',

    'quote.title':       'Je offerte',
    'quote.product':     'Product',
    'quote.format':      'Formaat',
    'quote.paper':       'Papier',
    'quote.sides':       'Druk',
    'quote.quantity':    'Aantal',
    'quote.delivery':    'Levering',
    'quote.total':       'Totaal incl. btw',
    'quote.perPiece':    'per stuk',
    'quote.deliveryPickup': 'Afhalen · Gratis',
    'quote.deliveryShip':   'DPD / fiets · € 15',
    'ready.pickup':      'Klaar om af te halen op',
    'ready.ship':        'Levering op',
    'ready.days':        '{n} werkdagen',
    'ready.express':     '48 uur · express',

    'btn.pay':           'Betaal € {amount}',
    'btn.order':         'Bestel online',
    'btn.fillRequired':  'Vul de verplichte velden in',
    'secure':            'Bancontact · Apple Pay · Veilig afrekenen',

    'product.flyer':     'Flyer',
    'product.card':      'Kaart',
    'product.poster':    'Poster',

    'bespoke.title':     'Iets op',
    'bespoke.titleEm':   'maat nodig?',
    'bespoke.desc':      'Uitgesneden vormen, ongebruikelijke formaten, gekleurd papier — laten we praten.',
    'bespoke.btn':       '✉ Mail ons',

    'footer.addr':       'Click Click Graphics · Aalststraat 7-11, 1000 Brussel · BE 0732.984.161',
    'footer.privacy':    'Privacyvriendelijk · Geen cookies · Geen tracking',
  },
};

// ── runtime ──────────────────────────────────────────────────────────────
let currentLang = 'en';
const listeners = new Set();

function detect() {
  try {
    const stored = localStorage.getItem('lang');
    if (stored && LANGS.includes(stored)) return stored;
  } catch (_) {}
  const nav = (navigator.language || 'en').slice(0, 2).toLowerCase();
  return LANGS.includes(nav) ? nav : 'en';
}

export function t(key, vars) {
  let s = STRINGS[currentLang]?.[key] ?? STRINGS.en[key] ?? key;
  if (vars) for (const [k, v] of Object.entries(vars)) s = s.replaceAll(`{${k}}`, v);
  return s;
}

export function getLang() { return currentLang; }

export function setLang(lang) {
  if (!LANGS.includes(lang)) return;
  currentLang = lang;
  try { localStorage.setItem('lang', lang); } catch (_) {}
  document.documentElement.lang = lang;
  applyToDom();
  listeners.forEach(fn => fn(lang));
}

export function onLangChange(fn) { listeners.add(fn); return () => listeners.delete(fn); }

export function applyToDom() {
  document.querySelectorAll('[data-i18n]').forEach(el => {
    el.textContent = t(el.dataset.i18n);
  });
  document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
    el.setAttribute('placeholder', t(el.dataset.i18nPlaceholder));
  });
  document.querySelectorAll('[data-i18n-title]').forEach(el => {
    el.setAttribute('title', t(el.dataset.i18nTitle));
  });
}

export function initLang() {
  setLang(detect());
}
