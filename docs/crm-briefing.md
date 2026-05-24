# Briefing: CRM-Webhook-Empfänger für WG-Konfigurator

**Adressat:** KI-Agent / Entwickler:in, der/die am WG-CRM arbeitet.
**Ziel:** Einen HTTP-Endpoint im CRM bauen, der Leads aus dem WordPress-Plugin
„WG Konfigurator" empfängt, verifiziert und persistiert.

---

## 1) Was du baust

Einen einzigen REST-Endpoint im CRM:

```
POST  /api/webhooks/wg-konfigurator
Content-Type: application/json
```

Der Endpoint:
1. **Verifiziert HMAC-Signatur** (Anti-Spoofing).
2. **Prüft Idempotency-Key** (gleicher Lead darf nicht doppelt angelegt werden).
3. **Persistiert** Lead, Quiz-Antworten, KI-Konzept, Preis-Range, Tracking-Daten, PDF-Link.
4. **Antwortet HTTP 200** binnen ≤ 5 Sekunden. Längere Folge-Aufgaben (E-Mail-Sequenz,
   Slack-Notification, Calendar-Sync) laufen async im CRM.

Bei einem Fehler antwortest du mit 4xx / 5xx — das Plugin retried automatisch
**bis zu 3-mal** (Backoff 2 s, 6 s). Nach 3 Fehlversuchen wird der Lead nur lokal in
WordPress protokolliert.

---

## 2) Quelle

| Feld | Wert |
|---|---|
| Absender | WordPress-Plugin `wg-konfigurator` (PHP 8.1+) |
| Hosting | wg-digitalmarketing.de (Mittwald mStudio) |
| Trigger | Kunde füllt 4-Schritte-Quiz aus auf einer Landingpage |
| Frequenz | Aktuell ~1–20 Leads pro Tag erwartet, mittelfristig bis 100/Tag |
| Versand | Async via WP-Cron, daher 1–60 s Verzögerung zum User-Submit |
| Retries | 3 Versuche mit Backoff (2 s, 6 s) |

---

## 3) Authentifizierung — HMAC-SHA256

Es gibt **keinen** klassischen API-Token. Stattdessen ein Shared Secret, mit dem das
Plugin den Request-Body signiert.

### Request-Header

| Header | Beispiel | Pflicht |
|---|---|---|
| `X-WG-Signature` | `sha256=4f8a1c…hex…` | Ja (wenn Secret konfiguriert ist) |
| `X-WG-Idempotency` | `550e8400-e29b-41d4-a716-446655440000` (UUID v4) | Ja |
| `X-WG-Source` | `wg-konfigurator/0.2.0` | Ja |
| `Content-Type` | `application/json` | Ja |
| `User-Agent` | `WG-Konfigurator/0.2.0` | Ja |

### Verifizierung (PHP-Beispiel)

```php
$secret    = getenv('WG_KONFIGURATOR_SECRET'); // gleicher String wie im WP-Plugin
$body      = file_get_contents('php://input');
$expected  = 'sha256=' . hash_hmac('sha256', $body, $secret);
$received  = $_SERVER['HTTP_X_WG_SIGNATURE'] ?? '';

if (!hash_equals($expected, $received)) {
    http_response_code(401);
    exit(json_encode(['error' => 'invalid_signature']));
}
```

### Verifizierung (Node.js)

```js
import crypto from 'node:crypto';

const secret   = process.env.WG_KONFIGURATOR_SECRET;
const raw      = req.rawBody;  // !! Raw Body, NICHT JSON.parse()-Ergebnis
const expected = 'sha256=' + crypto.createHmac('sha256', secret).update(raw).digest('hex');
const received = req.headers['x-wg-signature'];

if (!received || !crypto.timingSafeEqual(Buffer.from(expected), Buffer.from(received))) {
  return res.status(401).json({ error: 'invalid_signature' });
}
```

**Wichtig:** Signatur wird über den **rohen Body** berechnet, nicht über das geparste
JSON. Wenn dein Framework den Body schon vor dem Handler parst, brauchst du Zugriff
auf den Raw-Body (z. B. Express: `express.json({ verify: (req, res, buf) => { req.rawBody = buf } })`).

---

## 4) Idempotenz

Jeder Lead bekommt einen UUID v4 als `X-WG-Idempotency`-Header. Bei Retries (auf Plugin-Seite
oder z. B. wenn dein CRM 5xx antwortet und der nächste Versuch durchgeht) ist der
Key identisch.

**Regel im CRM:**

