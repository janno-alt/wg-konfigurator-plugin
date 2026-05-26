import React, { useState, useEffect, useMemo } from 'react';
import Intro from './Intro.jsx';
import Quiz from './Quiz.jsx';
import Result from './Result.jsx';
import Loading from './Loading.jsx';
import PriceSidebar from './PriceSidebar.jsx';
import { recommend } from './recommendation.js';

/* Phasen: 'intro' → 'quiz' → 'loading' → 'result' */

const STEPS = ['goal', 'branche', 'budget', 'zeitrahmen', 'kontext'];

export default function App({ theme = 'dark' }) {
  const config = window.WG_KONFIGURATOR || {};
  const [phase, setPhase] = useState('intro');
  const [sessionId, setSessionId] = useState('');
  const [step, setStep] = useState(0);
  const [answers, setAnswers] = useState({
    goal: '',           // NEU: Ziel des Kunden
    budget: '',         // NEU: Budget-Range
    branche: '',
    zeitrahmen: '',
    website: '',
    ziel: '',           // Freitext-Ziel (zusätzlich zu goal)
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

  // Recommendation wird live aus goal + budget berechnet
  const recommendation = useMemo(() => {
    if (!answers.goal) return null;
    return recommend(answers.goal, answers.budget || 'unknown');
  }, [answers.goal, answers.budget]);

  // Quiz-State, der an pricing.js übergeben wird, inkl. errechnete Empfehlung
  const effectiveAnswers = useMemo(() => {
    if (!recommendation) return answers;
    return {
      ...answers,
      video_typ:    recommendation.video_typ,
      output_paket: recommendation.output_paket || '',
      video_laenge: recommendation.video_laenge || 'medium',
      features:     recommendation.features || [],
    };
  }, [answers, recommendation]);

  const total = STEPS.length;
  const progress = Math.round((step / total) * 100);

  function next() { setStep((s) => Math.min(s + 1, total)); }
  function back() { setStep((s) => Math.max(0, s - 1)); }

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
      console.warn('[wg-konfigurator] /start failed, continuing anyway:', e.message);
      setPhase('quiz');
    } finally {
      setStarting(false);
    }
  }

  /* ---------- Submit → Loading → Result ---------- */
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

      // Wir schicken die Roh-Quiz-Antworten + die Recommendation ans Backend.
      // Der Server berechnet seine eigene Empfehlung nochmal (Source of Truth)
      // und gleicht sie ggf. ab.
      const response = await fetch(config.restUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce },
        body: JSON.stringify({
          lead,
          quiz: {
            ...answers,
            website: normalizedWebsite,
            // Recommendation-Output direkt mitgeben für CRM
            video_typ:    recommendation?.video_typ,
            output_paket: recommendation?.output_paket || '',
            video_laenge: recommendation?.video_laenge || 'medium',
            features:     recommendation?.features || [],
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
          budget: answers.budget,
          video_typ: recommendation?.video_typ,
          preis_min: data.preis_min,
          preis_max: data.preis_max,
        });
      } catch (e) { /* noop */ }
    } catch (e) {
      setError(e.message);
      setPhase('quiz');
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
            steps={STEPS}
            answers={answers}
            setAnswers={setAnswers}
            lead={lead}
            recommendation={recommendation}
            onNext={next}
            onBack={back}
            onSubmit={submit}
            submitting={submitting}
            error={error}
          />
        </div>
        <div className="wgk__layout-side">
          <PriceSidebar answers={effectiveAnswers} recommendation={recommendation} />
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
