/* ============================================================
   Client-side Preis-Logik — MUSS synchron mit
   includes/Services/PriceCalculator.php gehalten werden.

   Gibt ein aufgeschlüsseltes Breakdown zurück, das in der
   Preis-Sidebar live als Liste angezeigt werden kann.
   ============================================================ */

const BASE = {
  imagefilm:    { min: 2490, max: 4490, label: 'Imagefilm' },
  werbespot:    { min: 1490, max: 3490, label: 'Werbespot / Reel' },
  recruiting:   { min: 1990, max: 3990, label: 'Recruiting-Video' },
  erklaervideo: { min: 2990, max: 5490, label: 'Erklärvideo' },
};

const PAKET = {
  einzel:   { mult_min: 1.0,  mult_max: 1.0,  label: 'Ein Hauptvideo' },
  paket:    { mult_min: 1.35, mult_max: 1.45, label: 'Hauptvideo + Social-Cuts' },
  kampagne: { mult_min: 1.7,  mult_max: 2.0,  label: 'Vollkampagne' },
};

const LAENGE = {
  short:      { mult: 0.85, label: '15–30 Sek.' },
  medium:     { mult: 1.0,  label: '60–90 Sek.' },
  long:       { mult: 1.20, label: '2–3 Min.' },
  extra_long: { mult: 1.40, label: '4–5 Min.' },
};

const FEATURES = {
  voiceover:    { price: 290, label: 'Voiceover / Sprecher:in' },
  animation:    { price: 450, label: 'Animierte Texte / Lower-Thirds' },
  drohne:       { price: 590, label: 'Drohnen-Aufnahmen' },
  mehrsprachig: { price: 390, label: 'Mehrsprachige Versionen' },
};

const EXPRESS_MULT = 0.20;

/**
 * Berechnet die Preis-Range + aufgeschlüsselte Posten.
 * @param {object} q quiz answers
 * @returns {{items:Array, total_min:number, total_max:number, ready:boolean}}
 */
export function computeBreakdown(q) {
  const items = [];
  const ready = !!q.video_typ && !!q.output_paket;

  if (!ready) {
    return { items: [], total_min: 0, total_max: 0, ready: false };
  }

  const base   = BASE[q.video_typ] || BASE.werbespot;
  const paket  = PAKET[q.output_paket] || PAKET.einzel;
  const length = LAENGE[q.video_laenge] || LAENGE.medium;

  // 1) Basis
  items.push({
    key: 'base',
    label: `Basis · ${base.label}`,
    min: base.min,
    max: base.max,
  });

  // 2) Output-Paket-Aufschlag (nur wenn >1.0)
  if (paket.mult_min > 1.0 || paket.mult_max > 1.0) {
    const addMin = Math.round(base.min * (paket.mult_min - 1));
    const addMax = Math.round(base.max * (paket.mult_max - 1));
    items.push({
      key: 'paket',
      label: `+ ${paket.label}`,
      min: addMin,
      max: addMax,
    });
  }

  // 3) Längen-Aufschlag/Rabatt
  const sumMin0 = base.min * paket.mult_min;
  const sumMax0 = base.max * paket.mult_max;
  if (length.mult !== 1.0) {
    const addMin = Math.round(sumMin0 * (length.mult - 1));
    const addMax = Math.round(sumMax0 * (length.mult - 1));
    const sign = length.mult > 1.0 ? '+' : '−';
    items.push({
      key: 'length',
      label: `${sign} Länge ${length.label}`,
      min: addMin,
      max: addMax,
    });
  }

  // 4) Features (jeder einzeln)
  const featArr = q.features || [];
  featArr.forEach((f) => {
    if (FEATURES[f]) {
      items.push({
        key: `feat-${f}`,
        label: `+ ${FEATURES[f].label}`,
        min: FEATURES[f].price,
        max: FEATURES[f].price,
      });
    }
  });

  // 5) Express
  if (q.zeitrahmen === 'express') {
    const subtotalMin = items.reduce((s, it) => s + it.min, 0);
    const subtotalMax = items.reduce((s, it) => s + it.max, 0);
    items.push({
      key: 'express',
      label: `+ Express-Aufschlag (+${EXPRESS_MULT * 100}%)`,
      min: Math.round(subtotalMin * EXPRESS_MULT),
      max: Math.round(subtotalMax * EXPRESS_MULT),
    });
  }

  const total_min = items.reduce((s, it) => s + it.min, 0);
  const total_max = items.reduce((s, it) => s + it.max, 0);

  return { items, total_min, total_max, ready: true };
}

export function fmtEur(n) {
  return new Intl.NumberFormat('de-DE').format(n) + ' €';
}

export function fmtRange(min, max) {
  if (min === max) return fmtEur(min);
  return `${new Intl.NumberFormat('de-DE').format(min)} – ${fmtEur(max)}`;
}
