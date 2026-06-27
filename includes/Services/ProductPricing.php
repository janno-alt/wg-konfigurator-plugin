<?php
declare( strict_types=1 );

namespace WG\Konfigurator\Services;

/**
 * Preis-Logik für die fokussierten Produkt-Konfiguratoren (recruiting, social).
 * MUSS synchron mit assets/quiz-app/src/productConfig.js gehalten werden.
 *
 * Liefert die Grund-Struktur (preis_min/max, items, score, drehtage, breakdown)
 * plus wiederkehrende Felder (monatlich_*, paket_label, recurring_items[], product).
 *
 * Preise real (Stand 2026-06-27, von Janno bestätigt).
 */
final class ProductPricing {

    /* ---- Recruiting ---- */
    private const REC = [
        'video_base'     => 2000,   // 1 Stelle, inkl. Konzept
        'stelle_add'     => 750,    // je weitere Stelle
        'landingpage'    => 900,    // Bewerber-Landingpage (einmalig)
        'kampagne_monat' => 250,    // Social-Recruiting-Betreuung mtl. (exkl. Werbebudget)
        'express_mult'   => 0.20,
    ];

    /* ---- Social-Pakete ---- */
    private const SOCIAL = [
        'statisch' => [ 'price' => 300, 'label' => 'Statisch' ],
        'reels4_q' => [ 'price' => 500, 'label' => 'Reels Basis' ],
        'reels4_m' => [ 'price' => 700, 'label' => 'Reels Plus' ],
        'reels8'   => [ 'price' => 990, 'label' => 'Reels Pro' ],
    ];

    private const SOCIAL_INCL = [
        'statisch' => [ '6 statische Beiträge pro Monat', 'Redaktionsplan & Texte', 'Community-Management', 'Monatlicher Report' ],
        'reels4_q' => [ '4 Reels pro Monat', '1 Drehtag pro Quartal', 'Redaktionsplan, Schnitt & Veröffentlichung', 'Community-Management', 'Monatlicher Report' ],
        'reels4_m' => [ '4 Reels pro Monat', 'Drehtag jeden Monat (frischer Content)', 'Redaktionsplan, Schnitt & Veröffentlichung', 'Community-Management', 'Monatlicher Report' ],
        'reels8'   => [ '8 Reels pro Monat', '1 Drehtag alle 2 Monate', 'Redaktionsplan, Schnitt & Veröffentlichung', 'Community-Management', 'Monatlicher Report' ],
    ];

    /**
     * @param array<string,mixed> $quiz
     * @return array<string,mixed>
     */
    public function calculate( string $product, array $quiz ): array {
        if ( $product === 'social' ) {
            return $this->calc_social( $quiz );
        }
        return $this->calc_recruiting( $quiz );
    }

    private function calc_recruiting( array $quiz ): array {
        $P = self::REC;
        $items = [];
        $items[] = [ 'key' => 'base', 'label' => 'Recruiting-Video inkl. Konzept', 'min' => $P['video_base'], 'max' => $P['video_base'] ];

        $stellen = (string) ( $quiz['stellen'] ?? '' );
        if ( $stellen === '2-3' ) {
            $items[] = [ 'key' => 'stellen', 'label' => '+ weitere Stelle (1 bis 2)', 'min' => $P['stelle_add'], 'max' => $P['stelle_add'] * 2 ];
        } elseif ( $stellen === 'laufend' ) {
            $items[] = [ 'key' => 'stellen', 'label' => '+ weitere Stellen (je +' . $P['stelle_add'] . ' €)', 'min' => $P['stelle_add'], 'max' => $P['stelle_add'] * 2 ];
        }
        if ( ( $quiz['rec_lp'] ?? '' ) === 'ja' ) {
            $items[] = [ 'key' => 'lp', 'label' => '+ Bewerber-Landingpage', 'min' => $P['landingpage'], 'max' => $P['landingpage'] ];
        }

        $one_min = array_sum( array_column( $items, 'min' ) );
        $one_max = array_sum( array_column( $items, 'max' ) );

        $express = 0;
        if ( ( $quiz['zeitrahmen'] ?? '' ) === 'express' ) {
            $em = (int) round( $one_min * $P['express_mult'] );
            $ex = (int) round( $one_max * $P['express_mult'] );
            $items[] = [ 'key' => 'express', 'label' => '+ Express-Aufschlag (+20 %)', 'min' => $em, 'max' => $ex ];
            $one_min += $em; $one_max += $ex; $express = $ex;
        }

        $monat = 0; $monat_note = ''; $recurring_items = [];
        if ( ( $quiz['rec_kampagne'] ?? '' ) === 'ja' ) {
            $monat = $P['kampagne_monat'];
            $monat_note = 'monatliche Kampagnen-Betreuung, zzgl. Werbebudget (bestimmt ihr selbst)';
            $recurring_items = [ 'Anzeigen-Setup & Targeting', 'Laufende Optimierung der Kampagne', 'Reporting der Bewerbungen' ];
        }

        $score = 55
            + ( ( $quiz['rec_kampagne'] ?? '' ) === 'ja' ? 20 : 0 )
            + ( ( $quiz['rec_lp'] ?? '' ) === 'ja' ? 10 : 0 )
            + ( $stellen === 'laufend' ? 10 : 0 );

        return [
            'product'           => 'recruiting',
            'preis_min'         => (int) $one_min,
            'preis_max'         => (int) $one_max,
            'express_aufschlag' => (int) $express,
            'monatlich_min'     => (int) $monat,
            'monatlich_max'     => (int) $monat,
            'monatlich_from'    => false,
            'monatlich_note'    => $monat_note,
            'paket_label'       => 'Social-Recruiting-Kampagne',
            'recurring_items'   => $recurring_items,
            'score'             => min( 100, $score ),
            'drehtage'          => 1,
            'items'             => $items,
            'breakdown'         => [ 'product' => 'recruiting', 'video_typ' => 'recruiting' ],
        ];
    }

    private function calc_social( array $quiz ): array {
        $key = (string) ( $quiz['paket'] ?? '' );
        if ( ! isset( self::SOCIAL[ $key ] ) ) {
            $key = 'statisch';
        }
        $pkg = self::SOCIAL[ $key ];

        $score = 50 + [ 'statisch' => 5, 'reels4_q' => 15, 'reels4_m' => 25, 'reels8' => 35 ][ $key ];

        return [
            'product'           => 'social',
            'preis_min'         => 0,
            'preis_max'         => 0,
            'express_aufschlag' => 0,
            'monatlich_min'     => (int) $pkg['price'],
            'monatlich_max'     => (int) $pkg['price'],
            'monatlich_from'    => false,
            'monatlich_note'    => 'monatlich kündbar · 10 % Rabatt bei jährlicher Vorauszahlung',
            'paket_label'       => $pkg['label'] . '-Paket',
            'recurring_items'   => self::SOCIAL_INCL[ $key ],
            'score'             => min( 100, $score ),
            'drehtage'          => 0,
            'items'             => [],
            'breakdown'         => [ 'product' => 'social', 'paket' => $key ],
        ];
    }
}
