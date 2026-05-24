import React from 'react';

/* ============================================================
   Quiz aus Kundensicht: nur Dinge, die der Kunde wirklich kennt:
   - Was für ein Video (Ergebnis)
   - Welche Outputs er bekommt (Liefer-Paket)
   - Welche Features im Video drin sein sollen
   - Wann er es braucht
   - Wer er ist
   Technische Aufwand-Fragen ("wie viele Drehtage?") fliegen raus.
   ============================================================ */

const VIDEO_TYPEN = [
  { id: 'imagefilm',    label: 'Imagefilm',         hint: 'Marke greifbar machen, Vertrauen aufbauen' },
  { id: 'werbespot',    label: 'Werbespot / Reel',  hint: 'Verkauf, Reichweite, Social-Hook' },
  { id: 'recruiting',   label: 'Recruiting-Video',  hint: 'Bewerber:innen gewinnen' },
  { id: 'erklaervideo', label: 'Erklärvideo',       hint: 'Komplexes verständlich machen' },
];

const OUTPUT_PAKETE = [
  {
    id: 'einzel',
    label: 'Ein fertiges Hauptvideo',
    hint: 'Z. B. ein Image-/Recruiting-Spot für deine Website oder einen Kanal.',
  },
  {
    id: 'paket',
    label: 'Hauptvideo + Social-Cuts',
    hint: '1 Hauptvideo + 2–3 kurze Versionen (Reels/Shorts/TikTok) für Social.',
    badge: 'Empfohlen',
  },
  {
    id: 'kampagne',
    label: 'Vollkampagne',
    hint: 'Hauptvideo + Social-Cuts + Behind-the-Scenes + Story-Snippets.',
  },
];

const FEATURES = [
  { id: 'voiceover',  label: 'Voiceover / Sprecher:in' },
  { id: 'untertitel', label: 'Untertitel (für Stumm-Wiedergabe)' },
  { id: 'animation',  label: 'Animierte Texte / Lower-Thirds' },
  { id: 'drohne',     label: 'Drohnen-Aufnahmen' },
  { id: 'musik',      label: 'Lizenzierte Musik' },
  { id: 'mehrsprachig', label: 'Mehrsprachige Versionen' },
];

const ZEITRAHMEN = [
  { id: 'flexibel', label: 'Flexibel (4–6 Wochen)' },
  { id: 'express',  label: 'Express (<3 Wochen)', badge: '+20 %' },
  { id: 'planung',  label: 'Plane noch (>6 Wochen)' },
];

const BRANCHEN = [
  'Handwerk',
  'Industrie / Produktion',
  'Pflege / Gesundheit',
  'Hotel / Gastro',
  'Dienstleister / B2B',
  'Bildung / Verein',
  'Sonstiges',
];

