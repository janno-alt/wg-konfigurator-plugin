import React, { useEffect, useState } from 'react';

/**
 * Loading-Screen während der Submit-Request läuft.
 *
 * Phasen sind kunden-orientiert formuliert — keine internen Prozesse
 * ("CRM" etc.) sichtbar. Die Animation ist client-side getriggert,
 * der echte Backend-Submit ist ein einzelner Roundtrip.
 */
const PHASES = [
  { id: 'scrape',  label: 'Wir nehmen dein Unternehmen unter die Lupe …', icon: '🔍', ms: 2500 },
  { id: 'analyze', label: 'Was macht euch besonders? Wir lesen mit …',    icon: '💡', ms: 3000 },
  { id: 'gemini',  label: 'Unsere KI brainstormt Video-Ideen für dich …', icon: '🧠', ms: 5500 },
  { id: 'concept', label: 'Dein individuelles Konzept entsteht …',        icon: '✍️', ms: 4000 },
  { id: 'pdf',     label: 'Wir packen alles in dein PDF …',               icon: '📄', ms: 3000 },
  { id: 'mail',    label: 'Dein Konzept ist gleich in deinem Postfach …', icon: '📬', ms: 2000 },
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
                {state === 'done' ? '✓' : state === 'active' ? p.icon : ''}
              </span>
              <span>{p.label}</span>
            </li>
          );
        })}
      </ul>
    </div>
  );
}
