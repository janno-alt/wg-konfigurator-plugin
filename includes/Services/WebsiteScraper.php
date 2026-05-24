<?php
declare( strict_types=1 );

namespace WG\Konfigurator\Services;

use fivefilters\Readability\Configuration;
use fivefilters\Readability\Readability;
use WG\Konfigurator\Admin\Settings;

/**
 * Holt eine URL ab, isoliert den Haupt-Content (Readability) und kürzt ihn auf eine
 * für Gemini-Prompt verträgliche Länge. Robust gegen unerreichbare Hosts.
 */
final class WebsiteScraper {

    public function scrape( string $url ): string {
        $url = trim( $url );
        if ( $url === '' ) {
            return '';
        }
        if ( ! preg_match( '#^https?://#i', $url ) ) {
            $url = 'https://' . $url;
        }
        if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
            return '';
        }

        // SSRF-Schutz: keine internen IPs
        $host = wp_parse_url( $url, PHP_URL_HOST );
        if ( ! is_string( $host ) || $this->is_local_host( $host ) ) {
            return '';
        }

        $response = wp_remote_get( $url, [
            'timeout'     => 12,
            'redirection' => 4,
            'sslverify'   => true,
            'user-agent'  => 'WG-Konfigurator/' . WG_KONFIGURATOR_VERSION . ' (+https://wg-digitalmarketing.de)',
            'headers'     => [
                'Accept'          => 'text/html,application/xhtml+xml',
                'Accept-Language' => 'de-DE,de;q=0.9,en;q=0.8',
            ],
        ] );

        if ( is_wp_error( $response ) ) {
            return '';
        }
        if ( wp_remote_retrieve_response_code( $response ) >= 400 ) {
            return '';
        }

        $html = wp_remote_retrieve_body( $response );
        if ( $html === '' ) {
            return '';
        }

        try {
            $config = new Configuration();
            $config->setFixRelativeURLs( true );
            $config->setOriginalURL( $url );

            $readability = new Readability( $config );
            $readability->parse( $html );

            $title   = (string) $readability->getTitle();
            $excerpt = (string) $readability->getExcerpt();
            $content = wp_strip_all_tags( (string) $readability->getContent(), true );

            $combined = trim( "{$title}\n\n{$excerpt}\n\n{$content}" );
        } catch ( \Throwable $e ) {
            $combined = wp_strip_all_tags( $html, true );
        }

        $combined = preg_replace( '/[ \t]+/', ' ', $combined );
        $combined = preg_replace( "/\n{3,}/", "\n\n", $combined );

        $max = (int) ( Settings::get()['gemini_max_input'] ?? 8000 );
        if ( function_exists( 'mb_substr' ) && mb_strlen( $combined ) > $max ) {
            $combined = mb_substr( $combined, 0, $max ) . '…';
        } elseif ( strlen( $combined ) > $max ) {
            $combined = substr( $combined, 0, $max ) . '…';
        }

        return trim( (string) $combined );
    }

    private function is_local_host( string $host ): bool {
        $blocked = [ 'localhost', '127.0.0.1', '0.0.0.0', '::1' ];
        if ( in_array( strtolower( $host ), $blocked, true ) ) {
            return true;
        }

        $ip = filter_var( $host, FILTER_VALIDATE_IP ) ?: gethostbyname( $host );
        if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
            return false;
        }

        return ! filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}
