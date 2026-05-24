/* ============================================================
   Client-side Preis-Logik — MUSS synchron mit
   includes/Services/PriceCalculator.php gehalten werden.
   ============================================================ */

const BASE = {
  imagefilm:    { min: 2490, max: 4490 },
  werbespot:    { min: 1490, max: 3490 },
  recruiting:   { min: 1990, max: 3990 },
  erklaervideo: { min: 2990, max: 5490 },
};

const PAKET = {
  einzel:   { mult_min: 1.0,  mult_max: 1.0  },
  paket:    { mult_min: 1.35, mult_max: 1.45 },
  kampagne: { mult_min: 1.7,  mult_max: 2.0  },
};

const LAENGE = {
  short:      0.85,
  medium:     1.0,
  long:       1.20,
  extra_long: 1.40,
};

const FEATURE_PRICE = {
  voiceover:    290,
  untertitel:   190,
  animation:    450,
  drohne:       590,
  musik:        150,
  mehrsprachig: 390,
};

const EXPRESS_MULT = 0.20;

/**
 * Berechnet die Preis-Range aus den Quiz-Antworten.
 * @param {object} q - quiz answers
 * @returns {{min:number, max:number, express:number}}
 */
export function computePrice(q) {
  const b      = BASE[q.video_typ]      || { min: 1990, max: 3990 };
  const paket  = PAKET[q.output_paket]  || PAKET.einzel;
  const length = LAENGE[q.video_laenge] || 1.0;

  let min = Math.round(b.min * paket.mult_min * length);
  let max = Math.round(b.max * paket.mult_max * length);

  // Features
  const featAdd = (q.features || [])
    .map((f) => FEATURE_PRICE[f] || 0)
    .reduce((a, c) => a + c, 0);
  min += featAdd;
  max += featAdd;

  // Express
  let express = 0;
  if (q.zeitrahmen === 'express') {
    express = Math.round(max * EXPRESS_MULT);
    min += Math.round(min * EXPRESS_MULT);
    max += express;
  }

  return { min, max, express };
}

export function formatPriceRange({ min, max }) {
  const fmt = (n) => new Intl.NumberFormat('de-DE').format(n);
  return `${fmt(min)} – ${fmt(max)} €`;
}
