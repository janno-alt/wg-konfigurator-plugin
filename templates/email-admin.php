<?php
/**
 * Interne Lead-Benachrichtigung.
 *
 * @var array $lead
 * @var array $quiz
 * @var array $pricing
 * @var array $pdf
 * @var array $settings
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$fmt = static fn( $n ) => number_format( (int) $n, 0, ',', '.' ) . ' €';
?>
<!doctype html>
<html lang="de"><body style="font-family:Arial,sans-serif;color:#222;background:#fff;padding:20px;">
    <h2 style="margin:0 0 12px;color:#141414;">Neuer Konfigurator-Lead</h2>
    <p style="margin:0 0 16px;font-size:13px;color:#666;">
        Score: <strong><?php echo (int) ( $pricing['score'] ?? 0 ); ?>/100</strong> ·
        Eingegangen: <?php echo esc_html( wp_date( 'd.m.Y H:i' ) ); ?>
    </p>

    <h3 style="margin:18px 0 6px;">Kontakt</h3>
    <table cellpadding="4" cellspacing="0" style="font-size:13px;border-collapse:collapse;">
        <tr><td><strong>Name</strong></td><td><?php echo esc_html( $lead['name'] ?? ( $lead['vorname'] ?? '' ) ); ?></td></tr>
        <tr><td><strong>E-Mail</strong></td><td><a href="mailto:<?php echo esc_attr( $lead['email'] ?? '' ); ?>"><?php echo esc_html( $lead['email'] ?? '' ); ?></a></td></tr>
        <tr><td><strong>Marketing-Opt-In</strong></td><td><?php echo ! empty( $lead['marketing_opt_in'] ) ? 'Ja' : 'Nein'; ?></td></tr>
    </table>

    <?php
        $paket_lbl = \WG\Konfigurator\Services\PriceCalculator::paket_label(
            (string) ( $quiz['output_paket'] ?? 'einzel' )
        );
        $feat_lbl = \WG\Konfigurator\Services\PriceCalculator::feature_labels(
            (array) ( $quiz['features'] ?? [] )
        );
    ?>
    <h3 style="margin:18px 0 6px;">Quiz</h3>
    <table cellpadding="4" cellspacing="0" style="font-size:13px;border-collapse:collapse;">
        <tr><td><strong>Video-Typ</strong></td><td><?php echo esc_html( (string) ( $quiz['video_typ'] ?? '–' ) ); ?></td></tr>
        <tr><td><strong>Output-Paket</strong></td><td><?php echo esc_html( $paket_lbl ); ?></td></tr>
        <tr><td><strong>Features</strong></td><td><?php echo esc_html( $feat_lbl ? implode( ', ', $feat_lbl ) : 'Standard-Setup' ); ?></td></tr>
        <tr><td><strong>Drehtage (intern)</strong></td><td><?php echo (int) ( $pricing['drehtage'] ?? 1 ); ?></td></tr>
        <tr><td><strong>Zeitrahmen</strong></td><td><?php echo esc_html( (string) ( $quiz['zeitrahmen'] ?? '–' ) ); ?></td></tr>
        <tr><td><strong>Branche</strong></td><td><?php echo esc_html( (string) ( $quiz['branche'] ?? '–' ) ); ?></td></tr>
        <tr><td><strong>Website</strong></td><td><?php echo esc_html( (string) ( $quiz['website'] ?? '–' ) ); ?></td></tr>
        <tr><td><strong>Ziel</strong></td><td><?php echo esc_html( (string) ( $quiz['ziel'] ?? '–' ) ); ?></td></tr>
    </table>

    <h3 style="margin:18px 0 6px;">Preis</h3>
    <p style="font-size:14px;">
        <?php echo esc_html( $fmt( $pricing['preis_min'] ?? 0 ) ); ?>
        bis
        <?php echo esc_html( $fmt( $pricing['preis_max'] ?? 0 ) ); ?>
        <?php if ( ! empty( $pricing['express_aufschlag'] ) ) : ?>
            (Express-Aufschlag: <?php echo esc_html( $fmt( $pricing['express_aufschlag'] ) ); ?>)
        <?php endif; ?>
    </p>

    <h3 style="margin:18px 0 6px;">PDF</h3>
    <p><a href="<?php echo esc_url( $pdf['url'] ); ?>"><?php echo esc_html( $pdf['filename'] ); ?></a></p>
</body></html>