export default function Quiz(props) {
  const {
    step, steps, answers, setAnswers, lead, setLead,
    onNext, onBack, onSubmit, submitting, error,
  } = props;

  const key = steps[step];

  function set(field, value) {
    setAnswers((a) => ({ ...a, [field]: value }));
  }

  function toggleFeature(id) {
    setAnswers((a) => {
      const cur = Array.isArray(a.features) ? a.features : [];
      return {
        ...a,
        features: cur.includes(id) ? cur.filter((f) => f !== id) : [ ...cur, id ],
      };
    });
  }

  /* ---------- Step 1: Video-Typ ---------- */
  if (key === 'video_typ') {
    return (
      <Step
        title="Welche Art von Video brauchst du?"
        subtitle="Was soll das Video bei deiner Zielgruppe auslösen?"
        canNext={!!answers.video_typ}
        onNext={onNext}
      >
        <div className="wgk__grid wgk__grid--2">
          {VIDEO_TYPEN.map((t) => (
            <button
              type="button"
              key={t.id}
              className={`wgk__card ${answers.video_typ === t.id ? 'is-active' : ''}`}
              onClick={() => set('video_typ', t.id)}
            >
              <strong>{t.label}</strong>
              <span>{t.hint}</span>
            </button>
          ))}
        </div>
      </Step>
    );
  }

  /* ---------- Step 2: Output-Paket ---------- */
  if (key === 'output_paket') {
    return (
      <Step
        title="Was möchtest du am Ende in der Hand haben?"
        subtitle="Du musst nichts über Drehtage oder Schnitt-Stunden wissen — sag uns einfach, welches Liefer-Paket du brauchst."
        canNext={!!answers.output_paket}
        onNext={onNext}
        onBack={onBack}
      >
        <div className="wgk__stack">
          {OUTPUT_PAKETE.map((p) => (
            <button
              type="button"
              key={p.id}
              className={`wgk__card ${answers.output_paket === p.id ? 'is-active' : ''}`}
              onClick={() => set('output_paket', p.id)}
            >
              <strong style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                <span>{p.label}</span>
                {p.badge && <em className="wgk__badge">{p.badge}</em>}
              </strong>
              <span>{p.hint}</span>
            </button>
          ))}
        </div>
      </Step>
    );
  }

  /* ---------- Step 3: Features ---------- */
  if (key === 'features') {
    const sel = Array.isArray(answers.features) ? answers.features : [];
    return (
      <Step
        title="Welche Features soll dein Video haben?"
        subtitle="Mehrfach-Auswahl möglich. Du kannst diesen Schritt auch überspringen — wir empfehlen dir das passende Setup."
        canNext={true}
        nextLabel="Weiter"
        onNext={onNext}
        onBack={onBack}
      >
        <div className="wgk__checklist">
          {FEATURES.map((f) => {
            const checked = sel.includes(f.id);
            return (
              <button
                type="button"
                key={f.id}
                className={`wgk__checkitem ${checked ? 'is-checked' : ''}`}
                onClick={() => toggleFeature(f.id)}
              >
                <span className="wgk__checkitem-box">{checked ? '✓' : ''}</span>
                <span>{f.label}</span>
              </button>
            );
          })}
        </div>
      </Step>
    );
  }

  /* ---------- Step 4: Zeitrahmen ---------- */
  if (key === 'zeitrahmen') {
    return (
      <Step
        title="Wann brauchst du das Video?"
        canNext={!!answers.zeitrahmen}
        onNext={onNext}
        onBack={onBack}
      >
        <div className="wgk__stack">
          {ZEITRAHMEN.map((z) => (
            <button
              type="button"
              key={z.id}
              className={`wgk__row ${answers.zeitrahmen === z.id ? 'is-active' : ''}`}
              onClick={() => set('zeitrahmen', z.id)}
            >
              <span>{z.label}</span>
              {z.badge && <em className="wgk__badge">{z.badge}</em>}
            </button>
          ))}
        </div>
      </Step>
    );
  }

  /* ---------- Step 5: Branche + Website + Ziel ---------- */
  if (key === 'kontext') {
    return (
      <Step
        title="Wer bist du?"
        subtitle="Damit wir das Konzept auf deine Branche und Zielgruppe zuschneiden."
        canNext={!!answers.branche}
        onNext={onNext}
        onBack={onBack}
      >
        <div className="wgk__grid wgk__grid--2">
          {BRANCHEN.map((b) => (
            <button
              type="button"
              key={b}
              className={`wgk__card wgk__card--narrow ${answers.branche === b ? 'is-active' : ''}`}
              onClick={() => set('branche', b)}
            >
              <strong>{b}</strong>
            </button>
          ))}
        </div>

        <label className="wgk__field">
          <span>Deine Website (für ein passgenaueres Konzept – optional)</span>
          <input
            type="url"
            placeholder="https://deine-firma.de"
            value={answers.website}
            onChange={(e) => set('website', e.target.value)}
          />
        </label>
        <label className="wgk__field">
          <span>Wofür konkret soll das Video Wirkung erzeugen? (optional)</span>
          <textarea
            rows={3}
            placeholder="z. B. mehr Bewerbungen für die Pflegekräfte-Stellen, oder einen ersten Eindruck für Neukunden"
            value={answers.ziel}
            onChange={(e) => set('ziel', e.target.value)}
          />
        </label>
      </Step>
    );
  }

  /* ---------- Step 6: Lead ---------- */
  if (key === 'lead') {
    const valid = lead.vorname.trim().length > 1 && /\S+@\S+\.\S+/.test(lead.email);
    return (
      <Step
        title="Wohin schicken wir dein individuelles PDF?"
        subtitle="Wir erstellen daraus ein konkretes Konzept inkl. Preis-Range und mailen es dir in wenigen Sekunden zu."
        canNext={valid && !submitting}
        nextLabel={submitting ? 'Wird erstellt …' : 'Konzept generieren'}
        onNext={onSubmit}
        onBack={onBack}
      >
        <label className="wgk__field">
          <span>Vorname</span>
          <input
            type="text"
            value={lead.vorname}
            onChange={(e) => setLead({ ...lead, vorname: e.target.value })}
            placeholder="Anna"
            required
          />
        </label>
        <label className="wgk__field">
          <span>E-Mail</span>
          <input
            type="email"
            value={lead.email}
            onChange={(e) => setLead({ ...lead, email: e.target.value })}
            placeholder="anna@firma.de"
            required
          />
        </label>
        <label className="wgk__check">
          <input
            type="checkbox"
            checked={lead.marketing_opt_in}
            onChange={(e) => setLead({ ...lead, marketing_opt_in: e.target.checked })}
          />
          <span>Ja, gelegentlich Updates zu Videomarketing-Tipps von WG-Digital erhalten (jederzeit abbestellbar).</span>
        </label>
        <p className="wgk__note">
          Kein Spam, keine automatischen Newsletter ohne dein OK.
        </p>
        {error && <p className="wgk__error">{error}</p>}
      </Step>
    );
  }

  return null;
}

function Step({ title, subtitle, children, canNext, onNext, onBack, nextLabel = 'Weiter' }) {
  return (
    <div className="wgk__step">
      <h2 className="wgk__title">{title}</h2>
      {subtitle && <p className="wgk__subtitle">{subtitle}</p>}
      <div className="wgk__content">{children}</div>
      <div className="wgk__actions">
        {onBack && (
          <button type="button" className="wgk__btn wgk__btn--ghost" onClick={onBack}>
            Zurück
          </button>
        )}
        <button
          type="button"
          className="wgk__btn wgk__btn--primary"
          onClick={onNext}
          disabled={!canNext}
        >
          {nextLabel}
        </button>
      </div>
    </div>
  );
}
