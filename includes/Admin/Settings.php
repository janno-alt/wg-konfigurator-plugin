<?php
declare( strict_types=1 );

namespace WG\Konfigurator\Admin;

/**
 * Settings-Repository. Liest/schreibt die Plugin-Optionen.
 */
final class Settings {

    public const OPTION_KEY = 'wg_konfigurator_settings';

    /** @return array<string,mixed> */
    public static function defaults(): array {
        return [
            // KI
            'gemini_api_key'   => '',
            'gemini_model'     => 'gemini-2.5-flash',
            'gemini_max_input' => 8000, // Zeichen aus gescrapeter Website

            // PDF
            'pdf_brand_color'  => '#C2F21C',
            'pdf_bg_color'     => '#141414',
            'pdf_logo_url'     => '',

            // E-Mail
            'sender_name'      => 'WG-Digital',
            'sender_email'     => 'hello@wg-digital.xyz',
            'admin_email'      => 'hello@wg-digital.xyz',

            // SMTP (Mittwald)
            'smtp_enabled'     => false,
            'smtp_host'        => '',
            'smtp_port'        => 587,
            'smtp_secure'      => 'tls', // tls | ssl
            'smtp_user'        => '',
            'smtp_pass'        => '',

            // CRM-Webhook
            'webhook_url'      => 'https://crm.wg-digital.xyz/api/webhooks/wg-konfigurator',
            'webhook_secret'   => '',
            'webhook_timeout'  => 8,

            // Update-Checker
            'github_token'     => '',

            // Sicherheit
            'rate_limit_per_h' => 12,
            'recaptcha_site'   => '',
            'recaptcha_secret' => '',

            // Preis-Logik (Basis-Preise jetzt im PriceCalculator hardcoded — v0.5.0)
            'price_base'       => [],
            'price_per_day'    => 850,
            'express_surcharge'=> 0.20,
        ];
    }

    /** @return array<string,mixed> */
    public static function get(): array {
        $stored = get_option( self::OPTION_KEY, [] );
        return array_replace_recursive( self::defaults(), is_array( $stored ) ? $stored : [] );
    }

    public static function update( array $values ): void {
        $clean = self::sanitize( $values );
        update_option( self::OPTION_KEY, $clean );
    }

    /** @return array<string,mixed> */
    public static function sanitize( array $input ): array {
        $defaults = self::defaults();
        $out      = [];

        foreach ( $defaults as $key => $default ) {
            $val = $input[ $key ] ?? $default;

            switch ( $key ) {
                case 'gemini_api_key':
                case 'webhook_secret':
                case 'smtp_pass':
                case 'github_token':
                case 'recaptcha_secret':
                    $out[ $key ] = is_string( $val ) ? trim( $val ) : '';
                    break;

                case 'webhook_url':
                case 'pdf_logo_url':
                    $out[ $key ] = esc_url_raw( (string) $val );
                    break;

                case 'sender_email':
                case 'admin_email':
                    $out[ $key ] = sanitize_email( (string) $val );
                    break;

                case 'sender_name':
                case 'smtp_host':
                case 'smtp_user':
                case 'gemini_model':
                case 'recaptcha_site':
                    $out[ $key ] = sanitize_text_field( (string) $val );
                    break;

                case 'pdf_brand_color':
                case 'pdf_bg_color':
                    $out[ $key ] = sanitize_hex_color( (string) $val ) ?: $default;
                    break;

                case 'smtp_port':
                case 'webhook_timeout':
                case 'rate_limit_per_h':
                case 'gemini_max_input':
                    $out[ $key ] = max( 0, (int) $val );
                    break;

                case 'express_surcharge':
                    $out[ $key ] = max( 0, (float) $val );
                    break;

                case 'price_per_day':
                    $out[ $key ] = max( 0, (int) $val );
                    break;

                case 'smtp_enabled':
                    $out[ $key ] = ! empty( $val );
                    break;

                case 'smtp_secure':
                    $out[ $key ] = in_array( $val, [ 'tls', 'ssl', '' ], true ) ? $val : 'tls';
                    break;

                case 'price_base':
                    $out[ $key ] = is_array( $val ) ? $val : $default;
                    break;

                default:
                    $out[ $key ] = $val;
            }
        }

        return $out;
    }
}
