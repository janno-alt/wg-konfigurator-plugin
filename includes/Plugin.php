<?php
declare( strict_types=1 );

namespace WG\Konfigurator;

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

/**
 * Hauptklasse – orchestriert Boot, Update-Checker, REST, Admin, Shortcode.
 */
final class Plugin {

    private static ?Plugin $instance = null;

    private bool $booted = false;

    public static function instance(): Plugin {
        return self::$instance ??= new self();
    }

    public function boot(): void {
        if ( $this->booted ) {
            return;
        }
        $this->booted = true;

        // i18n
        load_plugin_textdomain( 'wg-konfigurator', false, dirname( WG_KONFIGURATOR_BASENAME ) . '/languages' );

        // GitHub-basierter Plugin Update Checker
        $this->register_update_checker();

        // Admin UI
        ( new Admin\SettingsPage() )->register();

        // REST API
        add_action( 'rest_api_init', static function () {
            ( new Rest\GenerateEndpoint() )->register_routes();
        } );

        // Shortcode [wg_konfigurator]
        ( new Frontend\Shortcode() )->register();
    }

    private function register_update_checker(): void {
        if ( ! class_exists( PucFactory::class ) ) {
            return;
        }

        $repo_url = apply_filters(
            'wg_konfigurator_update_repo',
            'https://github.com/janno-alt/wg-konfigurator-plugin'
        );

        $checker = PucFactory::buildUpdateChecker(
            $repo_url,
            WG_KONFIGURATOR_FILE,
            'wg-konfigurator'
        );

        // Optional: Pre-Release-Branch oder GitHub-Token aus Settings ziehen
        $settings = Admin\Settings::get();

        if ( ! empty( $settings['github_token'] ) ) {
            $checker->setAuthentication( $settings['github_token'] );
        }

        // Update über GitHub Releases (statt main-Branch). Tags wie v0.1.1.
        if ( method_exists( $checker, 'getVcsApi' ) && $checker->getVcsApi() ) {
            $checker->getVcsApi()->enableReleaseAssets();
        }
    }

    public static function on_activate(): void {
        // Storage-Verzeichnis für generierte PDFs
        $upload = wp_upload_dir();
        $dir    = trailingslashit( $upload['basedir'] ) . 'wg-konfigurator';
        if ( ! is_dir( $dir ) ) {
            wp_mkdir_p( $dir );
        }

        // .htaccess: PDFs sind über signierten Link erreichbar, nicht enumerierbar
        $htaccess = $dir . '/.htaccess';
        if ( ! file_exists( $htaccess ) ) {
            file_put_contents(
                $htaccess,
                "Options -Indexes\n<FilesMatch \"\\.(pdf)$\">\n  Require all granted\n</FilesMatch>\n"
            );
        }

        // Defaults setzen
        if ( false === get_option( 'wg_konfigurator_settings' ) ) {
            update_option( 'wg_konfigurator_settings', Admin\Settings::defaults() );
        }
    }

    public static function on_deactivate(): void {
        // Keine destructiven Aktionen — Daten bleiben erhalten.
    }
}