```
IF (lead mit idempotency_key existiert bereits):
    return 200 OK { ok: true, lead_id: <existing>, duplicate: true }
ELSE:
    create lead
    return 200 OK { ok: true, lead_id: <new>, duplicate: false }
```

Empfehlung: `idempotency_key` als Unique-Index in der DB. Auf Conflict-Insert mit
existierender ID zurück.

---

## 5) Request-Body

Vollständiges Beispiel — alle Felder sind im Plugin garantiert vorhanden (leere Strings
statt `null` wenn nicht angegeben):

```json
{
  "event": "konfigurator.completed",
  "idempotency_key": "550e8400-e29b-41d4-a716-446655440000",
  "generated_at": "2026-05-24T18:30:12+00:00",

  "lead": {
    "vorname": "Anna",
    "email": "anna@pflegedienst-mueller.de",
    "marketing_opt_in": true
  },

  "quiz": {
    "video_typ":   "imagefilm",
    "drehtage":    2,
    "zeitrahmen":  "flexibel",
    "branche":     "Pflege / Gesundheit",
    "website":     "https://pflegedienst-mueller.de",
    "ziel":        "Mehr Bewerbungen für Pflegekräfte-Stellen"
  },

  "berechnung": {
    "preis_min":          3340,
    "preis_max":          5340,
    "express_aufschlag":  0,
    "score":              85,
    "breakdown": {
      "video_typ": "imagefilm",
      "base_min":  2490,
      "base_max":  4490,
      "drehtage":  2,
      "per_day":   850,
      "extra":     850
    }
  },

  "ki_konzept": {
    "wirkungs_hypothese":       "Ein Imagefilm, der die menschliche Seite eures Pflegedienstes zeigt …",
    "story_skizze":             "Wir starten mit einer Frühschicht-Übergabe … (3–5 Sätze)",
    "empfohlene_protagonisten": ["Pflegedienstleitung", "Examinierte Pflegekraft", "Auszubildende"],
    "empfohlene_locations":     ["Aufenthaltsraum", "Pflegezimmer", "Garten"],
    "vorbereitungs_checkliste": [
      "Mitarbeitende über Drehtag informieren (Datenschutz-Einwilligung)",
      "Räume vorab aufgeräumt vorbereiten",
      "Outfit-Tipps an Protagonisten geben",
      "Tagesplan vorab freigeben"
    ],
    "naechste_schritte": "Wir melden uns innerhalb von 24 h für ein 30-Minuten-Briefing-Call."
  },

  "tracking": {
    "msclkid":      "abc123def456",
    "utm_source":   "bing",
    "utm_medium":   "cpc",
    "utm_campaign": "video-mitteldeutschland"
  },

  "pdf_url": "https://wg-digitalmarketing.de/wp-content/uploads/wg-konfigurator/konzept-anna-20260524-183012-a1b2c3.pdf"
}
```

### Feld-Constraints

| Pfad | Typ | Constraint |
|---|---|---|
| `event` | string | aktuell immer `"konfigurator.completed"`. Zukünftige Events: `"konfigurator.followup_24h"`, `"konfigurator.cancelled"`. **Switch-Statement, ignoriere unbekannte Events mit 200.** |
| `idempotency_key` | string | UUID v4, identisch mit Header |
| `generated_at` | string | ISO-8601 UTC |
| `lead.vorname` | string | 1–80 Zeichen, sanitized |
| `lead.email` | string | valides Email-Format |
| `lead.marketing_opt_in` | bool | DSGVO-Einwilligung für Marketing-Mails. **Wichtig fürs CRM**: nur bei `true` darf die Mailadresse in Newsletter-Listen. |
| `quiz.video_typ` | enum | `"imagefilm"` \| `"werbespot"` \| `"recruiting"` \| `"erklaervideo"` |
| `quiz.drehtage` | int | 1–5 |
| `quiz.zeitrahmen` | enum | `"flexibel"` (4–6 Wo) \| `"express"` (<3 Wo) \| `"planung"` (>6 Wo) |
| `quiz.branche` | string | freier Text, max 80 Zeichen |
| `quiz.website` | string | URL oder leerer String |
| `quiz.ziel` | string | freier Text, max 500 Zeichen, oder leerer String |
| `berechnung.preis_min` / `preis_max` | int | EUR netto, ganzzahlig |
| `berechnung.express_aufschlag` | int | EUR, 0 wenn kein Express |
| `berechnung.score` | int | 0–100, Lead-Qualität (siehe unten) |
| `ki_konzept.*` | string / string[] | Gemini-generiert, kann Sonderzeichen + Emojis enthalten |
| `tracking.*` | string | kann leer sein, alle Felder optional belegt |
| `pdf_url` | string | öffentlich abrufbares HTTPS-PDF, ~150 KB |

