import React from 'react';

const fmt = (n) => new Intl.NumberFormat('de-DE').format(Math.round(n)) + ' €';

export default function Result({ data, meetingUrl, theme, resultCopy }) {
  const hasOneOff  = (data.preis_max || 0) > 0;
  const hasMonthly = (data.monatlich_max || 0) > 0;

  return (
    <div className={`wgk wgk--${theme} wgk__result`}>
      <h2 className="wgk__title">{resultCopy?.headline || 'Dein Konzept ist unterwegs.'}</h2>
      <p className="wgk__note">
        {resultCopy?.sentText ||
          'Wir haben dir das individuelle Videokonzept als PDF an deine E-Mail-Adresse geschickt.'}{' '}
        Falls du es nicht findest: schau im Spam nach oder öffne es direkt:
      </p>

      {hasOneOff && (
        <div className="wgk__pricebox">
          <span className="wgk__pricebox-label">{hasMonthly ? 'Einmalig' : 'Investitionsrahmen'}</span>
          <strong className="wgk__pricebox-value">{data.preis_min === data.preis_max ? fmt(data.preis_min) : `${fmt(data.preis_min)} – ${fmt(data.preis_max)}`}</strong>
          {data.express_aufschlag > 0 && (
            <span className="wgk__pricebox-note">inkl. Express-Aufschlag {fmt(data.express_aufschlag)}</span>
          )}
        </div>
      )}

      {hasMonthly && (
        <div className="wgk__pricebox">
          <span className="wgk__pricebox-label">{data.paket_label ? data.paket_label : 'Monatlich'}</span>
          <strong className="wgk__pricebox-value">
            {data.monatlich_from ? 'ab ' : ''}{fmt(data.monatlich_min)} <span style={{ fontSize: '0.5em', fontWeight: 400 }}>/ Monat</span>
          </strong>
          {data.monatlich_note && <span className="wgk__pricebox-note">{data.monatlich_note}</span>}
        </div>
      )}

      {data.naechste_schritte && (
        <p className="wgk__note"><strong>Nächste Schritte:</strong> {data.naechste_schritte}</p>
      )}

      <div className="wgk__actions wgk__actions--center">
        <a className="wgk__btn wgk__btn--primary" href={meetingUrl} target="_blank" rel="noopener">
          30-Min-Gespräch buchen
        </a>
        {data.pdf_url && (
          <a className="wgk__btn wgk__btn--ghost" href={data.pdf_url} target="_blank" rel="noopener">
            PDF öffnen
          </a>
        )}
      </div>
    </div>
  );
}
