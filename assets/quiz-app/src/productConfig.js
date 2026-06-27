/* ============================================================
   WG-Konfigurator – Produkt-Definitionen (Recruiting + Social)
   ------------------------------------------------------------
   Jeder Eintrag definiert: Intro-Text, Frage-Flow (steps), Start-
   Antworten, Preis-Logik (computeBreakdown) und Result-Texte.

   PREISE: zentral hier oben gepflegt. Platzhalter sind mit
   "TODO PREIS" markiert und werden gemeinsam mit Janno scharf
   gestellt. Server-Spiegel: includes/Services/ProductPricing.php
   ============================================================ */

export const fmtEur = (n) => new Intl.NumberFormat('de-DE').format(Math.round(n)) + ' €';
export const fmtRange = (min, max) =>
  min === max ? fmtEur(min) : `${new Intl.NumberFormat('de-DE').format(Math.round(min))} – ${fmtEur(max)}`;

/* ===================== PREISE (zentral) ===================== */
const PRICES = {
  recruiting: {
    video_base_min: 2000,           // bestehende Recruiting-Video-Logik (real)
    video_base_max: 3000,           // real
    konzept: 800,                   // real
    cutdowns_add: 600,              // real (Paket-Aufschlag Kurzvideos)
    landingpage: 900,               // TODO PREIS bestätigen (Bewerber-Landingpage einmalig)
    kampagne_monat: 490,            // TODO PREIS bestätigen (Social-Recruiting-Betreuung mtl., exkl. Werbebudget)
    express_mult: 0.20,             // real (Express-Aufschlag auf Einmalkosten)
  },
  social: {
    // Reale Paketpreise von /social-media-betreuung (Seite 8142)
    starter:     { price: 300, from: false, label: 'Starter' },
    standard:    { price: 500, from: false, label: 'Standard' },
    performance: { price: 990, from: true,  label: 'Performance' },
  },
};

