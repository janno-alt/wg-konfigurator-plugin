<?php
/**
 * PDF-Template: 7-seitiges Konzept im Lime-on-Dark Design.
 *
 * Erwartet $ctx (siehe PdfGenerator). dompdf v3 versteht eingeschränktes CSS —
 * kein flexbox, kein grid, kein @container. Float + table + position für Layout.
 *
 * @var array<string,mixed> $ctx
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$brand    = (string) ( $ctx['settings']['pdf_brand_color'] ?? '#C2F21C' );
$bg       = (string) ( $ctx['settings']['pdf_bg_color']    ?? '#141414' );
$lead     = (array)  ( $ctx['lead']    ?? [] );
$quiz     = (array)  ( $ctx['quiz']    ?? [] );
$pricing  = (array)  ( $ctx['pricing'] ?? [] );
$concept  = (array)  ( $ctx['concept'] ?? [] );
$cover    = (string) ( $ctx['placeholder_cover_path'] ?? '' );
$logo_url = (string) ( $ctx['settings']['pdf_logo_url']    ?? '' );
$today    = wp_date( 'd.m.Y' );

$fmt_eur = static function ( $n ): string {
    return number_format( (int) $n, 0, ',', '.' ) . ' €';
};
?><!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Videokonzept – <?php echo esc_html( $lead['vorname'] ?? 'Kunde' ); ?></title>
<style>
    @page { margin: 0; }
    body {
        font-family: 'DejaVu Sans', sans-serif;
        color: #FBFBFB;
        background: <?php echo esc_attr( $bg ); ?>;
        margin: 0;
        font-size: 11pt;
        line-height: 1.5;
    }
    .page {
        page-break-after: always;
        padding: 30mm 22mm;
        position: relative;
        min-height: 297mm;
        box-sizing: border-box;
    }
    .page:last-child { page-break-after: auto; }
    .accent { color: <?php echo esc_attr( $brand ); ?>; }
    .accent-bar {
        display: inline-block;
        width: 60px; height: 4px;
        background: <?php echo esc_attr( $brand ); ?>;
        margin-bottom: 12pt;
    }
    h1 { font-size: 28pt; line-height: 1.1; margin: 0 0 12pt; font-weight: 700; }
    h2 { font-size: 18pt; line-height: 1.2; margin: 0 0 10pt; font-weight: 700; color: <?php echo esc_attr( $brand ); ?>; }
    h3 { font-size: 13pt; margin: 18pt 0 6pt; font-weight: 700; }
    p  { margin: 0 0 8pt; }
    ul { margin: 0 0 12pt 18pt; padding: 0; }
    li { margin-bottom: 4pt; }

    /* Footer auf jeder Seite */
    .footer {
        position: absolute;
        bottom: 14mm;
        left: 22mm; right: 22mm;
        font-size: 8pt;
        color: #888;
        border-top: 1px solid #333;
        padding-top: 6pt;
    }
    .footer .right { float: right; }

    /* Cover */
    .cover {
        background: <?php echo esc_attr( $bg ); ?>;
        text-align: left;
        padding-top: 80mm;
    }
    .cover .meta {
        margin-top: 60mm;
        font-size: 10pt;
        color: #BBB;
    }
    .cover-image-placeholder {
        position: absolute;
        top: 0; left: 0;
        width: 100%; height: 80mm;
        background: #222;
        border-bottom: 4px solid <?php echo esc_attr( $brand ); ?>;
    }
    .cover-image-placeholder .label {
        color: #555;
        font-size: 10pt;
        text-align: center;
        padding-top: 35mm;
    }

    /* Preis-Box */
    .pricebox {
        background: #1f1f1f;
        border: 2px solid <?php echo esc_attr( $brand ); ?>;
        padding: 14pt 18pt;
        margin: 18pt 0;
    }
    .pricebox .label { font-size: 9pt; color: #999; text-transform: uppercase; letter-spacing: 1pt; }
    .pricebox .value { font-size: 22pt; font-weight: 700; color: <?php echo esc_attr( $brand ); ?>; }

    /* Tabellen */
    table.kv { width: 100%; border-collapse: collapse; margin-bottom: 12pt; }
    table.kv td { padding: 5pt 0; border-bottom: 1px solid #2b2b2b; vertical-align: top; }
    table.kv td.k { color: #888; width: 38%; font-size: 9pt; text-transform: uppercase; letter-spacing: 0.5pt; }
    table.kv td.v { color: #FBFBFB; }

    .note { font-size: 9pt; color: #888; }
</style>
</head>
<body>

<!-- ============== SEITE 1: Cover ============== -->
<div class="page cover">
    <div class="cover-image-placeholder">
        <div class="label">[ Cover-Bild folgt — Platzhalter ]</div>
    </div>

    <div class="accent-bar"></div>
    <h1>Dein individuelles<br><span class="accent">Videokonzept</span></h1>
    <p style="font-size:14pt; color:#CCC;">
        Erstellt für <strong><?php echo esc_html( $lead['vorname'] ?? '–' ); ?></strong>
        am <?php echo esc_html( $today ); ?>.
    </p>

    <div class="meta">
        WG-Digital · Videomarketing aus Mitteldeutschland<br>
        <?php echo esc_html( $ctx['settings']['sender_email'] ?? '' ); ?>
    </div>
</div>

<!-- ============== SEITE 2: Wirkungs-Hypothese + Preis ============== -->
<div class="page">
    <div class="accent-bar"></div>
    <h2>Worauf wir bei dir hinarbeiten</h2>
    <p style="font-size:13pt; line-height:1.5;">
        <?php echo esc_html( $concept['wirkungs_hypothese'] ?? '–' ); ?>
    </p>

    <div class="pricebox">
        <div class="label">Investitionsrahmen (geschätzt)</div>
        <div class="value">
            <?php echo esc_html( $fmt_eur( $pricing['preis_min'] ?? 0 ) ); ?>
            &nbsp;–&nbsp;
            <?php echo esc_html( $fmt_eur( $pricing['preis_max'] ?? 0 ) ); ?>
        </div>
        <p class="note" style="margin-top:8pt;">
            Inkl. Konzept, Vorbereitung, Drehtag mit Equipment, Schnitt,
            Branding und plattformgerechtem Export. Reisekosten ab 100 km separat.
            <?php if ( ! empty( $pricing['express_aufschlag'] ) ) : ?>
                <br>Enthält Express-Aufschlag von ca. <?php echo esc_html( $fmt_eur( $pricing['express_aufschlag'] ) ); ?>.
            <?php endif; ?>
        </p>
    </div>

    <h3>Eckdaten deiner Anfrage</h3>
    <?php
        $paket_label = \WG\Konfigurator\Services\PriceCalculator::paket_label(
            (string) ( $quiz['output_paket'] ?? 'einzel' )
        );
        $features_label = \WG\Konfigurator\Services\PriceCalculator::feature_labels(
            (array) ( $quiz['features'] ?? [] )
        );
    ?>
    <table class="kv">
        <tr><td class="k">Video-Typ</td>     <td class="v"><?php echo esc_html( ucfirst( (string) ( $pricing['breakdown']['video_typ'] ?? '–' ) ) ); ?></td></tr>
        <tr><td class="k">Output-Paket</td>  <td class="v"><?php echo esc_html( $paket_label ); ?></td></tr>
        <tr><td class="k">Features</td>      <td class="v"><?php echo esc_html( $features_label ? implode( ', ', $features_label ) : 'Standard-Setup' ); ?></td></tr>
        <tr><td class="k">Zeitrahmen</td>    <td class="v"><?php echo esc_html( (string) ( $quiz['zeitrahmen'] ?? '–' ) ); ?></td></tr>
        <tr><td class="k">Branche</td>       <td class="v"><?php echo esc_html( (string) ( $quiz['branche'] ?? '–' ) ); ?></td></tr>
        <tr><td class="k">Website</td>       <td class="v"><?php echo esc_html( (string) ( $quiz['website'] ?? '–' ) ); ?></td></tr>
    </table>

    <div class="footer">
        WG-Digital · Videokonzept <?php echo esc_html( $today ); ?>
        <span class="right">Seite 2</span>
    </div>
</div>

<!-- ============== SEITE 3: Story-Skizze ============== -->
<div class="page">
    <div class="accent-bar"></div>
    <h2>Story-Skizze</h2>
    <p style="font-size:11pt; line-height:1.7;">
        <?php echo nl2br( esc_html( (string) ( $concept['story_skizze'] ?? '–' ) ) ); ?>
    </p>

    <h3>Empfohlene Protagonist:innen</h3>
    <ul>
        <?php foreach ( (array) ( $concept['empfohlene_protagonisten'] ?? [] ) as $p ) : ?>
            <li><?php echo esc_html( (string) $p ); ?></li>
        <?php endforeach; ?>
    </ul>

    <h3>Empfohlene Drehorte bei dir vor Ort</h3>
    <ul>
        <?php foreach ( (array) ( $concept['empfohlene_locations'] ?? [] ) as $l ) : ?>
            <li><?php echo esc_html( (string) $l ); ?></li>
        <?php endforeach; ?>
    </ul>

    <div class="footer">
        WG-Digital · Videokonzept <?php echo esc_html( $today ); ?>
        <span class="right">Seite 3</span>
    </div>
</div>

<!-- ============== SEITE 4: Vorbereitungs-Checkliste ============== -->
<div class="page">
    <div class="accent-bar"></div>
    <h2>Vorbereitungs-Checkliste</h2>
    <p>Damit der Drehtag bei dir reibungslos läuft, hilft uns folgende Vorbereitung:</p>
    <ul>
        <?php foreach ( (array) ( $concept['vorbereitungs_checkliste'] ?? [] ) as $item ) : ?>
            <li><?php echo esc_html( (string) $item ); ?></li>
        <?php endforeach; ?>
    </ul>

    <h3 style="margin-top:24pt;">So geht's weiter</h3>
    <p><?php echo esc_html( (string) ( $concept['naechste_schritte'] ?? '–' ) ); ?></p>

    <div class="footer">
        WG-Digital · Videokonzept <?php echo esc_html( $today ); ?>
        <span class="right">Seite 4</span>
    </div>
</div>

<!-- ============== SEITE 5: Ablauf ============== -->
<div class="page">
    <div class="accent-bar"></div>
    <h2>Ablauf in 4 Schritten</h2>

    <table class="kv">
        <tr><td class="k">01 · Briefing</td>   <td class="v">Kostenloses 30-Minuten-Gespräch, Wirkungs-Ziel definieren, Eckdaten klären.</td></tr>
        <tr><td class="k">02 · Konzept</td>    <td class="v">Drehbuch, Shotliste, Locations und Personen-Plan – bei dir freigegeben.</td></tr>
        <tr><td class="k">03 · Drehtag</td>    <td class="v">Wir kommen mit eigenem Profi-Equipment zu dir. Regie auf Augenhöhe.</td></tr>
        <tr><td class="k">04 · Schnitt</td>    <td class="v">Branding, Untertitel, Versionen pro Kanal. Eine Feedback-Schleife inklusive.</td></tr>
    </table>

    <h3>Was im Preis enthalten ist</h3>
    <ul>
        <li>Konzept-Workshop + Drehbuch</li>
        <li>Drehtag(e) mit eigenem Profi-Equipment (Kamera, Licht, Funkmikrofone)</li>
        <li>Schnitt inkl. Branding, Untertiteln, plattformgerechtem Export</li>
        <li>1 Feedback-Schleife</li>
        <li>Rohmaterial auf Anfrage</li>
    </ul>

    <div class="footer">
        WG-Digital · Videokonzept <?php echo esc_html( $today ); ?>
        <span class="right">Seite 5</span>
    </div>
</div>

<!-- ============== SEITE 6: Warum WG ============== -->
<div class="page">
    <div class="accent-bar"></div>
    <h2>Warum WG-Digital</h2>

    <h3>Drehtag-Konzept statt Studio-Schaulauf</h3>
    <p>Wir kommen zu dir – kein steriles Studio, keine Statisten. Deine Räume,
    dein Team, dein Geschäft. So entstehen Videos, die wirklich nach dir aussehen.</p>

    <h3>Konzept aus einer Hand</h3>
    <p>Idee, Drehbuch, Dreh, Schnitt – alles im selben Team.
    Keine Übergaben, keine Reibungsverluste, eine Ansprechperson.</p>

    <h3>Wirkungs-Ziel statt Schaulauf</h3>
    <p>Jedes Video hat ein konkretes Ziel: Anfragen, Bewerbungen,
    Markenaufbau oder Produktverkäufe – und wird darauf hin gebaut.</p>

    <h3>Eigenes Profi-Equipment</h3>
    <p>Profi-Kamera, Slider, Licht, Funkmikrofone – wir bringen alles mit
    und richten direkt vor Ort ein.</p>

    <div class="footer">
        WG-Digital · Videokonzept <?php echo esc_html( $today ); ?>
        <span class="right">Seite 6</span>
    </div>
</div>

<!-- ============== SEITE 7: Nächste Schritte / CTA ============== -->
<div class="page">
    <div class="accent-bar"></div>
    <h2>Lass uns reden</h2>

    <p style="font-size:12pt; line-height:1.6;">
        Buche dir einen 30-Minuten-Slot. Du bekommst eine ehrliche Einschätzung,
        auch wenn ein anderes Format für dich besser passt.
    </p>

    <div class="pricebox" style="border-color:<?php echo esc_attr( $brand ); ?>; margin-top:24pt;">
        <div class="label">Terminbuchung</div>
        <div class="value" style="font-size:14pt;">
            cal.meetergo.com/janno-fleischer
        </div>
        <p class="note" style="margin-top:8pt;">
            Oder antworte einfach auf die E-Mail, mit der du dieses PDF bekommen hast.
        </p>
    </div>

    <h3>Kontakt</h3>
    <table class="kv">
        <tr><td class="k">WG-Digital</td><td class="v">Videomarketing aus Mitteldeutschland</td></tr>
        <tr><td class="k">E-Mail</td><td class="v"><?php echo esc_html( $ctx['settings']['sender_email'] ?? '' ); ?></td></tr>
        <tr><td class="k">Web</td><td class="v">wg-digitalmarketing.de</td></tr>
    </table>

    <div class="footer">
        WG-Digital · Videokonzept <?php echo esc_html( $today ); ?>
        <span class="right">Seite 7</span>
    </div>
</div>

</body>
</html>
