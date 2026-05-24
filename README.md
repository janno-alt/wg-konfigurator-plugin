# WG Konfigurator – WordPress-Plugin

Quiz-Wizard → KI-generiertes Videokonzept → PDF per E-Mail → Lead-Webhook ans CRM.
Komplett im eigenen WordPress, ohne n8n, ohne SaaS.

## Komponenten

| Schicht | Was | Wo |
|---|---|---|
| Frontend | React 18 Quiz (4 Schritte + Lead-Form) | `assets/quiz-app/` |
| Shortcode | `[wg_konfigurator]` für Elementor/Gutenberg | `includes/Frontend/Shortcode.php` |
| REST | `POST /wp-json/wg-konfigurator/v1/generate` | `includes/Rest/GenerateEndpoint.php` |
| KI | Google Gemini 2.5 Flash mit JSON-Schema | `includes/Services/GeminiClient.php` |
| Scraper | Readability auf der Kunden-Website | `includes/Services/WebsiteScraper.php` |
| Preis | Deterministische Range aus Quiz-Antworten | `includes/Services/PriceCalculator.php` |
| PDF | dompdf v3 + Lime-on-Dark Template | `includes/Services/PdfGenerator.php` |
| Mail | wp_mail + optionales Mittwald-SMTP | `includes/Services/Mailer.php` |
| Webhook | async (WP-Cron) mit HMAC + Retry | `includes/Services/WebhookSender.php` |
| Updates | GitHub-Releases via Plugin Update Checker | siehe `INSTALL.md` |

## Schnellstart

```bash
git clone git@github.com:janno-alt/wg-konfigurator-plugin.git wg-konfigurator
cd wg-konfigurator
composer install --no-dev
cd assets/quiz-app && npm install && npm run build
```

Dann ZIP packen (Wurzelverzeichnis = Plugin-Ordner) und in WP unter
**Plugins → Installieren → Plugin hochladen** einspielen.

## Konfiguration

WP-Admin → **Einstellungen → WG Konfigurator**:

- **Gemini API-Key** (https://aistudio.google.com)
- **PDF-Farben** (Default: Lime `#C2F21C` auf Dark `#141414`)
- **SMTP**: Mittwald-Hostdaten oder leer lassen (dann wp_mail Default)
- **Webhook**: erst Mock-URL (z. B. `https://webhook.site/<dein-uuid>`), später CRM-Endpoint
- **GitHub Token**: nur nötig wenn Repo privat

## Einbettung

Beliebiges Elementor-HTML-Widget, Gutenberg-Block oder Klassik-Editor:

```text
[wg_konfigurator]
[wg_konfigurator theme="light"]
```

## Webhook-Payload

Siehe [`docs/webhook-schema.md`](docs/webhook-schema.md).

## Lizenz

Proprietär – WG-Digital. Nicht zur Weitergabe ohne ausdrückliche Genehmigung.
