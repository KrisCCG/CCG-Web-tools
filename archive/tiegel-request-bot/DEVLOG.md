# Devlog — tiegel.be Request bot

A running log of what's been built, what's been tuned, and what's next.
Newest entries on top.

---

## 2026-05-14 — Day 1: build, deploy, iterate

### Built from scratch

- **Frontend** (`request/`) — static chat UI matching the calculator's design
  language (cream + indigo, Space Grotesk, thick black borders, hard shadows).
  Vanilla JS, single-file, no framework.
- **Backend** (`request-api/`) — PHP endpoints on Infomaniak shared hosting.
  - `start.php` / `chat.php` / `submit.php` — conversation flow
  - `cleanup.php` / `retention.php` — daily + weekly crons
  - `system_prompt.php` — the bot's brain
  - `lib/anthropic.php` — Anthropic Messages API wrapper (cURL, no SDK)
  - `lib/signing.php` — HMAC sign/verify for conversation tokens + history
  - `lib/conversation.php` — signed-history ↔ Anthropic-history glue
  - `lib/ratelimit.php` — per-IP flat-file sliding window
  - `lib/audit.php` — PII-redacting JSONL logger
  - `lib/vies.php` — EU VAT registry lookup
  - `lib/turnstile.php` — stubbed Cloudflare anti-abuse (off by default)
- **Live at** https://tiegel.be/request/

### Tuned over the day

- **Tone & flow** — restructured into two distinct phases:
  - Phase 1 (quote essentials, fast — 6–10 exchanges target)
  - Phase 2 (file — gentle, customer's call to share now / send later / get help)
- **Binding logic** — saddle-stitched ≤40pg, perfect-bound 32–250pg,
  thread-sewn 32–250pg premium, hardcover >250pg. Page count always ÷4.
- **Sides** — books/brochures default to double-sided; bot no longer asks.
- **Soft / hard cover** — bot asks when perfect-bound or thread-sewn.
- **VAT validation** — VIES integration; registered name + address surface
  on their own rows in the team email.
- **Postal address** — always captured when offered, never asked unprompted.
- **Competitor quotes** — bot listens for "I got X elsewhere"-style cues
  and politely asks for a copy. Yellow-flagged row in the team email.
- **Customer-led shortcut** — "send what you have" recognised in
  EN / FR / NL → bot calls submit_request immediately.
- **Skip button** — small ghost button in the chat card, appears after
  first bot reply. Sends a "skip the rest" message in the conversation's
  language.
- **Edit button** — clears the summary panel, drops the previous (stale)
  bot bubble, shows "What would you like to change?" in the conversation
  language.
- **Email** — From `noreply@clickclickgraphics.com` (real mailbox);
  Reply-To = customer; `-f` envelope sender set for SPF alignment.

### Bugs fixed

- Bot asking customers to confirm their language → prompt now hard-bans
  this; auto-detect from first message only.
- Bot replying "I'm sending this now" without calling the submit_request
  tool → prompt now explicitly forbids that text and pairs every "click
  SEND" reference with an actual tool call.
- Cross-domain From (`noreply@tiegel.be`) silently dropped by Infomaniak →
  switched to a real mailbox on `clickclickgraphics.com`.
- VS Code SFTP "permission denied" with site-specific FTP account →
  switched to the master `eu3mfw_system` account (same as the calculator).

### Operational

- **Model**: Claude Sonnet 4.5 (`claude-sonnet-4-5-20250929`).
  Haiku 4.5 was the initial choice but Sonnet handles the prompt's
  nuance noticeably better. Falls into prompt-caching too (1024-token
  minimum) so cost difference is small.
- **Daily spend cap**: €5/day in the Anthropic console.
- **Trusted IPs**: dev machine added to `secrets.php` to bypass rate limits.
- **Git**: repo published at https://github.com/KrisCCG/tiegel-request-bot
  (private). `.gitignore` excludes `secrets.php`, `.vscode/sftp.json`,
  customer JSONs, logs, ratelimit buckets.

---

## Tomorrow

- **Paper choices deep-dive** — get the bot fluent in real paper weights
  Click Click carries (coated / uncoated grades, common gsm options, when
  to recommend what). Will need a short conversation with Kris to extract
  the real catalog.
- More real-conversation testing → tune any rough edges that surface.
- Outstanding from before launch:
  - 301 redirect `/quotes/` → `/request/` in the WP root `.htaccess`
  - Schedule the two crons in Infomaniak Task Scheduler
    (cleanup.php daily 5am, retention.php Sunday 5am)
  - Update tiegel.be privacy policy to list Anthropic (US), Cloudflare,
    and VIES (EU) as sub-processors
  - Update `/a-good-file/` WP page to mention Fogra 51/52 (currently Fogra 39)

---

## Reference

- **Plan**: `/Users/clickclick/.claude/plans/on-our-website-tiegel-be-sequential-muffin.md`
- **Project root**: `~/Desktop/Node-CCG/tiegel-request-bot/`
- **Live URL**: https://tiegel.be/request/
- **Deploy host**: `eu3mfw.ftp.infomaniak.com`
- **Deploy path**: `/home/clients/bb59b9bb68f9d5f0bbd421f23f3ae8ed/sites/tiegel.be/`
- **Deploy method**: VS Code SFTP extension (right-click → Upload Folder)
