import React, { useMemo, useState } from 'react';
import Intro from './Intro.jsx';
import Quiz from './Quiz.jsx';
import Result from './Result.jsx';
import Loading from './Loading.jsx';
import PriceSidebar from './PriceSidebar.jsx';
import { stepsForType } from './pricing.js';

/* Phasen: 'intro' → 'quiz' → 'loading' → 'result' */

export default function App({ theme = 'dark' }) {
  const config = window.WG_KONFIGURATOR || {};
  const [phase, setPhase] = useState('intro');
  const [sessionId, setSessionId] = useState('');
  const [step, setStep] = useState(0);
  const [answers, setAnswers] = useState({
    video_typ: '',
    output_paket: '',
    video_laenge: 'medium',
    features: [],
    zeitrahmen: '',
    branche: '',
    website: '',
    ziel: '',
  });
  const [lead, setLead] = useState({
    name: '',
    email: '',
    dsgvo_opt_in: false,
    marketing_opt_in: false,
  });
  const [starting, setStarting]     = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [result, setResult]         = useState(null);
  const [error, setError]           = useState('');

  // Steps werden dynamisch berechnet — je nach Video-Typ unterschiedlich
  const steps = useMemo(() => stepsForType(answers.video_typ), [answers.video_typ]);
  const total = steps.length;
  const progress = Math.round((step / total) * 100);

  function next() { setStep((s) => Math.min(s + 1, total)); }
  function back() { setStep((s) => Math.max(0, s - 1)); }

  React.useEffect(() => {
    if (step >= steps.length) {
      setStep(Math.max(0, steps.length - 1));
    }
  }, [steps.length]); // eslint-disable-line react-hooks/exhaustive-deps

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
            dsgvo_opt_in: lead.dsgvo_opt_in,
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
      // Nicht-blockierend: Start funktioniert auch ohne erfolgreichen /start
      // (CRM-Endpoint könnte offline sein, wir wollen den User nicht abhalten).
      // Stattdessen loggen wir den Fehler und gehen trotzdem weiter.
      console.warn('[wg-konfigurator] /start failed, continuing anyway:', e.message);
      setPhase('quiz');
    } finally {
      setStarting(false);
    }
  }

  /* ---------- Quiz Submit → Loading → Result ---------- */
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
          quiz: { ...answers, website: normalizedWebsite },
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
          video_typ: answers.video_typ,
          output_paket: answers.output_paket,
          video_laenge: answers.video_laenge,
          zeitrahmen: answers.zeitrahmen,
          preis_min: data.preis_min,
          preis_max: data.preis_max,
        });
      } catch (e) { /* noop */ }
    } catch (e) {
      setError(e.message);
      setPhase('quiz'); // zurück, damit User retry kann
    } finally {
      setSubmitting(false);
    }
  }

  /* ---------- Render je nach Phase ---------- */

  if (phase === 'intro') {
    return (
      <Intro
        lead={lead}
        setLead={setLead}
        onStart={handleStart}
        starting={starting}
        error={error}
      />
    );
  }

  if (phase === 'loading') {
    return <Loading />;
  }

  if (phase === 'result' && result) {
    return <Result data={result} meetingUrl={config.meetingUrl} theme={theme} />;
  }

  // phase === 'quiz'
  return (
    <div className={`wgk wgk--${theme}`}>
      <div className="wgk__progress">
        <div className="wgk__progress-bar" style={{ width: `${progress}%` }} />
      </div>

      <div className="wgk__layout">
        <div className="wgk__layout-main">
          <Quiz
            step={step}
            steps={steps}
            answers={answers}
            setAnswers={setAnswers}
            lead={lead}
            setLead={setLead}
            onNext={next}
            onBack={back}
            onSubmit={submit}
            submitting={submitting}
            error={error}
          />
        </div>
        <div className="wgk__layout-side">
          <PriceSidebar answers={answers} />
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
