import React, { useState } from 'react';

/**
 * Kompakter Start-Screen.
 *
 * - DSGVO wird implizit mit dem Klick auf "Konfigurator starten" akzeptiert
 *   (Hinweis-Text statt Checkbox).
 * - Name + E-Mail werden hier erfasst — der Lead-Step am Ende des Quiz entfällt.
 * - Email-Domain wird im /start-Endpoint geprüft (Website-Auto-Discovery).
 */
const DEFAULT_COPY = {
  eyebrow: 'Video-Konfigurator',
  headlineHtml: 'Dein <span class="accent">Videokonzept</span> in 2 Minuten.',
  sub: 'Konfiguriere dein Video — wir schicken dir kostenlos eine KI-Analyse mit konkretem Preis-Rahmen per E-Mail.',
  flow: ['Konfigurieren', 'KI-Analyse', 'PDF im Postfach'],
  cta: 'Konfigurator starten',
};

export default function Intro({ lead, setLead, onStart, starting, error, copy }) {
  const c = copy || DEFAULT_COPY;
  const [touched, setTouched] = useState(false);
  const nameValid  = lead.name.trim().split(/\s+/).length >= 2;
  const emailValid = /\S+@\S+\.\S+/.test(lead.email);
  const canStart   = nameValid && emailValid && !starting;

  function handleStart() {
    setTouched(true);
    if (canStart) {
      // Mit Klick wird DSGVO-Einwilligung implizit gegeben.
      setLead((l) => ({ ...l, dsgvo_opt_in: true }));
      onStart();
    }
  }

  return (
    <div className="wgk wgk--dark wgk__intro">
      <div className="wgk__intro-hero">
        <span className="wgk__intro-eyebrow">{c.eyebrow}</span>
        <h1 className="wgk__intro-headline" dangerouslySetInnerHTML={{ __html: c.headlineHtml }} />
        <p className="wgk__intro-sub">{c.sub}</p>
      </div>

      <div className="wgk__intro-flow">
        {c.flow.map((label, i) => (
          <React.Fragment key={i}>
            {i > 0 && <span className="wgk__intro-flow-arrow">→</span>}
            <span><strong>{i + 1}.</strong> {label}</span>
          </React.Fragment>
        ))}
      </div>

      <div className="wgk__intro-form">
        <p className="wgk__intro-form-intro">An wen dürfen wir das Konzept senden?</p>

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
          {touched && !nameValid && (
            <span className="wgk__intro-fielderror">Bitte Vor- und Nachname eingeben.</span>
          )}
        </label>

        <label className="wgk__field">
          <span>E-Mail-Adresse</span>
          <input
            type="email"
            value={lead.email}
            onChange={(e) => setLead({ ...lead, email: e.target.value })}
            placeholder="anna@firma.de"
            autoComplete="email"
            required
          />
          {touched && !emailValid && (
            <span className="wgk__intro-fielderror">Bitte gültige E-Mail-Adresse eingeben.</span>
          )}
        </label>

        <label className="wgk__field">
          <span>Telefon <span style={{ color: '#9a9a9a', fontWeight: 400 }}>(optional)</span></span>
          <input
            type="tel"
            value={lead.telefon || ''}
            onChange={(e) => setLead({ ...lead, telefon: e.target.value })}
            placeholder="0151 23456789"
            autoComplete="tel"
          />
          <span style={{ display: 'block', marginTop: '4px', fontSize: '12px', color: '#9a9a9a' }}>
            Für schnellere Rückfragen zu deiner Anfrage.
          </span>
        </label>

        <label className="wgk__check wgk__intro-optin">
          <input
            type="checkbox"
            checked={!!lead.marketing_opt_in}
            onChange={(e) => setLead({ ...lead, marketing_opt_in: e.target.checked })}
          />
          <span>Ja, schickt mir gelegentlich Praxis-Tipps und Angebote von WG-Digital per E-Mail (Newsletter, jederzeit abbestellbar).</span>
        </label>

        <button
          type="button"
          className="wgk__btn wgk__btn--primary wgk__intro-cta"
          onClick={handleStart}
          disabled={starting}
        >
          {starting ? 'Wird vorbereitet …' : c.cta}
        </button>

        {error && <p className="wgk__error">{error}</p>}

        <p className="wgk__intro-legal">
          Mit dem Klick auf „Konfigurator starten" stimmst du der
          {' '}<a href="/datenschutz" target="_blank" rel="noopener">Datenschutzerklärung</a>{' '}
          zu. Falls du nicht zu Ende konfigurierst, schreiben wir dich einmal an.
        </p>
      </div>
    </div>
  );
}