/* ===================== RECRUITING ===================== */
const recruiting = {
  id: 'recruiting',
  intro: {
    eyebrow: 'Recruiting-Konfigurator',
    headlineHtml: 'Dein <span class="accent">Bewerber-System</span> in 2 Minuten.',
    sub: 'Stell dir dein Recruiting-Paket zusammen, vom Video bis zur Kampagne. Du bekommst kostenlos eine Einschätzung mit Preis-Rahmen per E-Mail.',
    flow: ['Konfigurieren', 'KI-Analyse', 'PDF im Postfach'],
    cta: 'Konfigurator starten',
  },
  initialAnswers: {
    branche: '', stellen: '', rec_video: 'einzel', rec_kampagne: 'nein',
    rec_lp: 'nein', zeitrahmen: 'flexibel', website: '', ziel: '',
  },
  steps: [
    {
      key: 'branche', field: 'branche', type: 'single',
      title: 'Für welchen Bereich sucht ihr Leute?',
      subtitle: 'Damit wir die Ansprache auf eure Zielgruppe abstimmen.',
      grid: true,
      options: [
        { id: 'Handwerk / Bau', label: 'Handwerk / Bau' },
        { id: 'Pflege / Gesundheit', label: 'Pflege / Gesundheit' },
        { id: 'Produktion / Industrie', label: 'Produktion / Industrie' },
        { id: 'Gastro / Hotel', label: 'Gastro / Hotel' },
        { id: 'Logistik / Transport', label: 'Logistik / Transport' },
        { id: 'Büro / Verwaltung', label: 'Büro / Verwaltung' },
        { id: 'Sonstiges', label: 'Sonstiges' },
      ],
    },
    {
      key: 'stellen', field: 'stellen', type: 'single',
      title: 'Wie viele Stellen wollt ihr besetzen?',
      options: [
        { id: '1', label: '1 Stelle', hint: 'Eine konkrete Position' },
        { id: '2-3', label: '2 bis 3 Stellen', hint: 'Mehrere Positionen parallel' },
        { id: 'laufend', label: 'Laufend / mehrere', hint: 'Ihr stellt dauerhaft ein' },
      ],
    },
    {
      key: 'rec_video', field: 'rec_video', type: 'single',
      title: 'Welchen Video-Umfang wollt ihr?',
      subtitle: 'Das Recruiting-Video ist der Kern. Cutdowns sind kurze Schnitte für die Anzeigen.',
      options: [
        { id: 'einzel', label: 'Ein Recruiting-Video', hint: '1 Drehtag, ein fertiges Hauptvideo' },
        { id: 'paket', label: 'Video + Kurz-Cutdowns', hint: 'Hauptvideo plus mehrere kurze Schnitte für Anzeigen', badge: 'beliebt' },
      ],
    },
    {
      key: 'rec_kampagne', field: 'rec_kampagne', type: 'single',
      title: 'Sollen wir auch die Kampagne schalten?',
      subtitle: 'Social-Recruiting: Wir spielen euer Video per Meta/Instagram-Ads gezielt an passende Profile aus und betreuen die Anzeigen.',
      options: [
        { id: 'ja', label: 'Ja, Kampagne dazu', hint: 'Monatliche Betreuung, ihr bestimmt das Werbebudget', badge: 'mehr Bewerbungen' },
        { id: 'nein', label: 'Nein, nur das Video', hint: 'Ihr spielt das Video selbst aus' },
      ],
    },
    {
      key: 'rec_lp', field: 'rec_lp', type: 'single',
      title: 'Braucht ihr eine Bewerber-Landingpage?',
      subtitle: 'Eine einfache Seite, auf der die Bewerbung in unter einer Minute klappt, ohne Lebenslauf-Hürde.',
      options: [
        { id: 'ja', label: 'Ja, Landingpage dazu', hint: 'Deutlich höhere Bewerbungsquote' },
        { id: 'nein', label: 'Nein, brauchen wir nicht', hint: 'Wir leiten woanders hin' },
      ],
    },
    {
      key: 'zeitrahmen', field: 'zeitrahmen', type: 'single',
      title: 'Wann wollt ihr starten?',
      options: [
        { id: 'flexibel', label: 'Flexibel (3 bis 5 Wochen)' },
        { id: 'express', label: 'Express (unter 3 Wochen)', badge: '+20 %' },
        { id: 'planung', label: 'Planen noch (mehr als 6 Wochen)' },
      ],
    },
    { key: 'kontext', type: 'final' },
  ],
  sidebar: {
    eyebrow: 'Live-Berechnung',
    title: 'Dein Recruiting-Paket',
    emptyHint: 'Triff deine Auswahl, dann siehst du hier die Posten und den Preis-Rahmen.',
    inclText: 'Inklusive: Drehtag mit Equipment, Schnitt, Untertitel und plattformgerechter Export. Reisekosten ab 100 km separat. Alle Preise netto, zzgl. MwSt.',
  },
  result: {
    headline: 'Dein Recruiting-Konzept ist unterwegs.',
    sentText: 'Wir haben dir die individuelle Einschätzung mit Preis-Rahmen als PDF an deine E-Mail geschickt.',
  },
  computeBreakdown(a) {
    const P = PRICES.recruiting;
    const items = [];
    items.push({ key: 'base', label: 'Recruiting-Video (1 Drehtag)', min: P.video_base_min, max: P.video_base_max });
    items.push({ key: 'konzept', label: '+ Konzept-Workshop (Drehbuch, Drehplan)', min: P.konzept, max: P.konzept });
    if (a.rec_video === 'paket') {
      items.push({ key: 'cutdowns', label: '+ Kurz-Cutdowns für Anzeigen', min: P.cutdowns_add, max: P.cutdowns_add });
    }
    if (a.rec_lp === 'ja') {
      items.push({ key: 'lp', label: '+ Bewerber-Landingpage', min: P.landingpage, max: P.landingpage });
    }
    let one_min = items.reduce((s, it) => s + it.min, 0);
    let one_max = items.reduce((s, it) => s + it.max, 0);
    if (a.zeitrahmen === 'express') {
      const em = Math.round(one_min * P.express_mult);
      const ex = Math.round(one_max * P.express_mult);
      items.push({ key: 'express', label: '+ Express-Aufschlag (+20 %)', min: em, max: ex });
      one_min += em; one_max += ex;
    }
    let recurring = null;
    if (a.rec_kampagne === 'ja') {
      recurring = {
        label: 'Social-Recruiting-Kampagne',
        items: [
          { label: 'Anzeigen-Setup & Targeting' },
          { label: 'Laufende Optimierung der Kampagne' },
          { label: 'Reporting der Bewerbungen' },
        ],
        min: P.kampagne_monat, max: P.kampagne_monat, from: true,
        note: 'monatliche Betreuung, zzgl. Werbebudget (bestimmt ihr selbst)',
      };
    }
    return {
      ready: true,
      items, total_min: one_min, total_max: one_max,
      recurring,
      type_label: 'Recruiting-Paket',
    };
  },
};

/* ===================== SOCIAL-MEDIA BETREUUNG ===================== */
function socialTier(a) {
  let tier = 'starter';
  if (a.content === '16' || a.plattformen === '23' || a.ads === 'ja') tier = 'standard';
  if (a.plattformen === '45' || a.content === '20' ||
      (a.ads === 'ja' && (a.plattformen === '23' || a.content === '16'))) tier = 'performance';
  return tier;
}

const SOCIAL_INCL = {
  starter: ['1 Plattform', '8 Posts/Reels pro Monat', 'Community-Management (Mo–Fr)', 'Monatlicher Report'],
  standard: ['bis 3 Plattformen', '16 Posts/Reels pro Monat', 'Werbeanzeigen-Betreuung', 'Community-Management', 'Monatlicher Report'],
  performance: ['alle 5 Plattformen', 'höchste Content-Frequenz', 'Meta + LinkedIn Ads (Setup & Optimierung)', 'erweiterte Service-Zeiten', 'monatlicher Strategie-Call'],
};

