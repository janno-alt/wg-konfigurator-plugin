<?php
declare( strict_types=1 );

namespace WG\Konfigurator\Services;

use WG\Konfigurator\Admin\Settings;

/**
 * Schickt das Lead-Payload an den CRM-Webhook.
 *
 * SYNCHRON gesendet (nicht via WP-Cron), damit der Lead garantiert ankommt –
 * der User hat eh schon auf Gemini + PDF gewartet, die 1–3s Webhook fallen
 * nicht weiter ins Gewicht. WP-Cron-basierter Async-Versand ist auf Mittwald
 * + DISABLE_WP_CRON unzuverlässig und hat in v0.2.0 zu Lead-Verlust geführt.
 *
 * - Idempotenz-Key (X-WG-Idempotency)
 * - HMAC-Signatur (X-WG-Signature) wenn Secret gesetzt
 * - Retry mit Backoff (3 Versuche)
 */
final class WebhookSender {

    /**
     * Hauptmethode: synchron senden mit Retry.
     *
     * @param array<string,mixed> $payload
     * @return array{ok:bool, status:int, body:string, attempts:int}
     */
    public function dispatch( array $payload ): array {
        $idempotency = (string) ( $payload['idempotency_key'] ?? wp_generate_uuid4() );
        return $this->send( $payload, $idempotency );
    }

    /**
     * @param array<string,mixed> $payload
     * @return array{ok:bool, status:int, body:string, attempts:int}
     */
    private function send( array $payload, string $idempotency ): array {
        $s   = Settings::get();
        $url = (string) ( $s['webhook_url'] ?? '' );

        if ( $url === '' || strpos( $url, 'REPLACE_ME' ) !== false ) {
            return [ 'ok' => false, 'status' => 0, 'body' => 'webhook_url_not_configured', 'attempts' => 0 ];
        }

        $body    = (string) wp_json_encode( $payload, JSON_UNESCAPED_UNICODE );
        $headers = [
            'Content-Type'      => 'application/json',
            'X-WG-Idempotency'  => $idempotency,
            'X-WG-Source'       => 'wg-konfigurator/' . WG_KONFIGURATOR_VERSION,
            'User-Agent'        => 'WG-Konfigurator/' . WG_KONFIGURATOR_VERSION,
        ];

        $secret = (string) ( $s['webhook_secret'] ?? '' );
        if ( $secret !== '' ) {
            $headers['X-WG-Signature'] = 'sha256=' . hash_hmac( 'sha256', $body, $secret );
        }

        $timeout = max( 3, min( 30, (int) ( $s['webhook_timeout'] ?? 8 ) ) );
        $attempts = 0;
        $last_status = 0;
        $last_body   = '';

        do {
            $attempts++;
            $response = wp_remote_post( $url, [
                'timeout'    => $timeout,
                'headers'    => $headers,
                'body'       => $body,
                'blocking'   => true,
                'sslverify'  => true,
            ] );

            if ( ! is_wp_error( $response ) ) {
                $last_status = (int) wp_remote_retrieve_response_code( $response );
                $last_body   = (string) wp_remote_retrieve_body( $response );

                // 2xx + 409 (duplicate) → fertig, kein Retry
                if ( ( $last_status >= 200 && $last_status < 300 ) || $last_status === 409 ) {
                    return [ 'ok' => true, 'status' => $last_status, 'body' => $last_body, 'attempts' => $attempts ];
                }
                // 4xx (außer 429) → kein Retry, ist ein Client-Fehler
                if ( $last_status >= 400 && $last_status < 500 && $last_status !== 429 ) {
                    error_log( "[wg-konfigurator] Webhook 4xx ({$last_status}): " . substr( $last_body, 0, 300 ) );
                    return [ 'ok' => false, 'status' => $last_status, 'body' => $last_body, 'attempts' => $attempts ];
                }
            } else {
                $last_body = $response->get_error_message();
                error_log( '[wg-konfigurator] Webhook WP_Error: ' . $last_body );
            }

            // Retry mit kurzem Backoff
            if ( $attempts < 3 ) {
                sleep( $attempts ); // 1s, 2s
            }
        } while ( $attempts < 3 );

        error_log( '[wg-konfigurator] Webhook fehlgeschlagen nach 3 Versuchen: ' . $url );
        return [ 'ok' => false, 'status' => $last_status, 'body' => $last_body, 'attempts' => $attempts ];
    }
}
