#!/usr/bin/env python3
"""
Builds legal/index.html (EN), legal/fr.html and legal/nl.html.

Three languages of the same three documents — legal notice, privacy policy and
terms of sale. Keeping them in one generator means a change to the structure
cannot land in one language and be forgotten in the other two, which is exactly
how trilingual legal pages usually rot.

The LOOK lives in ../lib/ccg_page.py, shared with /papers/ and any other
standalone page. Only the words live here.

THE WORDS IN THIS FILE ARE BINDING. They are the terms a customer agrees to at
checkout, and the withdrawal-rights notice in particular is what makes the
no-cancellation clause hold. Change the design freely; change the text only
deliberately, and bump UPDATED_ISO and the three "updated" strings when you do.

Edit CONTENT below, run `python3 build.py`, upload the three HTML files.
"""
import os
import sys

sys.path.insert(0, os.path.join(os.path.dirname(os.path.abspath(__file__)), "..", "lib"))
import ccg_page as kit

COMPANY       = kit.COMPANY
LEGAL_ADDRESS = kit.LEGAL_ADDRESS
SHOP_ADDRESS  = kit.SHOP_ADDRESS
VAT           = kit.VAT
TEL           = kit.TEL
UPDATED_ISO   = "2026-08-18"

CONTENT = {}
CONTENT["en"] = {
 "back_label": "Back to the shop",
 "back_href": kit.SHOP_HREF,
 "nav": [("Services", "https://tiegel.be/"), ("Shop", "/flyers/"), ("Paper", "/papers/"), ("Terms", "index.html")],
 "title": "Legal, privacy & terms — Click Click Graphics",
 "desc": "Company details, privacy policy and terms of sale for Click Click Graphics SRL, Brussels.",
 "h1": "Legal, privacy &amp; terms",
 "sub": f"{COMPANY} — Brussels", "updated": "Last updated 18 August 2026",
 "toc_label": "On this page",
 "footer": f'{COMPANY} · {LEGAL_ADDRESS} · {VAT} · <a href="mailto:info@clickclickgraphics.com">info@clickclickgraphics.com</a> · {TEL}',
 "sections": [
  ("company", "Who we are", f"""
<p>This website and the online print shop are operated by:</p>
<dl>
  <dt>Company</dt><dd>{COMPANY}</dd>
  <dt>Registered address</dt><dd>{LEGAL_ADDRESS}, Belgium</dd>
  <dt>Enterprise / VAT no.</dt><dd>{VAT}</dd>
  <dt>Email</dt><dd><a href="mailto:info@clickclickgraphics.com">info@clickclickgraphics.com</a> — general<br>
                    <a href="mailto:shop@clickclickgraphics.com">shop@clickclickgraphics.com</a> — orders</dd>
  <dt>Telephone</dt><dd>{TEL}</dd>
</dl>
<p>{COMPANY} is the data controller for the personal data described below.</p>"""),

  ("privacy", "Privacy policy", """
<p>We keep this short because there is not much to tell. We do not advertise, we do not profile
anyone, and we do not share data with anyone who is not needed to print and deliver your order.</p>
<div class="note"><p><strong>No cookies, no tracking.</strong> This site sets no cookies at all and
runs no analytics. The only thing stored in your browser is your language choice, so the site stays
in your language between visits. That never leaves your device and needs no consent — which is why
you see no cookie banner.</p></div>
<p>Fonts, scripts and images are served from our own server. No third party is contacted simply
because you visited a page.</p>"""),

  ("data", "What we collect and why", """
<div class="tw"><table>
<tr><th>What</th><th>When</th><th>Why — and legal basis</th></tr>
<tr><td>Name, email, phone, billing and delivery address, VAT number for companies</td>
    <td>When you place an order</td>
    <td>To produce, invoice and deliver your order. <em>Performance of a contract; legal
    obligation.</em></td></tr>
<tr><td>The artwork files you upload</td><td>When you place an order</td>
    <td>To print your job. We do not reuse your artwork, and never show it outside the workshop.
    <em>Performance of a contract.</em></td></tr>
<tr><td>Payment status and reference</td><td>At checkout</td>
    <td>To confirm your payment. We never see or store your card or bank details.
    <em>Performance of a contract.</em></td></tr>
<tr><td>What you type in the chat, plus your contact details if you give them</td>
    <td>If you use the chat assistant</td>
    <td>To prepare a quote request and reply to you. <em>Legitimate interest in answering
    enquiries.</em></td></tr>
<tr><td>Server logs, and a one-way hash of your IP address</td><td>Automatically</td>
    <td>Security and abuse prevention. The hash cannot be turned back into an address.
    <em>Legitimate interest.</em></td></tr>
</table></div>"""),

  ("sharing", "Who else sees it", """
<p>Only the parties needed to complete your order. Each is bound by contract to process data only
on our instructions.</p>
<div class="tw"><table>
<tr><th>Who</th><th>What they receive</th><th>Where</th></tr>
<tr><td>Mollie B.V. — payments</td><td>Order amount, reference, and what you enter on their payment
    page</td><td>Netherlands (EU)</td></tr>
<tr><td>Infomaniak — hosting and encrypted backups</td><td>Everything stored on the site</td>
    <td>Switzerland (adequacy decision)</td></tr>
<tr><td>DPD — delivery</td><td>Recipient name, address and phone, if you choose delivery</td>
    <td>Belgium (EU)</td></tr>
<tr><td>Anthropic — the chat assistant</td><td>What you type in the chat. Only if you use it.</td>
    <td>United States, under EU Standard Contractual Clauses</td></tr>
<tr><td>European Commission (VIES)</td><td>A company VAT number, to check it is valid</td>
    <td>EU</td></tr>
</table></div>
<p>We also share invoicing data with our accountant and, where the law requires it, with the tax
authorities. We never sell personal data, and we do not use it for advertising.</p>"""),

  ("retention", "How long we keep it", """
<div class="tw"><table>
<tr><th>What</th><th>How long</th><th>Then</th></tr>
<tr><td>Your uploaded artwork</td><td>6 months after payment</td><td>Deleted automatically</td></tr>
<tr><td>Order records with your details</td><td>2 years after payment</td>
    <td>Your personal details are replaced with a marker; the invoice number and amount remain</td></tr>
<tr><td>Chat conversations</td><td>90 days</td>
    <td>Contact details removed; the rest deleted after 2 years</td></tr>
<tr><td>Security logs</td><td>6 months at most</td><td>Deleted automatically</td></tr>
</table></div>
<p>These sweeps run automatically on our servers. We do not keep things “just in case”.</p>
<p>Invoices are held separately in our accounting system, as any business must, and are not covered
by the sweeps above.</p>"""),

  ("rights", "Your rights", """
<p>Under the GDPR you may ask us to:</p>
<ul>
  <li>tell you what data we hold about you, and give you a copy;</li>
  <li>correct anything that is wrong;</li>
  <li>delete your data, where we are not required to keep it — invoicing records are subject to
      statutory retention periods;</li>
  <li>restrict or object to how we use it;</li>
  <li>receive it in a portable, machine-readable format.</li>
</ul>
<p>Write to <a href="mailto:info@clickclickgraphics.com">info@clickclickgraphics.com</a>. We reply
within one month, and it costs you nothing.</p>
<p>If you are not satisfied with our answer, you can complain to the Belgian Data Protection
Authority, Rue de la Presse 35, 1000 Brussels —
<a href="https://www.dataprotectionauthority.be" rel="noopener">dataprotectionauthority.be</a>.</p>"""),

  ("cookies", "Cookies and tracking", """
<p>We use no cookies. There is no analytics, no advertising pixel, no social media tracker, and no
third-party font or script.</p>
<p>Your browser stores one thing for us: the language you chose, so the site opens in it next time.
That is a functional preference, it stays on your device, and clearing your browser data removes it.
No consent is required, which is why this site has no cookie banner.</p>"""),

  ("terms", "Terms of sale", f"""
<p>These terms apply to every order placed through our online shop. Placing an order means you
accept them. We are {COMPANY}, {LEGAL_ADDRESS}, {VAT}.</p>

<h3 class="plain">Prices</h3>
<p>All prices shown include 21% VAT. The price you see when you order is the price you pay — we do
not add surcharges afterwards. Delivery within Belgium is a flat 15 € including VAT, added once per
order regardless of quantity.</p>

<h3 class="plain">Ordering and payment</h3>
<p>Your order is confirmed once payment has gone through. We ask for full payment before production
because every job is made specifically for you and cannot be resold. Payment is handled by Mollie;
we never see or store your card or bank details.</p>

<h3 class="plain">Your artwork</h3>
<p><strong>You are responsible for the file you supply</strong> — its content, spelling, colours,
resolution, and your right to print it. We print what we receive.</p>
<p>We check files for obvious technical problems before printing — a missing bleed, a very low
resolution, an odd colour mode — and we will tell you if we spot something. That is a courtesy, not
a guarantee: we cannot catch everything, and we do not proofread content.</p>
<p>We prefer print-ready PDF, with 3 mm bleed, up to 100 MB per file. We also accept JPG, PNG and
TIFF.</p>

<h3 class="plain">Production time</h3>
<p>Standard production is <strong>5 working days</strong>. Express is <strong>48 hours</strong>, and
is available for pick-up only — a courier cannot make 48 hours.</p>
<p>Working days are Monday to Friday, excluding Belgian public holidays. Orders placed after 15:00
start counting the next working day.</p>
<p>This is the time to produce your job, not to deliver it. The date shown at checkout is when your
order leaves us.</p>

<h3 class="plain">Delivery and pick-up</h3>
<p><strong>Pick-up</strong> at {SHOP_ADDRESS} is free.</p>
<p><strong>Delivery</strong> is 15 € flat within Belgium, by DPD. Transit time is added on top of
production and is not within our control, so we do not promise an arrival date. You receive a
tracking code when your order ships.</p>
<p><strong>Outside Belgium</strong> we do ship, but the cost depends on the destination, so it is
quoted by hand. Ask us and we will come back with a price.</p>

<h3 class="plain">Right of withdrawal</h3>
<div class="note warn"><p>Everything we print is made to your specifications, from your file. Under
Article VI.53, 3° of the Belgian Code of Economic Law, goods made to the consumer’s specifications
or clearly personalised are <strong>exempt from the 14-day right of withdrawal</strong>.</p>
<p><strong>Your order cannot be cancelled or returned once production has started.</strong> Please
check your file carefully before you order.</p></div>
<p>If you contact us before we start printing, we will always try to help.</p>

<h3 class="plain">If something is wrong</h3>
<p>Check your order when you receive or collect it, and tell us within <strong>7 calendar
days</strong> if there is a problem — email
<a href="mailto:shop@clickclickgraphics.com">shop@clickclickgraphics.com</a> with your invoice
number and a photo.</p>
<p>If the fault is ours — a printing defect, the wrong specification, damage in transit — we reprint
the job at our cost. This does not affect your statutory rights as a consumer under Belgian law,
which we do not limit.</p>
<p>We cannot reprint for problems in the supplied file: a typo, a low-resolution image, or colours
that were wrong in the artwork.</p>

<h3 class="plain">Colour</h3>
<p>Screens are backlit and printers are not. Small colour differences between what you see on screen
and what comes off the press are normal and are not a defect. If exact colour matters, ask us about
a proof before ordering.</p>

<h3 class="plain">Liability</h3>
<p>We are liable for damage caused by our own fault. Our liability is limited to the value of the
order concerned, except where the law does not permit such a limit — in particular for death,
personal injury, or intent.</p>

<h3 class="plain">Applicable law and disputes</h3>
<p>Belgian law applies. We would always rather sort a problem out directly, so please write to us
first. Consumers may also contact the Belgian Consumer Mediation Service, Boulevard du Roi Albert II
8, 1000 Brussels. Disputes fall under the courts of Brussels.</p>"""),
 ]}

