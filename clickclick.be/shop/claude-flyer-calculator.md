# Click Click — Flyer Calculator Rebuild Brief

This file is context for Claude Code. Read it in full before making any changes to the project.

---

## Project snapshot

**Click Click** is a Brussels-based print shop (the production side of a broader operation that also runs under `clickclickgraphics.com`). We produce:

- **Flyers** (A6–A3, short and medium runs)
- **Posters** (A2–A0, display work)
- **Cards** (business cards, invitations, cards)

The machines behind the shop are an **Konica Minolta C12010s** (digital press, including white toner on coloured stock) and 

## What exists today

A working calculator module with three separate pages:

see: clickclick.be/quote
- A **working billing/payment integration** — this is live and must not be broken

The landing page uses a CMYK-themed dark design (cyan, magenta, yellow accents on near-black), Outfit + Playfair Display fonts, and an animated CMYK-dot background. Styling should stay consistent with this aesthetic.

## What we want to build

A **guided quiz-style flow** that replaces the current "pick a calculator, then fill in specs" experience with a faster, friendlier sequence:

1. **What's it for?** → Flyer / Poster / Card (with visual cues, not just labels)
2. **What's the occasion or context?** → e.g. party, event, gallery show, corporate (this is mostly for tone and future marketing — it doesn't change pricing, but may filter the size options)
3. **How many?** → preset buttons (50 / 100 / 250 / 500 / 1000 / 2500, not adjustable)
4. **Size / paper** → minimal choices, only the profitable combinations (see "Commercial gating" below) > A6, A5, A4, A3
5. **Instant price** → shown live, with a clear CTA to order and pay
6. Need anything else, ask a detailed quote on info@clickclickgraphics.com

The flow should feel like **one page**, not a series of navigations. Think of it as a single-screen configurator where each step reveals the next. No page reloads.

## Hard constraints

1. **Do not touch the billing/payment integration.** It's already wired up and working. Read the existing code, understand the handoff (what data the payment flow expects — item description, price, currency, customer details), and ensure the new guided flow produces exactly the same payload at the end. If anything is unclear, ask before changing.

2. **The existing calculator pages stay in place** as fallback/backup while we build and test the new flow. The new guided flow lives at a new route (e.g. `/quote` or the root `/`), and the old pages remain accessible until we're confident in the replacement.

3. **Deploy target is `clickclick.be`** — a temporary domain for this early launch. The main brand site `clickclickgraphics.com` is being built separately by a colleague. Do not make changes that assume either domain structure permanently.

## Commercial gating (important — this is the whole point)

The calculator should only quote **profitable jobs**. Unprofitable or commodity jobs should either be hidden, routed to "request a quote", or priced deliberately high.

- **Flyer minimum quantity: 250.** Lower quantities eat margin on setup time.
- **Hide the cheapest commodity specs** — standard 135gsm gloss A5 in runs of 500 is exactly what commodity printers (drukwerkdeal etc.) compete on. Either remove these options or surface them only if the user explicitly wants them, at a price that reflects our actual cost.
- **Feature the specs where we have an edge:** heavier stocks (250gsm+), coloured board, white toner, unusual sizes, shaped cuts.
- **Anything bespoke** (die-cut shapes, layered cards, custom boxes, unusual sizes) → route to a "request quote" form rather than returning an instant number. We want a conversation for those jobs.

## Target customers (informs copy, not logic)

- **Flyers:** party promoters, event organisers, pop-up shops, student events, club nights. Copy should be casual and energetic.
- **Posters:** festivals, club nights, galleries, independent artists. Copy should feel creative and bold.
- **Cards:** weddings, luxury retail (Sablon, Avenue Louise, Dansaert), event agencies, restaurants, independent publishers. Copy should be premium and understated.

Keep copy short and direct. No corporate filler.

## Lead capture

Add an **email field between the final price and the payment step**. Even users who don't complete payment become leads. For the Brussels event/luxury segment, that list is valuable. Do not make the field blocking if the user is ready to pay immediately — it can be filled as part of the checkout details.

## Analytics

Add a lightweight privacy-friendly analytics script (Plausible or Umami — suggest which and why). We want to see:

- Which product is configured most often
- What quantities people pick
- Where people drop off in the flow
- Conversion rate from quote to order

No Google Analytics. No cookie banner required if we stick to privacy-friendly tools.
Mention privacy friendliness. 

## Tech notes

- Current stack is **plain HTML / CSS / JavaScript** — no framework. Keep it that way unless there's a strong reason not to. The whole thing is meant to be fast-loading and easy to maintain.
- Payment integration is already functional. Inspect how it currently receives data (URL params, form POST, JavaScript object — check the code) and preserve that contract exactly.
- Mobile-first. Most flyer/poster orders will come from Instagram traffic on phones.
- Deploy target is **Netlify** (drag-and-drop from the project folder). Nothing server-side unless the payment integration already requires it.

## Where to start

1. Read the existing html-pages, and the cards calculator file. Summarise the pricing logic for each product — the formulas, the options, the defaults.
2. Read the payment integration code. Document what data it expects at checkout.
3. Propose a single-page guided-flow structure (HTML skeleton + flow diagram) **before writing the full implementation**. We'll review that together first.
4. Only after the structure is agreed, build the new page, keeping the old pages untouched.

## Things to ask about before assuming

- The exact payment provider is mollie
- Whether VAT is already handled in the existing flow it is in the flow at the end. Calculations without vat, total is with VAT
- Whether there's an order confirmation email system and how it's triggered
- Whether any of the calculators currently produce a quote PDF or order summary

## Tone for interactions

This project belongs to a small print shop, not a software team. Prefer readable code over clever code. Prefer fewer files over more. Comment anything that isn't obvious, especially pricing constants and margin assumptions. When suggesting trade-offs, explain them in plain language.

---

*Last updated: before the first Claude Code session on this project. Update as decisions are made.*
