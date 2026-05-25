import React, { useMemo, useState } from 'react';
import Quiz from './Quiz.jsx';
import Result from './Result.jsx';
import PriceSidebar from './PriceSidebar.jsx';
import { stepsForType } from './pricing.js';

export default function App({ theme = 'dark' }) {
  const config = window.WG_KONFIGURATOR || {};
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
  const [lead, setLead] = useState({ name: '', email: '', marketing_opt_in: false });
  const [submitting, setSubmitting] = useState(false);
  const [result, setResult] = useState(null);
  const [error, setError] = useState('');

  // Steps werden dynamisch berechnet — je nach Video-Typ unterschiedlich
  const steps = useMemo(() => stepsForType(answers.video_typ), [answers.video_typ]);
  const total = steps.length;
  const progress = Math.round((step / total) * 100);

  function next() { setStep((s) => Math.min(s + 1, total)); }
  function back() { setStep((s) => Math.max(0, s - 1)); }

  // Wenn der Nutzer Video-Typ ändert und die neuen Steps anders sind:
  // step zurücksetzen, falls der aktuelle Step nicht mehr im Flow ist
  React.useEffect(() => {
    if (step >= steps.length) {
      setStep(Math.max(0, steps.length - 1));
    }
  }, [steps.length]); // eslint-disable-line react-hooks/exhaustive-deps

  async function submit() {
    setSubmitting(true);
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
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': config.nonce,
        },
        body: JSON.stringify({
          lead,
          quiz: { ...answers, website: normalizedWebsite },
          tracking: config.tracking || {},
          recaptcha_token: recaptchaToken,
        }),
      });
      const data = await response.json();
      if (!response.ok) {
        throw new Error(data.message || 'Etwas ist schiefgelaufen.');
      }
      setResult(data);
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
    } finally {
      setSubmitting(false);
    }
  }

  if (result) {
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
