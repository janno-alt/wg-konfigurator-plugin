<?php
/**
 * PDF-Template: 7-seitiges Konzept im Lime-on-Dark Design.
 *
 * Wichtig für dompdf v3:
 *  - KEIN min-height auf .page in Kombination mit page-break-after (führt zu
 *    Leerseiten dazwischen). Stattdessen: jede .page einfach beschreibt einen
 *    Seiten-Inhalt, und nur page-break-after:always trennt sie.
 *  - Body-Background hat in dompdf Macken — wir colorieren die "Page" selber.
 *  - @page { margin: 0 } + .page { padding } = volle Bleed-Fläche pro Seite.
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
$today    = wp_date( 'd.m.Y' );

$fmt_eur = static function ( $n ): string {
    return number_format( (int) $n, 0, ',', '.' ) . ' €';
};

$paket_label    = \WG\Konfigurator\Services\PriceCalculator::paket_label(
    (string) ( $quiz['output_paket'] ?? 'einzel' )
);
$features_label = \WG\Konfigurator\Services\PriceCalculator::feature_labels(
    (array) ( $quiz['features'] ?? [] )
);

$length_label = [
    'short'      => '15–30 Sek. (Reel / Short)',
    'medium'     => '60–90 Sek. (Spot)',
    'long'       => '2–3 Min. (Imagefilm)',
    'extra_long' => '4–5 Min. (Erklärfilm)',
][ $quiz['video_laenge'] ?? '' ] ?? '–';
?><!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Videokonzept – <?php echo esc_html( $lead['vorname'] ?? 'Kunde' ); ?></title>
<style>
    @page { margin: 0; size: A4 portrait; }
    body, html { margin: 0; padding: 0; }

    .page {
        page-break-after: always;
        padding: 28mm 22mm 22mm;
        background-color: <?php echo esc_attr( $bg ); ?>;
        color: #FBFBFB;
        font-family: 'DejaVu Sans', sans-serif;
        font-size: 11pt;
        line-height: 1.55;
        position: relative;
        height: 247mm;     /* A4 = 297mm; minus 50mm padding = 247mm Content-Höhe */
        overflow: hidden;
    }
    .page:last-child { page-break-after: auto; }

    .accent { color: <?php echo esc_attr( $brand ); ?>; }
    .accent-bar {
        display: block;
        width: 60px;
        height: 4px;
        background-color: <?php echo esc_attr( $brand ); ?>;
        margin-bottom: 14pt;
    }

    h1 { font-size: 30pt; line-height: 1.1; margin: 0 0 12pt; font-weight: 700; }
    h2 { font-size: 20pt; line-height: 1.2; margin: 0 0 12pt; font-weight: 700; color: <?php echo esc_attr( $brand ); ?>; }
    h3 { font-size: 13pt; margin: 20pt 0 6pt; font-weight: 700; }
    p  { margin: 0 0 8pt; }
    ul { margin: 0 0 12pt 18pt; padding: 0; }
    li { margin-bottom: 5pt; }

    .footer {
        position: absolute;
        bottom: 12mm;
        left: 22mm;
        right: 22mm;
        font-size: 8pt;
        color: #888;
        border-top: 1px solid #333;
        padding-top: 6pt;
    }
    .footer .right { float: right; }

    .pricebox {
        background-color: #1f1f1f;
        border: 2px solid <?php echo esc_attr( $brand ); ?>;
        padding: 16pt 20pt;
        margin: 20pt 0;
    }
    .pricebox .label { font-size: 9pt; color: #999; text-transform: uppercase; letter-spacing: 1pt; }
    .pricebox .value { font-size: 24pt; font-weight: 700; color: <?php echo esc_attr( $brand ); ?>; margin-top: 4pt; }

    table.kv { width: 100%; border-collapse: collapse; margin-bottom: 12pt; }
    table.kv td { padding: 6pt 0; border-bottom: 1px solid #2b2b2b; vertical-align: top; }
    table.kv td.k { color: #888; width: 38%; font-size: 9pt; text-transform: uppercase; letter-spacing: 0.5pt; }
    table.kv td.v { color: #FBFBFB; }

    .note { font-size: 9pt; color: #888; }

    /* Cover */
    .cover-header {
        background-color: #1c1c1c;
        height: 60mm;
        margin: -28mm -22mm 30mm;
        padding: 28mm 22mm 0;
        border-bottom: 4px solid <?php echo esc_attr( $brand ); ?>;
    }
    .cover-meta { margin-top: 50mm; font-size: 10pt; color: #BBB; }
</style>
</head>
<body>

<!-- ============== SEITE 1: Cover ============== -->
<div class="page">
    <div class="cover-header"></div>
    <span class="accent-bar"></span>
    <h1>Dein individuelles<br><span class="accent">Videokonzept</span></h1>
    <p style="font-size:14pt; color:#CCC; margin-top:14pt;">
        Erstellt für <strong><?php echo esc_html( $lead['vorname'] ?? '–' ); ?></strong>
        am <?php echo esc_html( $today ); ?>.
    </p>
    <div class="cover-meta">
        WG-Digital · Videomarketing aus Mitteldeutschland<br>
        <?php echo esc_html( $ctx['settings']['sender_email'] ?? '' ); ?>
    </div>
    <div class="footer">
        Konzept <?php echo esc_html( $today ); ?>
        <span class="right">Seite 1</span>
    </div>
</div>

<!-- ============== SEITE 2: Wirkungs-Hypothese + Preis ============== -->
<div class="page">
    <span class="accent-bar"></span>
    <h2>Worauf wir bei dir hinarbeiten</h2>
    <p style="font-size:13pt; line-height:1.55;">
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
    <table class="kv">
        <tr><td class="k">Video-Typ</td>     <td class="v"><?php echo esc_html( ucfirst( (string) ( $pricing['breakdown']['video_typ'] ?? '–' ) ) ); ?></td></tr>
        <tr><td class="k">Output-Paket</td>  <td class="v"><?php echo esc_html( $paket_label ); ?></td></tr>
        <tr><td class="k">Video-Länge</td>   <td class="v"><?php echo esc_html( $length_label ); ?></td></tr>
        <tr><td class="k">Features</td>      <td class="v"><?php echo esc_html( $features_label ? implode( ', ', $features_label ) : 'Standard-Setup' ); ?></td></tr>
        <tr><td class="k">Zeitrahmen</td>    <td class="v"><?php echo esc_html( (string) ( $quiz['zeitrahmen'] ?? '–' ) ); ?></td></tr>
        <tr><td class="k">Branche</td>       <td class="v"><?php echo esc_html( (string) ( $quiz['branche'] ?? '–' ) ); ?></td></tr>
        <tr><td class="k">Website</td>       <td class="v"><?php echo esc_html( (string) ( $quiz['website'] ?? '–' ) ); ?></td></tr>
    </table>

    <div class="footer">
        Konzept <?php echo esc_html( $today ); ?>
        <span class="right">Seite 2</span>
    </div>
</div>

<!-- ============== SEITE 3: Story-Skizze ============== -->
<div class="page">
    <span class="accent-bar"></span>
    <h2>Story-Skizze</h2>
    <p style="line-height:1.7;">
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
        Konzept <?php echo esc_html( $today ); ?>
        <span class="right">Seite 3</span>
    </div>
</div>

<!-- ============== SEITE 4: Vorbereitungs-Checkliste ============== -->
<div class="page">
    <span class="accent-bar"></span>
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
        Konzept <?php echo esc_html( $today ); ?>
        <span class="right">Seite 4</span>
    </div>
</div>

<!-- ============== SEITE 5: Ablauf ============== -->
<div class="page">
    <span class="accent-bar"></span>
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
        Konzept <?php echo esc_html( $today ); ?>
        <span class="right">Seite 5</span>
    </div>
</div>

<!-- ============== SEITE 6: Warum WG ============== -->
<div class="page">
    <span class="accent-bar"></span>
    <h2>Warum WG-Digital</h2>

    <h3>Drehtag-Konzept statt Studio-Schaulauf</h3>
    <p>Wir kommen zu dir – kein steriles Studio, keine Statisten. Deine Räume, dein Team, dein Geschäft. So entstehen Videos, die wirklich nach dir aussehen.</p>

    <h3>Konzept aus einer Hand</h3>
    <p>Idee, Drehbuch, Dreh, Schnitt – alles im selben Team. Keine Übergaben, keine Reibungsverluste, eine Ansprechperson.</p>

    <h3>Wirkungs-Ziel statt Schaulauf</h3>
    <p>Jedes Video hat ein konkretes Ziel: Anfragen, Bewerbungen, Markenaufbau oder Produktverkäufe – und wird darauf hin gebaut.</p>

    <h3>Eigenes Profi-Equipment</h3>
    <p>Profi-Kamera, Slider, Licht, Funkmikrofone – wir bringen alles mit und richten direkt vor Ort ein.</p>

    <div class="footer">
        Konzept <?php echo esc_html( $today ); ?>
        <span class="right">Seite 6</span>
    </div>
</div>

<!-- ============== SEITE 7: Nächste Schritte / CTA ============== -->
<div class="page">
    <span class="accent-bar"></span>
    <h2>Lass uns reden</h2>

    <p style="font-size:12pt; line-height:1.6;">
        Buche dir einen 30-Minuten-Slot. Du bekommst eine ehrliche Einschätzung,
        auch wenn ein anderes Format für dich besser passt.
    </p>

    <div class="pricebox" style="margin-top:24pt;">
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
        Konzept <?php echo esc_html( $today ); ?>
        <span class="right">Seite 7</span>
    </div>
</div>

</body>
</html>
