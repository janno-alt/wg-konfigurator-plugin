import React, { useState } from 'react';

/**
 * Start-Screen. Holt sich Email + Einwilligungen bevor das Quiz beginnt,
 * damit wir auch bei späterem Abbruch eine Recovery-Mail schicken können.
 */
export default function Intro({ lead, setLead, onStart, starting, error }) {
  const [touched, setTouched] = useState(false);
  const emailValid = /\S+@\S+\.\S+/.test(lead.email);
  const dsgvoOk    = !!lead.dsgvo_opt_in;
  const canStart   = emailValid && dsgvoOk && !starting;

  function handleStart() {
    setTouched(true);
    if (canStart) onStart();
  }

  return (
    <div className="wgk wgk--dark wgk__intro">
      <div className="wgk__intro-hero">
        <span className="wgk__intro-eyebrow">Video-Konfigurator</span>
        <h1 className="wgk__intro-headline">
          Dein <span className="accent">individuelles Videokonzept</span> in 2&nbsp;Minuten.
        </h1>
        <p className="wgk__intro-sub">
          Konfiguriere dein Video nach Bedarf — wir schicken dir kostenlos eine
          KI-gestützte Konzept-Analyse mit konkretem Preis-Rahmen per E-Mail zu.
        </p>
      </div>

      <ol className="wgk__intro-steps">
        <li>
          <span className="wgk__intro-step-num">1</span>
          <div>
            <strong>Konfigurieren</strong>
            <span>Video-Typ, Länge, Features — Live-Preis nebenan.</span>
          </div>
        </li>
        <li>
          <span className="wgk__intro-step-num">2</span>
          <div>
            <strong>Kostenfreie Analyse</strong>
            <span>Unsere KI analysiert dein Unternehmen und schlägt konkrete Video-Botschaften vor.</span>
          </div>
        </li>
        <li>
          <span className="wgk__intro-step-num">3</span>
          <div>
            <strong>PDF erhalten</strong>
            <span>Konzept + Preis-Rahmen als PDF in deinem Postfach – ohne Vertrag, ohne Verpflichtung.</span>
          </div>
        </li>
      </ol>

      <div className="wgk__intro-form">
        <label className="wgk__field">
          <span>Deine E-Mail-Adresse</span>
          <input
            type="email"
            value={lead.email}
            onChange={(e) => setLead({ ...lead, email: e.target.value })}
            placeholder="z. B. anna@firma.de"
            autoComplete="email"
            required
          />
          {touched && !emailValid && (
            <span className="wgk__intro-fielderror">Bitte gültige E-Mail-Adresse eingeben.</span>
          )}
        </label>

        <label className="wgk__check wgk__intro-check">
          <input
            type="checkbox"
            checked={!!lead.dsgvo_opt_in}
            onChange={(e) => setLead({ ...lead, dsgvo_opt_in: e.target.checked })}
          />
          <span>
            Ich willige ein, dass meine Angaben zur Erstellung des Konzepts gespeichert
            und verarbeitet werden. Details in der{' '}
            <a href="/datenschutz" target="_blank" rel="noopener">Datenschutzerklärung</a>.
            <strong> Pflichtfeld.</strong>
          </span>
        </label>
        {touched && !dsgvoOk && (
          <span className="wgk__intro-fielderror">Ohne diese Einwilligung dürfen wir keine Daten verarbeiten.</span>
        )}

        <label className="wgk__check">
          <input
            type="checkbox"
            checked={!!lead.marketing_opt_in}
            onChange={(e) => setLead({ ...lead, marketing_opt_in: e.target.checked })}
          />
          <span>
            Ja, gelegentlich Updates zu Videomarketing-Tipps von WG-Digital erhalten
            (jederzeit abbestellbar). Optional.
          </span>
        </label>

        <button
          type="button"
          className="wgk__btn wgk__btn--primary wgk__intro-cta"
          onClick={handleStart}
          disabled={starting}
        >
          {starting ? 'Wird vorbereitet …' : 'Konfigurator starten'}
        </button>

        {error && <p className="wgk__error">{error}</p>}

        <p className="wgk__intro-note">
          Falls du den Konfigurator nicht zu Ende führst, erinnern wir dich
          einmal per E-Mail — dann kannst du nahtlos weitermachen oder direkt
          einen Beratungstermin buchen.
        </p>
      </div>
    </div>
  );
}
