import React from 'react';

const fmt = (n) => new Intl.NumberFormat('de-DE').format(n) + ' €';

export default function Result({ data, meetingUrl, theme }) {
  return (
    <div className={`wgk wgk--${theme} wgk__result`}>
      <h2 className="wgk__title">Dein Konzept ist unterwegs.</h2>
      <p className="wgk__note">
        Wir haben dir das individuelle Videokonzept als PDF an deine E-Mail-Adresse geschickt.
        Falls du es nicht findest: schau im Spam nach oder öffne es direkt:
      </p>

      <div className="wgk__pricebox">
        <span className="wgk__pricebox-label">Investitionsrahmen</span>
        <strong className="wgk__pricebox-value">
          {fmt(data.preis_min)} – {fmt(data.preis_max)}
        </strong>
        {data.express_aufschlag > 0 && (
          <span className="wgk__pricebox-note">inkl. Express-Aufschlag {fmt(data.express_aufschlag)}</span>
        )}
      </div>

      {data.naechste_schritte && (
        <p className="wgk__note"><strong>Nächste Schritte:</strong> {data.naechste_schritte}</p>
      )}

      <div className="wgk__actions wgk__actions--center">
        <a className="wgk__btn wgk__btn--primary" href={meetingUrl} target="_blank" rel="noopener">
          30-Min-Gespräch buchen
        </a>
        <a className="wgk__btn wgk__btn--ghost" href={data.pdf_url} target="_blank" rel="noopener">
          PDF öffnen
        </a>
      </div>
    </div>
  );
}
