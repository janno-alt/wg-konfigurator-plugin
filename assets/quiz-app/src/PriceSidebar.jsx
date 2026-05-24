import React from 'react';
import { computeBreakdown, fmtEur, fmtRange } from './pricing.js';

export default function PriceSidebar({ answers }) {
  const breakdown = computeBreakdown(answers);

  return (
    <aside className="wgk__pricesidebar" aria-live="polite">
      <div className="wgk__pricesidebar-header">
        <span className="wgk__pricesidebar-eyebrow">Live-Berechnung</span>
        <h3 className="wgk__pricesidebar-title">Deine Investition</h3>
      </div>

      {!breakdown.ready && (
        <p className="wgk__pricesidebar-empty">
          Wähle Video-Typ und Liefer-Paket — dann siehst du hier die geschätzte
          Range, aufgeschlüsselt nach Posten.
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
            <span className="wgk__pricesidebar-total-label">Gesamt</span>
            <strong className="wgk__pricesidebar-total-value">
              {fmtRange(breakdown.total_min, breakdown.total_max)}
            </strong>
          </div>

          <p className="wgk__pricesidebar-incl">
            Inklusive: <strong>Untertitel</strong> für Stumm-Wiedergabe,
            <strong> lizenzierte Musik</strong>, Konzept, Schnitt, plattformgerechter
            Export. Reisekosten ab 100 km separat.
          </p>
        </>
      )}
    </aside>
  );
}
