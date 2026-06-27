import React, { useState, useMemo } from 'react';
import Intro from './Intro.jsx';
import Loading from './Loading.jsx';
import Result from './Result.jsx';
import { getProduct, fmtEur, fmtRange } from './productConfig.js';

/**
 * Orchestrator für die Nicht-Video-Produkte (recruiting, social).
 * Hält den Video-Pfad (App.jsx) komplett unangetastet.
 */
export default function AppProduct({ theme = 'dark', product }) {
  const def = getProduct(product);
  const config = window.WG_KONFIGURATOR || {};

  const [phase, setPhase] = useState('intro');
  const [sessionId, setSessionId] = useState('');
  const [step, setStep] = useState(0);
  const [answers, setAnswers] = useState({ ...def.initialAnswers });
  const [lead, setLead] = useState({ name: '', email: '', dsgvo_opt_in: false, marketing_opt_in: false });
  const [starting, setStarting] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [result, setResult] = useState(null);
  const [error, setError] = useState('');

  const steps = def.steps;
  const total = steps.length;
  const progress = Math.round((step / total) * 100);
  const breakdown = useMemo(() => def.computeBreakdown(answers), [answers, def]);

  function set(field, value) { setAnswers((a) => ({ ...a, [field]: value })); }
  function next() { setStep((s) => Math.min(s + 1, total - 1)); }
  function back() { setStep((s) => Math.max(0, s - 1)); }

  async function handleStart() {
    setStarting(true); setError('');
    try {
      const startUrl = config.restUrl.replace('/generate', '/start');
      const res = await fetch(startUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce },
        body: JSON.stringify({
          lead: { email: lead.email, dsgvo_opt_in: lead.dsgvo_opt_in, marketing_opt_in: lead.marketing_opt_in },
          tracking: config.tracking || {},
          product,
        }),
      });
      const data = await res.json();
      if (res.ok && data.session_id) setSessionId(data.session_id);
    } catch (e) {
      console.warn('[wg-konfigurator] /start failed, continuing:', e.message);
    } finally {
      setStarting(false);
      setPhase('quiz');
    }
  }

  async function submit() {
    setSubmitting(true); setPhase('loading'); setError('');
    try {
      let recaptchaToken = '';
      if (config.recaptchaSite && window.grecaptcha) {
        recaptchaToken = await new Promise((resolve) => {
          window.grecaptcha.ready(() => {
            window.grecaptcha.execute(config.recaptchaSite, { action: 'konfigurator' })
              .then(resolve).catch(() => resolve(''));
          });
        });
      }
      const normalizedWebsite = normalizeWebsite(answers.website);
      const response = await fetch(config.restUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce },
        body: JSON.stringify({
          lead,
          quiz: { ...answers, website: normalizedWebsite, product },
          tracking: config.tracking || {},
          session_id: sessionId,
          recaptcha_token: recaptchaToken,
        }),
      });
      const data = await response.json();
      if (!response.ok) throw new Error(data.message || 'Etwas ist schiefgelaufen.');
      setResult(data); setPhase('result');
      try {
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({
          event: 'konfigurator_completed', product,
          preis_min: data.preis_min, preis_max: data.preis_max,
          monatlich_max: data.monatlich_max || 0,
        });
      } catch (e) { /* noop */ }
    } catch (e) {
      setError(e.message); setPhase('quiz');
    } finally {
      setSubmitting(false);
    }
  }

  /* ---------- Render ---------- */
  if (phase === 'intro') {
    return <Intro lead={lead} setLead={setLead} onStart={handleStart} starting={starting} error={error} copy={def.intro} />;
  }
  if (phase === 'loading') return <Loading />;
  if (phase === 'result' && result) {
    return <Result data={result} meetingUrl={config.meetingUrl} theme={theme} resultCopy={def.result} />;
  }

  const cur = steps[step];

  return (
    <div className={`wgk wgk--${theme}`}>
      <div className="wgk__progress"><div className="wgk__progress-bar" style={{ width: `${progress}%` }} /></div>
      <div className="wgk__layout">
        <div className="wgk__layout-main">
          {cur.type === 'final' ? (
            <FinalStep answers={answers} set={set} lead={lead} onBack={back} onSubmit={submit} submitting={submitting} error={error} />
          ) : (
            <SingleStep stepDef={cur} answers={answers} set={set} onNext={next} onBack={step > 0 ? back : null} />
          )}
        </div>
        <div className="wgk__layout-side">
          <ProductSidebar def={def} breakdown={breakdown} />
        </div>
      </div>
    </div>
  );
}

