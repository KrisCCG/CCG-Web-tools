tiegel.be — Request bot
================================

AI chatbot for the /request/ page on tiegel.be. Greets a customer in their
language (FR / NL / EN), asks the right questions about their print job,
and emails a structured Request to info@clickclickgraphics.com.

Self-contained: static HTML frontend + PHP backend. No Node, no Composer,
no framework. Mirrors the deploy shape of clickclick.be/flyers and
clickclick.be/print-calculator, but lives entirely under /tiegel/ in this
repo and has zero shared files with those projects.


─── Folder map ────────────────────────────────────────────────────────────

  tiegel/
    request/                  uploads to tiegel.be/request/  (frontend)
      index.html              single-file chat UI
      lib/i18n.js             FR/NL/EN labels for UI chrome
      .htaccess               disables WP rewrites, CSP, optional auth gate

    request-api/              uploads to tiegel.be/request-api/  (backend)
      start.php               POST — opens a conversation
      chat.php                POST — continues a conversation
      submit.php              POST — finalises Request, emails the team
      cleanup.php             cron — prunes stale runtime files (daily 5am)
      retention.php           cron — GDPR PII sweep (weekly Sunday 5am)
      system_prompt.php       THE BRAIN. Plain-English bot instructions.
      secrets.php             API keys + config. GITIGNORED. Copy from .example.
      secrets.example.php     Template — copy and fill in for first deploy.
      lib/
        anthropic.php         Anthropic Messages API cURL wrapper
        signing.php           HMAC sign/verify for tokens + turns
        ratelimit.php         Per-IP sliding-window flat-file limiter
        audit.php             PII-redacting JSONL logger
        conversation.php      Signed-history <-> Anthropic-history glue
        turnstile.php         Cloudflare Turnstile stub (disabled by default)
      requests/               persisted JSON Request drafts (gitignored)
      logs/                   audit JSONL (gitignored, gzipped after 30d)
      .ratelimit/             per-IP rate-limit buckets (gitignored)
      .htaccess               denies secrets+lib+state, gates cron URLs


─── First deploy on Infomaniak ────────────────────────────────────────────

1. Get an Anthropic API key
     https://console.anthropic.com/
     Set a daily spend cap of €5 (Settings → Limits). This is the real
     cost ceiling — without it a determined attacker could rack up bills.

2. Generate a signing key (locally, anywhere PHP runs)
     php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"

3. Copy secrets.example.php to secrets.php and fill in:
     - anthropic_api_key   (sk-ant-api03-...)
     - signing_key         (the 64-char hex from step 2)
     - notify_email        (info@clickclickgraphics.com — already set)
     - from_email          (noreply@tiegel.be — must align with SPF)
     - reply_to_email      (info@clickclickgraphics.com)

4. (Optional) Generate an .htpasswd for the cron endpoints
     htpasswd -c request-api/.htpasswd cron
     Then in request-api/.htaccess, replace REPLACE_WITH_INFOMANIAK_UUID
     with the real Infomaniak path prefix.

5. Upload via SFTP / Infomaniak file manager:
     tiegel/request/      ->  /sites/tiegel.be/request/
     tiegel/request-api/  ->  /sites/tiegel.be/request-api/

6. Smoke test (read-only, no Anthropic spend yet):
     curl https://tiegel.be/request-api/secrets.php            -> 403
     curl https://tiegel.be/request-api/lib/anthropic.php      -> 403
     curl https://tiegel.be/request-api/requests/              -> 403
     curl -X POST https://tiegel.be/request-api/start.php \
          -H "Content-Type: application/json" \
          -d '{"message":""}'                                    -> 400

7. End-to-end test (~€0.01 of Anthropic):
     Open https://tiegel.be/request/ in a browser, have a short conversation
     in French ("J'aimerais 500 cartes de visite"), confirm a summary
     appears and clicking the button delivers an email to
     info@clickclickgraphics.com.

8. Schedule the crons in Infomaniak control panel
     (Manager → Sites → tiegel.be → Tools → Task Scheduler):
       Daily   05:00   curl -s -u cron:PWD https://tiegel.be/request-api/cleanup.php
       Weekly  Sun 05:00  curl -s -u cron:PWD https://tiegel.be/request-api/retention.php

9. SPF / DKIM (mandatory for deliverability)
     - SPF for tiegel.be must include Infomaniak's mail relay
       (include:_spf.infomaniak.ch — confirm exact value in Mail panel)
     - DKIM: enable for tiegel.be in the Infomaniak mail panel
     - Test with https://www.mail-tester.com — score must be ≥ 9/10
       before going live, otherwise emails will land in spam.

10. (Optional) Replace the existing /quotes/ WP page
      Add a 301 redirect in the WP root .htaccess:
        Redirect 301 /quotes/ /request/


─── Updating the bot's behaviour ──────────────────────────────────────────

The bot's "brain" is `request-api/system_prompt.php`. To change what the
bot asks, how it greets, what it knows about your products, or how it
handles edge cases — just edit that file and re-upload. No code change,
no rebuild, no deploy needed.

After the first ~20 real conversations, read the logs (request-api/logs/)
and look for patterns the bot handled badly. Update the prompt accordingly.


─── Cost ──────────────────────────────────────────────────────────────────

  Anthropic Haiku 4.5 w/ prompt caching:
     - Pilot / testing:   €1–3 total
     - Production normal: €3–15 / month  (5–20 conversations / day)
     - Worst case attack: capped at €5/day = €150/month hard ceiling

  Everything else (hosting, domain, no captcha, JSON storage, mail): €0.


─── Anti-abuse posture (no captcha) ───────────────────────────────────────

Per the project plan, there is no captcha — first impression must be
friction-free. Defence is purely server-side:

  - Honeypot field            (silent reject)
  - Time-on-page check        (first message must arrive >1.5s after load)
  - Per-IP rate limits        (start 5/h, chat 30/h, submit 3/day)
  - 25-turn conversation cap
  - max_tokens=1024 per call
  - Daily Anthropic spend cap (€5/day)
  - HMAC signing on token + every history turn

If abuse ever happens, flip 'turnstile_enabled' in secrets.php and add a
Cloudflare Turnstile secret. The frontend will need a small edit to load
the widget (about 10 lines) — see lib/turnstile.php header for details.


─── Privacy / GDPR ────────────────────────────────────────────────────────

  - Conversation text goes to Anthropic (US sub-processor) — must be in
    the tiegel.be privacy policy before launch.
  - All logs PII-redacted at write time (lib/audit.php).
  - Persisted Requests scrubbed at 90 days, deleted at 2 years
    (retention.php weekly cron).
  - No cookies, no localStorage in the frontend → no consent banner needed
    for the chat itself.
