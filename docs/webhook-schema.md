# Webhook-Schema

`POST <Webhook-URL>` mit `Content-Type: application/json`.

## Headers

| Header | Inhalt |
|---|---|
| `X-WG-Idempotency` | UUID-v4 pro Lead. Bei Retries identisch. |
| `X-WG-Signature` | `sha256=<hex>` HMAC über den Body, sofern `webhook_secret` gesetzt. |
| `X-WG-Source` | `wg-konfigurator/<version>` |
| `User-Agent` | `WG-Konfigurator/<version>` |

## Body

```json
{
  "event": "konfigurator.completed",
  "idempotency_key": "0d2e2c5b-…",
  "generated_at": "2026-05-24T18:30:12+00:00",

  "lead": {
    "vorname": "Anna",
    "email": "anna@firma.de",
    "marketing_opt_in": true
  },

  "quiz": {
    "video_typ": "imagefilm",
    "drehtage": 2,
    "zeitrahmen": "flexibel",
    "branche": "Pflege / Gesundheit",
    "website": "https://pflegedienst-mueller.de",
    "ziel": "mehr Bewerbungen für Pflegekräfte-Stellen"
  },

  "berechnung": {
    "preis_min": 3340,
    "preis_max": 5340,
    "express_aufschlag": 0,
    "score": 85,
    "breakdown": {
      "video_typ": "imagefilm",
      "base_min": 2490,
      "base_max": 4490,
      "drehtage": 2,
      "per_day": 850,
      "extra": 850
    }
  },

  "ki_konzept": {
    "wirkungs_hypothese": "…",
    "story_skizze": "…",
    "empfohlene_protagonisten": ["…", "…"],
    "empfohlene_locations": ["…", "…"],
    "vorbereitungs_checkliste": ["…", "…"],
    "naechste_schritte": "…"
  },

  "tracking": {
    "msclkid": "…",
    "utm_source": "bing",
    "utm_medium": "cpc",
    "utm_campaign": "video-mitteldeutschland"
  },

  "pdf_url": "https://wg-digitalmarketing.de/wp-content/uploads/wg-konfigurator/konzept-anna-20260524-183012-a1b2c3.pdf"
}
```

## HMAC-Verifizierung (Empfänger-Seite)

```php
$secret    = 'das-gleiche-secret-wie-im-plugin';
$body      = file_get_contents('php://input');
$expected  = 'sha256=' . hash_hmac('sha256', $body, $secret);
$received  = $_SERVER['HTTP_X_WG_SIGNATURE'] ?? '';
if (!hash_equals($expected, $received)) {
    http_response_code(401);
    exit('invalid signature');
}
```

## Retries

Der Plugin versucht 3-mal mit Backoff (2 s, 6 s).
Antworte daher idempotent — der `X-WG-Idempotency`-Header hilft dabei.

## Mock-Testing

1. Auf https://webhook.site einen neuen UUID-Endpoint erzeugen.
2. URL in **WG Konfigurator → Webhook-URL** eintragen.
3. Test-Submission machen → Body wird im webhook.site-Dashboard sichtbar.
