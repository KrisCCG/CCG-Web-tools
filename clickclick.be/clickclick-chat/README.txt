tiegel.be — Chat (SANDBOX)
================================

A fork of `tiegel-request-bot/` that hooks the chat into the existing
online shop pricing engine at clickclick.be/calculator/calc.php.

When the customer's specs fit the shop catalog (flyer / card / poster
on Multiart / Biotop / Recystar at stocked gsm values, ≤A3, ≤10000),
the bot calls a tool that gets an instant price + a deep-link to the
shop. The customer can either order online directly, or keep refining.

When the specs DON'T fit the shop (brochures, books, banners, Arena
paper, Color 1802, custom finishes, etc.) the bot continues the normal
human-quote flow — Request emailed to the team.

This is a SANDBOX. The production bot at tiegel.be/request/ is
untouched. The chat lives in parallel at tiegel.be/chat/.
If it works, promote it. If not, delete it.


─── Folder map ──────────────────────────────────────────────────────────

  tiegel-chat/
    chat/                  → uploads to tiegel.be/chat/   (frontend)
      index.html               chat UI + shop-card render
      lib/i18n.js              FR/NL/EN labels (shop terminology included)
      logo.png
      .htaccess

    chat-api/              → uploads to tiegel.be/chat-api/  (backend)
      start.php / chat.php / submit.php
      system_prompt.php        includes shop-integration rules
      secrets.php              SAME keys as production (gitignored)
      cleanup.php / retention.php   crons
      lib/
        anthropic.php          adds check_shop_pricing tool definition
        shop.php               NEW — server-to-server cURL to calc.php
        signing.php audit.php ratelimit.php conversation.php vies.php turnstile.php


─── How the shop integration works ──────────────────────────────────────

1. Customer chats, bot gathers product + qty + size + paper
2. Bot checks if specs fit the shop catalog. If yes → calls the
   `check_shop_pricing` tool.
3. chat.php intercepts the tool call, runs an HTTP GET to
   clickclick.be/calculator/calc.php with the params.
4. Tool result (price + ready date + deep link) is fed back to Claude.
5. Claude generates a follow-up reply: "Here's the price from our shop
   — does this look right or want to change something?"
6. Frontend renders the bot's reply + a SHOP CARD with the price,
   ready date, and an "Order online" button.
7. Customer can: click "Order online" (hand-off to clickclick.be),
   refine specs (bot loops back to step 2), or proceed with the normal
   Request flow.

For non-shop products (brochures, banners, Arena paper, custom finishes,
etc.) the bot doesn't call the tool — normal Request flow → email.


─── First deploy on Infomaniak ──────────────────────────────────────────

This folder shares SFTP credentials with the production project. Files
upload to tiegel.be/chat/ and tiegel.be/chat-api/ in parallel
to production's /request/ and /request-api/.

1. secrets.php has the same Anthropic key + signing key as production.
   No changes needed.
2. Right-click `chat/` in VS Code → SFTP: Upload Folder.
   Right-click `chat-api/` → SFTP: Upload Folder.
3. Open https://tiegel.be/chat/ → say "500 business cards on
   Biotop 300 A6" → bot should call check_shop_pricing, return a price,
   and render the shop card with the order-online button.


─── If the experiment fails ─────────────────────────────────────────────

  # local cleanup
  rm -rf ~/Desktop/Node-CCG/tiegel-chat

  # server cleanup
  ssh eu3mfw_tiegel@eu3mfw.ftp.infomaniak.com \
    "rm -rf sites/tiegel.be/chat sites/tiegel.be/chat-api"

Production at tiegel.be/request/ keeps running, completely untouched.


─── If the experiment succeeds ──────────────────────────────────────────

Promote: copy the shop integration back into tiegel-request-bot/ and
deploy to /request/. Optionally keep /chat/ as a permanent A/B test.


─── Optional: prefill the shop URL ──────────────────────────────────────

The shop deep link from `lib/shop.php` includes query parameters
(product, paper_key, size, qty, sides, express, delivery). The shop
frontend at clickclick.be/flyers/index.html does NOT currently read
these — so the customer lands on the shop home and reconfigures.

To enable prefill: add a small JS block to flyers/index.html that reads
URLSearchParams on load and applies them to the state. ~20 lines, no
big lift. Worth doing once chat is proven valuable.


─── Cost ───────────────────────────────────────────────────────────────

Same Anthropic billing as production. Each shop-eligible request adds
ONE extra Anthropic round-trip (~€0.005) for the tool-result reply.
At realistic load (a few shop-eligible requests/day) that's pennies
extra — recovered many times over if even one customer self-checks-out.
