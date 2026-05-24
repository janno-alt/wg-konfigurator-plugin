<?php
declare( strict_types=1 );

namespace WG\Konfigurator\Admin;

/**
 * Admin-Menüseite + Settings-API-Registrierung.
 */
final class SettingsPage {

    private const SLUG = 'wg-konfigurator';

    public function register(): void {
        add_action( 'admin_menu', [ $this, 'add_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_filter(
            'plugin_action_links_' . WG_KONFIGURATOR_BASENAME,
            [ $this, 'add_action_link' ]
        );
    }

    public function add_menu(): void {
        add_options_page(
            __( 'WG Konfigurator', 'wg-konfigurator' ),
            __( 'WG Konfigurator', 'wg-konfigurator' ),
            'manage_options',
            self::SLUG,
            [ $this, 'render_page' ]
        );
    }

    public function add_action_link( array $links ): array {
        $url     = admin_url( 'options-general.php?page=' . self::SLUG );
        $links[] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Einstellungen', 'wg-konfigurator' ) . '</a>';
        return $links;
    }

    public function register_settings(): void {
        register_setting(
            'wg_konfigurator_group',
            Settings::OPTION_KEY,
            [
                'type'              => 'array',
                'sanitize_callback' => [ Settings::class, 'sanitize' ],
                'default'           => Settings::defaults(),
            ]
        );
    }

    public function render_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $s = Settings::get();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'WG Konfigurator – Einstellungen', 'wg-konfigurator' ); ?></h1>

            <p style="max-width:780px;color:#555">
                <?php esc_html_e( 'Konfiguration für den Video-Konfigurator: Gemini-API, PDF, SMTP-Versand und CRM-Webhook. Shortcode zum Einbetten: ', 'wg-konfigurator' ); ?>
                <code>[wg_konfigurator]</code>
            </p>

            <form action="options.php" method="post">
                <?php settings_fields( 'wg_konfigurator_group' ); ?>

                <h2 class="title"><?php esc_html_e( 'KI (Google Gemini)', 'wg-konfigurator' ); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="gemini_api_key"><?php esc_html_e( 'Gemini API-Key', 'wg-konfigurator' ); ?></label></th>
                        <td><input id="gemini_api_key" type="password" name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[gemini_api_key]" value="<?php echo esc_attr( $s['gemini_api_key'] ); ?>" class="regular-text" autocomplete="off" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="gemini_model"><?php esc_html_e( 'Modell', 'wg-konfigurator' ); ?></label></th>
                        <td><input id="gemini_model" type="text" name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[gemini_model]" value="<?php echo esc_attr( $s['gemini_model'] ); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="gemini_max_input"><?php esc_html_e( 'Max. Website-Zeichen', 'wg-konfigurator' ); ?></label></th>
                        <td><input id="gemini_max_input" type="number" min="1000" max="30000" step="500" name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[gemini_max_input]" value="<?php echo esc_attr( (string) $s['gemini_max_input'] ); ?>" /></td>
                    </tr>
                </table>

                <h2 class="title"><?php esc_html_e( 'PDF-Design', 'wg-konfigurator' ); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="pdf_brand_color"><?php esc_html_e( 'Akzentfarbe', 'wg-konfigurator' ); ?></label></th>
                        <td><input id="pdf_brand_color" type="text" name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[pdf_brand_color]" value="<?php echo esc_attr( $s['pdf_brand_color'] ); ?>" class="small-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pdf_bg_color"><?php esc_html_e( 'Hintergrundfarbe', 'wg-konfigurator' ); ?></label></th>
                        <td><input id="pdf_bg_color" type="text" name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[pdf_bg_color]" value="<?php echo esc_attr( $s['pdf_bg_color'] ); ?>" class="small-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pdf_logo_url"><?php esc_html_e( 'Logo-URL (optional)', 'wg-konfigurator' ); ?></label></th>
                        <td><input id="pdf_logo_url" type="url" name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[pdf_logo_url]" value="<?php echo esc_attr( $s['pdf_logo_url'] ); ?>" class="regular-text" /></td>
                    </tr>
                </table>

                <h2 class="title"><?php esc_html_e( 'E-Mail-Versand', 'wg-konfigurator' ); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="sender_name"><?php esc_html_e( 'Absender-Name', 'wg-konfigurator' ); ?></label></th>
                        <td><input id="sender_name" type="text" name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[sender_name]" value="<?php echo esc_attr( $s['sender_name'] ); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sender_email"><?php esc_html_e( 'Absender-E-Mail', 'wg-konfigurator' ); ?></label></th>
                        <td><input id="sender_email" type="email" name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[sender_email]" value="<?php echo esc_attr( $s['sender_email'] ); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="admin_email"><?php esc_html_e( 'Interne Benachrichtigung an', 'wg-konfigurator' ); ?></label></th>
                        <td><input id="admin_email" type="email" name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[admin_email]" value="<?php echo esc_attr( $s['admin_email'] ); ?>" class="regular-text" /></td>
                    </tr>
                </table>

