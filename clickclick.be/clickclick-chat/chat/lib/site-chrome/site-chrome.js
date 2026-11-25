/**
 * Click Click site header + footer for the stand-alone apps (shop /shop/, chat /chat/).
 *
 * Same content and look as the WordPress site. Texts and menus are below — when a menu
 * item or a text changes on the WordPress site, change it here too (one copy per app:
 * shop/shop/lib/site-chrome/ and clickclick-chat/chat/lib/site-chrome/).
 *
 * How it plugs in:
 *   <link rel="stylesheet" href="lib/site-chrome/site-chrome.css">
 *   <div id="ccs-header"></div>
 *   <script src="lib/site-chrome/site-chrome.js"></script>   ← right after the header div
 *   … app …
 *   <div id="ccs-footer"></div>
 *
 * The language switch renders as <div class="langs"><button data-lang="…"> — the same
 * markup the apps already use — so the apps' own language code keeps working unchanged.
 * The header/footer follow whichever button the app marks as .on.
 */
( function () {
	// Where the WordPress site lives. Until the real launch it is the password-protected
	// preview; on launch day set this to '' (the site then lives on clickclick.be itself).
	var SITE = 'https://preview.clickclick.be';
	window.CC_SITE = SITE;

	var BASE = ( document.currentScript && document.currentScript.src.replace( /site-chrome\.js.*$/, '' ) ) || 'lib/site-chrome/';

	var MENU = {
		en: [ [ 'Printing', '/en/printing/' ], [ 'Signage', '/en/signage/' ], [ 'Your project', '/en/your-project/' ], [ 'A good file?', '/en/good-file/' ], [ 'Contact', '/en/contact/' ] ],
		nl: [ [ 'Drukken', '/nl/drukken/' ], [ 'Signalitiek', '/nl/signalitiek/' ], [ 'Jouw project', '/nl/jouw-project/' ], [ 'Een goed bestand?', '/nl/goed-bestand/' ], [ 'Contact', '/nl/contacteer-ons/' ] ],
		fr: [ [ 'Impression', '/fr/impression/' ], [ 'Signalétique', '/fr/signaletique/' ], [ 'Votre projet', '/fr/votre-projet/' ], [ 'Un bon fichier ?', '/fr/bon-fichier/' ], [ 'Contact', '/fr/contactez-nous/' ] ]
	};
	var HOME = { en: '/en/', fr: '/fr/', nl: '/nl/' };
	var LEGAL = { en: '/en/legal/', fr: '/fr/mentions-legales/', nl: '/nl/juridisch/' };
	var PAPERS = { en: '/en/paper-guide/', fr: '/fr/guide-des-papiers/', nl: '/nl/papiergids/' };

	var T = {
		info_bar: {
			en: 'By appointment only · Mon–Fri 9:00–12:30 · 13:30–18:00 · Aalststraat 7–11, 1000 Brussels',
			fr: 'Uniquement sur rendez-vous · Lun–ven 9h00–12h30 · 13h30–18h00 · Rue d’Alost 7–11, 1000 Bruxelles',
			nl: 'Enkel op afspraak · Ma–vr 9u00–12u30 · 13u30–18u00 · Aalststraat 7–11, 1000 Brussel'
		},
		logo_alt: { en: 'Click Click Graphics — home', fr: 'Click Click Graphics — accueil', nl: 'Click Click Graphics — home' },
		menu: { en: 'Menu', fr: 'Menu', nl: 'Menu' },
		hours_title: { en: 'Opening hours', fr: 'Heures d’ouverture', nl: 'Openingsuren' },
		hours_text: {
			en: 'Monday to Friday<br>9:00–12:30 and 13:30–18:00<br>Closed on Saturday and Sunday.<br>Workshop visits by appointment only.',
			fr: 'Du lundi au vendredi<br>9h00–12h30 et 13h30–18h00<br>Fermé le samedi et le dimanche.<br>Visites de l’atelier uniquement sur rendez-vous.',
			nl: 'Maandag tot vrijdag<br>9u00–12u30 en 13u30–18u00<br>Gesloten op zaterdag en zondag.<br>Bezoek aan het atelier enkel op afspraak.'
		},
		pickup_title: { en: 'Picking up your order', fr: 'Retirer votre commande', nl: 'Je bestelling afhalen' },
		pickup_text: {
			en: 'Shop orders can be picked up during opening hours. Just let us know when you’re coming — we’re a small team, sometimes at the presses or out on a job.',
			fr: 'Les commandes de la boutique se retirent pendant les heures d’ouverture. Prévenez-nous simplement de votre passage — nous sommes une petite équipe, parfois aux presses ou en route pour un chantier.',
			nl: 'Bestellingen uit de shop kun je afhalen tijdens de openingsuren. Laat ons gewoon weten wanneer je komt — we zijn een klein team, soms aan de persen of op verplaatsing.'
		},
		contact_title: { en: 'Contact', fr: 'Contact', nl: 'Contact' },
		contact_text: {
			en: 'Simple orders: the <a href="%1">Shop</a>.<br>Everything else: the <a href="%2">request chat</a> — it sends us one complete request.<br>info@clickclickgraphics.com',
			fr: 'Commandes simples : la <a href="%1">boutique</a>.<br>Tout le reste : le <a href="%2">chat de demande</a> — il nous envoie une demande complète.<br>info@clickclickgraphics.com',
			nl: 'Eenvoudige bestellingen: de <a href="%1">shop</a>.<br>Al de rest: de <a href="%2">aanvraagchat</a> — die stuurt ons één volledige aanvraag.<br>info@clickclickgraphics.com'
		},
		here_title: { en: 'We are here', fr: 'Nous sommes ici', nl: 'Hier vind je ons' },
		maps: { en: 'Open in maps', fr: 'Ouvrir dans une carte', nl: 'Open in kaart' },
		follow_title: { en: 'Follow', fr: 'Suivez-nous', nl: 'Volg ons' },
		legal: { en: 'Legal and privacy', fr: 'Mentions légales et vie privée', nl: 'Juridisch en privacy' },
		papers: { en: 'Paper guide', fr: 'Guide des papiers', nl: 'Papiergids' }
	};
	function t( k, l ) { return ( T[ k ] && ( T[ k ][ l ] || T[ k ].en ) ) || k; }

	var BURGER = '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M4 7.5h16V9H4zM4 15h16v1.5H4z"/></svg>';

	function headerHTML() {
		return '' +
			'<div class="ccs-info"><div class="ccs-wrap">' +
				'<p data-ccs="info_bar"></p>' +
				'<div class="langs" role="group" aria-label="Language">' +
					'<button type="button" data-lang="nl">NL</button><span aria-hidden="true">·</span>' +
					'<button type="button" data-lang="fr">FR</button><span aria-hidden="true">·</span>' +
					'<button type="button" data-lang="en">EN</button>' +
				'</div>' +
			'</div></div>' +
			'<div class="ccs-main"><div class="ccs-wrap">' +
				'<a class="ccs-logo" data-ccs-href="home"><img src="' + BASE + 'logo.svg" alt="" data-ccs-alt="logo_alt" width="260"></a>' +
				'<button type="button" class="ccs-burger" aria-expanded="false" aria-controls="ccs-nav" data-ccs-label="menu">' + BURGER + '</button>' +
				'<nav class="ccs-nav" id="ccs-nav"><ul></ul></nav>' +
			'</div></div>';
	}
	function footerHTML() {
		function col( icon, title, body ) {
			return '<div class="ccs-col"><img src="' + BASE + 'icons/' + icon + '.svg" alt=""><p class="ccs-kicker" data-ccs="' + title + '"></p><p ' + body + '></p></div>';
		}
		return '' +
			'<div class="ccs-wrap">' +
				'<div class="ccs-cols">' +
					col( 'check', 'hours_title', 'data-ccs="hours_text"' ) +
					col( 'arrow-down', 'pickup_title', 'data-ccs="pickup_text"' ) +
					col( 'envelope', 'contact_title', 'data-ccs="contact_text"' ) +
					col( 'map', 'here_title', 'data-ccs="address"' ) +
					col( 'heart', 'follow_title', 'data-ccs="social"' ) +
				'</div>' +
				'<hr>' +
				'<div class="ccs-bottom"><p>© Click Click Graphics SRL · Brussel - Bruxelles</p><p data-ccs="links"></p></div>' +
			'</div>';
	}

	var header = document.getElementById( 'ccs-header' );
	if ( ! header ) {
		return;
	}
	header.classList.add( 'ccs' );
	header.setAttribute( 'role', 'banner' );
	header.innerHTML = headerHTML();

	var burger = header.querySelector( '.ccs-burger' );
	var nav = header.querySelector( '.ccs-nav' );
	burger.addEventListener( 'click', function () {
		var open = nav.classList.toggle( 'is-open' );
		burger.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
	} );

	var footer = null;
	function ensureFooter() {
		if ( footer ) {
			return footer;
		}
		footer = document.getElementById( 'ccs-footer' );
		if ( footer ) {
			footer.classList.add( 'ccs', 'ccs-footer' );
			footer.setAttribute( 'role', 'contentinfo' );
			footer.innerHTML = footerHTML();
		}
		return footer;
	}

	function currentLang() {
		var on = header.querySelector( '.langs button.on' );
		var l = ( on && on.getAttribute( 'data-lang' ) ) || ( document.documentElement.lang || 'en' ).slice( 0, 2 );
		return MENU[ l ] ? l : 'en';
	}

	var rendered = null;
	function render() {
		var l = currentLang();
		if ( l === rendered && ( footer || ! document.getElementById( 'ccs-footer' ) ) ) {
			return;
		}
		rendered = l;
		var shop = '/shop/?lang=' + l, chat = '/chat/#lang=' + l;
		var root = [ header, ensureFooter() ];
		var V = {
			address: 'Aalststraat 7–11 · Rue d’Alost 7–11<br>Brussel 1000 Bruxelles<br><a href="https://www.openstreetmap.org/search?query=Rue%20d%27Alost%207%2C%201000%20Bruxelles">' + t( 'maps', l ) + '</a>',
			social: '<a href="https://www.instagram.com/clickclickgraphics" target="_blank" rel="noopener noreferrer">Instagram @clickclickgraphics</a><br><a href="https://www.facebook.com/clickclickgraphics" target="_blank" rel="noopener noreferrer">Facebook clickclickgraphics</a>',
			links: '<a href="' + SITE + LEGAL[ l ] + '">' + t( 'legal', l ) + '</a> · <a href="' + SITE + PAPERS[ l ] + '">' + t( 'papers', l ) + '</a>',
			contact_text: t( 'contact_text', l ).replace( '%1', shop ).replace( '%2', chat )
		};
		root.forEach( function ( r ) {
			if ( ! r ) {
				return;
			}
			Array.prototype.forEach.call( r.querySelectorAll( '[data-ccs]' ), function ( el ) {
				var k = el.getAttribute( 'data-ccs' );
				el.innerHTML = V[ k ] !== undefined ? V[ k ] : t( k, l );
			} );
		} );
		header.querySelector( '[data-ccs-href="home"]' ).href = SITE + HOME[ l ];
		header.querySelector( '[data-ccs-alt="logo_alt"]' ).alt = t( 'logo_alt', l );
		burger.setAttribute( 'aria-label', t( 'menu', l ) );
		var here = location.pathname;
		header.querySelector( '.ccs-nav ul' ).innerHTML = MENU[ l ].map( function ( m ) {
			return '<li><a href="' + SITE + m[ 1 ] + '"' + ( here === m[ 1 ] ? ' aria-current="page"' : '' ) + '>' + m[ 0 ] + '</a></li>';
		} ).join( '' );
	}

	render();
	// Follow the app's language: it toggles .on on the buttons.
	new MutationObserver( render ).observe( header.querySelector( '.langs' ), { subtree: true, attributes: true, attributeFilter: [ 'class' ] } );
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', render );
	}
} )();
