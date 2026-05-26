/* ============================================================
   Recommendation-Engine
   Übersetzt Ziel + Budget + Branche → empfohlener Video-Typ + Konfig.

   MUSS synchron mit includes/Services/Recommender.php gehalten werden.
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
    hint: 'Maschinen, Bauteile, Prozesse zeigen — auch was im Realbild unsichtbar ist',
    icon: '⚙️',
  },
  {
    id: 'sales',
    label: 'Online verkaufen / Conversion steigern',
    hint: 'Performance-Anzeigen, Landingpage-Conversion',
    icon: '🛒',
  },
];

export const BUDGETS = [
  { id: 'low',     label: 'Bis 2.500 €',           hint: 'Schlanker Einstieg', icon: '💶' },
  { id: 'medium',  label: '2.500 – 5.000 €',       hint: 'Standard-Projekte',  icon: '💶💶' },
  { id: 'high',    label: '5.000 – 10.000 €',      hint: 'Mehr Reichweite, mehr Output', icon: '💶💶💶' },
  { id: 'premium', label: 'Über 10.000 €',         hint: 'Komplette Kampagne mit allem Drum und Dran', icon: '💎' },
  { id: 'unknown', label: 'Weiß noch nicht',       hint: 'Mach uns gerne einen Vorschlag', icon: '🤔' },
];

/**
 * Erzeugt die Empfehlung aus Ziel + Budget.
 *
 * @param {string} goal
 * @param {string} budget
 * @returns {{video_typ, output_paket, video_laenge, features, reasoning_short}}
 */