### Lead-Score-Logik (zur Info)

`score` ist eine Heuristik (0–100), die das CRM für Prio-Sortierung nutzen kann:

- Base: 50
- +15 wenn Typ = Imagefilm oder Recruiting (höherwertige Projekte)
- +10 wenn ≥ 2 Drehtage
- +10 wenn Zeitrahmen = flexibel (= mehr Vorlauf, bessere Margen)
- +10 wenn Website angegeben (= Kunde meint es ernst)
- Max 100

Faustregel fürs CRM: ≥ 75 = Hot, 50–74 = Warm, < 50 = Cold / Trash.

---

## 6) Response

### Erfolg

```http
HTTP/1.1 200 OK
Content-Type: application/json

{
  "ok": true,
  "lead_id": "lead_01H8X…",
  "duplicate": false
}
```

Das Plugin nutzt das Response-Body aktuell **nicht** (Fire-and-Forget). Du kannst aber
trotzdem strukturiert antworten — wir loggen das in WP error_log, hilft beim Debuggen.

### Fehler

| HTTP | Bedeutung | Plugin-Verhalten |
|---|---|---|
| 400 | Schema-Fehler im Body | Kein Retry. Lead geht verloren. ⚠️ |
| 401 | HMAC-Signatur falsch | Kein Retry. Lead geht verloren. ⚠️ |
| 409 | Duplicate (Idempotency-Treffer) | Plugin ignoriert, kein Retry |
| 429 | Rate-Limit | Plugin retried bis zu 3× |
| 5xx | Server-Fehler | Plugin retried bis zu 3× |

**Empfehlung:** Bei Schema-Fehlern (400) trotzdem 200 zurückgeben und den fehlerhaften
Lead in eine separate „Dead-Letter"-Queue im CRM legen — sonst geht der Lead komplett
verloren, weil das Plugin nach 3 Fehlversuchen aufgibt.

---

## 7) Datenmodell-Empfehlung im CRM

Beispiel-Schema (Postgres-Dialekt — übertrage es auf deine ORM):

```sql
CREATE TABLE konfigurator_leads (
    id                  TEXT PRIMARY KEY,                  -- 'lead_' || ULID
    idempotency_key     UUID UNIQUE NOT NULL,              -- aus Header
    event               TEXT NOT NULL,
    received_at         TIMESTAMPTZ DEFAULT now(),
    generated_at        TIMESTAMPTZ NOT NULL,

    -- Kontakt
    vorname             TEXT NOT NULL,
    email               TEXT NOT NULL,
    marketing_opt_in    BOOLEAN DEFAULT false,

    -- Quiz
    video_typ           TEXT NOT NULL,
    drehtage            INT  NOT NULL,
    zeitrahmen          TEXT NOT NULL,
    branche             TEXT,
    website             TEXT,
    ziel                TEXT,

    -- Berechnung
    preis_min           INT,
    preis_max           INT,
    express_aufschlag   INT DEFAULT 0,
    score               INT,

    -- KI-Konzept (als JSONB, da Struktur stabil ist)
    ki_konzept          JSONB,

    -- Tracking
    msclkid             TEXT,
    utm_source          TEXT,
    utm_medium          TEXT,
    utm_campaign        TEXT,

    -- PDF
    pdf_url             TEXT,

    -- CRM-State
    status              TEXT DEFAULT 'new',  -- new | contacted | qualified | won | lost
    assigned_to         TEXT,
    notes               TEXT
);

CREATE INDEX ON konfigurator_leads (received_at DESC);
CREATE INDEX ON konfigurator_leads (status);
CREATE INDEX ON konfigurator_leads (score DESC);
```

---

## 8) Was nach dem Empfang im CRM passieren sollte

(Diese Steps sind CRM-Job, nicht Webhook-Endpoint-Job — Endpoint nur Persist + 200.)

1. **Slack/Discord-Notification** an Sales-Channel mit Score + Vorname + Branche.
2. **Lead-Stage** je nach Score automatisch setzen (Hot/Warm/Cold).
3. **Folgemail-Sequenz starten** (nur wenn `marketing_opt_in = true`):
   - T+0 h: bereits durchs Plugin verschickt
   - T+24 h: „Hast du das PDF gelesen?"
   - T+72 h: „Lass uns reden — Termin-Link"
