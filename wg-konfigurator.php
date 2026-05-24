<?php
/**
 * Plugin Name:       WG Konfigurator
 * Plugin URI:        https://github.com/wg-digital/wg-konfigurator-plugin
 * Description:       Video-Konfigurator: Quiz-Wizard, KI-generiertes Konzept (Gemini), PDF-Auslieferung, CRM-Webhook.
 * Version:           0.1.0
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
define( 'WG_KONFIGURATOR_VERSION', '0.1.0' );
define( 'WG_KONFIGURATOR_FILE', __FILE__ );
define( 'WG_KONFIGURATOR_DIR', plugin_dir_path( __FILE__ ) );
define( 'WG_KONFIGURATOR_URL', plugin_dir_url( __FILE__ ) );
define( 'WG_KONFIGURATOR_BASENAME', plugin_basename( __FILE__ ) );

// ---------- Autoloader (Composer) ----------
$composer_autoload = WG_KONFIGURATOR_DIR . 'vendor/autoload.php';
if ( file_exists( $composer_autoload ) ) {
    require_once $composer_autoload;
} else {
    add_action( 'admin_notices', static function () {
        echo '<div class="notice notice-error"><p><strong>WG Konfigurator:</strong> '
            . esc_html__( 'Composer-Dependencies fehlen. Bitte `composer install` im Plugin-Verzeichnis ausführen.', 'wg-konfigurator' )
            . '</p></div>';
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