# ── FRANÇAIS ─────────────────────────────────────────────────────────────────
CONTENT["fr"] = {
 "back_label": "Retour à la boutique",
 "back_href": kit.SHOP_HREF,
 "nav": [("Services", "https://tiegel.be/"), ("Boutique", "/flyers/"), ("Papier", "/papers/fr.html"), ("Conditions", "fr.html")],
 "title": "Mentions légales, vie privée & conditions — Click Click Graphics",
 "desc": "Mentions légales, politique de confidentialité et conditions de vente de Click Click Graphics SRL, Bruxelles.",
 "h1": "Mentions légales, vie privée &amp; conditions",
 "sub": f"{COMPANY} — Bruxelles", "updated": "Dernière mise à jour : 18 août 2026",
 "toc_label": "Sur cette page",
 "footer": f'{COMPANY} · {LEGAL_ADDRESS} · {VAT} · <a href="mailto:info@clickclickgraphics.com">info@clickclickgraphics.com</a> · {TEL}',
 "sections": [
  ("company", "Qui sommes-nous", f"""
<p>Ce site et la boutique d’impression en ligne sont exploités par :</p>
<dl>
  <dt>Société</dt><dd>{COMPANY}</dd>
  <dt>Siège social</dt><dd>{LEGAL_ADDRESS}, Belgique</dd>
  <dt>N° d’entreprise / TVA</dt><dd>{VAT}</dd>
  <dt>E-mail</dt><dd><a href="mailto:info@clickclickgraphics.com">info@clickclickgraphics.com</a> — général<br>
                     <a href="mailto:shop@clickclickgraphics.com">shop@clickclickgraphics.com</a> — commandes</dd>
  <dt>Téléphone</dt><dd>{TEL}</dd>
</dl>
<p>{COMPANY} est le responsable du traitement des données personnelles décrites ci-dessous.</p>"""),

  ("privacy", "Politique de confidentialité", """
<p>Cette page est courte parce qu’il n’y a pas grand-chose à dire. Nous ne faisons pas de publicité,
nous ne profilons personne, et nous ne partageons vos données avec personne d’autre que ceux dont
nous avons besoin pour imprimer et livrer votre commande.</p>
<div class="note"><p><strong>Aucun cookie, aucun traçage.</strong> Ce site ne dépose aucun cookie et
n’utilise aucun outil de mesure d’audience. La seule chose enregistrée dans votre navigateur est la
langue que vous avez choisie, afin que le site reste dans votre langue. Cette information ne quitte
jamais votre appareil et ne requiert aucun consentement — c’est pourquoi vous ne voyez aucune
bannière de cookies.</p></div>
<p>Les polices, scripts et images sont servis depuis notre propre serveur. Aucun tiers n’est
contacté du simple fait que vous consultez une page.</p>"""),

  ("data", "Ce que nous collectons et pourquoi", """
<div class="tw"><table>
<tr><th>Quoi</th><th>Quand</th><th>Pourquoi — et base légale</th></tr>
<tr><td>Nom, e-mail, téléphone, adresse de facturation et de livraison, n° de TVA pour les
    sociétés</td><td>Lors de votre commande</td>
    <td>Pour produire, facturer et livrer votre commande. <em>Exécution du contrat ; obligation
    légale.</em></td></tr>
<tr><td>Les fichiers que vous téléversez</td><td>Lors de votre commande</td>
    <td>Pour imprimer votre travail. Nous ne réutilisons jamais vos fichiers et ne les montrons pas
    en dehors de l’atelier. <em>Exécution du contrat.</em></td></tr>
<tr><td>Statut et référence de paiement</td><td>Au paiement</td>
    <td>Pour confirmer votre paiement. Nous ne voyons ni ne conservons vos données bancaires.
    <em>Exécution du contrat.</em></td></tr>
<tr><td>Ce que vous écrivez dans le chat, et vos coordonnées si vous les donnez</td>
    <td>Si vous utilisez l’assistant</td>
    <td>Pour préparer une demande de devis et vous répondre. <em>Intérêt légitime à répondre aux
    demandes.</em></td></tr>
<tr><td>Journaux du serveur et empreinte à sens unique de votre adresse IP</td><td>Automatiquement</td>
    <td>Sécurité et prévention des abus. L’empreinte ne peut pas être reconvertie en adresse.
    <em>Intérêt légitime.</em></td></tr>
</table></div>"""),

  ("sharing", "Qui d’autre y a accès", """
<p>Uniquement les parties nécessaires à l’exécution de votre commande. Chacune est contractuellement
tenue de ne traiter les données que sur nos instructions.</p>
<div class="tw"><table>
<tr><th>Qui</th><th>Ce qu’ils reçoivent</th><th>Où</th></tr>
<tr><td>Mollie B.V. — paiements</td><td>Montant, référence, et ce que vous saisissez sur leur page de
    paiement</td><td>Pays-Bas (UE)</td></tr>
<tr><td>Infomaniak — hébergement et sauvegardes chiffrées</td><td>Tout ce qui est stocké sur le
    site</td><td>Suisse (décision d’adéquation)</td></tr>
<tr><td>DPD — livraison</td><td>Nom, adresse et téléphone du destinataire, si vous choisissez la
    livraison</td><td>Belgique (UE)</td></tr>
<tr><td>Anthropic — l’assistant de chat</td><td>Ce que vous écrivez dans le chat. Uniquement si vous
    l’utilisez.</td><td>États-Unis, sous clauses contractuelles types de l’UE</td></tr>
<tr><td>Commission européenne (VIES)</td><td>Un numéro de TVA d’entreprise, pour en vérifier la
    validité</td><td>UE</td></tr>
</table></div>
<p>Nous partageons également les données de facturation avec notre comptable et, lorsque la loi
l’exige, avec l’administration fiscale. Nous ne vendons jamais de données personnelles et ne les
utilisons pas à des fins publicitaires.</p>"""),

  ("retention", "Combien de temps nous les conservons", """
<div class="tw"><table>
<tr><th>Quoi</th><th>Durée</th><th>Ensuite</th></tr>
<tr><td>Vos fichiers d’impression</td><td>6 mois après le paiement</td>
    <td>Supprimés automatiquement</td></tr>
<tr><td>Les commandes avec vos coordonnées</td><td>2 ans après le paiement</td>
    <td>Vos données personnelles sont remplacées par un marqueur ; le numéro de facture et le
    montant sont conservés</td></tr>
<tr><td>Conversations du chat</td><td>90 jours</td>
    <td>Coordonnées supprimées ; le reste est effacé après 2 ans</td></tr>
<tr><td>Journaux de sécurité</td><td>6 mois au maximum</td><td>Supprimés automatiquement</td></tr>
</table></div>
<p>Ces nettoyages s’exécutent automatiquement sur nos serveurs. Nous ne gardons rien « au cas
où ».</p>
<p>Les factures sont conservées séparément dans notre logiciel comptable, comme toute entreprise
doit le faire, et ne sont pas concernées par ce qui précède.</p>"""),

  ("rights", "Vos droits", """
<p>Conformément au RGPD, vous pouvez nous demander de :</p>
<ul>
  <li>vous indiquer quelles données nous détenons à votre sujet et vous en remettre une copie ;</li>
  <li>corriger ce qui serait inexact ;</li>
  <li>effacer vos données, lorsque nous ne sommes pas tenus de les conserver — les documents de
      facturation sont soumis à des délais de conservation légaux ;</li>
  <li>limiter leur utilisation ou vous y opposer ;</li>
  <li>les recevoir dans un format portable et lisible par machine.</li>
</ul>
<p>Écrivez à <a href="mailto:info@clickclickgraphics.com">info@clickclickgraphics.com</a>. Nous
répondons dans le mois, et cela ne vous coûte rien.</p>
<p>Si notre réponse ne vous satisfait pas, vous pouvez introduire une réclamation auprès de
l’Autorité de protection des données, Rue de la Presse 35, 1000 Bruxelles —
<a href="https://www.autoriteprotectiondonnees.be" rel="noopener">autoriteprotectiondonnees.be</a>.</p>"""),

  ("cookies", "Cookies et traçage", """
<p>Nous n’utilisons aucun cookie. Ni mesure d’audience, ni pixel publicitaire, ni traceur de réseau
social, ni police ou script tiers.</p>
<p>Votre navigateur ne conserve qu’une chose pour nous : la langue que vous avez choisie, afin que le
site s’ouvre dans celle-ci la prochaine fois. C’est une préférence fonctionnelle, elle reste sur
votre appareil, et vider les données de votre navigateur la supprime. Aucun consentement n’est
requis, d’où l’absence de bannière.</p>"""),

  ("terms", "Conditions de vente", f"""
<p>Ces conditions s’appliquent à toute commande passée via notre boutique en ligne. Passer commande
implique que vous les acceptez. Nous sommes {COMPANY}, {LEGAL_ADDRESS}, {VAT}.</p>

<h3 class="plain">Prix</h3>
<p>Tous les prix affichés s’entendent TVA de 21 % comprise. Le prix affiché au moment de la commande
est celui que vous payez — aucun supplément n’est ajouté ensuite. La livraison en Belgique est un
forfait de 15 € TVAC, compté une seule fois par commande, quelle que soit la quantité.</p>

<h3 class="plain">Commande et paiement</h3>
<p>Votre commande est confirmée dès que le paiement est accepté. Nous demandons le paiement intégral
avant production, car chaque travail est réalisé spécifiquement pour vous et ne peut être revendu.
Le paiement est traité par Mollie ; nous ne voyons ni ne conservons vos données bancaires.</p>

<h3 class="plain">Vos fichiers</h3>
<p><strong>Vous êtes responsable du fichier que vous fournissez</strong> — son contenu, son
orthographe, ses couleurs, sa résolution, ainsi que votre droit de l’imprimer. Nous imprimons ce que
nous recevons.</p>
<p>Nous vérifions les fichiers pour repérer les problèmes techniques évidents avant impression — un
fond perdu manquant, une résolution très faible, un mode colorimétrique inhabituel — et nous vous
prévenons si nous remarquons quelque chose. C’est un service, pas une garantie : nous ne pouvons pas
tout détecter et nous ne relisons pas le contenu.</p>
<p>Nous préférons un PDF prêt à imprimer, avec 3 mm de fond perdu, jusqu’à 100 Mo par fichier. Nous
acceptons également JPG, PNG et TIFF.</p>

<h3 class="plain">Délai de production</h3>
<p>La production standard prend <strong>5 jours ouvrables</strong>. L’express prend
<strong>48 heures</strong> et est disponible uniquement en retrait — un transporteur ne peut pas
tenir 48 heures.</p>
<p>Les jours ouvrables sont du lundi au vendredi, jours fériés belges exclus. Les commandes passées
après 15 h sont comptées à partir du jour ouvrable suivant.</p>
<p>Il s’agit du délai de production, pas de livraison. La date affichée à la commande est celle où
votre commande quitte nos ateliers.</p>

<h3 class="plain">Livraison et retrait</h3>
<p><strong>Le retrait</strong> au {SHOP_ADDRESS} est gratuit.</p>
<p><strong>La livraison</strong> est un forfait de 15 € en Belgique, par DPD. Le délai
d’acheminement s’ajoute à la production et échappe à notre contrôle : nous ne promettons donc
aucune date d’arrivée. Vous recevez un code de suivi au moment de l’expédition.</p>
<p><strong>Hors Belgique</strong>, nous expédions également, mais le tarif dépend de la destination
et est établi manuellement. Demandez-nous un prix et nous vous répondrons.</p>

<h3 class="plain">Droit de rétractation</h3>
<div class="note warn"><p>Tout ce que nous imprimons est réalisé selon vos spécifications, à partir
de votre fichier. En vertu de l’article VI.53, 3° du Code de droit économique, les biens confectionnés
selon les spécifications du consommateur ou nettement personnalisés sont <strong>exclus du droit de
rétractation de 14 jours</strong>.</p>
<p><strong>Votre commande ne peut donc être ni annulée ni retournée une fois la production
lancée.</strong> Vérifiez soigneusement votre fichier avant de commander.</p></div>
<p>Si vous nous contactez avant le lancement de l’impression, nous ferons toujours notre possible
pour vous aider.</p>

<h3 class="plain">En cas de problème</h3>
<p>Vérifiez votre commande à la réception ou au retrait et signalez-nous tout problème dans les
<strong>7 jours calendrier</strong> — écrivez à
<a href="mailto:shop@clickclickgraphics.com">shop@clickclickgraphics.com</a> avec votre numéro de
facture et une photo.</p>
<p>Si l’erreur vient de nous — un défaut d’impression, une spécification erronée, un dommage lors du
transport — nous réimprimons le travail à nos frais. Cela ne porte pas atteinte à vos droits légaux
en tant que consommateur, que nous ne limitons pas.</p>
<p>Nous ne pouvons pas réimprimer pour un problème présent dans le fichier fourni : une faute de
frappe, une image en basse résolution, ou des couleurs erronées dans le document d’origine.</p>

<h3 class="plain">Couleurs</h3>
<p>Un écran est rétroéclairé, pas une impression. De légères différences de couleur entre l’écran et
le résultat imprimé sont normales et ne constituent pas un défaut. Si la précision des couleurs est
essentielle, demandez-nous une épreuve avant de commander.</p>

<h3 class="plain">Responsabilité</h3>
<p>Nous sommes responsables des dommages causés par notre propre faute. Notre responsabilité est
limitée à la valeur de la commande concernée, sauf lorsque la loi ne permet pas une telle limitation
— notamment en cas de décès, de dommage corporel ou de faute intentionnelle.</p>

<h3 class="plain">Droit applicable et litiges</h3>
<p>Le droit belge s’applique. Nous préférons toujours régler un problème directement : écrivez-nous
d’abord. Les consommateurs peuvent également s’adresser au Service de Médiation pour le
Consommateur, Boulevard du Roi Albert II 8, 1000 Bruxelles. Les litiges relèvent des tribunaux de
Bruxelles.</p>"""),
 ]}

