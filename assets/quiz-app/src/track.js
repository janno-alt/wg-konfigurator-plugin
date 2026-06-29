/* ============================================================
   dataLayer-Push beim erfolgreichen Konfigurator-Abschluss.
   Schema identisch zum bestehenden Lead-Tracking, damit es ohne
   neue Pipeline durch das vorhandene GTM-Setup läuft. Unterschieden
   wird allein über wg_form_name = 'konfigurator'.

   - PII kommt bereits SHA-256-gehasht vom Server (data.tracking.user_data),
     niemals Klartext im dataLayer.
   - Genau ein Push pro abgeschlossener Konfiguration (Dedup über pdf_url).
   ============================================================ */

const _pushed = new Set();

/**
 * @param {object} data Server-Response von /generate (enthält tracking{user_data,value,currency})
 */
export function pushKonfiguratorLead(data) {
  try {
    // Dedup: pro Abschluss (eindeutige pdf_url) nur ein einziges Event.
    const dedupKey = (data && data.pdf_url) || '';
    if (dedupKey) {
      if (_pushed.has(dedupKey)) return;
      _pushed.add(dedupKey);
    }

    const tracking = (data && data.tracking) || {};

    const payload = {
      event: 'wg_lead',
      wg_form_name: 'konfigurator',
      wg_event_id: 'evt.' + Date.now() + '.' + Math.floor(Math.random() * 1e9),
    };

    // wg_user_data nur, wenn der Server Hashes geliefert hat (Fall 1: Kontaktdaten erfasst).
    const ud = tracking.user_data;
    if (ud && typeof ud === 'object' && Object.keys(ud).length > 0) {
      payload.wg_user_data = ud;
    }

    // Optionaler Conversion-Wert.
    const value = Number(tracking.value);
    if (Number.isFinite(value) && value > 0) {
      payload.wg_value = value;
      payload.wg_currency = tracking.currency || 'EUR';
    }

    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push(payload);
  } catch (e) {
    /* noop – Tracking darf den Abschluss nie blockieren */
  }
}
