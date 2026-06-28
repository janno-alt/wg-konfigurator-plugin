<?php
declare( strict_types=1 );

namespace WG\Konfigurator\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use RuntimeException;
use WG\Konfigurator\Admin\Settings;

/**
 * Rendert ein Konzept-PDF mit dompdf v3.
 *
 * @phpstan-type GenerateContext array{
 *     lead: array<string,mixed>,
 *     quiz: array<string,mixed>,
 *     pricing: array<string,mixed>,
 *     concept: array<string,mixed>,
 *     generated_at: string,
 *     placeholder_cover_path: string
 * }
 */
final class PdfGenerator {

    /**
     * @param array<string,mixed> $ctx siehe phpstan-type GenerateContext
     * @return array{path:string,url:string,filename:string}
     */
    public function render( array $ctx ): array {
        $settings = Settings::get();

        $options = new Options();
        $options->set( 'isRemoteEnabled', true );
        $options->set( 'isHtml5ParserEnabled', true );
        $options->set( 'defaultFont', 'DejaVu Sans' );
        $options->set( 'chroot', WG_KONFIGURATOR_DIR );

        $dompdf = new Dompdf( $options );
        $dompdf->setPaper( 'A4', 'portrait' );

        $html = $this->load_template( $ctx, $settings );
        $dompdf->loadHtml( $html, 'UTF-8' );
        $dompdf->render();

        $this->add_footer( $dompdf );

        $output = $dompdf->output();
        if ( $output === '' ) {
            throw new RuntimeException( 'PDF-Output ist leer.' );
        }

        $filename = $this->build_filename( $ctx );
        $upload   = wp_upload_dir();
        $dir      = trailingslashit( $upload['basedir'] ) . 'wg-konfigurator';
        $url_base = trailingslashit( $upload['baseurl'] ) . 'wg-konfigurator';

        if ( ! is_dir( $dir ) ) {
            wp_mkdir_p( $dir );
        }

        $path = $dir . '/' . $filename;
        $bytes = file_put_contents( $path, $output );

        if ( $bytes === false ) {
            throw new RuntimeException( 'PDF konnte nicht gespeichert werden: ' . $path );
        }

        return [
            'path'     => $path,
            'url'      => $url_base . '/' . $filename,
            'filename' => $filename,
        ];
    }

    /**
     * Footer auf JEDER Seite via Canvas (deferred page_text/line) – verrutscht
     * nicht bei Inhalts-Überlauf und numeriert die Seiten automatisch korrekt.
     */
    private function add_footer( Dompdf $dompdf ): void {
        $canvas = $dompdf->getCanvas();
        $fm     = $dompdf->getFontMetrics();
        $font   = $fm->getFont( 'DejaVu Sans', 'normal' );
        if ( ! $font ) { return; }

        $w    = $canvas->get_width();
        $h    = $canvas->get_height();
        $mx   = 62.0;            // ~22mm Seitenrand
        $y    = $h - 36.0;       // ~12mm vom unteren Rand
        $grey = [ 0.55, 0.55, 0.55 ];
        $date = wp_date( 'd.m.Y' );

        $canvas->line( $mx, $y - 8.0, $w - $mx, $y - 8.0, [ 0.20, 0.20, 0.20 ], 0.5 );
        $canvas->page_text( $mx, $y, 'Konzept ' . $date, $font, 8.0, $grey );
        $canvas->page_text( $w - $mx - 78.0, $y, 'Seite {PAGE_NUM} / {PAGE_COUNT}', $font, 8.0, $grey );
    }

    private function build_filename( array $ctx ): string {
        $lead = (array) ( $ctx['lead'] ?? [] );
        $name = sanitize_title( (string) ( $lead['name'] ?: ( $lead['vorname'] ?? 'kunde' ) ) );
        $date = gmdate( 'Ymd-His' );
        $hash = substr( wp_hash( wp_json_encode( $ctx ) ), 0, 6 );
        return "konzept-{$name}-{$date}-{$hash}.pdf";
    }

    private function load_template( array $ctx, array $settings ): string {
        $template_path = WG_KONFIGURATOR_DIR . 'templates/pdf-konzept.php';
        if ( ! file_exists( $template_path ) ) {
            throw new RuntimeException( 'PDF-Template fehlt: ' . $template_path );
        }

        $ctx['settings'] = $settings;

        ob_start();
        // Extract dem Template als $ctx zur Verfügung stellen.
        ( static function ( $__tmpl, $__ctx ) {
            $ctx = $__ctx;
            include $__tmpl;
        } )( $template_path, $ctx );

        return (string) ob_get_clean();
    }
}