                <h2 class="title"><?php esc_html_e( 'SMTP (Mittwald)', 'wg-konfigurator' ); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="smtp_enabled"><?php esc_html_e( 'SMTP aktivieren', 'wg-konfigurator' ); ?></label></th>
                        <td><input id="smtp_enabled" type="checkbox" name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[smtp_enabled]" value="1" <?php checked( $s['smtp_enabled'] ); ?> /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="smtp_host"><?php esc_html_e( 'Host', 'wg-konfigurator' ); ?></label></th>
                        <td><input id="smtp_host" type="text" name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[smtp_host]" value="<?php echo esc_attr( $s['smtp_host'] ); ?>" class="regular-text" placeholder="smtp.mittwald.de" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="smtp_port"><?php esc_html_e( 'Port', 'wg-konfigurator' ); ?></label></th>
                        <td><input id="smtp_port" type="number" name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[smtp_port]" value="<?php echo esc_attr( (string) $s['smtp_port'] ); ?>" class="small-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="smtp_secure"><?php esc_html_e( 'Verschlüsselung', 'wg-konfigurator' ); ?></label></th>
                        <td>
                            <select id="smtp_secure" name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[smtp_secure]">
                                <option value="tls" <?php selected( $s['smtp_secure'], 'tls' ); ?>>STARTTLS</option>
                                <option value="ssl" <?php selected( $s['smtp_secure'], 'ssl' ); ?>>SSL/TLS</option>
                                <option value="" <?php selected( $s['smtp_secure'], '' ); ?>><?php esc_html_e( 'Keine', 'wg-konfigurator' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="smtp_user"><?php esc_html_e( 'Benutzer', 'wg-konfigurator' ); ?></label></th>
                        <td><input id="smtp_user" type="text" name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[smtp_user]" value="<?php echo esc_attr( $s['smtp_user'] ); ?>" class="regular-text" autocomplete="off" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="smtp_pass"><?php esc_html_e( 'Passwort', 'wg-konfigurator' ); ?></label></th>
                        <td><input id="smtp_pass" type="password" name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[smtp_pass]" value="<?php echo esc_attr( $s['smtp_pass'] ); ?>" class="regular-text" autocomplete="new-password" /></td>
                    </tr>
                </table>

                <h2 class="title"><?php esc_html_e( 'CRM-Webhook', 'wg-konfigurator' ); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="webhook_url"><?php esc_html_e( 'Webhook-URL', 'wg-konfigurator' ); ?></label></th>
                        <td>
                            <input id="webhook_url" type="url" name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[webhook_url]" value="<?php echo esc_attr( $s['webhook_url'] ); ?>" class="regular-text" />
                            <p class="description"><?php esc_html_e( 'Für Tests: einen Mock-Endpoint von webhook.site verwenden.', 'wg-konfigurator' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="webhook_secret"><?php esc_html_e( 'Shared Secret (HMAC)', 'wg-konfigurator' ); ?></label></th>
                        <td><input id="webhook_secret" type="text" name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[webhook_secret]" value="<?php echo esc_attr( $s['webhook_secret'] ); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="webhook_timeout"><?php esc_html_e( 'Timeout (Sekunden)', 'wg-konfigurator' ); ?></label></th>
                        <td><input id="webhook_timeout" type="number" name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[webhook_timeout]" value="<?php echo esc_attr( (string) $s['webhook_timeout'] ); ?>" class="small-text" /></td>
                    </tr>
                </table>

                <h2 class="title"><?php esc_html_e( 'Sicherheit', 'wg-konfigurator' ); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="rate_limit_per_h"><?php esc_html_e( 'Rate-Limit / IP / Stunde', 'wg-konfigurator' ); ?></label></th>
                        <td><input id="rate_limit_per_h" type="number" min="1" max="100" name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[rate_limit_per_h]" value="<?php echo esc_attr( (string) $s['rate_limit_per_h'] ); ?>" class="small-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="recaptcha_site"><?php esc_html_e( 'reCAPTCHA Site Key (optional)', 'wg-konfigurator' ); ?></label></th>
                        <td><input id="recaptcha_site" type="text" name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[recaptcha_site]" value="<?php echo esc_attr( $s['recaptcha_site'] ); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="recaptcha_secret"><?php esc_html_e( 'reCAPTCHA Secret', 'wg-konfigurator' ); ?></label></th>
                        <td><input id="recaptcha_secret" type="password" name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[recaptcha_secret]" value="<?php echo esc_attr( $s['recaptcha_secret'] ); ?>" class="regular-text" autocomplete="off" /></td>
                    </tr>
                </table>

                <h2 class="title"><?php esc_html_e( 'Plugin-Updates (GitHub)', 'wg-konfigurator' ); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="github_token"><?php esc_html_e( 'GitHub Token (privates Repo)', 'wg-konfigurator' ); ?></label></th>
                        <td>
                            <input id="github_token" type="password" name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[github_token]" value="<?php echo esc_attr( $s['github_token'] ); ?>" class="regular-text" autocomplete="off" />
                            <p class="description"><?php esc_html_e( 'Nur erforderlich, wenn das Repo privat ist. Updates erscheinen automatisch im WP-Update-Center.', 'wg-konfigurator' ); ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
