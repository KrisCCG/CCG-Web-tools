// UI chrome strings — only the static labels users see around the chat itself.
// The bot's own messages translate themselves (Claude does it via the system
// prompt's LANGUAGE rule). So this file is intentionally tiny.

window.I18N = {
  fr: {
    page_title:        "Demander un devis — Click Click",
    intro_heading:     "Bonjour 👋",
    intro_body:        "Décrivez-nous votre projet d'impression. Notre assistant vous guidera et notre équipe vous répondra dans la journée.",
    email_alt:         "Vous préférez par e-mail, vous savez déjà ce que vous voulez, ou vous voulez nous envoyer un fichier ?\nEnvoyez votre demande à",
    privacy_notice:    "Cette conversation utilise l'IA (Anthropic, États-Unis) pour préparer votre demande, qui sera envoyée à Click Click.\nLogs conservés 90 jours puis anonymisés.",
    placeholder:       "Écrivez votre message…",
    send:              "Envoyer",
    thinking:          "L'assistant réfléchit…",
    summary_heading:   "Récapitulatif de votre demande",
    submit_button:     "Envoyer ma demande à Click Click",
    submit_sending:    "Envoi en cours…",
    success_heading:   "Demande envoyée ✓",
    success_body:      "Merci ! Notre équipe vous répondra dans la journée à l'adresse e-mail que vous avez fournie.",
    err_network:       "Problème de connexion. Réessayez dans un instant.",
    err_too_fast:      "Prenez un instant avant d'envoyer.",
    err_long_msg:      "Message trop long — résumez si possible.",
    err_generic:       "Une erreur s'est produite. Veuillez réessayer.",
    edit_request:      "Modifier ma demande",
    edit_prompt:       "Bien sûr — que souhaitez-vous modifier ?",
    skip_hint:         "Vous en avez assez ?",
    skip_button:       "Envoyer maintenant",
    skip_message:      "Envoyez ce que vous avez, s'il vous plaît. Sautez les questions restantes.",
  },
  nl: {
    page_title:        "Vraag een offerte aan — Click Click",
    intro_heading:     "Hallo 👋",
    intro_body:        "Vertel ons over uw drukwerkproject. Onze assistent helpt u stap voor stap, en ons team antwoordt binnen één werkdag.",
    email_alt:         "Liever per e-mail, weet u al wat u wilt, of wilt u ons een bestand sturen?\nStuur uw aanvraag naar",
    privacy_notice:    "Dit gesprek gebruikt AI (Anthropic, VS) om uw aanvraag op te stellen, die vervolgens naar Click Click wordt gemaild.\nLogs worden 90 dagen bewaard en daarna geanonimiseerd.",
    placeholder:       "Typ uw bericht…",
    send:              "Versturen",
    thinking:          "De assistent denkt na…",
    summary_heading:   "Overzicht van uw aanvraag",
    submit_button:     "Mijn aanvraag naar Click Click sturen",
    submit_sending:    "Bezig met versturen…",
    success_heading:   "Aanvraag verzonden ✓",
    success_body:      "Bedankt! Ons team neemt binnen één werkdag contact op via het opgegeven e-mailadres.",
    err_network:       "Verbindingsprobleem. Probeer het zo opnieuw.",
    err_too_fast:      "Wacht even voor u verstuurt.",
    err_long_msg:      "Bericht is te lang — kort het in indien mogelijk.",
    err_generic:       "Er ging iets mis. Probeer het opnieuw.",
    edit_request:      "Mijn aanvraag aanpassen",
    edit_prompt:       "Natuurlijk — wat wilt u aanpassen?",
    skip_hint:         "Genoeg vragen?",
    skip_button:       "Nu versturen",
    skip_message:      "Verstuur wat u nu heeft, alstublieft. Sla de resterende vragen over.",
  },
  en: {
    page_title:        "Request a Quote — Click Click",
    intro_heading:     "Hi 👋",
    intro_body:        "Tell us about your print project. Our assistant will walk you through it, and our team will reply within one business day.",
    email_alt:         "Prefer email, you already know what you want, or you want to send us a file?\nSend your Request to",
    privacy_notice:    "This chat uses AI (Anthropic, US) to prepare your Request, which is then emailed to Click Click.\nLogs are kept 90 days, then anonymised.",
    placeholder:       "Type your message…",
    send:              "Send",
    thinking:          "Assistant is thinking…",
    summary_heading:   "Summary of your Request",
    submit_button:     "Send my Request to Click Click",
    submit_sending:    "Sending…",
    success_heading:   "Request sent ✓",
    success_body:      "Thank you! Our team will get back to you within one business day at the email address you provided.",
    err_network:       "Connection problem. Try again in a moment.",
    err_too_fast:      "Please take a moment before sending.",
    err_long_msg:      "Message is too long — please shorten it.",
    err_generic:       "Something went wrong. Please try again.",
    edit_request:      "Edit my Request",
    edit_prompt:       "Sure — what would you like to change?",
    skip_hint:         "Had enough questions?",
    skip_button:       "Send now",
    skip_message:      "Please send what you have so far. Skip any remaining questions.",
  },
};

window.detectLanguage = function () {
  const stored = (location.hash.match(/lang=([a-z]{2})/) || [])[1];
  if (stored && window.I18N[stored]) return stored;
  const nav = (navigator.language || 'en').slice(0, 2).toLowerCase();
  if (window.I18N[nav]) return nav;
  // Belgian default: French is the more common print-shop language in Brussels.
  return 'fr';
};
