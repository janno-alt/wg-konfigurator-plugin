<?php
declare( strict_types=1 );

namespace WG\Konfigurator\Frontend;

use WG\Konfigurator\Admin\Settings;

/**
 * Shortcode [wg_konfigurator]
 *
 * Rendert das Mount-Element für die React-App und reicht REST-URL + Nonce per
 * window.WG_KONFIGURATOR weiter. Optional: reCAPTCHA v3 wird automatisch geladen.
 */
final class Shortcode {

    public function register(): void {
        add_shortcode( 'wg_konfigurator', [ $this, 'render' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    public function enqueue_assets(): void {
        // Wird per Shortcode-Render lazy registriert (siehe render()),
        // damit Scripts nur auf Seiten mit dem Shortcode geladen werden.
    }

    /**
     * @param array<string,mixed> $atts
     */
    public function render( $atts = [] ): string {
        $atts = shortcode_atts( [
            'theme'    => 'dark',     // dark | light
            'compact'  => 'no',
            'product'  => 'video',    // video | recruiting | social
        ], $atts, 'wg_konfigurator' );

        $product = in_array( $atts['product'], [ 'video', 'recruiting', 'social' ], true )
            ? $atts['product']
            : 'video';

        $build_dir = WG_KONFIGURATOR_DIR . 'assets/quiz-app/dist/';
        $build_url = WG_KONFIGURATOR_URL . 'assets/quiz-app/dist/';

        // Erkenne aktuelle Vite-Bundle-Dateinamen (gehashed).
        $js  = $this->find_asset( $build_dir, 'assets', '.js' );
        $css = $this->find_asset( $build_dir, 'assets', '.css' );

        if ( $js === null ) {
            return '<div style="padding:24px;border:1px dashed #C2F21C;color:#FBFBFB;background:#141414;">'
                 . '<strong>WG Konfigurator:</strong> Quiz-App noch nicht gebaut. '
                 . 'Bitte im Plugin-Verzeichnis <code>npm install && npm run build</code> in <code>assets/quiz-app/</code> ausführen.'
                 . '</div>';
        }

        wp_register_script(
            'wg-konfigurator-app',
            $build_url . 'assets/' . $js,
            [],
            WG_KONFIGURATOR_VERSION,
            true
        );

        if ( $css !== null ) {
            wp_register_style(
                'wg-konfigurator-app',
                $build_url . 'assets/' . $css,
                [],
                WG_KONFIGURATOR_VERSION
            );
            wp_enqueue_style( 'wg-konfigurator-app' );
        }

        $settings = Settings::get();
        wp_localize_script( 'wg-konfigurator-app', 'WG_KONFIGURATOR', [
            'restUrl'         => esc_url_raw( rest_url( 'wg-konfigurator/v1/generate' ) ),
            'nonce'           => wp_create_nonce( 'wp_rest' ),
            'product'         => $product,
            'recaptchaSite'   => $settings['recaptcha_site'] ?? '',
            'meetingUrl'      => 'https://cal.meetergo.com/janno-fleischer/30-min-meeting-or-janno-fleischer',
            'tracking'        => [
                'msclkid'      => $this->get_query( 'msclkid' ),
                'utm_source'   => $this->get_query( 'utm_source' ),
                'utm_medium'   => $this->get_query( 'utm_medium' ),
                'utm_campaign' => $this->get_query( 'utm_campaign' ),
            ],
        ] );

        wp_enqueue_script( 'wg-konfigurator-app' );

        if ( ! empty( $settings['recaptcha_site'] ) ) {
            wp_enqueue_script(
                'wg-konfigurator-recaptcha',
                'https://www.google.com/recaptcha/api.js?render=' . rawurlencode( $settings['recaptcha_site'] ),
                [],
                null,
                true
            );
        }

        return sprintf(
            '<div id="wg-konfigurator-root" data-theme="%s" data-compact="%s" data-product="%s"></div>',
            esc_attr( $atts['theme'] ),
            esc_attr( $atts['compact'] ),
            esc_attr( $product )
        );
    }

    private function find_asset( string $dir, string $subdir, string $ext ): ?string {
        $path = $dir . $subdir;
        if ( ! is_dir( $path ) ) {
            return null;
        }
        foreach ( (array) scandir( $path ) as $f ) {
            if ( is_string( $f ) && str_starts_with( $f, 'index' ) && str_ends_with( $f, $ext ) ) {
                return $f;
            }
        }
        return null;
    }

    private function get_query( string $key ): string {
        return isset( $_GET[ $key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) : '';
    }
}
