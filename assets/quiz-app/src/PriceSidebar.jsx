import React from 'react';
import { computeBreakdown, fmtEur, fmtRange, VIDEO_TYPES } from './pricing.js';

export default function PriceSidebar({ answers, recommendation }) {
  const breakdown = computeBreakdown(answers);
  const typeLabel = recommendation?.video_typ
    ? VIDEO_TYPES[recommendation.video_typ]?.label
    : null;

  return (
    <aside className="wgk__pricesidebar" aria-live="polite">
      {recommendation && typeLabel ? (
        <div className="wgk__pricesidebar-rec">
          <span className="wgk__pricesidebar-eyebrow">Unsere Empfehlung</span>
          <strong className="wgk__pricesidebar-rec-typ">{typeLabel}</strong>
          {recommendation.reasoning_short && (
            <p className="wgk__pricesidebar-rec-text">{recommendation.reasoning_short}</p>
          )}
        </div>
      ) : (
        <div className="wgk__pricesidebar-header">
          <span className="wgk__pricesidebar-eyebrow">Live-Berechnung</span>
          <h3 className="wgk__pricesidebar-title">Deine Investition</h3>
        </div>
      )}

      {!breakdown.ready && (
        <p className="wgk__pricesidebar-empty">
          Wähle dein Ziel — sobald die Empfehlung steht, siehst du hier
          die geschätzte Range, aufgeschlüsselt nach Posten.
        </p>
      )}

      {breakdown.ready && (
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
            <span className="wgk__pricesidebar-total-label">Gesamt netto</span>
            <strong className="wgk__pricesidebar-total-value">
              {fmtRange(breakdown.total_min, breakdown.total_max)}
            </strong>
            {breakdown.payment_note && (
              <span className="wgk__pricesidebar-payment">{breakdown.payment_note}</span>
            )}
          </div>

          <p className="wgk__pricesidebar-incl">
            Inklusive: <strong>Untertitel</strong> für Stumm-Wiedergabe,
            <strong> lizenzierte Musik</strong>, Schnitt, plattformgerechter Export.
            Reisekosten ab 100 km separat. Alle Preise zzgl. MwSt.
          </p>
        </>
      )}
    </aside>
  );
}
