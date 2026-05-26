import React from 'react';
import { GOALS, CHANNELS, GOAL_TO_TYPES } from './recommendation.js';
import { VIDEO_TYPES, LAENGE, PAKET, FEATURES, isFeatureAvailable, lengthsForType } from './pricing.js';

/* ============================================================
   Quiz v0.10 — Ziel + Branche + Kanäle + Zeitrahmen + Configure + Kontext
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

const FEATURE_TOOLTIPS = {
  voiceover:    'Profi-Sprecher:in spricht den Text ein. Wenn du oder dein Team selbst sprechen wollt, einfach abwählen.',
  animation:    'Eingeblendete Textgrafiken: z. B. „Frau Müller, Pflegedienstleitung". Untertitel sind eh immer Standard.',
  drohne:       'Luftaufnahmen für kinoreifen Look. Ideal für Außen-Locations.',
  sound:        'Atmosphäre, Soundeffekte, Akzente. Hintergrundmusik ist eh immer dabei.',
  mehrsprachig: 'Zusätzliche Sprachversion (z. B. Englisch) inkl. Übersetzung + Voiceover.',
};

export default function Quiz(props) {
  const {
    step, steps, answers, setAnswers, setConfigField, lead,
    recommendation, userOverride,
    onNext, onBack, onSubmit, submitting, error,
  } = props;

  const key = steps[step];

  function set(field, value) {
    setAnswers((a) => ({ ...a, [field]: value }));
  }

  function toggleChannel(id) {
    setAnswers((a) => {
      const cur = Array.isArray(a.channels) ? a.channels : [];
      return {
        ...a,
        channels: cur.includes(id) ? cur.filter((c) => c !== id) : [ ...cur, id ],
      };
    });
  }

  function toggleConfigFeature(id) {
    const cur = Array.isArray(answers.features) ? answers.features : [];
    setConfigField('features', cur.includes(id) ? cur.filter((f) => f !== id) : [ ...cur, id ]);
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
        subtitle="Damit wir das Konzept und die Botschaften auf deine Zielgruppe abstimmen."
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

  /* ---------- Step 3: Ausspiel-Kanäle (Multi-Select) ---------- */
  if (key === 'channels') {
    const sel = answers.channels || [];
    return (
      <Step
        title="Wo soll das Video laufen?"
        subtitle="Mehrfach-Auswahl. Beeinflusst die Konfiguration: Bei Social Media z. B. zusätzliche Kurzvideos, bei Messe-Einsatz ohne Ton entsprechende Anpassung."
        canNext={sel.length > 0}
        onNext={onNext}
        onBack={onBack}
      >
        <div className="wgk__checklist">
          {CHANNELS.map((c) => {
            const checked = sel.includes(c.id);
            return (
              <button
                type="button"
                key={c.id}
                className={`wgk__checkitem ${checked ? 'is-checked' : ''}`}
                onClick={() => toggleChannel(c.id)}
              >
                <span className="wgk__checkitem-icon">{c.icon}</span>
                <span className="wgk__checkitem-label">{c.label}</span>
                <span className="wgk__checkitem-box">{checked ? '✓' : ''}</span>
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

  /* ---------- Step 5: Configure (Anpassen-Übersicht) ---------- */
  if (key === 'configure') {
    return (
      <Step
        title="Deine Konfiguration"
        subtitle={userOverride
          ? 'Du hast die Empfehlung angepasst. Alle Änderungen siehst du live in der Preis-Übersicht rechts.'
          : 'Das schlagen wir dir vor. Du kannst alles einzeln anpassen.'}
        canNext={!!answers.video_typ}
        nextLabel="Weiter"
        onNext={onNext}
        onBack={onBack}
      >
        <ConfigureSection
          answers={answers}
          setConfigField={setConfigField}
          toggleConfigFeature={toggleConfigFeature}
        />
      </Step>
    );
  }

  /* ---------- Step 6: Kontext + SUBMIT ---------- */
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

/* ============================================================
   Configure-Section: zeigt alle Anpass-Optionen
   ============================================================ */
function ConfigureSection({ answers, setConfigField, toggleConfigFeature }) {
  const altTypes = (GOAL_TO_TYPES[answers.goal] || []).filter((t) => VIDEO_TYPES[t]);
  const currentType = VIDEO_TYPES[answers.video_typ];
  const allowedLengths = lengthsForType(answers.video_typ);

  return (
    <div className="wgk__config">
      {/* Video-Typ */}
      <div className="wgk__config-group">
        <div className="wgk__config-label">Video-Typ</div>
        <div className="wgk__config-typtabs">
          {altTypes.map((tId) => (
            <button
              key={tId}
              type="button"
              className={`wgk__config-typtab ${answers.video_typ === tId ? 'is-active' : ''}`}
              onClick={() => setConfigField('video_typ', tId)}
            >
              {VIDEO_TYPES[tId].label}
            </button>
          ))}
        </div>
      </div>

      {/* Output-Paket (nur wenn Typ es unterstützt) */}
      {currentType?.has_paket && (
        <div className="wgk__config-group">
          <div className="wgk__config-label">Liefer-Paket</div>
          <div className="wgk__config-options">
            {Object.entries(PAKET).map(([id, p]) => (
              <button
                key={id}
                type="button"
                className={`wgk__config-option ${answers.output_paket === id ? 'is-active' : ''}`}
                onClick={() => setConfigField('output_paket', id)}
              >
                <span className="wgk__config-option-label">{p.label}</span>
                {p.add_flat > 0 && (
                  <span className="wgk__config-option-price">+{p.add_flat} €</span>
                )}
              </button>
            ))}
          </div>
        </div>
      )}

      {/* Länge (nur wenn Typ es unterstützt) */}
      {currentType?.has_laenge && allowedLengths.length > 0 && (
        <div className="wgk__config-group">
          <div className="wgk__config-label">Länge</div>
          <div className="wgk__config-pills">
            {allowedLengths.map((lId) => (
              <button
                key={lId}
                type="button"
                className={`wgk__config-pill ${answers.video_laenge === lId ? 'is-active' : ''}`}
                onClick={() => setConfigField('video_laenge', lId)}
              >
                {LAENGE[lId].label}
              </button>
            ))}
          </div>
        </div>
      )}

      {/* Extras */}
      <div className="wgk__config-group">
        <div className="wgk__config-label">Extras (kannst du an- oder abwählen)</div>
        <div className="wgk__config-features">
          {Object.entries(FEATURES).map(([id, f]) => {
            if (!isFeatureAvailable(answers.video_typ, id)) return null;
            const checked = (answers.features || []).includes(id);
            return (
              <button
                key={id}
                type="button"
                className={`wgk__config-feature ${checked ? 'is-checked' : ''}`}
                onClick={() => toggleConfigFeature(id)}
                title={FEATURE_TOOLTIPS[id] || ''}
              >
                <span className="wgk__config-feature-check">{checked ? '✓' : ''}</span>
                <span className="wgk__config-feature-label">{f.label}</span>
                <span className="wgk__config-feature-price">
                  {priceLabel(id, f)}
                </span>
              </button>
            );
          })}
        </div>
        <p className="wgk__config-hint">
          Untertitel und Hintergrundmusik sind bei uns immer Standard – nicht extra zu wählen.
        </p>
      </div>
    </div>
  );
}

function priceLabel(id, f) {
  if (id === 'drohne') return `+${f.price_per_day} €/Drehtag`;
  if (id === 'sound')  return `+${f.price_per_min} €/Min.`;
  return `+${f.price} €`;
}

/* ---------- Building Blocks ---------- */

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
