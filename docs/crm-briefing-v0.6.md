# CRM-Briefing Update — Konfigurator v0.6.0

**Adressat:** Der CRM-Agent / das CRM-Entwicklerteam.
**Vorgängerversion:** Siehe `crm-briefing.md` für die Basis-Spezifikation des Webhooks.

Plugin-Update v0.6.0 ergänzt **zwei neue Event-Typen** und **vier zusätzliche `quiz.video_typ`-Werte**.
Bitte den Webhook-Endpoint entsprechend erweitern.

---

## 1) Neuer Event: `konfigurator.started`

### Wann wird das gesendet?

Sobald ein Besucher auf dem Konfigurator-Intro-Screen
- seine E-Mail-Adresse einträgt,
- die DSGVO-Einwilligung gibt,
- und auf **„Konfigurator starten"** klickt.

Das passiert **bevor** der Quiz gestartet wird — der Lead hat zu dem Zeitpunkt noch
nichts konfiguriert. Wir wollen den Lead trotzdem erfassen, damit das CRM eine
**Recovery-Mail** schicken kann, falls der Besucher das Quiz nicht zu Ende
führt.

### Headers

Identisch zu `konfigurator.completed`:
- `X-WG-Signature: sha256=<hmac>`
- `X-WG-Idempotency: <uuid v4>` — **gleiche ID wie spätere `completed`-Events** (siehe unten)
- `X-WG-Source: wg-konfigurator/0.6.0`

### Body-Beispiel

```json
{
  "event": "konfigurator.started",
  "idempotency_key": "0d2e2c5b-…",
  "session_id":      "0d2e2c5b-…",
  "generated_at":    "2026-05-25T08:30:00+00:00",

  "lead": {
    "email": "anna@firma.de",
    "dsgvo_opt_in": true,
    "marketing_opt_in": false
  },

  "tracking": {
    "msclkid":      "abc",
    "utm_source":   "bing",
    "utm_medium":   "cpc",
    "utm_campaign": "video-mitteldeutschland"
  }
}
```

### Was das CRM damit machen soll

1. **Lead anlegen** mit `status = "started"`.
2. **Felder, die noch fehlen** (Vorname, Quiz-Antworten, KI-Konzept, Preis):
   leer lassen, NICHT als Fehler werten — kommen ggf. später via `completed`.
3. **Recovery-Strategie** (Vorschlag):
   - Wenn `started`-Lead nach **6 Stunden** noch keinen `completed`-Event hatte,
     einen Job einplanen, der eine Recovery-Mail schickt.
   - Mail-Inhalt (Vorschlag):
     - Subject: „Dein Videokonzept wartet auf dich"
     - Body: „Hi! Du hattest unseren Konfigurator gestartet, aber das
       Konzept noch nicht abgeholt. Möchtest du …
       - Konfigurator fortsetzen → Link zur Landingpage
       - Oder direkt einen Beratungstermin buchen → Meetergo-Link
       "
   - Nach **48h** zweite Erinnerung. Danach Lead-Status auf `cold` setzen,
     kein weiterer Versand.

### Sehr wichtig: Idempotenz mit späterem `completed`-Event

Der `idempotency_key` ist gleichzeitig die `session_id`. Wenn derselbe User
dann das Quiz abschließt, kommt ein zweites Event:

```json
{
  "event": "konfigurator.completed",
  "idempotency_key": "0d2e2c5b-…",  // SELBE UUID wie beim started
  "session_id":      "0d2e2c5b-…",
  ...
}
```

→ Das CRM sollte beim `completed` den vorhandenen Lead (matched über
`idempotency_key`) **upserten**, statt einen neuen anzulegen. Status:
`started` → `completed`. Recovery-Job für diese ID **abbrechen**.

**Vereinfachte Logik:**

```sql
INSERT INTO konfigurator_leads (idempotency_key, status, email, ...)
VALUES ('0d2e...', 'started', 'anna@firma.de', ...)
ON CONFLICT (idempotency_key) DO UPDATE
SET status = EXCLUDED.status,
    -- alle Felder die im neuen Payload da sind, aktualisieren
    ...;
```

