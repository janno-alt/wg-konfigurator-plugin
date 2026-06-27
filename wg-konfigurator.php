<?php
/**
 * Plugin Name:       WG Konfigurator
 * Plugin URI:        https://github.com/janno-alt/wg-konfigurator-plugin
 * Description:       Konfigurator (Video, Recruiting, Social): Quiz-Wizard, KI-generiertes Konzept (Gemini), PDF-Auslieferung, CRM-Webhook.
 * Version:           0.11.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            WG-Digital
 * Author URI:        https://wg-digitalmarketing.de
 * License:           Proprietary
 * Text Domain:       wg-konfigurator
 * Domain Path:       /languages
 *
 * @package WG\Konfigurator
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ---------- Constants ----------
define( 'WG_KONFIGURATOR_VERSION', '0.11.0' );
define( 'WG_KONFIGURATOR_FILE', __FILE__ );
define( 'WG_KONFIGURATOR_DIR', plugin_dir_path( __FILE__ ) );
define( 'WG_KONFIGURATOR_URL', plugin_dir_url( __FILE__ ) );
define( 'WG_KONFIGURATOR_BASENAME', plugin_basename( __FILE__ ) );

// ---------- Autoloader (Composer) ----------
$composer_autoload = WG_KONFIGURATOR_DIR . 'vendor/autoload.php';
if ( file_exists( $composer_autoload ) && is_readable( $composer_autoload ) ) {
    require_once $composer_autoload;
} else {
    // Detaillierte Diagnose, damit wir den Pfad sehen wenn das ZIP unvollständig ausgepackt wurde
    add_action( 'admin_notices', static function () use ( $composer_autoload ) {
        $exists   = file_exists( $composer_autoload );
        $readable = $exists && is_readable( $composer_autoload );
        $vendor_dir = WG_KONFIGURATOR_DIR . 'vendor';
        $vendor_exists = is_dir( $vendor_dir );
        $vendor_files  = $vendor_exists ? count( (array) @scandir( $vendor_dir ) ) - 2 : 0;

        echo '<div class="notice notice-error"><p><strong>WG Konfigurator:</strong> '
            . esc_html__( 'Composer-Dependencies fehlen oder sind nicht lesbar.', 'wg-konfigurator' )
            . '</p><pre style="background:#fff;padding:8px;border:1px solid #ccc;font-size:12px;">'
            . 'Plugin-Pfad: ' . esc_html( WG_KONFIGURATOR_DIR ) . "\n"
            . 'Erwarteter autoload.php: ' . esc_html( $composer_autoload ) . "\n"
            . 'file_exists():  ' . ( $exists ? 'JA' : 'NEIN' ) . "\n"
            . 'is_readable():  ' . ( $readable ? 'JA' : 'NEIN' ) . "\n"
            . 'vendor/ existiert: ' . ( $vendor_exists ? 'JA' : 'NEIN' ) . "\n"
            . 'vendor/ Einträge: ' . esc_html( (string) $vendor_files ) . "\n"
            . 'PHP open_basedir: ' . esc_html( ini_get( 'open_basedir' ) ?: '(nicht gesetzt)' ) . "\n"
            . 'PHP-Version: ' . PHP_VERSION
            . '</pre></div>';
    } );
    return;
}

// ---------- Boot ----------
add_action( 'plugins_loaded', static function () {
    \WG\Konfigurator\Plugin::instance()->boot();
} );

// ---------- Activation / Deactivation ----------
register_activation_hook( __FILE__, [ '\WG\Konfigurator\Plugin', 'on_activate' ] );
register_deactivation_hook( __FILE__, [ '\WG\Konfigurator\Plugin', 'on_deactivate' ] );
