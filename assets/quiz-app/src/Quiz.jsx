import React from 'react';
import { isFeatureAvailable } from './pricing.js';

/* ============================================================
   Quiz aus Kundensicht.
   - 8 Video-Typen in 2 Sektionen
   - Quiz-Flow verzweigt je nach Typ (siehe stepsForType)
   - Feature-Liste filtert sich je nach Typ
   ============================================================ */

const VIDEO_TYPEN_CLASSIC = [
  { id: 'imagefilm',  label: 'Imagefilm',         hint: 'Marke greifbar machen',            icon: '🎬' },
  { id: 'werbespot',  label: 'Werbespot',         hint: 'Verkauf, Hero-Video, Pre-Roll',    icon: '📣' },
  { id: 'recruiting', label: 'Recruiting-Video',  hint: 'Bewerber:innen gewinnen',          icon: '🤝' },
  { id: 'reel_paket', label: 'Reel-Paket',        hint: '12 Reels in einem ½ Drehtag',      icon: '📱', badge: 'Abo 3 × 500 €' },
];

const VIDEO_TYPEN_ANIMATION = [
  { id: 'erklaer_real',   label: 'Erklärvideo (Real)',   hint: 'Mit echtem Material gedreht', icon: '💡' },
  { id: 'erklaer_anim',   label: 'Erklärvideo (2D)',     hint: 'Animiertes Erklärvideo',      icon: '✏️' },
  { id: 'animation_3d',   label: '3D-Animation',         hint: 'Volumen, Materialien, Licht', icon: '🧊' },
  { id: 'animation_tech', label: 'Technische Animation', hint: 'Maschinen, Bauteile, Prozess', icon: '⚙️' },
];

const OUTPUT_PAKETE = [
  { id: 'einzel',   label: 'Ein fertiges Hauptvideo',    hint: 'Z. B. ein Image-Spot für deine Website oder einen Kanal.',                       icon: '🎯' },
  { id: 'paket',    label: 'Hauptvideo + Social-Cuts',   hint: '1 Hauptvideo + 2–3 kurze Versionen (Reels/Shorts) für Social.', icon: '📦', badge: 'Empfohlen' },
  { id: 'kampagne', label: 'Vollkampagne',               hint: 'Hauptvideo + Social-Cuts + Behind-the-Scenes + Story-Snippets.', icon: '🚀' },
];

const VIDEO_LAENGEN = [
  { id: 'short',      label: '15–30 Sek.', hint: 'Reel, Short, TikTok – ein Punch.',            icon: '⚡' },
  { id: 'medium',     label: '60–90 Sek.', hint: 'Klassischer Spot, Pre-Roll, Hero-Video.',     icon: '🎞️' },
  { id: 'long',       label: '2–3 Min.',   hint: 'Imagefilm mit Story und mehreren Szenen.',    icon: '📺' },
  { id: 'extra_long', label: '4–5 Min.',   hint: 'Erklärfilm, Mitarbeiter-Portrait, Bewegt-FAQ.', icon: '🎥' },
];

const FEATURES = [
  { id: 'voiceover',    label: 'Voiceover / Sprecher:in',        icon: '🎙️' },
  { id: 'animation',    label: 'Animierte Texte / Lower-Thirds', icon: '✨' },
  { id: 'drohne',       label: 'Drohnen-Aufnahmen',              icon: '🚁' },
  { id: 'sound',        label: 'Sound Design (Atmo, SFX)',       icon: '🔊' },
  { id: 'mehrsprachig', label: 'Mehrsprachige Versionen',        icon: '🌐' },
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

  /* ---------- Step 1: Video-Typ (2 Sektionen) ---------- */
  if (key === 'video_typ') {
    return (
      <Step
        title="Welche Art von Video brauchst du?"
        subtitle="Was soll das Video bei deiner Zielgruppe auslösen?"
        canNext={!!answers.video_typ}
        onNext={onNext}
      >
        <h3 className="wgk__section-title">📹 Klassische Videoproduktion</h3>
        <div className="wgk__grid wgk__grid--2">
          {VIDEO_TYPEN_CLASSIC.map((t) => (
            <IconCard
              key={t.id}
              icon={t.icon}
              label={t.label}
              hint={t.hint}
              badge={t.badge}
              active={answers.video_typ === t.id}
              onClick={() => set('video_typ', t.id)}
            />
          ))}
        </div>

        <h3 className="wgk__section-title" style={{ marginTop: 18 }}>🎨 Erklärvideo & Animation</h3>
        <div className="wgk__grid wgk__grid--2">
          {VIDEO_TYPEN_ANIMATION.map((t) => (
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

  /* ---------- Step 2: Output-Paket (nur bei klassischen Videos) ---------- */
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
    const isPerMinute = ['erklaer_real', 'erklaer_anim', 'animation_3d', 'animation_tech'].includes(answers.video_typ);
    return (
      <Step
        title="Wie lang soll dein Video sein?"
        subtitle={isPerMinute
          ? 'Bei Animation und Erklärvideos ist die Länge der Haupt-Preistreiber – jede Minute mehr braucht mehr Aufwand.'
          : 'Längere Videos brauchen mehr Story-Bögen und mehr Schnitt.'}
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

  /* ---------- Step 4: Features (gefiltert nach Typ-Support) ---------- */
  if (key === 'features') {
    const sel = Array.isArray(answers.features) ? answers.features : [];
    const availableFeatures = FEATURES.filter((f) => isFeatureAvailable(answers.video_typ, f.id));

    return (
      <Step
        title="Welche Features soll dein Video haben?"
        subtitle="Mehrfach-Auswahl möglich. Untertitel und lizenzierte Musik sind bei uns immer Standard – das musst du nicht extra wählen."
        canNext={true}
        nextLabel="Weiter"
        onNext={onNext}
        onBack={onBack}
      >
        {availableFeatures.length === 0 ? (
          <p className="wgk__note">Für diese Auswahl sind alle Standard-Features bereits inklusive.</p>
        ) : (
          <div className="wgk__checklist">
            {availableFeatures.map((f) => {
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
        )}
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
          <span>Deine Website (für eine passgenauere Analyse – optional)</span>
          <input
            type="text"
            inputMode="url"
            autoCapitalize="off"
            autoCorrect="off"
            spellCheck={false}
            placeholder="z. B. deine-firma.de"
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
    const valid = lead.name.trim().split(/\s+/).length >= 2 && /\S+@\S+\.\S+/.test(lead.email);
    return (
      <Step
        title="Wohin schicken wir dein individuelles PDF?"
        subtitle="Wir erstellen daraus eine konkrete Analyse + Preis-Range und mailen es dir in wenigen Sekunden zu."
        canNext={valid && !submitting}
        nextLabel={submitting ? 'Wird erstellt …' : 'Konzept generieren'}
        onNext={onSubmit}
        onBack={onBack}
      >
        <label className="wgk__field">
          <span>Vor- und Nachname</span>
          <input
            type="text"
            value={lead.name}
            onChange={(e) => setLead({ ...lead, name: e.target.value })}
            placeholder="Anna Müller"
            autoComplete="name"
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
            autoComplete="email"
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
        <p className="wgk__note">Kein Spam, keine automatischen Newsletter ohne dein OK. Alle Preise verstehen sich netto.</p>
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