# ── NEDERLANDS ───────────────────────────────────────────────────────────────
CONTENT["nl"] = {
 "back_label": "Terug naar de winkel",
 "back_href": kit.SHOP_HREF,
 "nav": [("Diensten", "https://tiegel.be/"), ("Winkel", "/flyers/"), ("Papier", "/papers/nl.html"), ("Voorwaarden", "nl.html")],
 "title": "Juridisch, privacy & voorwaarden — Click Click Graphics",
 "desc": "Juridische gegevens, privacybeleid en verkoopvoorwaarden van Click Click Graphics SRL, Brussel.",
 "h1": "Juridisch, privacy &amp; voorwaarden",
 "sub": f"{COMPANY} — Brussel", "updated": "Laatst bijgewerkt op 18 augustus 2026",
 "toc_label": "Op deze pagina",
 "footer": f'{COMPANY} · {LEGAL_ADDRESS} · {VAT} · <a href="mailto:info@clickclickgraphics.com">info@clickclickgraphics.com</a> · {TEL}',
 "sections": [
  ("company", "Wie wij zijn", f"""
<p>Deze website en de online drukwerkshop worden beheerd door:</p>
<dl>
  <dt>Vennootschap</dt><dd>{COMPANY}</dd>
  <dt>Maatschappelijke zetel</dt><dd>{LEGAL_ADDRESS}, België</dd>
  <dt>Ondernemings- / btw-nr.</dt><dd>{VAT}</dd>
  <dt>E-mail</dt><dd><a href="mailto:info@clickclickgraphics.com">info@clickclickgraphics.com</a> — algemeen<br>
                     <a href="mailto:shop@clickclickgraphics.com">shop@clickclickgraphics.com</a> — bestellingen</dd>
  <dt>Telefoon</dt><dd>{TEL}</dd>
</dl>
<p>{COMPANY} is de verwerkingsverantwoordelijke voor de hieronder beschreven persoonsgegevens.</p>"""),

  ("privacy", "Privacybeleid", """
<p>Deze pagina is kort, omdat er weinig te vertellen valt. Wij adverteren niet, wij profileren
niemand, en wij delen uw gegevens met niemand die niet nodig is om uw bestelling te drukken en te
leveren.</p>
<div class="note"><p><strong>Geen cookies, geen tracking.</strong> Deze site plaatst helemaal geen
cookies en gebruikt geen statistiekentools. Het enige wat uw browser voor ons bewaart, is de taal
die u koos, zodat de site in uw taal blijft. Dat verlaat uw toestel nooit en vereist geen
toestemming — daarom ziet u geen cookiebanner.</p></div>
<p>Lettertypes, scripts en afbeeldingen komen van onze eigen server. Er wordt geen enkele derde
partij gecontacteerd louter omdat u een pagina bekijkt.</p>"""),

  ("data", "Wat wij verzamelen en waarom", """
<div class="tw"><table>
<tr><th>Wat</th><th>Wanneer</th><th>Waarom — en rechtsgrond</th></tr>
<tr><td>Naam, e-mail, telefoon, facturatie- en leveringsadres, btw-nummer voor vennootschappen</td>
    <td>Bij uw bestelling</td>
    <td>Om uw bestelling te produceren, te factureren en te leveren. <em>Uitvoering van de
    overeenkomst; wettelijke verplichting.</em></td></tr>
<tr><td>De bestanden die u uploadt</td><td>Bij uw bestelling</td>
    <td>Om uw werk te drukken. Wij hergebruiken uw bestanden nooit en tonen ze niet buiten het
    atelier. <em>Uitvoering van de overeenkomst.</em></td></tr>
<tr><td>Betaalstatus en referentie</td><td>Bij het afrekenen</td>
    <td>Om uw betaling te bevestigen. Wij zien of bewaren uw bankgegevens nooit. <em>Uitvoering van
    de overeenkomst.</em></td></tr>
<tr><td>Wat u in de chat typt, en uw contactgegevens als u die geeft</td>
    <td>Als u de assistent gebruikt</td>
    <td>Om een offerteaanvraag voor te bereiden en u te antwoorden. <em>Gerechtvaardigd belang bij
    het beantwoorden van vragen.</em></td></tr>
<tr><td>Serverlogs en een eenrichtingshash van uw IP-adres</td><td>Automatisch</td>
    <td>Beveiliging en misbruikpreventie. De hash kan niet worden herleid tot een adres.
    <em>Gerechtvaardigd belang.</em></td></tr>
</table></div>"""),

  ("sharing", "Wie er verder toegang toe heeft", """
<p>Enkel de partijen die nodig zijn om uw bestelling uit te voeren. Elk van hen is contractueel
verplicht gegevens uitsluitend volgens onze instructies te verwerken.</p>
<div class="tw"><table>
<tr><th>Wie</th><th>Wat zij ontvangen</th><th>Waar</th></tr>
<tr><td>Mollie B.V. — betalingen</td><td>Bedrag, referentie, en wat u op hun betaalpagina invult</td>
    <td>Nederland (EU)</td></tr>
<tr><td>Infomaniak — hosting en versleutelde back-ups</td><td>Alles wat op de site is opgeslagen</td>
    <td>Zwitserland (adequaatheidsbesluit)</td></tr>
<tr><td>DPD — levering</td><td>Naam, adres en telefoon van de bestemmeling, als u levering kiest</td>
    <td>België (EU)</td></tr>
<tr><td>Anthropic — de chatassistent</td><td>Wat u in de chat typt. Enkel als u die gebruikt.</td>
    <td>Verenigde Staten, onder EU-modelcontractbepalingen</td></tr>
<tr><td>Europese Commissie (VIES)</td><td>Een btw-nummer van een vennootschap, om de geldigheid te
    controleren</td><td>EU</td></tr>
</table></div>
<p>Wij delen facturatiegegevens ook met onze boekhouder en, waar de wet dat vereist, met de
belastingadministratie. Wij verkopen nooit persoonsgegevens en gebruiken ze niet voor
reclame.</p>"""),

  ("retention", "Hoe lang wij ze bewaren", """
<div class="tw"><table>
<tr><th>Wat</th><th>Hoe lang</th><th>Daarna</th></tr>
<tr><td>Uw drukbestanden</td><td>6 maanden na betaling</td><td>Automatisch verwijderd</td></tr>
<tr><td>Bestellingen met uw gegevens</td><td>2 jaar na betaling</td>
    <td>Uw persoonsgegevens worden vervangen door een markering; factuurnummer en bedrag
    blijven</td></tr>
<tr><td>Chatgesprekken</td><td>90 dagen</td>
    <td>Contactgegevens verwijderd; de rest na 2 jaar gewist</td></tr>
<tr><td>Beveiligingslogs</td><td>Maximaal 6 maanden</td><td>Automatisch verwijderd</td></tr>
</table></div>
<p>Deze opruimingen verlopen automatisch op onze servers. Wij bewaren niets « voor het geval
dat ».</p>
<p>Facturen worden apart bewaard in ons boekhoudpakket, zoals elke onderneming moet doen, en vallen
niet onder het bovenstaande.</p>"""),

  ("rights", "Uw rechten", """
<p>Op grond van de AVG kunt u ons vragen om:</p>
<ul>
  <li>u te zeggen welke gegevens wij over u hebben, en u daarvan een kopie te bezorgen;</li>
  <li>te corrigeren wat onjuist is;</li>
  <li>uw gegevens te wissen, voor zover wij ze niet moeten bewaren — facturatiestukken zijn
      onderworpen aan wettelijke bewaartermijnen;</li>
  <li>het gebruik ervan te beperken of zich ertegen te verzetten;</li>
  <li>ze te ontvangen in een overdraagbaar, machineleesbaar formaat.</li>
</ul>
<p>Schrijf naar <a href="mailto:info@clickclickgraphics.com">info@clickclickgraphics.com</a>. Wij
antwoorden binnen de maand, en het kost u niets.</p>
<p>Bent u niet tevreden met ons antwoord, dan kunt u klacht indienen bij de
Gegevensbeschermingsautoriteit, Drukpersstraat 35, 1000 Brussel —
<a href="https://www.gegevensbeschermingsautoriteit.be" rel="noopener">gegevensbeschermingsautoriteit.be</a>.</p>"""),

  ("cookies", "Cookies en tracking", """
<p>Wij gebruiken geen cookies. Geen statistieken, geen advertentiepixel, geen social-mediatracker,
geen lettertype of script van derden.</p>
<p>Uw browser bewaart één ding voor ons: de taal die u koos, zodat de site daarin opent bij uw
volgende bezoek. Dat is een functionele voorkeur, ze blijft op uw toestel, en het wissen van uw
browsergegevens verwijdert ze. Er is geen toestemming voor nodig, vandaar geen banner.</p>"""),

  ("terms", "Verkoopvoorwaarden", f"""
<p>Deze voorwaarden gelden voor elke bestelling via onze online shop. Door te bestellen aanvaardt u
ze. Wij zijn {COMPANY}, {LEGAL_ADDRESS}, {VAT}.</p>

<h3 class="plain">Prijzen</h3>
<p>Alle vermelde prijzen zijn inclusief 21 % btw. De prijs die u ziet bij het bestellen is de prijs
die u betaalt — er komen achteraf geen toeslagen bij. Levering in België is een forfait van 15 €
incl. btw, één keer per bestelling aangerekend, ongeacht de hoeveelheid.</p>

<h3 class="plain">Bestelling en betaling</h3>
<p>Uw bestelling is bevestigd zodra de betaling is aanvaard. Wij vragen volledige betaling vóór
productie, omdat elk werk specifiek voor u wordt gemaakt en niet kan worden doorverkocht. De
betaling verloopt via Mollie; wij zien of bewaren uw bankgegevens nooit.</p>

<h3 class="plain">Uw bestanden</h3>
<p><strong>U bent verantwoordelijk voor het bestand dat u aanlevert</strong> — de inhoud, de
spelling, de kleuren, de resolutie, en uw recht om het te drukken. Wij drukken wat wij
ontvangen.</p>
<p>Wij controleren bestanden op duidelijke technische problemen vóór het drukken — een ontbrekende
afloop, een zeer lage resolutie, een ongebruikelijke kleurmodus — en verwittigen u als ons iets
opvalt. Dat is een service, geen garantie: wij kunnen niet alles opmerken en wij lezen de inhoud
niet na.</p>
<p>Wij werken bij voorkeur met een drukklare PDF, met 3 mm afloop, tot 100 MB per bestand. Ook JPG,
PNG en TIFF worden aanvaard.</p>

<h3 class="plain">Productietijd</h3>
<p>Standaardproductie duurt <strong>5 werkdagen</strong>. Express duurt <strong>48 uur</strong> en is
enkel mogelijk met afhaling — een koerier haalt geen 48 uur.</p>
<p>Werkdagen zijn maandag tot vrijdag, Belgische feestdagen niet meegerekend. Bestellingen na 15 u
worden vanaf de volgende werkdag geteld.</p>
<p>Dit is de productietijd, niet de levertijd. De datum bij het bestellen is het moment waarop uw
bestelling ons verlaat.</p>

<h3 class="plain">Levering en afhaling</h3>
<p><strong>Afhalen</strong> op {SHOP_ADDRESS} is gratis.</p>
<p><strong>Levering</strong> kost een forfait van 15 € in België, via DPD. De transporttijd komt
boven op de productie en ligt buiten onze controle: wij beloven dus geen aankomstdatum. U ontvangt
een trackingcode zodra uw bestelling verzonden is.</p>
<p><strong>Buiten België</strong> verzenden wij ook, maar de kostprijs hangt af van de bestemming en
wordt manueel berekend. Vraag het ons en wij bezorgen u een prijs.</p>

<h3 class="plain">Herroepingsrecht</h3>
<div class="note warn"><p>Alles wat wij drukken wordt gemaakt volgens uw specificaties, op basis van
uw bestand. Krachtens artikel VI.53, 3° van het Wetboek van economisch recht zijn goederen die
volgens de specificaties van de consument zijn vervaardigd of duidelijk gepersonaliseerd zijn
<strong>uitgesloten van het herroepingsrecht van 14 dagen</strong>.</p>
<p><strong>Uw bestelling kan dus niet worden geannuleerd of teruggestuurd zodra de productie is
gestart.</strong> Controleer uw bestand zorgvuldig vóór u bestelt.</p></div>
<p>Contacteert u ons vóór wij beginnen drukken, dan doen wij altijd ons best om te helpen.</p>

<h3 class="plain">Als er iets misgaat</h3>
<p>Controleer uw bestelling bij ontvangst of afhaling en meld ons een probleem binnen
<strong>7 kalenderdagen</strong> — mail naar
<a href="mailto:shop@clickclickgraphics.com">shop@clickclickgraphics.com</a> met uw factuurnummer en
een foto.</p>
<p>Ligt de fout bij ons — een drukfout, een verkeerde specificatie, transportschade — dan herdrukken
wij het werk op onze kosten. Dit doet geen afbreuk aan uw wettelijke rechten als consument, die wij
niet beperken.</p>
<p>Wij kunnen niet herdrukken voor problemen in het aangeleverde bestand: een tikfout, een
afbeelding met lage resolutie, of kleuren die in het bestand zelf verkeerd waren.</p>

<h3 class="plain">Kleur</h3>
<p>Een scherm licht op, drukwerk niet. Kleine kleurverschillen tussen scherm en drukwerk zijn normaal
en vormen geen gebrek. Is exacte kleur belangrijk, vraag ons dan vooraf om een proefdruk.</p>

<h3 class="plain">Aansprakelijkheid</h3>
<p>Wij zijn aansprakelijk voor schade door onze eigen fout. Onze aansprakelijkheid is beperkt tot de
waarde van de betrokken bestelling, behalve waar de wet zo’n beperking niet toelaat — met name bij
overlijden, lichamelijk letsel of opzet.</p>

<h3 class="plain">Toepasselijk recht en geschillen</h3>
<p>Het Belgische recht is van toepassing. Wij lossen een probleem liever rechtstreeks op: schrijf
ons dus eerst. Consumenten kunnen zich ook richten tot de Consumentenombudsdienst, Koning Albert
II-laan 8, 1000 Brussel. Geschillen behoren tot de bevoegdheid van de rechtbanken van Brussel.</p>"""),
 ]}

if __name__ == "__main__":
    kit.write_all(CONTENT, os.path.dirname(os.path.abspath(__file__)))