4. **Calendar-Integration**: Wenn Termin gebucht wird, Stage auf `contacted` setzen.
5. **DSGVO-Eintrag** in Consent-Log: Quelle = „Konfigurator", Zeitstempel = `generated_at`,
   IP wird vom Plugin **nicht** mitgeschickt (Datensparsamkeit).

---

## 9) Test-Workflow

### Schritt 1: Mock-Endpoint aufsetzen

Bevor du den echten Endpoint baust, teste mit https://webhook.site einen UUID-Endpoint
und gib mir die URL. Ich trage sie temporär ins Plugin ein, schicke einen Test-Lead,
und du siehst den exakten Request-Body.

### Schritt 2: Dein Endpoint vs. unser Test-Skript

Sobald dein Endpoint steht, ruf ihn mit curl an:

```bash
SECRET="das-secret-aus-dem-plugin"
BODY='{"event":"konfigurator.completed","idempotency_key":"test-uuid-001","generated_at":"2026-05-24T18:00:00+00:00","lead":{"vorname":"Test","email":"test@example.com","marketing_opt_in":false},"quiz":{"video_typ":"imagefilm","drehtage":1,"zeitrahmen":"flexibel","branche":"Test","website":"","ziel":""},"berechnung":{"preis_min":2490,"preis_max":4490,"express_aufschlag":0,"score":65,"breakdown":{"video_typ":"imagefilm","base_min":2490,"base_max":4490,"drehtage":1,"per_day":850,"extra":0}},"ki_konzept":{"wirkungs_hypothese":"Test","story_skizze":"Test","empfohlene_protagonisten":[],"empfohlene_locations":[],"vorbereitungs_checkliste":[],"naechste_schritte":"Test"},"tracking":{"msclkid":"","utm_source":"test","utm_medium":"","utm_campaign":""},"pdf_url":"https://example.com/test.pdf"}'
SIG="sha256=$(printf '%s' "$BODY" | openssl dgst -sha256 -hmac "$SECRET" | awk '{print $2}')"

curl -X POST https://dein-crm.de/api/webhooks/wg-konfigurator \
  -H "Content-Type: application/json" \
  -H "X-WG-Signature: $SIG" \
  -H "X-WG-Idempotency: test-uuid-001" \
  -H "X-WG-Source: wg-konfigurator/0.2.0" \
  -d "$BODY"
```

Erwartet: `200 OK` mit `{ "ok": true, "lead_id": "..." }`.

### Schritt 3: Idempotenz testen

Den exakt gleichen Request nochmal abschicken (gleicher `X-WG-Idempotency`).
Erwartet: `200 OK` mit `{ "duplicate": true }`, **kein** zweiter Datenbankeintrag.

### Schritt 4: Signatur-Fehler testen

Body ändern, aber Signatur lassen:
```bash
curl ... -d "$BODY-tampered"
```
Erwartet: `401 invalid_signature`.

---

## 10) Was du mir liefern musst

1. **Endpoint-URL** (Production + Staging falls vorhanden).
2. **Shared Secret** für HMAC — generier eines mit `openssl rand -hex 32` und
   gib es mir verschlüsselt (1Password / signed message / persönlich).
3. **Test-Bestätigung**, dass Schritte 2–4 oben funktionieren.

Dann trage ich die URL + Secret im WP-Plugin (Settings → Webhook) ein und
ab dem nächsten Lead landet alles im CRM.

---

## 11) Was später kommt (nicht jetzt — Planung)

Wenn das CRM-Webhook läuft, planen wir:

- **Status-Updates zurück ans Plugin**: CRM könnte signalisieren, wenn ein Lead
  „won" oder „lost" ist → das Plugin loggt das für PDF-Performance-Analyse.
- **CRM → WP-Webhook** (umgekehrte Richtung) für: „neue Folge-Mail soll raus".
- **Mehrere Konfiguratoren**: das Schema ist generisch — `event` ist der Discriminator
  für `webdesign.completed`, `recruiting.completed`, etc.

Für den ersten Wurf: nur der eine Endpoint, nur dieses Event.

---

## TL;DR

- Ein REST-Endpoint, der HMAC-signierte JSON-Bodies entgegennimmt.
- Idempotency-Key in den Headers → DB-Unique-Constraint.
- 200 binnen 5 s, Folge-Jobs (Slack/Mail) async.
- Bei Schema-Fehlern lieber 200 + Dead-Letter als 400, damit Leads nicht verloren gehen.
- Test mit dem curl-Snippet aus Abschnitt 9.

Bei Fragen: hier ist das Plugin-Repo (Code zu Webhook-Sender + HMAC-Generierung):
`includes/Services/WebhookSender.php`.
