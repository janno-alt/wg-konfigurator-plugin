import React from 'react';

const VIDEO_TYPEN = [
  { id: 'imagefilm',    label: 'Imagefilm',     hint: 'Marke greifbar machen' },
  { id: 'werbespot',    label: 'Werbespot / Reel', hint: 'Verkauf & Reichweite' },
  { id: 'recruiting',   label: 'Recruiting-Video', hint: 'Bewerber gewinnen' },
  { id: 'erklaervideo', label: 'Erklärvideo',   hint: 'Komplexes verständlich machen' },
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

  if (key === 'video_typ') {
    return (
      <Step title="Welche Art von Video brauchst du?" canNext={!!answers.video_typ} onNext={onNext}>
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

  if (key === 'drehtage') {
    return (
      <Step title="Wie viele Drehtage?" canNext={answers.drehtage >= 1} onNext={onNext} onBack={onBack}>
        <div className="wgk__grid wgk__grid--3">
          {[1, 2, 3].map((n) => (
            <button
              type="button"
              key={n}
              className={`wgk__card wgk__card--narrow ${answers.drehtage === n ? 'is-active' : ''}`}
              onClick={() => set('drehtage', n)}
            >
              <strong>{n} {n === 1 ? 'Tag' : 'Tage'}</strong>
              <span>{n === 1 ? 'Kompakt' : n === 2 ? 'Standard' : 'Aufwendig'}</span>
            </button>
          ))}
        </div>
      </Step>
    );
  }

  if (key === 'zeitrahmen') {
    return (
      <Step title="Bis wann brauchst du das Video?" canNext={!!answers.zeitrahmen} onNext={onNext} onBack={onBack}>
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

  if (key === 'branche') {
    return (
      <Step title="In welcher Branche bist du?" canNext={!!answers.branche} onNext={onNext} onBack={onBack}>
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
          <span>Deine Website (für ein noch besseres Konzept – optional)</span>
          <input
            type="url"
            placeholder="https://deine-firma.de"
            value={answers.website}
            onChange={(e) => set('website', e.target.value)}
          />
        </label>
        <label className="wgk__field">
          <span>Was soll das Video bei deiner Zielgruppe auslösen? (optional)</span>
          <textarea
            rows={3}
            placeholder="z. B. mehr Bewerbungen für die Pflegekräfte-Stellen"
            value={answers.ziel}
            onChange={(e) => set('ziel', e.target.value)}
          />
        </label>
      </Step>
    );
  }

  if (key === 'lead') {
    const valid = lead.vorname.trim().length > 1 && /\S+@\S+\.\S+/.test(lead.email);
    return (
      <Step
        title="Wohin schicken wir dein individuelles PDF?"
        canNext={valid && !submitting}
        nextLabel={submitting ? 'Wird erstellt…' : 'Konzept generieren'}
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
          <span>Ja, ich möchte gelegentlich Updates zu Videomarketing-Tipps von WG-Digital erhalten (jederzeit abbestellbar).</span>
        </label>
        <p className="wgk__note">
          Wir berechnen dein individuelles Konzept inkl. Preis-Range und mailen dir das PDF in wenigen Sekunden zu.
          Kein Spam, keine automatischen Newsletter ohne dein OK.
        </p>
        {error && <p className="wgk__error">{error}</p>}
      </Step>
    );
  }

  return null;
}

function Step({ title, children, canNext, onNext, onBack, nextLabel = 'Weiter' }) {
  return (
    <div className="wgk__step">
      <h2 className="wgk__title">{title}</h2>
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
