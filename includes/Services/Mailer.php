<?php
declare( strict_types=1 );

namespace WG\Konfigurator\Services;

use PHPMailer\PHPMailer\PHPMailer;
use WG\Konfigurator\Admin\Settings;

/**
 * Versendet Kunden- und Admin-Mails. Nutzt wp_mail mit SMTP-Override über
 * den phpmailer_init-Hook (PHPMailer ist in WP gebündelt).
 */
final class Mailer {

    public function __construct() {
        add_action( 'phpmailer_init', [ $this, 'configure_smtp' ] );
    }

    public function send_customer( array $lead, array $pdf, array $concept ): bool {
        $settings = Settings::get();
        $subject  = sprintf( '%s, dein Videokonzept ist da', $lead['vorname'] ?? '' );

        $html = $this->render_template( 'email-customer.php', [
            'lead'    => $lead,
            'pdf'     => $pdf,
            'concept' => $concept,
            'settings'=> $settings,
        ] );

        $headers = $this->headers( $settings );

        return wp_mail(
            $lead['email'],
            $subject,
            $html,
            $headers,
            [ $pdf['path'] ]
        );
    }

    public function send_admin( array $lead, array $quiz, array $pricing, array $pdf ): bool {
        $settings = Settings::get();
        $subject  = sprintf(
            '[Konfigurator] Neuer Lead · %s · %s',
            $lead['vorname'] ?? '–',
            $lead['email']   ?? '–'
        );

        $html = $this->render_template( 'email-admin.php', [
            'lead'    => $lead,
            'quiz'    => $quiz,
            'pricing' => $pricing,
            'pdf'     => $pdf,
            'settings'=> $settings,
        ] );

        $headers = $this->headers( $settings );
        if ( ! empty( $lead['email'] ) ) {
            $headers[] = 'Reply-To: ' . sanitize_email( (string) $lead['email'] );
        }

        return wp_mail(
            $settings['admin_email'],
            $subject,
            $html,
            $headers
        );
    }

    public function configure_smtp( PHPMailer $mailer ): void {
        $s = Settings::get();
        if ( empty( $s['smtp_enabled'] ) || empty( $s['smtp_host'] ) ) {
            return;
        }

        $mailer->isSMTP();
        $mailer->Host        = (string) $s['smtp_host'];
        $mailer->Port        = (int) $s['smtp_port'];
        $mailer->SMTPAuth    = ! empty( $s['smtp_user'] );
        $mailer->Username    = (string) $s['smtp_user'];
        $mailer->Password    = (string) $s['smtp_pass'];
        $mailer->SMTPSecure  = in_array( $s['smtp_secure'], [ 'tls', 'ssl' ], true ) ? $s['smtp_secure'] : '';
        $mailer->From        = (string) $s['sender_email'];
        $mailer->FromName    = (string) $s['sender_name'];
        $mailer->CharSet     = 'UTF-8';
        $mailer->Encoding    = 'base64';
    }

    /** @param array<string,mixed> $vars */
    private function render_template( string $file, array $vars ): string {
        $path = WG_KONFIGURATOR_DIR . 'templates/' . $file;
        if ( ! file_exists( $path ) ) {
            return '';
        }
        ob_start();
        ( static function ( $__p, $__v ) {
            extract( $__v, EXTR_SKIP );
            include $__p;
        } )( $path, $vars );
        return (string) ob_get_clean();
    }

    /** @return array<int,string> */
    private function headers( array $s ): array {
        return [
            'Content-Type: text/html; charset=UTF-8',
            sprintf( 'From: %s <%s>', $s['sender_name'], $s['sender_email'] ),
        ];
    }
}
