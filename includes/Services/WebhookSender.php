<?php
declare( strict_types=1 );

namespace WG\Konfigurator\Services;

use WG\Konfigurator\Admin\Settings;

/**
 * Schickt das Lead-Payload an einen konfigurierbaren Webhook (Mock oder CRM).
 *
 * - Idempotenz-Key (X-WG-Idempotency)
 * - HMAC-Signatur (X-WG-Signature) wenn Secret gesetzt
 * - Retry mit Backoff (3 Versuche)
 * - Async via wp_schedule_single_event, damit der Request nicht blockiert
 */
final class WebhookSender {

    private const HOOK_NAME = 'wg_konfigurator_send_webhook';

    public function __construct() {
        add_action( self::HOOK_NAME, [ $this, 'execute' ], 10, 2 );
    }

    /**
     * Plant den Versand für 1 Sekunde in die Zukunft (= effektiv async, sobald
     * die WP-Response zurück an den Browser ist und WP-Cron triggert).
     *
     * @param array<string,mixed> $payload
     */
    public function dispatch( array $payload ): void {
        $idempotency = $payload['idempotency_key'] ?? wp_generate_uuid4();
        wp_schedule_single_event( time() + 1, self::HOOK_NAME, [ $payload, $idempotency ] );
    }

    /**
     * Wird vom WP-Cron aufgerufen.
     *
     * @param array<string,mixed> $payload
     */
    public function execute( array $payload, string $idempotency ): void {
        $s   = Settings::get();
        $url = (string) ( $s['webhook_url'] ?? '' );

        if ( $url === '' || strpos( $url, 'REPLACE_ME' ) !== false ) {
            // Mock-Default unverändert – nichts senden.
            return;
        }

        $body    = wp_json_encode( $payload, JSON_UNESCAPED_UNICODE );
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
        do {
            $attempts++;
            $response = wp_remote_post( $url, [
                'timeout' => $timeout,
                'headers' => $headers,
                'body'    => $body,
            ] );

            if ( ! is_wp_error( $response ) ) {
                $code = wp_remote_retrieve_response_code( $response );
                if ( $code >= 200 && $code < 300 ) {
                    return;
                }
            }

            if ( $attempts < 3 ) {
                // Exponential backoff: 2s, 6s
                sleep( $attempts * $attempts * 2 );
            }
        } while ( $attempts < 3 );

        // Letzter Fehler — als Notice in den Log.
        error_log( '[wg-konfigurator] Webhook fehlgeschlagen nach 3 Versuchen: ' . $url );
    }
}
