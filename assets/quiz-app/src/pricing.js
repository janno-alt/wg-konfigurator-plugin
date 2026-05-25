/* ============================================================
   WG-Digital Konfigurator – Preis-Logik
   Synchron mit includes/Services/PriceCalculator.php halten!

   Pricing-Modelle:
   - 'flat':       Pauschale + Output-Paket-Pauschale + Längen-Multi
   - 'per_minute': Basis × Mittelwert-Minuten
   - 'fixed':      Festpreis (für Reel-Paket)

   Pro Typ definieren wir zusätzlich:
   - has_*       Welche Features sind erlaubt
   - lengths[]   Welche Längen sind erlaubt (leer = alle)
   ============================================================ */

const KONZEPT_PAUSCHALE = 1000;

const VIDEO_TYPES = {
  // ---------- Klassische Videoproduktion (max ~50% Spread) ----------
  imagefilm: {
    label: 'Imagefilm',
    model: 'flat',
    base_min: 2000, base_max: 3000,
    has_konzept: true, has_drohne: true, has_voiceover: true, has_animation: true, has_sound: true, has_mehrsprachig: true,
    has_paket: true,   has_laenge: true,
    lengths: ['medium', 'long', 'extra_long'],
  },
  werbespot: {
    label: 'Werbespot',
    model: 'flat',
    base_min: 2500, base_max: 3750,
    has_konzept: true, has_drohne: true, has_voiceover: true, has_animation: true, has_sound: true, has_mehrsprachig: true,
    has_paket: true,   has_laenge: true,
    lengths: ['short', 'medium', 'long'],
  },
  recruiting: {
    label: 'Recruiting-Video',
    model: 'flat',
    base_min: 2500, base_max: 3750,
    has_konzept: true, has_drohne: true, has_voiceover: true,
    has_animation: false,   // Captions sind Standard bei Recruiting (Social)
    has_sound: true, has_mehrsprachig: true,
    has_paket: true,   has_laenge: true,
    lengths: ['medium', 'long'],   // 15-30s zu kurz, 4-5 min zu lang
  },
  reel_paket: {
    label: 'Reel-Paket (12 Kurzvideos)',
    model: 'fixed',
    base_min: 1500, base_max: 1500,
    has_konzept: false,
    has_drohne: true,
    has_voiceover: false,
    has_animation: false,    // Captions Standard
    has_sound: false,
    has_mehrsprachig: false,
    has_paket: false, has_laenge: false,
    lengths: [],
    drehtage: 0.5,
    payment_note: '3 × 500 € monatlich',
  },

  // ---------- Erklärvideo & Animation ----------
  erklaer_real: {
    label: 'Erklärvideo (mit Real-Material)',
    model: 'per_minute',
    base_min: 1000, base_max: 1500,
    has_konzept: true, has_drohne: true, has_voiceover: true, has_animation: true, has_sound: true, has_mehrsprachig: true,
    has_paket: false,  has_laenge: true,
    lengths: ['medium', 'long', 'extra_long'],
  },
  erklaer_anim: {
    label: 'Erklärvideo (Animation)',
    model: 'per_minute',
    base_min: 1500, base_max: 2250,
    has_konzept: true, has_drohne: false, has_voiceover: true, has_animation: false, has_sound: true, has_mehrsprachig: true,
    has_paket: false,  has_laenge: true,
    lengths: ['short', 'medium', 'long', 'extra_long'],
  },
  animation_3d: {
    label: '3D-Animation',
    model: 'per_minute',
    base_min: 2000, base_max: 3000,
    has_konzept: true, has_drohne: false, has_voiceover: true, has_animation: false, has_sound: true, has_mehrsprachig: true,
    has_paket: false,  has_laenge: true,
    lengths: ['short', 'medium', 'long'],
  },
  animation_tech: {
    label: 'Technische Animation',
    model: 'per_minute',
    base_min: 2500, base_max: 3750,
    has_konzept: true, has_drohne: false, has_voiceover: true, has_animation: false, has_sound: true, has_mehrsprachig: true,
    has_paket: false,  has_laenge: true,
    lengths: ['medium', 'long', 'extra_long'],
  },
};

