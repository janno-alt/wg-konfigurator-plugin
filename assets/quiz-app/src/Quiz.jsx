import React from 'react';

/* ============================================================
   Quiz aus Kundensicht — Steps mit Image-Icons + Live-Preis-Anbindung.
   Technische Aufwandsfragen ("wie viele Drehtage?") fliegen raus.
   ============================================================ */

const VIDEO_TYPEN = [
  { id: 'imagefilm',    label: 'Imagefilm',         hint: 'Marke greifbar machen, Vertrauen aufbauen', icon: '🎬' },
  { id: 'werbespot',    label: 'Werbespot / Reel',  hint: 'Verkauf, Reichweite, Social-Hook',         icon: '📱' },
  { id: 'recruiting',   label: 'Recruiting-Video',  hint: 'Bewerber:innen gewinnen',                  icon: '🤝' },
  { id: 'erklaervideo', label: 'Erklärvideo',       hint: 'Komplexes verständlich machen',            icon: '💡' },
];

const OUTPUT_PAKETE = [
  {
    id: 'einzel',
    label: 'Ein fertiges Hauptvideo',
    hint: 'Z. B. ein Image-/Recruiting-Spot für deine Website oder einen Kanal.',
    icon: '🎯',
  },
  {
    id: 'paket',
    label: 'Hauptvideo + Social-Cuts',
    hint: '1 Hauptvideo + 2–3 kurze Versionen (Reels/Shorts/TikTok) für Social.',
    icon: '📦',
    badge: 'Empfohlen',
  },
  {
    id: 'kampagne',
    label: 'Vollkampagne',
    hint: 'Hauptvideo + Social-Cuts + Behind-the-Scenes + Story-Snippets.',
    icon: '🚀',
  },
];

const VIDEO_LAENGEN = [
  { id: 'short',      label: '15–30 Sek.',  hint: 'Reel, Short, TikTok – ein Punch.',                icon: '⚡' },
  { id: 'medium',     label: '60–90 Sek.',  hint: 'Klassischer Spot, Pre-Roll, Hero-Video.',         icon: '🎞️' },
  { id: 'long',       label: '2–3 Min.',    hint: 'Imagefilm mit Story und mehreren Szenen.',        icon: '📺' },
  { id: 'extra_long', label: '4–5 Min.',    hint: 'Erklärfilm, Mitarbeiter-Portrait, Bewegt-FAQ.',   icon: '🎥' },
];

const FEATURES = [
  { id: 'voiceover',    label: 'Voiceover / Sprecher:in',           icon: '🎙️' },
  { id: 'untertitel',   label: 'Untertitel (für Stumm-Wiedergabe)', icon: '💬' },
  { id: 'animation',    label: 'Animierte Texte / Lower-Thirds',    icon: '✨' },
  { id: 'drohne',       label: 'Drohnen-Aufnahmen',                 icon: '🚁' },
  { id: 'musik',        label: 'Lizenzierte Musik',                 icon: '🎵' },
  { id: 'mehrsprachig', label: 'Mehrsprachige Versionen',           icon: '🌐' },
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
            <IconCard
              key={t.id}
              icon={t.icon}
              label={t.label}
              hint={t.hint}
              active={answers.video_typ === t.id}
              onClick={() => set('video_typ', t.id)}
            />
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
        subtitle="Du musst nichts über Drehtage oder Schnitt-Stunden wissen. Wähle einfach das Liefer-Paket, das zu deinem Vorhaben passt."
        canNext={!!answers.output_paket}
        onNext={onNext}
        onBack={onBack}
      >
        <div className="wgk__stack">
          {OUTPUT_PAKETE.map((p) => (
            <IconCard
              key={p.id}
              icon={p.icon}
              label={p.label}
              hint={p.hint}
              badge={p.badge}
              wide
              active={answers.output_paket === p.id}
              onClick={() => set('output_paket', p.id)}
            />
          ))}
        </div>
      </Step>
    );
  }

  /* ---------- Step 3: Video-Länge ---------- */
  if (key === 'video_laenge') {
    return (
      <Step
        title="Wie lang soll dein Hauptvideo sein?"
        subtitle="Längere Videos brauchen mehr Story-Bögen und mehr Schnitt – kürzere mehr Verdichtung. Der Preis passt sich entsprechend an."
        canNext={!!answers.video_laenge}
        onNext={onNext}
        onBack={onBack}
      >
        <div className="wgk__grid wgk__grid--2">
          {VIDEO_LAENGEN.map((l) => (
            <IconCard
              key={l.id}
              icon={l.icon}
              label={l.label}
              hint={l.hint}
              active={answers.video_laenge === l.id}
              onClick={() => set('video_laenge', l.id)}
            />
          ))}
        </div>
      </Step>
    );
  }

  /* ---------- Step 4: Features ---------- */
  if (key === 'features') {
    const sel = Array.isArray(answers.features) ? answers.features : [];
    return (
      <Step
        title="Welche Features soll dein Video haben?"
        subtitle="Mehrfach-Auswahl. Diesen Schritt kannst du auch überspringen — wir empfehlen dir das passende Setup."
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
                <span className="wgk__checkitem-icon">{f.icon}</span>
                <span className="wgk__checkitem-label">{f.label}</span>
                <span className="wgk__checkitem-box">{checked ? '✓' : ''}</span>
              </button>
            );
          })}
        </div>
      </Step>
    );
  }

  /* ---------- Step 5: Zeitrahmen ---------- */
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

  /* ---------- Step 6: Branche + Website + Ziel ---------- */
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

  /* ---------- Step 7: Lead ---------- */
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
        <p className="wgk__note">Kein Spam, keine automatischen Newsletter ohne dein OK.</p>
        {error && <p className="wgk__error">{error}</p>}
      </Step>
    );
  }

  return null;
}

/* ---------- Components ---------- */

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

function IconCard({ icon, label, hint, badge, active, wide, onClick }) {
  return (
    <button
      type="button"
      className={`wgk__iconcard ${wide ? 'wgk__iconcard--wide' : ''} ${active ? 'is-active' : ''}`}
      onClick={onClick}
    >
      <span className="wgk__iconcard-icon" aria-hidden="true">{icon}</span>
      <span className="wgk__iconcard-body">
        <span className="wgk__iconcard-label">
          {label}
          {badge && <em className="wgk__badge">{badge}</em>}
        </span>
        {hint && <span className="wgk__iconcard-hint">{hint}</span>}
      </span>
    </button>
  );
}
