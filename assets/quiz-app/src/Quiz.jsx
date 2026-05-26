import React from 'react';
import { GOALS, BUDGETS } from './recommendation.js';
import { VIDEO_TYPES } from './pricing.js';

/* ============================================================
   Quiz aus Kundensicht — v0.9: Ziel-basiert
   Der Kunde wählt nicht mehr direkt Video-Typ/Output-Paket/Länge,
   sondern Ziel + Budget. Plugin schlägt im Hintergrund den passenden
   Video-Typ vor (in der Sidebar sichtbar).
   ============================================================ */

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
    step, steps, answers, setAnswers, lead, recommendation,
    onNext, onBack, onSubmit, submitting, error,
  } = props;

  const key = steps[step];

  function set(field, value) {
    setAnswers((a) => ({ ...a, [field]: value }));
  }

  /* ---------- Step 1: Ziel ---------- */
  if (key === 'goal') {
    return (
      <Step
        title="Was willst du mit dem Video erreichen?"
        subtitle="Sag uns dein Ziel — wir empfehlen dir im Hintergrund die passende Video-Form."
        canNext={!!answers.goal}
        onNext={onNext}
      >
        <div className="wgk__stack">
          {GOALS.map((g) => (
            <IconCard
              key={g.id}
              icon={g.icon}
              label={g.label}
              hint={g.hint}
              wide
              active={answers.goal === g.id}
              onClick={() => set('goal', g.id)}
            />
          ))}
        </div>
      </Step>
    );
  }

  /* ---------- Step 2: Branche ---------- */
  if (key === 'branche') {
    return (
      <Step
        title="In welcher Branche bist du?"
        subtitle="Damit wir das Konzept und die Botschaften auf deine Zielgruppe abstimmen können."
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
      </Step>
    );
  }

  /* ---------- Step 3: Budget ---------- */
  if (key === 'budget') {
    return (
      <Step
        title="Wie viel Budget hast du eingeplant?"
        subtitle={'Keine Sorge — wir empfehlen passend zum Budget. „Weiß noch nicht" ist auch eine Antwort.'}
        canNext={!!answers.budget}
        onNext={onNext}
        onBack={onBack}
      >
        <div className="wgk__stack">
          {BUDGETS.map((b) => (
            <IconCard
              key={b.id}
              icon={b.icon}
              label={b.label}
              hint={b.hint}
              wide
              active={answers.budget === b.id}
              onClick={() => set('budget', b.id)}
            />
          ))}
        </div>

        {recommendation && (
          <div className="wgk__rec-preview">
            <span className="wgk__rec-preview-eyebrow">Unsere Empfehlung</span>
            <strong>{VIDEO_TYPES[recommendation.video_typ]?.label}</strong>
            <p>{recommendation.reasoning_short}</p>
          </div>
        )}
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

  /* ---------- Step 5: Kontext + SUBMIT ---------- */
  if (key === 'kontext') {
    return (
      <Step
        title="Fast geschafft – ein paar letzte Details"
        subtitle="Damit das KI-Konzept zu dir passt. Wir analysieren bereits deine E-Mail-Domain automatisch."
        canNext={!submitting}
        nextLabel={submitting ? 'Wird erstellt …' : 'Konzept erstellen'}
        onNext={onSubmit}
        onBack={onBack}
      >
        <label className="wgk__field">
          <span>Deine Website (optional – für ein noch passgenaueres Konzept)</span>
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
          <span>Was wir noch wissen sollten? (optional)</span>
          <textarea
            rows={3}
            placeholder="z. B. konkrete Zielgruppe, vorhandene Werte, besondere Anforderungen"
            value={answers.ziel}
            onChange={(e) => set('ziel', e.target.value)}
          />
        </label>

        <p className="wgk__note">
          Wir mailen das fertige Konzept an <strong>{lead.email}</strong>. Alle Preise netto, zzgl. MwSt.
        </p>
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
