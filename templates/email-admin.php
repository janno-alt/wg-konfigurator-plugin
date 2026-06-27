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

    <?php $product = (string) ( $quiz['product'] ?? 'video' ); ?>
    <h3 style="margin:18px 0 6px;">Anfrage (<?php echo esc_html( $product ); ?>)</h3>
    <table cellpadding="4" cellspacing="0" style="font-size:13px;border-collapse:collapse;">
        <?php if ( $product === 'video' ) :
            $type_lbl   = \WG\Konfigurator\Services\PriceCalculator::type_label( (string) ( $quiz['video_typ'] ?? '' ) );
            $paket_lbl  = $quiz['output_paket'] ? \WG\Konfigurator\Services\PriceCalculator::paket_label( (string) $quiz['output_paket'] ) : '–';
            $laenge_lbl = $quiz['video_laenge'] ? \WG\Konfigurator\Services\PriceCalculator::length_label( (string) $quiz['video_laenge'] ) : '–';
            $feat_lbl   = \WG\Konfigurator\Services\PriceCalculator::feature_labels( (array) ( $quiz['features'] ?? [] ) );
        ?>
            <tr><td><strong>Video-Typ</strong></td><td><?php echo esc_html( $type_lbl ); ?></td></tr>
            <tr><td><strong>Output-Paket</strong></td><td><?php echo esc_html( $paket_lbl ); ?></td></tr>
            <tr><td><strong>Video-Länge</strong></td><td><?php echo esc_html( $laenge_lbl ); ?></td></tr>
            <tr><td><strong>Features</strong></td><td><?php echo esc_html( $feat_lbl ? implode( ', ', $feat_lbl ) : 'Standard-Setup' ); ?></td></tr>
            <tr><td><strong>Drehtage (intern)</strong></td><td><?php echo (int) ( $pricing['drehtage'] ?? 1 ); ?></td></tr>
        <?php elseif ( $product === 'recruiting' ) : ?>
            <tr><td><strong>Berufsfeld</strong></td><td><?php echo esc_html( (string) ( $quiz['branche'] ?? '–' ) ); ?></td></tr>
            <tr><td><strong>Offene Stellen</strong></td><td><?php echo esc_html( (string) ( $quiz['stellen'] ?? '–' ) ); ?></td></tr>
            <tr><td><strong>Video-Umfang</strong></td><td><?php echo esc_html( ( $quiz['rec_video'] ?? '' ) === 'paket' ? 'Video + Cutdowns' : 'Ein Video' ); ?></td></tr>
            <tr><td><strong>Kampagne</strong></td><td><?php echo ( $quiz['rec_kampagne'] ?? '' ) === 'ja' ? 'Ja' : 'Nein'; ?></td></tr>
            <tr><td><strong>Bewerber-LP</strong></td><td><?php echo ( $quiz['rec_lp'] ?? '' ) === 'ja' ? 'Ja' : 'Nein'; ?></td></tr>
        <?php else : ?>
            <tr><td><strong>Empfohlenes Paket</strong></td><td><?php echo esc_html( (string) ( $pricing['paket_label'] ?? '–' ) ); ?></td></tr>
            <tr><td><strong>Plattformen</strong></td><td><?php echo esc_html( (string) ( $quiz['plattformen'] ?? '–' ) ); ?></td></tr>
            <tr><td><strong>Content/Monat</strong></td><td><?php echo esc_html( (string) ( $quiz['content'] ?? '–' ) ); ?></td></tr>
            <tr><td><strong>Ads</strong></td><td><?php echo ( $quiz['ads'] ?? '' ) === 'ja' ? 'Ja' : 'Organisch'; ?></td></tr>
        <?php endif; ?>
        <tr><td><strong>Zeitrahmen</strong></td><td><?php echo esc_html( (string) ( $quiz['zeitrahmen'] ?? '–' ) ); ?></td></tr>
        <tr><td><strong>Branche</strong></td><td><?php echo esc_html( (string) ( $quiz['branche'] ?? '–' ) ); ?></td></tr>
        <tr><td><strong>Website</strong></td><td><?php echo esc_html( (string) ( $quiz['website'] ?? '–' ) ); ?></td></tr>
        <tr><td><strong>Ziel</strong></td><td><?php echo esc_html( (string) ( $quiz['ziel'] ?? '–' ) ); ?></td></tr>
    </table>

    <h3 style="margin:18px 0 6px;">Preis-Aufschlüsselung</h3>
    <table cellpadding="3" cellspacing="0" style="font-size:13px;border-collapse:collapse;">
        <?php foreach ( (array) ( $pricing['items'] ?? [] ) as $it ) :
            $val = ( (int) $it['min'] === (int) $it['max'] )
                ? number_format( (int) $it['min'], 0, ',', '.' ) . ' €'
                : number_format( (int) $it['min'], 0, ',', '.' ) . ' – ' . number_format( (int) $it['max'], 0, ',', '.' ) . ' €';
        ?>
            <tr>
                <td style="border-bottom:1px solid #eee;"><?php echo esc_html( (string) $it['label'] ); ?></td>
                <td style="border-bottom:1px solid #eee;text-align:right;font-variant-numeric:tabular-nums;"><?php echo esc_html( $val ); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h3 style="margin:18px 0 6px;">Preis</h3>
    <p style="font-size:14px;">
        <?php if ( (int) ( $pricing['preis_max'] ?? 0 ) > 0 ) : ?>
            <strong>Einmalig:</strong>
            <?php echo esc_html( $fmt( $pricing['preis_min'] ?? 0 ) ); ?> bis <?php echo esc_html( $fmt( $pricing['preis_max'] ?? 0 ) ); ?>
            <?php if ( ! empty( $pricing['express_aufschlag'] ) ) : ?>
                (Express-Aufschlag: <?php echo esc_html( $fmt( $pricing['express_aufschlag'] ) ); ?>)
            <?php endif; ?>
            <br>
        <?php endif; ?>
        <?php if ( (int) ( $pricing['monatlich_max'] ?? 0 ) > 0 ) : ?>
            <strong>Monatlich:</strong>
            <?php echo ! empty( $pricing['monatlich_from'] ) ? 'ab ' : ''; ?><?php echo esc_html( $fmt( $pricing['monatlich_min'] ?? 0 ) ); ?> / Monat
            <?php if ( ! empty( $pricing['paket_label'] ) ) : ?>(<?php echo esc_html( (string) $pricing['paket_label'] ); ?>)<?php endif; ?>
        <?php endif; ?>
    </p>

    <h3 style="margin:18px 0 6px;">PDF</h3>
    <p><a href="<?php echo esc_url( $pdf['url'] ); ?>"><?php echo esc_html( $pdf['filename'] ); ?></a></p>
</body></html>