export function recommend(goal, budget) {
  const isLow     = budget === 'low';
  const isMedium  = budget === 'medium' || budget === 'unknown';
  const isHigh    = budget === 'high';
  const isPremium = budget === 'premium';

  // Default-Fallback
  let rec = {
    video_typ: 'werbespot',
    output_paket: 'einzel',
    video_laenge: 'medium',
    features: [],
    reasoning_short: '',
  };

  switch (goal) {
    case 'awareness':
      if (isLow) {
        rec = {
          video_typ: 'reel_paket', output_paket: '', video_laenge: '', features: ['drohne'],
          reasoning_short: 'Bei schmalem Budget bekommst du mit dem Reel-Paket maximale Reichweite – 12 Kurzvideos für 3 Monate Social-Sichtbarkeit.',
        };
      } else if (isPremium) {
        rec = {
          video_typ: 'werbespot', output_paket: 'kampagne', video_laenge: 'medium', features: ['voiceover', 'sound', 'drohne'],
          reasoning_short: 'Eine komplette Kampagne: Hauptspot + Kurzvideos + Bonus-Material. Damit bespielst du alle Kanäle mit einem Drehtag.',
        };
      } else {
        rec = {
          video_typ: 'werbespot', output_paket: 'paket', video_laenge: 'medium', features: ['voiceover', 'sound'],
          reasoning_short: 'Werbespot mit Kurzvideos für Social Media – klassische Werbeflächen + organische Reichweite in einem Aufwasch.',
        };
      }
      break;

    case 'brand':
      if (isLow) {
        rec = {
          video_typ: 'imagefilm', output_paket: 'einzel', video_laenge: 'medium', features: ['voiceover'],
          reasoning_short: 'Ein kompakter Imagefilm (60–90 Sek.) für deine Website – Vertrauen aufbauen, ohne dein Budget zu sprengen.',
        };
      } else if (isPremium) {
        rec = {
          video_typ: 'imagefilm', output_paket: 'kampagne', video_laenge: 'long', features: ['voiceover', 'sound', 'drohne'],
          reasoning_short: 'Ein vollwertiger 2–3-Min.-Imagefilm + Social-Cuts + Behind-the-Scenes. Genug Raum, eure Werte und Persönlichkeit zu zeigen.',
        };
      } else {
        rec = {
          video_typ: 'imagefilm', output_paket: 'einzel', video_laenge: 'long', features: ['voiceover', 'sound'],
          reasoning_short: 'Ein Imagefilm in der bewährten 2–3-Min.-Form gibt Raum für Story und Persönlichkeit – wirkt langfristig auf Vertrauen.',
        };
      }
      break;

    case 'recruiting':
      if (isLow) {
        rec = {
          video_typ: 'recruiting', output_paket: 'einzel', video_laenge: 'medium', features: [],
          reasoning_short: 'Ein fokussiertes Recruiting-Video für deine Karriere-Seite und Stellenanzeigen – schlank und auf den Punkt.',
        };
      } else if (isPremium) {
        rec = {
          video_typ: 'recruiting', output_paket: 'kampagne', video_laenge: 'long', features: ['voiceover', 'drohne'],
          reasoning_short: 'Vollkampagne: Hauptvideo + Kurzvideos für Social Recruiting + Bonus-Material. Maximaler Bewerber-Funnel.',
        };
      } else {
        rec = {
          video_typ: 'recruiting', output_paket: 'paket', video_laenge: 'medium', features: ['voiceover'],
          reasoning_short: 'Recruiting-Video + Kurzvideos für Social Media: Authentische Einblicke ins Team plus Hooks für LinkedIn und Instagram.',
        };
      }
      break;

    case 'social':
      rec = {
        video_typ: 'reel_paket', output_paket: '', video_laenge: '',
        features: isPremium || isHigh ? ['drohne'] : [],
        reasoning_short: '12 Kurzvideos für 30–60 Sek. in einem halben Drehtag – konstanter Content für Instagram, TikTok und LinkedIn über 3 Monate.',
      };
      break;

    case 'explain':
      if (isLow) {
        rec = {
          video_typ: 'erklaer_anim', output_paket: '', video_laenge: 'short', features: ['voiceover'],
          reasoning_short: 'Eine kurze animierte Erklärung (15–30 Sek.) ist günstig produziert und perfekt für Social-Hooks.',
        };
      } else if (isPremium) {
        rec = {
          video_typ: 'erklaer_real', output_paket: '', video_laenge: 'extra_long', features: ['voiceover', 'animation', 'sound'],
          reasoning_short: 'Längeres Erklärvideo mit Real-Material: Glaubwürdiger als reine Animation und genug Zeit, das Thema sauber aufzubauen.',
        };
      } else {
        rec = {
          video_typ: 'erklaer_real', output_paket: '', video_laenge: 'long', features: ['voiceover'],
          reasoning_short: 'Erklärvideo mit echtem Material (2–3 Min.): Glaubwürdiger als reine Animation und ausreichend Zeit, das Thema verständlich aufzubauen.',
        };
      }
      break;

    case 'technical':
      if (isPremium) {
        rec = {
          video_typ: 'animation_tech', output_paket: '', video_laenge: 'long', features: ['voiceover', 'sound'],
          reasoning_short: 'Technische Animation visualisiert, was Realbild nicht zeigt – Schnitte durchs Bauteil, Materialflüsse, unsichtbare Vorgänge.',
        };
      } else if (isHigh) {
        rec = {
          video_typ: 'animation_tech', output_paket: '', video_laenge: 'medium', features: ['voiceover'],
          reasoning_short: 'Eine fokussierte technische Animation (60–90 Sek.) – ideal für Produkt-Detail-Seiten und Messe-Loops.',
        };
      } else {
        rec = {
          video_typ: 'animation_3d', output_paket: '', video_laenge: 'medium', features: ['voiceover'],
          reasoning_short: 'Eine 3D-Produkt-Animation in 60–90 Sek. zeigt dein Produkt von allen Seiten – schon mit moderatem Budget realistisch.',
        };
      }
      break;

    case 'sales':
      if (isLow) {
        rec = {
          video_typ: 'werbespot', output_paket: 'einzel', video_laenge: 'medium', features: ['voiceover'],
          reasoning_short: 'Ein klassischer 60-Sek.-Werbespot konvertiert – einfache Botschaft, klarer Call-to-Action.',
        };
      } else if (isPremium) {
        rec = {
          video_typ: 'werbespot', output_paket: 'kampagne', video_laenge: 'medium', features: ['voiceover', 'sound', 'drohne'],
          reasoning_short: 'Komplette Performance-Kampagne: Hauptspot + Kurzvideos + A/B-Material für Meta-Ads, YouTube und LinkedIn.',
        };
      } else {
        rec = {
          video_typ: 'werbespot', output_paket: 'paket', video_laenge: 'medium', features: ['voiceover', 'sound'],
          reasoning_short: 'Werbespot + Kurzvideos für Performance-Anzeigen: Hauptvideo für die Landingpage, Social-Cuts für Meta- und YouTube-Ads.',
        };
      }
      break;
  }

  return rec;
}