---

## 2) Neue `quiz.video_typ`-Werte

Bisher: `imagefilm | werbespot | recruiting | erklaervideo`.

**NEU ab v0.5.0/v0.6.0:**

| Wert | Bedeutung |
|---|---|
| `imagefilm` | Klassischer Imagefilm (unverändert) |
| `werbespot` | Werbespot (umbenannt, war früher „werbespot/reel" — Reels sind jetzt separat) |
| `recruiting` | Recruiting-Video (unverändert) |
| **`reel_paket`** | Festpreis-Paket: 12 Reels, ½ Drehtag, 3×500 €/Monat |
| **`erklaer_real`** | Erklärvideo mit Real-Material |
| **`erklaer_anim`** | Erklärvideo 2D-Animation |
| **`animation_3d`** | 3D-Animation |
| **`animation_tech`** | Technische Animation (Maschinen, Bauteile, Prozesse) |

**Bitte den Zod-Enum-Validator entsprechend erweitern**, sonst landen die neuen
Typen im Dead-Letter.

---

## 3) Neue optionale Quiz-Felder

Im `quiz`-Object kommen jetzt zusätzliche Felder mit:

```json
{
  "quiz": {
    "video_typ":    "imagefilm",
    "output_paket": "kampagne",      // NEU – Enum: einzel|paket|kampagne (leer bei Reel/Animation)
    "video_laenge": "long",          // NEU – Enum: short|medium|long|extra_long
    "features":     ["voiceover", "drohne", "sound"],  // NEU – array<enum>
    "drehtage":     3,
    "zeitrahmen":   "flexibel",
    "branche":      "Industrie / Produktion",
    "website":      "https://kunde.de",
    "ziel":         "Lange Form mit Aufschlüsselung"
  }
}
```

**Empfehlung:** Als optionale Felder in `rawPayload` JSONB speichern. Für
Reports später typisieren, wenn Volumen es lohnt.

---

## 4) Lead-Felder erweitert

```json
{
  "lead": {
    "name":             "Anna Müller",   // NEU – voller Name
    "vorname":          "Anna",          // bleibt für Backwards-Compat
    "nachname":         "Müller",        // NEU
    "email":            "anna@firma.de",
    "dsgvo_opt_in":     true,            // NEU – immer true bei completed
    "marketing_opt_in": false
  }
}
```

---

## 5) Neue Berechnungs-Struktur

`berechnung` enthält jetzt zusätzlich:

```json
{
  "berechnung": {
    "preis_min": 5350,
    "preis_max": 12850,
    "express_aufschlag": 0,
    "score": 90,
    "drehtage": 3,
    "items": [                          // NEU – aufgeschlüsselte Posten
      { "key": "base",        "label": "Basis · Imagefilm", "min": 2000, "max": 5000 },
      { "key": "paket",       "label": "+ Vollkampagne",    "min": 1000, "max": 4000 },
      { "key": "length",      "label": "+ Länge 4–5 Min.",  "min": 750,  "max": 2250 },
      { "key": "konzept",     "label": "+ Konzept-Workshop","min": 1000, "max": 1000 },
      { "key": "feat-drohne", "label": "+ Drohne (3 Tage)", "min": 600,  "max": 600 }
    ],
    "breakdown": { ... }
  }
}
```

Die `items[]` sind ideal für eine spätere Auflistung im CRM-UI.

---

## 6) TL;DR — was du tun musst

1. **Zod-Schema erweitern:**
   - `event`: `started | completed` (statt nur `completed`)
   - `video_typ`: 8 Werte (siehe §2)
2. **Upsert-Logik** statt Insert für Idempotenz
3. **Recovery-Job** für `status = "started"` und älter als 6h
4. **Optional:** `items[]` aus `berechnung` in einer UI-Tabelle anzeigen
5. **Optional:** `quiz.output_paket`, `quiz.video_laenge`, `quiz.features[]`
   als typisierte Spalten ergänzen

Sag Bescheid wenn du eine Live-Test-URL für den geupdateten Endpoint hast,
dann teste ich die `started`/`completed`-Idempotenz mit curl.
