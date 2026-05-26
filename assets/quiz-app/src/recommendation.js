/* ============================================================
   Recommendation-Engine v0.10
   Input: Ziel + Ausspiel-Kanäle (Multi) + Branche
   Output: empfohlener Video-Typ + Default-Konfig
   Budget-Step entfernt — er führte zu paradoxen Empfehlungen.
   ============================================================ */

export const GOALS = [
  {
    id: 'awareness',
    label: 'Mehr Aufmerksamkeit und Neukunden',
    hint: 'Sichtbar werden, neue Zielgruppen ansprechen',
    icon: '📣',
  },
  {
    id: 'brand',
    label: 'Marke und Vertrauen aufbauen',
    hint: 'Werte zeigen, Persönlichkeit transportieren',
    icon: '🎬',
  },
  {
    id: 'recruiting',
    label: 'Mehr Bewerber:innen gewinnen',
    hint: 'Authentische Einblicke ins Team und in den Arbeitsalltag',
    icon: '🤝',
  },
  {
    id: 'social',
    label: 'Reichweite auf Social Media',
    hint: 'Konstanter Content für Reels, Shorts, TikTok',
    icon: '📱',
  },
  {
    id: 'explain',
    label: 'Komplexes Produkt oder Thema erklären',
    hint: 'Z. B. eine Dienstleistung verständlich machen',
    icon: '💡',
  },
  {
    id: 'technical',
    label: 'Technisches Produkt visualisieren',
    hint: 'Maschinen, Bauteile, Prozesse zeigen — auch was unsichtbar ist',
    icon: '⚙️',
  },
  {
    id: 'sales',
    label: 'Online verkaufen / Conversion steigern',
    hint: 'Performance-Anzeigen, Landingpage-Conversion',
    icon: '🛒',
  },
];

export const CHANNELS = [
  { id: 'website', label: 'Eigene Website / Landingpage',                     icon: '🌐' },
  { id: 'youtube', label: 'YouTube',                                           icon: '▶️' },
  { id: 'social',  label: 'Social Media (Instagram, TikTok, LinkedIn)',        icon: '📱' },
  { id: 'ads',     label: 'Bezahlte Anzeigen (Google, Meta, LinkedIn Ads)',    icon: '🎯' },
  { id: 'messe',   label: 'Messe / Display vor Ort',                           icon: '🖥️' },
  { id: 'tv',      label: 'TV / Streaming-Werbung',                            icon: '📺' },
];

/* Welche Video-Typen passen zum Ziel? Erste Position = primäre Empfehlung. */
export const GOAL_TO_TYPES = {
  awareness:  ['werbespot', 'imagefilm', 'reel_paket'],
  brand:      ['imagefilm', 'werbespot'],
  recruiting: ['recruiting'],
  social:     ['reel_paket', 'werbespot'],
  explain:    ['erklaer_real', 'erklaer_anim', 'animation_3d'],
  technical:  ['animation_tech', 'animation_3d', 'erklaer_anim'],
  sales:      ['werbespot', 'erklaer_anim', 'imagefilm'],
};

/**
 * Erzeugt die Empfehlung aus Ziel + Kanälen.
 *
 * @param {string} goal
 * @param {string[]} channels  Multi-Select Array (z. B. ['website', 'social'])
 * @returns {{video_typ, output_paket, video_laenge, features, reasoning_short}}
 */
export function recommend(goal, channels = []) {
  const hasSocial  = channels.includes('social');
  const hasTv      = channels.includes('tv');
  const hasMesse   = channels.includes('messe');
  const hasAds     = channels.includes('ads');
  const hasWebsite = channels.includes('website') || channels.includes('youtube');

  // Default
  let rec = {
    video_typ: 'werbespot',
    output_paket: 'einzel',
    video_laenge: 'medium',
    features: ['voiceover'],
    reasoning_short: '',
  };

  switch (goal) {
    case 'awareness':
      rec = {
        video_typ: 'werbespot',
        output_paket: hasSocial || hasAds ? 'paket' : 'einzel',
        video_laenge: hasMesse ? 'short' : 'medium',
        features: hasMesse ? ['animation', 'sound'] : ['voiceover', 'sound'],
        reasoning_short: hasSocial
          ? 'Werbespot + Kurzvideos für Social Media: klassische Werbeflächen plus organische Reichweite.'
          : 'Klassischer Werbespot, der deine Hauptbotschaft auf den Punkt bringt.',
      };
      break;

    case 'brand':
      rec = {
        video_typ: 'imagefilm',
        output_paket: hasSocial ? 'paket' : 'einzel',
        video_laenge: hasMesse || hasSocial ? 'medium' : 'long',
        features: hasMesse ? ['sound', 'animation'] : ['voiceover', 'sound'],
        reasoning_short: 'Ein Imagefilm baut Vertrauen auf — Raum für Werte und Persönlichkeit.',
      };
      break;

    case 'recruiting':
      rec = {
        video_typ: 'recruiting',
        output_paket: hasSocial ? 'paket' : 'einzel',
        video_laenge: 'medium',
        features: ['voiceover'],
        reasoning_short: hasSocial
          ? 'Recruiting-Video + Kurzvideos: Hauptvideo für die Karriere-Seite, kurze Hooks für LinkedIn und Instagram.'
          : 'Fokussiertes Recruiting-Video für deine Karriere-Seite und Stellenanzeigen.',
      };
      break;

    case 'social':
      rec = {
        video_typ: 'reel_paket',
        output_paket: '',
        video_laenge: '',
        features: [],
        reasoning_short: '12 Kurzvideos in einem ½ Drehtag — konstante Sichtbarkeit auf Social Media über 3 Monate.',
      };
      break;

    case 'explain':
      rec = {
        video_typ: 'erklaer_real',
        output_paket: '',
        video_laenge: hasSocial ? 'medium' : 'long',
        features: ['voiceover'],
        reasoning_short: 'Erklärvideo mit echtem Material wirkt glaubwürdiger als reine Animation.',
      };
      break;

    case 'technical':
      rec = {
        video_typ: 'animation_tech',
        output_paket: '',
        video_laenge: hasMesse ? 'medium' : 'long',
        features: hasMesse ? ['sound'] : ['voiceover', 'sound'],
        reasoning_short: 'Technische Animation zeigt, was Realbild nicht zeigen kann — Schnitte durchs Bauteil, Materialflüsse, unsichtbare Vorgänge.',
      };
      break;

    case 'sales':
      rec = {
        video_typ: 'werbespot',
        output_paket: hasAds || hasSocial ? 'paket' : 'einzel',
        video_laenge: 'medium',
        features: ['voiceover'],
        reasoning_short: hasAds
          ? 'Werbespot + Kurzvideos: Hauptvideo für die Landingpage, Social-Cuts für Meta- und YouTube-Ads.'
          : 'Klassischer Werbespot mit klarem Call-to-Action — direkt für die Landingpage.',
      };
      break;
  }

  return rec;
}

export function goalLabel(id) {
  return (GOALS.find((g) => g.id === id) || {}).label || id;
}

export function channelLabel(id) {
  return (CHANNELS.find((c) => c.id === id) || {}).label || id;
}
