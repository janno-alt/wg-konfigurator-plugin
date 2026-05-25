<?php
declare( strict_types=1 );

namespace WG\Konfigurator\Rest;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WG\Konfigurator\Admin\Settings;
use WG\Konfigurator\Services\WebhookSender;

/**
 * POST /wp-json/wg-konfigurator/v1/start
 *
 * Wird aufgerufen, sobald der User auf dem Intro-Screen "Konfigurator starten"
 * klickt. Wir erfassen Email + DSGVO-Einwilligung sofort, damit das CRM
 * Recovery-Mails schicken kann falls der User später abspringt.
 *
 * Schickt ein `konfigurator.started`-Event an den Webhook (CRM legt
 * daraus einen Lead mit status="started" an).
 */
final class StartEndpoint {

    public function register_routes(): void {
        register_rest_route(
            'wg-konfigurator/v1',
            '/start',
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'handle' ],
                'permission_callback' => '__return_true',
                'args'                => [
                    'lead'     => [ 'required' => true,  'type' => 'object' ],
                    'tracking' => [ 'required' => false, 'type' => 'object' ],
                ],
            ]
        );
    }

    public function handle( WP_REST_Request $request ) {
        $settings = Settings::get();
        $ip       = $this->client_ip( $request );

        // Sanftes Rate-Limit (gleiches Bucket wie /generate, niedriger Threshold)
        $limit   = max( 1, (int) $settings['rate_limit_per_h'] );
        $key     = 'wg_konfig_rl_' . md5( $ip );
        $current = (int) get_transient( $key );
        if ( $current >= $limit * 2 ) { // doppelt so tolerant wie /generate
            return new WP_Error( 'rate_limit', 'Zu viele Anfragen.', [ 'status' => 429 ] );
        }
        set_transient( $key, $current + 1, HOUR_IN_SECONDS );

        $lead_in  = (array) $request->get_param( 'lead' );
        $tracking = (array) ( $request->get_param( 'tracking' ) ?? [] );

        $email = sanitize_email( (string) ( $lead_in['email'] ?? '' ) );
        if ( ! is_email( $email ) ) {
            return new WP_Error( 'invalid_email', 'Bitte gültige E-Mail-Adresse eingeben.', [ 'status' => 400 ] );
        }
        if ( empty( $lead_in['dsgvo_opt_in'] ) ) {
            return new WP_Error( 'no_consent', 'DSGVO-Einwilligung erforderlich.', [ 'status' => 400 ] );
        }

        $session_id = wp_generate_uuid4();

        $payload = [
            'event'           => 'konfigurator.started',
            'idempotency_key' => $session_id,
            'session_id'      => $session_id,
            'generated_at'    => gmdate( 'c' ),
            'lead'            => [
                'email'            => $email,
                'dsgvo_opt_in'     => true,
                'marketing_opt_in' => ! empty( $lead_in['marketing_opt_in'] ),
            ],
            'tracking'        => [
                'msclkid'      => sanitize_text_field( (string) ( $tracking['msclkid']      ?? '' ) ),
                'utm_source'   => sanitize_text_field( (string) ( $tracking['utm_source']   ?? '' ) ),
                'utm_medium'   => sanitize_text_field( (string) ( $tracking['utm_medium']   ?? '' ) ),
                'utm_campaign' => sanitize_text_field( (string) ( $tracking['utm_campaign'] ?? '' ) ),
            ],
        ];

        // Synchron senden (kurzer Roundtrip ~500ms, der User wartet eh).
        $webhook = new WebhookSender();
        $result  = $webhook->dispatch( $payload );

        if ( ! $result['ok'] ) {
            // Nicht-fatal: User darf weiter, aber loggen
            error_log( sprintf(
                '[wg-konfigurator] /start webhook failed: status=%d, attempts=%d',
                $result['status'],
                $result['attempts']
            ) );
        }

        return new WP_REST_Response( [
            'ok'         => true,
            'session_id' => $session_id,
        ], 200 );
    }

    private function client_ip( WP_REST_Request $request ): string {
        $candidates = [
            $request->get_header( 'cf-connecting-ip' ),
            $request->get_header( 'x-forwarded-for' ),
            $_SERVER['REMOTE_ADDR'] ?? '',
        ];
        foreach ( $candidates as $c ) {
            $c = trim( explode( ',', (string) $c )[0] ?? '' );
            if ( filter_var( $c, FILTER_VALIDATE_IP ) ) {
                return $c;
            }
        }
        return '0.0.0.0';
    }
}
