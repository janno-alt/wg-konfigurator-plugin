/* ============================================================
   WG-Konfigurator – Produkt-Definitionen (Recruiting + Social)
   ------------------------------------------------------------
   Preise zentral hier. Server-Spiegel: includes/Services/ProductPricing.php
   (beide MÜSSEN synchron bleiben).
   ============================================================ */

export const fmtEur = (n) => new Intl.NumberFormat('de-DE').format(Math.round(n)) + ' €';
export const fmtRange = (min, max) =>
  min === max ? fmtEur(min) : `${new Intl.NumberFormat('de-DE').format(Math.round(min))} – ${fmtEur(max)}`;

/* ===================== PREISE (zentral, real von Janno) ===================== */
const PRICES = {
  recruiting: {
    video_base:     2000,   // 1 Stelle, inkl. Konzept
    stelle_add:     750,    // je weitere Stelle
    landingpage:    900,    // Bewerber-Landingpage (einmalig)
    kampagne_monat: 250,    // Social-Recruiting-Betreuung mtl. (exkl. Werbebudget)
    express_mult:   0.20,   // Express-Aufschlag auf Einmalkosten
  },
  social: {
    statisch: { price: 300, label: 'Statisch',    incl: [ '6 statische Beiträge pro Monat', 'Redaktionsplan & Texte', 'Community-Management', 'Monatlicher Report' ] },
    reels4_q: { price: 500, label: 'Reels Basis', incl: [ '4 Reels pro Monat', '1 Drehtag pro Quartal', 'Redaktionsplan, Schnitt & Veröffentlichung', 'Community-Management', 'Monatlicher Report' ] },
    reels4_m: { price: 700, label: 'Reels Plus',  incl: [ '4 Reels pro Monat', 'Drehtag jeden Monat (frischer Content)', 'Redaktionsplan, Schnitt & Veröffentlichung', 'Community-Management', 'Monatlicher Report' ] },
    reels8:   { price: 990, label: 'Reels Pro',   incl: [ '8 Reels pro Monat', '1 Drehtag alle 2 Monate', 'Redaktionsplan, Schnitt & Veröffentlichung', 'Community-Management', 'Monatlicher Report' ] },
  },
};

const BRANCHEN = [
  'Handwerk', 'Industrie / Produktion', 'Pflege / Gesundheit', 'Hotel / Gastro',
  'Dienstleister / B2B', 'Handel / E-Commerce', 'Sonstiges',
];

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
    branche: '', stellen: '', rec_kampagne: 'nein', rec_lp: 'nein',
    zeitrahmen: 'flexibel', website: '', ziel: '',
  },
  steps: [
    {
      key: 'branche', field: 'branche', type: 'single', grid: true,
      title: 'Für welchen Bereich sucht ihr Leute?',
      subtitle: 'Damit wir die Ansprache auf eure Zielgruppe abstimmen.',
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
      subtitle: 'Basis ist eine Stelle inkl. Konzept. Jede weitere Stelle kostet 750 € extra.',
      options: [
        { id: '1-2', label: '1 bis 2 Stellen', hint: 'Ein oder zwei konkrete Positionen' },
        { id: '3plus', label: 'Ab 3 Stellen', hint: 'Mehrere Positionen, gemeinsamer Drehtag' },
        { id: 'dauerhaft', label: 'Dauerhaft mehrere Stellen', hint: 'Ihr stellt laufend ein, individuelles Volumen-Paket' },
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
    const triple = P.video_base + 2 * P.stelle_add; // ab 3 Stellen
    const STELLEN = {
      '1-2':       { min: P.video_base, max: P.video_base + P.stelle_add, label: 'Recruiting-Video inkl. Konzept (1 bis 2 Stellen)' },
      '3plus':     { min: triple, max: triple, label: 'Recruiting-Video inkl. Konzept (ab 3 Stellen, je weitere +750 €)' },
      'dauerhaft': { min: triple, max: triple, label: 'Recruiting-Video inkl. Konzept (dauerhaft, Volumen-Paket ab)' },
    };
    const base = STELLEN[a.stellen] || STELLEN['1-2'];
    const items = [];
    items.push({ key: 'base', label: base.label, min: base.min, max: base.max });

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
        min: P.kampagne_monat, max: P.kampagne_monat, from: false,
        note: 'monatliche Betreuung, zzgl. Werbebudget (bestimmt ihr selbst)',
      };
    }
    return { ready: true, items, total_min: one_min, total_max: one_max, recurring, type_label: 'Recruiting-Paket' };
  },
};

/* ===================== SOCIAL-MEDIA BETREUUNG ===================== */
const social = {
  id: 'social',
  intro: {
    eyebrow: 'Social-Media-Konfigurator',
    headlineHtml: 'Dein <span class="accent">Social-Media-Paket</span> in 90 Sekunden.',
    sub: 'Wähle deinen Content-Umfang, wir zeigen dir das passende Paket und schicken dir kostenlos eine Einschätzung per E-Mail.',
    flow: ['Umfang wählen', 'Paket-Empfehlung', 'PDF im Postfach'],
    cta: 'Konfigurator starten',
  },
  initialAnswers: { paket: '', branche: '', website: '', ziel: '' },
  steps: [
    {
      key: 'paket', field: 'paket', type: 'single',
      title: 'Welcher Content-Umfang passt zu euch?',
      subtitle: 'Das bestimmt euer monatliches Paket. Die enthaltenen Leistungen siehst du rechts.',
      options: [
        { id: 'statisch', label: '6 statische Beiträge / Monat', hint: 'Regelmäßige Posts, ohne Video' },
        { id: 'reels4_q', label: '4 Reels / Monat', hint: 'Dreh alle 3 Monate, günstigster Reels-Einstieg', badge: 'beliebt' },
        { id: 'reels4_m', label: '4 Reels / Monat, frischer', hint: 'Drehtag jeden Monat für aktuellen Content' },
        { id: 'reels8', label: '8 Reels / Monat', hint: 'Maximale Reichweite, Dreh alle 2 Monate' },
      ],
    },
    {
      key: 'branche', field: 'branche', type: 'single', grid: true,
      title: 'In welcher Branche seid ihr?',
      subtitle: 'Damit wir Themen und Tonalität abstimmen.',
      options: BRANCHEN.map((b) => ({ id: b, label: b })),
    },
    { key: 'kontext', type: 'final' },
  ],
  sidebar: {
    eyebrow: 'Dein Paket',
    title: 'Social-Media Betreuung',
    emptyHint: 'Wähle deinen Content-Umfang, dann siehst du hier dein Paket und den Monatspreis.',
    inclText: 'Alle Pakete laufen monatlich und sind mit einem Monat zum Monatsende kündbar. 10 % Rabatt bei jährlicher Vorauszahlung. Alle Preise netto, zzgl. MwSt.',
  },
  result: {
    headline: 'Deine Paket-Empfehlung ist unterwegs.',
    sentText: 'Wir haben dir die Empfehlung mit Monatspreis und Leistungsumfang als PDF an deine E-Mail geschickt.',
  },
  computeBreakdown(a) {
    if (!a.paket || !PRICES.social[a.paket]) {
      return { ready: false, items: [], total_min: 0, total_max: 0, recurring: null };
    }
    const pkg = PRICES.social[a.paket];
    return {
      ready: true,
      items: [], total_min: 0, total_max: 0,
      recurring: {
        label: pkg.label + '-Paket',
        items: pkg.incl.map((t) => ({ label: t })),
        min: pkg.price, max: pkg.price, from: false,
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