function SingleStep({ stepDef, answers, set, onNext, onBack }) {
  const val = answers[stepDef.field];
  return (
    <div className="wgk__step">
      <h2 className="wgk__title">{stepDef.title}</h2>
      {stepDef.subtitle && <p className="wgk__subtitle">{stepDef.subtitle}</p>}
      <div className="wgk__content">
        <div className={stepDef.grid ? 'wgk__grid wgk__grid--2' : 'wgk__stack'}>
          {stepDef.options.map((o) => (
            <button
              type="button"
              key={o.id}
              className={`${stepDef.grid ? 'wgk__card wgk__card--narrow' : 'wgk__iconcard wgk__iconcard--wide'} ${val === o.id ? 'is-active' : ''}`}
              onClick={() => set(stepDef.field, o.id)}
            >
              {stepDef.grid ? (
                <strong>{o.label}{o.badge && <em className="wgk__badge">{o.badge}</em>}</strong>
              ) : (
                <span className="wgk__iconcard-body">
                  <span className="wgk__iconcard-label">{o.label}{o.badge && <em className="wgk__badge">{o.badge}</em>}</span>
                  {o.hint && <span className="wgk__iconcard-hint">{o.hint}</span>}
                </span>
              )}
            </button>
          ))}
        </div>
      </div>
      <div className="wgk__actions">
        {onBack && <button type="button" className="wgk__btn wgk__btn--ghost" onClick={onBack}>Zurück</button>}
        <button type="button" className="wgk__btn wgk__btn--primary" onClick={onNext} disabled={!val}>Weiter</button>
      </div>
    </div>
  );
}

function FinalStep({ answers, set, lead, onBack, onSubmit, submitting, error }) {
  return (
    <div className="wgk__step">
      <h2 className="wgk__title">Fast geschafft – ein paar letzte Details</h2>
      <p className="wgk__subtitle">Damit die Einschätzung zu euch passt. Wir analysieren eure E-Mail-Domain automatisch.</p>
      <div className="wgk__content">
        <label className="wgk__field">
          <span>Eure Website (optional – für eine passgenauere Einschätzung)</span>
          <input type="text" inputMode="url" autoCapitalize="off" autoCorrect="off" spellCheck={false}
            placeholder="z. B. eure-firma.de" value={answers.website}
            onChange={(e) => set('website', e.target.value)} />
        </label>
        <p className="wgk__note">Wir mailen die Einschätzung an <strong>{lead.email}</strong>. Alle Preise netto, zzgl. MwSt.</p>
        {error && <p className="wgk__error">{error}</p>}
      </div>
      <div className="wgk__actions">
        <button type="button" className="wgk__btn wgk__btn--ghost" onClick={onBack}>Zurück</button>
        <button type="button" className="wgk__btn wgk__btn--primary" onClick={onSubmit} disabled={submitting}>
          {submitting ? 'Wird erstellt …' : 'Einschätzung anfordern'}
        </button>
      </div>
    </div>
  );
}

function ProductSidebar({ def, breakdown }) {
  const s = def.sidebar;
  return (
    <aside className="wgk__pricesidebar" aria-live="polite">
      <div className="wgk__pricesidebar-header">
        <span className="wgk__pricesidebar-eyebrow">{s.eyebrow}</span>
        <h3 className="wgk__pricesidebar-title">{s.title}</h3>
      </div>

      {!breakdown.ready && <p className="wgk__pricesidebar-empty">{s.emptyHint}</p>}

      {breakdown.ready && (
        <>
          {breakdown.items.length > 0 && (
            <>
              <ul className="wgk__pricesidebar-list">
                {breakdown.items.map((it) => (
                  <li key={it.key} className="wgk__pricesidebar-item">
                    <span className="wgk__pricesidebar-item-label">{it.label}</span>
                    <span className="wgk__pricesidebar-item-value">
                      {it.min === it.max ? fmtEur(it.min) : `${fmtEur(it.min)} – ${fmtEur(it.max)}`}
                    </span>
                  </li>
                ))}
              </ul>
              <div className="wgk__pricesidebar-total">
                <span className="wgk__pricesidebar-total-label">Einmalig netto</span>
                <strong className="wgk__pricesidebar-total-value">{fmtRange(breakdown.total_min, breakdown.total_max)}</strong>
              </div>
            </>
          )}

          {breakdown.recurring && (
            <div className="wgk__rec">
              <span className="wgk__rec-label">{breakdown.recurring.label}</span>
              <div className="wgk__rec-price">
                {breakdown.recurring.from && <span className="wgk__rec-from">ab </span>}
                <strong>{fmtEur(breakdown.recurring.min)}</strong>
                <span className="wgk__rec-per"> / Monat</span>
              </div>
              <ul className="wgk__rec-incl">
                {breakdown.recurring.items.map((it, i) => (
                  <li key={i}><span className="wgk__rec-check">✓</span>{it.label}</li>
                ))}
              </ul>
              {breakdown.recurring.note && <p className="wgk__rec-note">{breakdown.recurring.note}</p>}
            </div>
          )}

          <p className="wgk__pricesidebar-incl">{s.inclText}</p>
        </>
      )}
    </aside>
  );
}

function normalizeWebsite(raw) {
  const v = (raw || '').trim();
  if (!v) return '';
  if (/^https?:\/\//i.test(v)) return v;
  return 'https://' + v.replace(/^\/+/, '');
}
