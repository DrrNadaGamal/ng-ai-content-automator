# NG AI Content Automator

A keyword queue that writes article drafts with your own Gemini API key. A person approves every article before it goes live.

## What it does
- Queue of keywords or topics (one per line, with language and optional notes). Duplicates are refused. Max 200 items.
- "Write draft" creates a normal WordPress post with status Draft. The plugin never publishes by itself: publishing needs the "Approve and publish" button (or the normal editor) by a user who can publish posts.
- Optional hourly auto-draft with a daily cap (off by default). Auto mode also creates drafts only.
- Generated HTML is sanitized (scripts, event handlers and H1 removed). Replies that are not valid, or too short, are rejected and nothing is created.
- The prompt asks for no invented statistics, quotes or sources, and lists recent titles to avoid repeats.
- Your key: define `NGACA_KEY` in wp-config.php (recommended), or save it in settings (stored encrypted, never shown again). It is sent as a header, never in the URL, and is not written into error messages.
- Languages: English, Arabic, French, German for the interface (FR and DE are machine translations and need review) and for the articles.

## Where
Content Automator in the admin menu.

## Tested
56 checks in WordPress Playground (WP latest, PHP 8.2) against a simulated Gemini reply: queue rules, prompt, parsing and sanitizing, draft-only creation, failures (bad JSON, short text, HTTP 500, transport error, bad key, no key), retry, approval rules, cron cap, escaping, permission and nonce checks.

## Honest limits
- Not tested against the live Gemini API (no key was available). The request shape follows the public docs. The default model name `gemini-flash-latest` is not verified, so it is a setting.
- AI text can contain mistakes. Read and fact-check every draft before approving. Medical, legal and financial topics need extra care.
- No images, no SEO fields, no internal links in drafts.
- Hourly auto-draft relies on WP-Cron, which runs when the site gets visits.

Product direction, scope and delivery: Nada Gamal
