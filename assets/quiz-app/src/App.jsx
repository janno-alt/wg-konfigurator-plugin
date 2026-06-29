import React, { useState, useEffect, useMemo } from 'react';
import Intro from './Intro.jsx';
import Quiz from './Quiz.jsx';
import Result from './Result.jsx';
import Loading from './Loading.jsx';
import PriceSidebar from './PriceSidebar.jsx';
import AppProduct from './AppProduct.jsx';
import { recommend } from './recommendation.js';
import { pushKonfiguratorLead } from './track.js';

const STEPS = ['goal', 'branche', 'channels', 'zeitrahmen', 'configure', 'kontext'];

export default function App({ theme = 'dark', product = 'video' }) {
  // Recruiting & Social laufen über einen eigenen Orchestrator,
  // damit der bewährte Video-Pfad unten unverändert bleibt.
  if (product === 'recruiting' || product === 'social') {
    return <AppProduct theme={theme} product={product} />;
  }

  const config = window.WG_KONFIGURATOR || {};
  const [phase, setPhase] = useState('intro');
  const [sessionId, setSessionId] = useState('');
  const [step, setStep] = useState(0);
  const [answers, setAnswers] = useState({
    // Quiz-Inputs
    goal: '',
    channels: [],         // Multi-Select
    branche: '',
    zeitrahmen: '',
    website: '',
    ziel: '',
    // Konfig-Werte (vom Recommender initial gesetzt, vom User im Configure-Step anpassbar)
    video_typ: '',
    output_paket: '',
    video_laenge: 'medium',
    features: [],
  });
  // Track ob der User im Configure-Step manuell etwas geändert hat —
  // dann überschreibt die Recommendation seine Auswahl nicht mehr.
  const [userOverride, setUserOverride] = useState(false);

  const [lead, setLead] = useState({
    name: '',
    email: '',
    telefon: '',
    dsgvo_opt_in: false,
    marketing_opt_in: false,
  });
  const [starting, setStarting]     = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [result, setResult]         = useState(null);
  const [error, setError]           = useState('');

  // Recommendation live aus goal + channels berechnen
  const recommendation = useMemo(() => {
    if (!answers.goal) return null;
    return recommend(answers.goal, answers.channels || []);
  }, [answers.goal, answers.channels]);

  // Wenn sich goal oder channels ändern → answers automatisch mit Empfehlung füllen
  // (außer der User hat im Configure-Step bereits manuell überschrieben).
  useEffect(() => {
    if (!recommendation) return;
    if (userOverride) return;
    setAnswers((a) => ({
      ...a,
      video_typ:    recommendation.video_typ,
      output_paket: recommendation.output_paket || '',
      video_laenge: recommendation.video_laenge || 'medium',
      features:     recommendation.features || [],
    }));
  }, [recommendation, userOverride]);

  // Wenn der User goal oder channels ändert → User-Override resetten,
  // damit die neue Empfehlung wieder greift.
  useEffect(() => {
    setUserOverride(false);
  }, [answers.goal, JSON.stringify(answers.channels)]);

  const total = STEPS.length;
  const progress = Math.round((step / total) * 100);

  function next() { setStep((s) => Math.min(s + 1, total)); }
  function back() { setStep((s) => Math.max(0, s - 1)); }

  function setConfigField(field, value) {
    setUserOverride(true);
    setAnswers((a) => ({ ...a, [field]: value }));
  }

  /* ---------- Intro → Quiz ---------- */
  async function handleStart() {
    setStarting(true);
    setError('');
    try {
      const startUrl = config.restUrl.replace('/generate', '/start');
      const res = await fetch(startUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce },
        body: JSON.stringify({
          lead: {
            email: lead.email,
            // Klick auf "Konfigurator starten" = DSGVO-Einwilligung (siehe Intro-Legaltext).
            // Hartkodiert true, weil das setLead aus dem Intro hier noch nicht propagiert ist.
            dsgvo_opt_in: true,
            marketing_opt_in: lead.marketing_opt_in,
          },
          tracking: config.tracking || {},
        }),
      });
      const data = await res.json();
      if (!res.ok) {
        throw new Error(data.message || 'Konfigurator konnte nicht gestartet werden.');
      }
      if (data.session_id) setSessionId(data.session_id);
      setPhase('quiz');
    } catch (e) {
      console.warn('[wg-konfigurator] /start failed, continuing anyway:', e.message);
      setPhase('quiz');
    } finally {
      setStarting(false);
    }
  }

  /* ---------- Submit ---------- */
  async function submit() {
    setSubmitting(true);
    setPhase('loading');
    setError('');
    try {
      let recaptchaToken = '';
      if (config.recaptchaSite && window.grecaptcha) {
        recaptchaToken = await new Promise((resolve) => {
          window.grecaptcha.ready(() => {
            window.grecaptcha
              .execute(config.recaptchaSite, { action: 'konfigurator' })
              .then(resolve)
              .catch(() => resolve(''));
          });
        });
      }

      const normalizedWebsite = normalizeWebsite(answers.website);

      const response = await fetch(config.restUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce },
        body: JSON.stringify({
          lead,
          quiz: {
            ...answers,
            website: normalizedWebsite,
            user_override: userOverride,
          },
          tracking: config.tracking || {},
          session_id: sessionId,
          recaptcha_token: recaptchaToken,
        }),
      });
      const data = await response.json();
      if (!response.ok) {
        throw new Error(data.message || 'Etwas ist schiefgelaufen.');
      }
      setResult(data);
      setPhase('result');
      try {
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({
          event: 'konfigurator_completed',
          goal: answers.goal,
          channels: answers.channels,
          video_typ: answers.video_typ,
          user_override: userOverride,
          preis_min: data.preis_min,
          preis_max: data.preis_max,
        });
      } catch (e) { /* noop */ }
      // Lead-Event fürs GTM-/Microsoft-Ads-Tracking (gehashte PII vom Server).
      pushKonfiguratorLead(data);
    } catch (e) {
      setError(e.message);
      setPhase('quiz');
    } finally {
      setSubmitting(false);
    }
  }

  /* ---------- Render ---------- */

  if (phase === 'intro') {
    return (
      <Intro lead={lead} setLead={setLead} onStart={handleStart} starting={starting} error={error} />
    );
  }
  if (phase === 'loading') return <Loading />;
  if (phase === 'result' && result) {
    return <Result data={result} meetingUrl={config.meetingUrl} theme={theme} />;
  }

  return (
    <div className={`wgk wgk--${theme}`}>
      <div className="wgk__progress">
        <div className="wgk__progress-bar" style={{ width: `${progress}%` }} />
      </div>

      <div className="wgk__layout">
        <div className="wgk__layout-main">
          <Quiz
            step={step}
            steps={STEPS}
            answers={answers}
            setAnswers={setAnswers}
            setConfigField={setConfigField}
            lead={lead}
            recommendation={recommendation}
            userOverride={userOverride}
            onNext={next}
            onBack={back}
            onSubmit={submit}
            submitting={submitting}
            error={error}
          />
        </div>
        <div className="wgk__layout-side">
          <PriceSidebar answers={answers} recommendation={recommendation} />
        </div>
      </div>
    </div>
  );
}

function normalizeWebsite(raw) {
  const v = (raw || '').trim();
  if (!v) return '';
  if (/^https?:\/\//i.test(v)) return v;
  return 'https://' + v.replace(/^\/+/, '');
}