/* Output-Paket: Pauschale statt Multiplikator (hält Spread konstant) */
const PAKET = {
  einzel:   { add_flat: 0,    drehtage: 1, label: 'Ein Hauptvideo' },
  paket:    { add_flat: 750,  drehtage: 2, label: 'Hauptvideo + Kurzvideos für Social Media' },
  kampagne: { add_flat: 1500, drehtage: 3, label: 'Komplette Kampagne (Hauptvideo + Kurzvideos + Bonus-Material)' },
};

const LAENGE = {
  short:      { mult: 0.9,  minutes: 0.4,  label: '15–30 Sek.' },
  medium:     { mult: 1.0,  minutes: 1.25, label: '60–90 Sek.' },
  long:       { mult: 1.15, minutes: 2.5,  label: '2–3 Min.' },
  extra_long: { mult: 1.25, minutes: 4.5,  label: '4–5 Min.' },
};

const FEATURES = {
  voiceover:    { price: 400, label: 'Voiceover / Sprecher:in' },
  animation:    { price: 250, label: 'Text-Einblendungen (Namen, Zitate)' },
  drohne:       { price_per_day: 200, label: 'Drohnen-Aufnahmen' },
  sound:        { price_per_min: 250, label: 'Sound Design (Atmosphäre, Effekte)' },
  mehrsprachig: { price: 390, label: 'Zweite Sprachfassung' },
};

const EXPRESS_MULT = 0.20;

/**
 * Berechnet die Preis-Range + aufgeschlüsselte Posten.
 * @param {object} q quiz answers
 * @returns {{items:Array, total_min:number, total_max:number, ready:boolean, type_label:string, payment_note?:string}}
 */
export function computeBreakdown(q) {
  const type = VIDEO_TYPES[q.video_typ];
  if (!type) {
    return { items: [], total_min: 0, total_max: 0, ready: false };
  }

  const items = [];

  // ---------- Modell: FIXED (Reel-Paket) ----------
  if (type.model === 'fixed') {
    items.push({
      key: 'fixed',
      label: type.label,
      min: type.base_min,
      max: type.base_max,
    });
    addOptionalFeatures(items, q, type);
    const total = sumItems(items);
    return {
      items, total_min: total.min, total_max: total.max, ready: true,
      type_label: type.label, payment_note: type.payment_note,
    };
  }

  // ---------- Modell: FLAT (Image / Werbe / Recruiting) ----------
  if (type.model === 'flat') {
    if (!q.output_paket) {
      return { items: [], total_min: 0, total_max: 0, ready: false };
    }

    const paket  = PAKET[q.output_paket]   || PAKET.einzel;
    const length = LAENGE[q.video_laenge]  || LAENGE.medium;

    items.push({
      key: 'base',
      label: `Basis · ${type.label}`,
      min: type.base_min,
      max: type.base_max,
    });

    if (paket.add_flat > 0) {
      items.push({
        key: 'paket',
        label: `+ ${paket.label}`,
        min: paket.add_flat,
        max: paket.add_flat,
      });
    }

    if (length.mult !== 1.0) {
      const sumMin0 = type.base_min;
      const sumMax0 = type.base_max;
      const sign = length.mult > 1.0 ? '+' : '−';
      items.push({
        key: 'length',
        label: `${sign} Länge ${length.label}`,
        min: Math.round(sumMin0 * (length.mult - 1)),
        max: Math.round(sumMax0 * (length.mult - 1)),
      });
    }

    addKonzept(items, type);
    addOptionalFeatures(items, q, type, paket.drehtage, length.minutes);
    addExpress(items, q);

    const total = sumItems(items);
    return { items, total_min: total.min, total_max: total.max, ready: true, type_label: type.label };
  }

  // ---------- Modell: PER_MINUTE (Erklär / Animation) ----------
  if (type.model === 'per_minute') {
    const length = LAENGE[q.video_laenge] || LAENGE.medium;
    const minutes = length.minutes;

    items.push({
      key: 'base',
      label: `${type.label} · ${length.label}`,
      min: Math.round(type.base_min * minutes),
      max: Math.round(type.base_max * minutes),
    });

    addKonzept(items, type);
    addOptionalFeatures(items, q, type, 1, minutes);
    addExpress(items, q);

    const total = sumItems(items);
    return { items, total_min: total.min, total_max: total.max, ready: true, type_label: type.label };
  }

  return { items: [], total_min: 0, total_max: 0, ready: false };
}

