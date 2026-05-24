<?php
/**
 * E-Mail an den Kunden mit PDF im Anhang.
 *
 * @var array $lead
 * @var array $pdf
 * @var array $concept
 * @var array $settings
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$brand = (string) ( $settings['pdf_brand_color'] ?? '#C2F21C' );
$dark  = (string) ( $settings['pdf_bg_color']    ?? '#141414' );
?>
<!doctype html>
<html lang="de"><body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;color:#222;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:24px 0;">
    <tr><td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;">
            <tr><td style="background:<?php echo esc_attr( $dark ); ?>;padding:28px 28px 20px;">
                <div style="height:4px;width:60px;background:<?php echo esc_attr( $brand ); ?>;margin-bottom:14px;"></div>
                <h1 style="margin:0;color:#fff;font-size:24px;line-height:1.2;">
                    Hi <?php echo esc_html( $lead['vorname'] ?? '' ); ?>,<br>
                    <span style="color:<?php echo esc_attr( $brand ); ?>;">dein Videokonzept ist da.</span>
                </h1>
            </td></tr>

            <tr><td style="padding:24px 28px 8px;">
                <p style="margin:0 0 14px;font-size:15px;line-height:1.6;">
                    Im Anhang findest du dein individuelles Konzept als PDF –
                    inklusive Story-Skizze, Vorbereitungs-Checkliste und einer
                    Einschätzung des Investitionsrahmens.
                </p>
                <p style="margin:0 0 14px;font-size:15px;line-height:1.6;">
                    <strong>Was wir uns überlegt haben:</strong><br>
                    <?php echo esc_html( $concept['wirkungs_hypothese'] ?? '' ); ?>
                </p>
                <p style="margin:24px 0;text-align:center;">
                    <a href="https://cal.meetergo.com/janno-fleischer/30-min-meeting-or-janno-fleischer"
                       style="display:inline-block;background:<?php echo esc_attr( $brand ); ?>;color:<?php echo esc_attr( $dark ); ?>;padding:14px 28px;border-radius:6px;text-decoration:none;font-weight:700;font-size:15px;">
                        30-Min-Gespräch buchen
                    </a>
                </p>
                <p style="margin:0 0 14px;font-size:13px;color:#666;line-height:1.6;">
                    Lieber per Mail? Antworte einfach auf diese Nachricht.
                </p>
            </td></tr>

            <tr><td style="padding:14px 28px 24px;border-top:1px solid #eee;font-size:12px;color:#888;">
                WG-Digital · Videomarketing aus Mitteldeutschland<br>
                <a href="https://wg-digitalmarketing.de" style="color:#888;">wg-digitalmarketing.de</a>
            </td></tr>
        </table>
    </td></tr>
</table>
</body></html>
