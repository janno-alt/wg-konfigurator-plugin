import React, { useEffect, useState } from 'react';

/**
 * Loading-Screen während der eigentliche Submit-Request läuft.
 * Wir simulieren Phasen visuell — der Backend-Submit ist ein einzelner
 * Roundtrip, aber dem User zeigen wir konkret was gerade passiert.
 */
const PHASES = [
  { id: 'scrape',  label: 'Deine Website wird analysiert …',           ms: 2500 },
  { id: 'gemini',  label: 'KI erstellt dein Konzept …',                ms: 8000 },
  { id: 'pdf',     label: 'PDF wird gerendert …',                      ms: 3500 },
  { id: 'mail',    label: 'E-Mail wird versendet …',                   ms: 2000 },
  { id: 'crm',     label: 'Lead wird im CRM angelegt …',               ms: 1500 },
];

export default function Loading() {
  const [activeIdx, setActiveIdx] = useState(0);

  useEffect(() => {
    let cancelled = false;
    let total = 0;
    const timers = PHASES.map((p, i) => {
      total += p.ms;
      return setTimeout(() => {
        if (!cancelled) setActiveIdx(i + 1);
      }, total);
    });
    return () => {
      cancelled = true;
      timers.forEach(clearTimeout);
    };
  }, []);

  return (
    <div className="wgk wgk--dark wgk__loading">
      <div className="wgk__loading-spinner" aria-hidden="true">
        <div className="wgk__loading-ring" />
        <div className="wgk__loading-ring wgk__loading-ring--2" />
        <div className="wgk__loading-ring wgk__loading-ring--3" />
      </div>

      <h2 className="wgk__loading-title">Dein Konzept wird erstellt</h2>
      <p className="wgk__loading-sub">Das dauert etwa 15–25 Sekunden. Bitte das Browser-Tab offen lassen.</p>

      <ul className="wgk__loading-list">
        {PHASES.map((p, i) => {
          const state = i < activeIdx ? 'done' : i === activeIdx ? 'active' : 'pending';
          return (
            <li key={p.id} className={`wgk__loading-item wgk__loading-item--${state}`}>
              <span className="wgk__loading-bullet" aria-hidden="true">
                {state === 'done' ? '✓' : state === 'active' ? '…' : ''}
              </span>
              <span>{p.label}</span>
            </li>
          );
        })}
      </ul>
    </div>
  );
}
