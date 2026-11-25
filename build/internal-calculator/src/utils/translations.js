// ── Click Click Graphics — Translation strings ──────────────────────────
// Keys: dot-separated. Each maps to { en, fr, nl }.
// Paper names, brand names, and size dimensions are NOT translated.

const T = {
  // ── Header ─────────────────────────────────────────────────────────────
  'header.print':       { en: 'Print', fr: 'Print', nl: 'Print' },
  'header.calculator':  { en: 'Calculator', fr: 'Calculator', nl: 'Calculator' },
  'header.subtitle':    { en: 'Click Click Graphics · Brussels', fr: 'Click Click Graphics · Bruxelles', nl: 'Click Click Graphics · Brussel' },

  // ── Product tabs ───────────────────────────────────────────────────────
  'tab.flyer':          { en: 'Flyer / Card', fr: 'Flyer / Carte', nl: 'Flyer / Kaart' },
  'tab.coloured':       { en: 'Coloured Papers', fr: 'Papiers couleur', nl: 'Gekleurd papier' },
  'tab.coloured.short': { en: 'Coloured', fr: 'Couleur', nl: 'Kleur' },
  'tab.poster':         { en: 'Mini Poster', fr: 'Mini Poster', nl: 'Mini Poster' },
  'tab.poster.short':   { en: 'Poster', fr: 'Poster', nl: 'Poster' },

  // ── Form labels ────────────────────────────────────────────────────────
  'label.paper':        { en: 'Paper', fr: 'Papier', nl: 'Papier' },
  'label.weight':       { en: 'Weight', fr: 'Grammage', nl: 'Gewicht' },
  'label.size':         { en: 'Size', fr: 'Format', nl: 'Formaat' },
  'label.quantity':     { en: 'Quantity', fr: 'Quantité', nl: 'Aantal' },
  'label.versions':     { en: 'Versions', fr: 'Versions', nl: 'Versies' },
  'label.printSides':   { en: 'Print sides', fr: 'Impression', nl: 'Bedrukking' },
  'label.widthMm':      { en: 'Width (mm)', fr: 'Largeur (mm)', nl: 'Breedte (mm)' },
  'label.heightMm':     { en: 'Height (mm)', fr: 'Hauteur (mm)', nl: 'Hoogte (mm)' },
  'label.colouredPaper': { en: 'Coloured Paper', fr: 'Papier couleur', nl: 'Gekleurd papier' },
  'label.recto':        { en: 'Recto', fr: 'Recto', nl: 'Recto' },
  'label.verso':        { en: 'Verso', fr: 'Verso', nl: 'Verso' },

  // ── Sides toggle ───────────────────────────────────────────────────────
  'sides.single':       { en: 'Recto only', fr: 'Recto seul', nl: 'Enkel recto' },
  'sides.double':       { en: 'Recto & Verso', fr: 'Recto & Verso', nl: 'Recto en verso' },

  // ── Ink options ────────────────────────────────────────────────────────
  'ink.white':          { en: 'White', fr: 'Blanc', nl: 'Wit' },
  'ink.black':          { en: 'Black', fr: 'Noir', nl: 'Zwart' },
  'ink.colour':         { en: 'Colour', fr: 'Couleur', nl: 'Kleur' },
  'print.cmyk':         { en: 'Full colour (CMYK)', fr: 'Quadrichromie (CMJN)', nl: 'Full colour (CMYK)' },

  // ── Versions ───────────────────────────────────────────────────────────
  'diffPaper':          { en: 'Different paper per version', fr: 'Papier différent par version', nl: 'Ander papier per versie' },
  'versionPaper':       { en: 'Version {n} paper', fr: 'Papier version {n}', nl: 'Papier versie {n}' },

  // ── Other options ──────────────────────────────────────────────────────
  'otherOptions':       { en: 'Other options on request —', fr: 'Autres options sur demande —', nl: 'Andere opties op aanvraag —' },
  'sendEmail':          { en: 'send us an e-mail', fr: 'envoyez-nous un e-mail', nl: 'stuur ons een e-mail' },
  'size.custom':        { en: 'Custom Size', fr: 'Format personnalisé', nl: 'Aangepast formaat' },

  // ── Express service ────────────────────────────────────────────────────
  'express.title':      { en: 'Express Service', fr: 'Service Express', nl: 'Express Service' },
  'express.desc':       { en: 'Ready for pick-up within 48 hours', fr: 'Prêt en 48 heures', nl: 'Klaar binnen 48 uur' },
  'express.short':      { en: 'Express 48h', fr: 'Express 48h', nl: 'Express 48u' },
  'express.quote':      { en: 'Express — 48h', fr: 'Express — 48h', nl: 'Express — 48u' },

  // ── Delivery ───────────────────────────────────────────────────────────
  'delivery.label':     { en: 'Delivery', fr: 'Livraison', nl: 'Levering' },
  'delivery.pickup.title':   { en: 'Pick-up at our shop', fr: 'Retrait à l\'imprimerie', nl: 'Afhalen in de drukkerij' },
  'delivery.pickup.address': { en: 'Aalststraat 7-11, Brussels · Mon–Fri 9–12:30 & 14–18h', fr: 'Aalststraat 7-11, Bruxelles · Lun–Ven 9h–12h30 & 14h–18h', nl: 'Aalststraat 7-11, Brussel · Ma–Vr 9–12:30 & 14–18u' },
  'delivery.pickup.free':    { en: 'Free', fr: 'Gratuit', nl: 'Gratis' },
  'delivery.ship.title':     { en: 'Delivery', fr: 'Livraison', nl: 'Levering' },
  'delivery.ship.desc':      { en: 'DPD Home / by bike in Brussels', fr: 'DPD Home / par vélo à Bruxelles', nl: 'DPD Home / per fiets in Brussel' },
  'delivery.ship.price':     { en: '€15,00 incl. VAT', fr: '15,00€ TVA incl.', nl: '€15,00 incl. btw' },
  'delivery.pickup.quote':   { en: 'Pick-up — Free', fr: 'Pick-up — Gratuit', nl: 'Afhalen — Gratis' },
  'delivery.ship.quote':     { en: 'DPD / bike — €15,00', fr: 'DPD / vélo — 15,00€', nl: 'DPD / fiets — €15,00' },

  // ── Quote panel ────────────────────────────────────────────────────────
  'quote.title':        { en: 'Your Quote', fr: 'Votre devis', nl: 'Uw offerte' },
  'quote.product':      { en: 'Product', fr: 'Produit', nl: 'Product' },
  'quote.format':       { en: 'Format', fr: 'Format', nl: 'Formaat' },
  'quote.paper':        { en: 'Paper', fr: 'Papier', nl: 'Papier' },
  'quote.brand':        { en: 'Brand', fr: 'Marque', nl: 'Merk' },
  'quote.weight':       { en: 'Weight', fr: 'Grammage', nl: 'Gewicht' },
  'quote.print':        { en: 'Print', fr: 'Impression', nl: 'Druk' },
  'quote.sides':        { en: 'Sides', fr: 'Faces', nl: 'Bedrukking' },
  'quote.versions':     { en: 'Versions', fr: 'Versions', nl: 'Versies' },
  'quote.quantity':     { en: 'Quantity', fr: 'Quantité', nl: 'Aantal' },
  'quote.service':      { en: 'Service', fr: 'Service', nl: 'Service' },
  'quote.delivery':     { en: 'Delivery', fr: 'Livraison', nl: 'Levering' },
  'quote.readyBy':      { en: 'Estimated ready by', fr: 'Prêt le', nl: 'Klaar tegen' },
  'quote.totalInclVat': { en: 'Total incl. VAT', fr: 'Total TVA incl.', nl: 'Totaal incl. btw' },
  'quote.perPiece':     { en: 'Per piece incl. VAT', fr: 'Par pièce TVA incl.', nl: 'Per stuk incl. btw' },
  'quote.exclVat':      { en: 'Excl. VAT', fr: 'Hors TVA', nl: 'Excl. btw' },
  'quote.perPieceShort': { en: 'per piece', fr: 'par pièce', nl: 'per stuk' },
  'quote.inclDelivery': { en: '(incl. delivery)', fr: '(livraison incl.)', nl: '(incl. levering)' },
  'quote.copies':       { en: 'copies', fr: 'exemplaires', nl: 'exemplaren' },
  'quote.timeExpress':  { en: 'Express 48h', fr: 'Express 48h', nl: 'Express 48u' },
  'quote.timeCP':       { en: '~2 weeks', fr: '~2 semaines', nl: '~2 weken' },
  'quote.timeStandard': { en: '5 working days', fr: '5 jours ouvrables', nl: '5 werkdagen' },

  // ── Product names (for quote panel) ────────────────────────────────────
  'product.flyer':      { en: 'Flyer / Card', fr: 'Flyer / Carte', nl: 'Flyer / Kaart' },
  'product.poster':     { en: 'Mini Poster', fr: 'Mini Poster', nl: 'Mini Poster' },

  // ── Buttons ────────────────────────────────────────────────────────────
  'btn.continue':       { en: 'Continue to order →', fr: 'Passer commande →', nl: 'Bestellen →' },
  'btn.order':          { en: 'Order →', fr: 'Commander →', nl: 'Bestellen →' },
  'btn.pay':            { en: 'Pay', fr: 'Payer', nl: 'Betalen' },
  'btn.payBancontact':  { en: 'Pay with Bancontact — €{amount}', fr: 'Payer par Bancontact — {amount}€', nl: 'Betalen met Bancontact — €{amount}' },
  'btn.fillRequired':   { en: 'Fill in the required fields to pay', fr: 'Remplissez les champs obligatoires', nl: 'Vul de verplichte velden in' },
  'btn.backToCalc':     { en: '← Back to calculator', fr: '← Retour au calculateur', nl: '← Terug naar calculator' },

  // ── Secure / checkout ──────────────────────────────────────────────────
  'secure.checkout':    { en: 'Secure checkout · Prices incl. 21% VAT', fr: 'Paiement sécurisé · Prix TVA 21% incl.', nl: 'Veilig betalen · Prijzen incl. 21% btw' },
  'secure.mollie':      { en: 'Secure checkout via Mollie · Prices incl. 21% VAT', fr: 'Paiement sécurisé via Mollie · Prix TVA 21% incl.', nl: 'Veilig betalen via Mollie · Prijzen incl. 21% btw' },

  // ── Step 2: Order form ─────────────────────────────────────────────────
  'order.complete':     { en: 'Complete your', fr: 'Finalisez votre', nl: 'Rond uw' },
  'order.orderWord':    { en: 'order', fr: 'commande', nl: 'bestelling' },
  'order.completeEnd':  { en: '', fr: '', nl: 'af' },
  'order.summary':      { en: 'Order summary', fr: 'Récapitulatif', nl: 'Overzicht' },

  // ── Upload section ─────────────────────────────────────────────────────
  'upload.title':       { en: 'Upload your print files', fr: 'Téléchargez vos fichiers', nl: 'Upload uw bestanden' },
  'upload.desc':        { en: 'Upload your print-ready PDF or image files to our secure kDrive folder. We accept PDF, AI, EPS, JPG, PNG and TIFF.', fr: 'Téléchargez vos fichiers PDF ou images prêts à imprimer sur notre dossier kDrive sécurisé. Nous acceptons PDF, AI, EPS, JPG, PNG et TIFF.', nl: 'Upload uw drukklare PDF- of afbeeldingsbestanden naar onze beveiligde kDrive-map. We accepteren PDF, AI, EPS, JPG, PNG en TIFF.' },
  'upload.button':      { en: 'Open kDrive to upload files', fr: 'Ouvrir kDrive pour télécharger', nl: 'Open kDrive om bestanden te uploaden' },
  'upload.tip':         { en: 'Tip: name your files with your name or order reference so we can match them.', fr: 'Conseil : nommez vos fichiers avec votre nom ou référence de commande.', nl: 'Tip: geef uw bestanden uw naam of orderreferentie zodat we ze kunnen koppelen.' },

  // ── Form fields ────────────────────────────────────────────────────────
  'form.yourDetails':   { en: 'Your details', fr: 'Vos coordonnées', nl: 'Uw gegevens' },
  'form.fullName':      { en: 'Full name', fr: 'Nom complet', nl: 'Volledige naam' },
  'form.email':         { en: 'Email', fr: 'E-mail', nl: 'E-mail' },
  'form.phone':         { en: 'Phone', fr: 'Téléphone', nl: 'Telefoon' },
  'form.isCompany':     { en: 'This order is for a company', fr: 'Cette commande est pour une entreprise', nl: 'Deze bestelling is voor een bedrijf' },
  'form.companyName':   { en: 'Company name', fr: 'Nom de l\'entreprise', nl: 'Bedrijfsnaam' },
  'form.vatNumber':     { en: 'VAT Number (for Peppol e-invoicing)', fr: 'Numéro de TVA (pour facturation Peppol)', nl: 'BTW-nummer (voor Peppol e-facturatie)' },
  'form.invoiceAddress': { en: 'Invoice address', fr: 'Adresse de facturation', nl: 'Factuuradres' },
  'form.address':       { en: 'Address', fr: 'Adresse', nl: 'Adres' },
  'form.street':        { en: 'Street + number', fr: 'Rue + numéro', nl: 'Straat + nummer' },
  'form.zip':           { en: 'Zip', fr: 'Code postal', nl: 'Postcode' },
  'form.city':          { en: 'City', fr: 'Ville', nl: 'Stad' },
  'form.diffShip':      { en: 'Ship to a different address', fr: 'Livrer à une autre adresse', nl: 'Verzenden naar een ander adres' },
  'form.shipStreet':    { en: 'Shipping street + number', fr: 'Rue de livraison + numéro', nl: 'Leveringsstraat + nummer' },
  'form.shipZip':       { en: 'Shipping zip', fr: 'Code postal de livraison', nl: 'Postcode levering' },
  'form.shipCity':      { en: 'Shipping city', fr: 'Ville de livraison', nl: 'Stad levering' },

  // ── Placeholders ───────────────────────────────────────────────────────
  'ph.name':            { en: 'Jan Janssen', fr: 'Jean Dupont', nl: 'Jan Janssen' },
  'ph.email':           { en: 'jan@example.be', fr: 'jean@exemple.be', nl: 'jan@voorbeeld.be' },
  'ph.phone':           { en: '+32 2 ...', fr: '+32 2 ...', nl: '+32 2 ...' },
  'ph.street':          { en: 'Rue de la Loi 1', fr: 'Rue de la Loi 1', nl: 'Wetstraat 1' },
  'ph.zip':             { en: '1000', fr: '1000', nl: '1000' },
  'ph.city':            { en: 'Brussels', fr: 'Bruxelles', nl: 'Brussel' },
  'ph.vat':             { en: 'e.g. BE0732.984.161', fr: 'ex. BE0732.984.161', nl: 'bijv. BE0732.984.161' },

  // ── Alerts ─────────────────────────────────────────────────────────────
  'alert.fillRequired': { en: 'Please fill in your name, email, address and city.', fr: 'Veuillez remplir votre nom, e-mail, adresse et ville.', nl: 'Vul a.u.b. uw naam, e-mail, adres en stad in.' },
  'alert.fillShipping': { en: 'Please fill in the shipping address and city.', fr: 'Veuillez remplir l\'adresse et la ville de livraison.', nl: 'Vul a.u.b. het leveringsadres en de stad in.' },

  // ── Receipt info ───────────────────────────────────────────────────────
  'receipt.personal':   { en: 'You will receive a receipt by email after payment.', fr: 'Vous recevrez un reçu par e-mail après le paiement.', nl: 'U ontvangt een ontvangstbewijs per e-mail na betaling.' },
  'receipt.company':    { en: 'You will receive a receipt and invoice by email after payment.', fr: 'Vous recevrez un reçu et une facture par e-mail après le paiement.', nl: 'U ontvangt een ontvangstbewijs en factuur per e-mail na betaling.' },
  'receipt.required':   { en: 'Name, email, address and city are required', fr: 'Nom, e-mail, adresse et ville sont obligatoires', nl: 'Naam, e-mail, adres en stad zijn verplicht' },

  // ── Contact line ───────────────────────────────────────────────────────
  'contact.notFound':   { en: 'Didn\'t find what you are looking for?', fr: 'Vous n\'avez pas trouvé ce que vous cherchez ?', nl: 'Niet gevonden wat u zoekt?' },
  'contact.contactUs':  { en: 'Contact us', fr: 'Contactez-nous', nl: 'Contacteer ons' },
  'contact.or':         { en: 'or', fr: 'ou', nl: 'of' },

  // ── Footer ─────────────────────────────────────────────────────────────
  'footer.prices':      { en: 'Prices incl. 21% VAT · Prices are indicative', fr: 'Prix TVA 21% incl. · Prix indicatifs', nl: 'Prijzen incl. 21% btw · Prijzen zijn indicatief' },

  // ── Mobile bottom bar ──────────────────────────────────────────────────
  'mobile.details':     { en: 'Details', fr: 'Détails', nl: 'Details' },
  'mobile.close':       { en: 'Close', fr: 'Fermer', nl: 'Sluiten' },

  // ── Date formatting ────────────────────────────────────────────────────
  'days': {
    en: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
    fr: ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'],
    nl: ['Zondag', 'Maandag', 'Dinsdag', 'Woensdag', 'Donderdag', 'Vrijdag', 'Zaterdag'],
  },
  'months': {
    en: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
    fr: ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'],
    nl: ['Januari', 'Februari', 'Maart', 'April', 'Mei', 'Juni', 'Juli', 'Augustus', 'September', 'Oktober', 'November', 'December'],
  },
}

export default T