function addKonzept(items, type) {
  if (!type.has_konzept) return;
  items.push({
    key: 'konzept',
    label: '+ Konzept-Workshop (Drehbuch, Drehplan)',
    min: KONZEPT_PAUSCHALE,
    max: KONZEPT_PAUSCHALE,
  });
}

function addOptionalFeatures(items, q, type, drehtage = 1, minutes = 0) {
  const features = q.features || [];
  features.forEach((f) => {
    if (!FEATURES[f]) return;
    if (f === 'voiceover'    && !type.has_voiceover)    return;
    if (f === 'animation'    && !type.has_animation)    return;
    if (f === 'drohne'       && !type.has_drohne)       return;
    if (f === 'sound'        && !type.has_sound)        return;
    if (f === 'mehrsprachig' && !type.has_mehrsprachig) return;

    if (f === 'drohne') {
      const total = drehtage * FEATURES.drohne.price_per_day;
      const days_label = drehtage === 1 ? '1 Drehtag' : `${drehtage} Drehtage`;
      items.push({
        key: 'feat-drohne',
        label: `+ ${FEATURES.drohne.label} (${days_label})`,
        min: total,
        max: total,
      });
      return;
    }

    if (f === 'sound') {
      const total = Math.round(minutes * FEATURES.sound.price_per_min);
      items.push({
        key: 'feat-sound',
        label: `+ ${FEATURES.sound.label} (${minutes.toString().replace('.', ',')} Min.)`,
        min: total,
        max: total,
      });
      return;
    }

    items.push({
      key: `feat-${f}`,
      label: `+ ${FEATURES[f].label}`,
      min: FEATURES[f].price,
      max: FEATURES[f].price,
    });
  });
}

function addExpress(items, q) {
  if (q.zeitrahmen !== 'express') return;
  const sub = sumItems(items);
  items.push({
    key: 'express',
    label: `+ Express-Aufschlag (+${EXPRESS_MULT * 100} %)`,
    min: Math.round(sub.min * EXPRESS_MULT),
    max: Math.round(sub.max * EXPRESS_MULT),
  });
}

function sumItems(items) {
  return items.reduce(
    (acc, it) => ({ min: acc.min + it.min, max: acc.max + it.max }),
    { min: 0, max: 0 }
  );
}

export function isFeatureAvailable(videoType, featureId) {
  const type = VIDEO_TYPES[videoType];
  if (!type) return false;
  return {
    voiceover:    type.has_voiceover,
    animation:    type.has_animation,
    drohne:       type.has_drohne,
    sound:        type.has_sound,
    mehrsprachig: type.has_mehrsprachig,
  }[featureId] || false;
}

/** Welche Längen sind für diesen Typ erlaubt? */
export function lengthsForType(videoType) {
  const type = VIDEO_TYPES[videoType];
  if (!type) return ['short', 'medium', 'long', 'extra_long'];
  if (!type.lengths || type.lengths.length === 0) return [];
  return type.lengths;
}

/** Quiz-Steps die für diesen Video-Typ relevant sind */
export function stepsForType(videoType) {
  const type = VIDEO_TYPES[videoType];
  if (!type) return ['video_typ'];

  const steps = ['video_typ'];
  if (type.has_paket)   steps.push('output_paket');
  if (type.has_laenge && type.lengths.length > 0) steps.push('video_laenge');
  steps.push('features', 'zeitrahmen', 'kontext');
  return steps;
}

export function fmtEur(n) {
  return new Intl.NumberFormat('de-DE').format(n) + ' €';
}

export function fmtRange(min, max) {
  if (min === max) return fmtEur(min);
  return `${new Intl.NumberFormat('de-DE').format(min)} – ${fmtEur(max)}`;
}

export { VIDEO_TYPES, PAKET, LAENGE, FEATURES };
