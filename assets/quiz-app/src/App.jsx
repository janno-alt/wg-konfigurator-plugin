import React, { useState } from 'react';
import Quiz from './Quiz.jsx';
import Result from './Result.jsx';
import PriceSidebar from './PriceSidebar.jsx';

const STEPS = ['video_typ', 'output_paket', 'video_laenge', 'features', 'zeitrahmen', 'kontext', 'lead'];

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

  const total = STEPS.length;
  const progress = Math.round((step / total) * 100);

  function next() { setStep((s) => Math.min(s + 1, total)); }
  function back() { setStep((s) => Math.max(0, s - 1)); }

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

      // Website: User kann "deine-firma.de" oder "https://deine-firma.de"
      // eingeben. Wir normalisieren auf der Client-Seite, falls möglich.
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
            steps={STEPS}
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