const social = {
  id: 'social',
  intro: {
    eyebrow: 'Social-Media-Konfigurator',
    headlineHtml: 'Dein <span class="accent">Social-Media-Paket</span> in 90 Sekunden.',
    sub: 'Beantworte ein paar kurze Fragen, wir empfehlen dir das passende Betreuungs-Paket und schicken dir kostenlos eine Einschätzung per E-Mail.',
    flow: ['Fragen beantworten', 'Paket-Empfehlung', 'PDF im Postfach'],
    cta: 'Konfigurator starten',
  },
  initialAnswers: {
    plattformen: '', content: '', ads: '', branche: '', website: '', ziel: '',
  },
  steps: [
    {
      key: 'plattformen', field: 'plattformen', type: 'single',
      title: 'Auf wie vielen Plattformen wollt ihr aktiv sein?',
      subtitle: 'Z. B. Instagram, Facebook, LinkedIn, TikTok, Google-Profil.',
      options: [
        { id: '1', label: 'Eine Plattform', hint: 'Fokus auf den wichtigsten Kanal' },
        { id: '23', label: 'Zwei bis drei', hint: 'Der typische Mix' },
        { id: '45', label: 'Vier bis fünf', hint: 'Maximale Sichtbarkeit' },
      ],
    },
    {
      key: 'content', field: 'content', type: 'single',
      title: 'Wie viel Content pro Monat?',
      subtitle: 'Posts und Reels, die wir für euch erstellen und veröffentlichen.',
      options: [
        { id: '8', label: 'Etwa 8 pro Monat', hint: 'Solide Grundpräsenz' },
        { id: '16', label: 'Etwa 16 pro Monat', hint: 'Spürbares Wachstum', badge: 'beliebt' },
        { id: '20', label: '20+ pro Monat', hint: 'Maximale Frequenz' },
      ],
    },
    {
      key: 'ads', field: 'ads', type: 'single',
      title: 'Sollen wir auch Werbeanzeigen betreuen?',
      subtitle: 'Meta- und LinkedIn-Ads: Setup, Optimierung und Tracking, zusätzlich zur organischen Betreuung.',
      options: [
        { id: 'ja', label: 'Ja, Ads dazu', hint: 'Reichweite gezielt verstärken' },
        { id: 'nein', label: 'Erstmal organisch', hint: 'Nur regelmäßige Beiträge' },
      ],
    },
    {
      key: 'branche', field: 'branche', type: 'single',
      title: 'In welcher Branche seid ihr?',
      subtitle: 'Damit wir Themen und Tonalität abstimmen.',
      grid: true,
      options: [
        { id: 'Handwerk', label: 'Handwerk' },
        { id: 'Industrie / Produktion', label: 'Industrie / Produktion' },
        { id: 'Pflege / Gesundheit', label: 'Pflege / Gesundheit' },
        { id: 'Hotel / Gastro', label: 'Hotel / Gastro' },
        { id: 'Dienstleister / B2B', label: 'Dienstleister / B2B' },
        { id: 'Handel / E-Commerce', label: 'Handel / E-Commerce' },
        { id: 'Sonstiges', label: 'Sonstiges' },
      ],
    },
    { key: 'kontext', type: 'final' },
  ],
  sidebar: {
    eyebrow: 'Empfehlung',
    title: 'Dein Social-Media-Paket',
    emptyHint: 'Beantworte die Fragen, dann empfehlen wir dir hier das passende Paket.',
    inclText: 'Alle Pakete laufen monatlich und sind mit einem Monat zum Monatsende kündbar. 10 % Rabatt bei jährlicher Vorauszahlung. Alle Preise netto, zzgl. MwSt.',
  },
  result: {
    headline: 'Deine Paket-Empfehlung ist unterwegs.',
    sentText: 'Wir haben dir die Empfehlung mit Monatspreis und Leistungsumfang als PDF an deine E-Mail geschickt.',
  },
  computeBreakdown(a) {
    if (!a.plattformen || !a.content || !a.ads) {
      return { ready: false, items: [], total_min: 0, total_max: 0, recurring: null };
    }
    const tier = socialTier(a);
    const pkg = PRICES.social[tier];
    return {
      ready: true,
      items: [], total_min: 0, total_max: 0,
      recurring: {
        label: pkg.label + '-Paket',
        items: SOCIAL_INCL[tier].map((t) => ({ label: t })),
        min: pkg.price, max: pkg.price, from: pkg.from,
        note: 'monatlich kündbar · 10 % Rabatt bei jährlicher Vorauszahlung',
      },
      type_label: 'Social-Media Betreuung · ' + pkg.label,
    };
  },
};

const PRODUCTS = { recruiting, social };

export function getProduct(id) {
  return PRODUCTS[id] || null;
}
