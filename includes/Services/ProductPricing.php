<?php
declare( strict_types=1 );

namespace WG\Konfigurator\Services;

/**
 * Preis-Logik für die fokussierten Produkt-Konfiguratoren (recruiting, social).
 * MUSS synchron mit assets/quiz-app/src/productConfig.js gehalten werden.
 *
 * Liefert dieselbe Grund-Struktur wie PriceCalculator (preis_min/max, items,
 * score, drehtage, breakdown) plus wiederkehrende Felder:
 *   - monatlich_min / monatlich_max / monatlich_from / monatlich_note
 *   - paket_label
 *   - recurring_items[]  (Leistungs-Inklusiva, ohne Einzelpreis)
 *   - product
 *
 * PREISE: zentral hier. Platzhalter mit "TODO PREIS" sind gemeinsam mit
 * Janno scharf zu stellen.
 */
final class ProductPricing {

    /* ---- Recruiting (TODO-Stellen markiert) ---- */
    private const REC = [
        'video_base_min' => 2000,   // real (bestehende Recruiting-Video-Logik)
        'video_base_max' => 3000,   // real
        'konzept'        => 800,    // real
        'cutdowns_add'   => 600,    // real
        'landingpage'    => 900,    // TODO PREIS bestätigen
        'kampagne_monat' => 490,    // TODO PREIS bestätigen (exkl. Werbebudget)
        'express_mult'   => 0.20,   // real
    ];

    /* ---- Social-Pakete (real, von Seite 8142) ---- */
    private const SOCIAL = [
        'starter'     => [ 'price' => 300, 'from' => false, 'label' => 'Starter' ],
        'standard'    => [ 'price' => 500, 'from' => false, 'label' => 'Standard' ],
        'performance' => [ 'price' => 990, 'from' => true,  'label' => 'Performance' ],
    ];

    private const SOCIAL_INCL = [
        'starter'     => [ '1 Plattform', '8 Posts/Reels pro Monat', 'Community-Management (Mo-Fr)', 'Monatlicher Report' ],
        'standard'    => [ 'bis 3 Plattformen', '16 Posts/Reels pro Monat', 'Werbeanzeigen-Betreuung', 'Community-Management', 'Monatlicher Report' ],
        'performance' => [ 'alle 5 Plattformen', 'höchste Content-Frequenz', 'Meta + LinkedIn Ads (Setup & Optimierung)', 'erweiterte Service-Zeiten', 'monatlicher Strategie-Call' ],
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
        $items[] = [ 'key' => 'base',    'label' => 'Recruiting-Video (1 Drehtag)',                 'min' => $P['video_base_min'], 'max' => $P['video_base_max'] ];
        $items[] = [ 'key' => 'konzept', 'label' => '+ Konzept-Workshop (Drehbuch, Drehplan)',      'min' => $P['konzept'],        'max' => $P['konzept'] ];

        if ( ( $quiz['rec_video'] ?? '' ) === 'paket' ) {
            $items[] = [ 'key' => 'cutdowns', 'label' => '+ Kurz-Cutdowns für Anzeigen', 'min' => $P['cutdowns_add'], 'max' => $P['cutdowns_add'] ];
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

        $monat_min = 0; $monat_max = 0; $monat_note = ''; $recurring_items = [];
        if ( ( $quiz['rec_kampagne'] ?? '' ) === 'ja' ) {
            $monat_min = $P['kampagne_monat'];
            $monat_max = $P['kampagne_monat'];
            $monat_note = 'monatliche Kampagnen-Betreuung, zzgl. Werbebudget (bestimmt ihr selbst)';
            $recurring_items = [ 'Anzeigen-Setup & Targeting', 'Laufende Optimierung der Kampagne', 'Reporting der Bewerbungen' ];
        }

        $score = 55
            + ( ( $quiz['rec_kampagne'] ?? '' ) === 'ja' ? 20 : 0 )
            + ( ( $quiz['rec_lp'] ?? '' ) === 'ja' ? 10 : 0 )
            + ( ( $quiz['stellen'] ?? '' ) === 'laufend' ? 10 : 0 );

        return [
            'product'         => 'recruiting',
            'preis_min'       => (int) $one_min,
            'preis_max'       => (int) $one_max,
            'express_aufschlag' => (int) $express,
            'monatlich_min'   => (int) $monat_min,
            'monatlich_max'   => (int) $monat_max,
            'monatlich_from'  => true,
            'monatlich_note'  => $monat_note,
            'paket_label'     => 'Social-Recruiting-Kampagne',
            'recurring_items' => $recurring_items,
            'score'           => min( 100, $score ),
            'drehtage'        => ( $quiz['rec_video'] ?? '' ) === 'paket' ? 1 : 1,
            'items'           => $items,
            'breakdown'       => [ 'product' => 'recruiting', 'video_typ' => 'recruiting' ],
        ];
    }

    private function calc_social( array $quiz ): array {
        $tier = $this->social_tier( $quiz );
        $pkg  = self::SOCIAL[ $tier ];

        $score = 50 + ( $tier === 'performance' ? 30 : ( $tier === 'standard' ? 20 : 10 ) )
            + ( ( $quiz['ads'] ?? '' ) === 'ja' ? 10 : 0 );

        return [
            'product'         => 'social',
            'preis_min'       => 0,
            'preis_max'       => 0,
            'express_aufschlag' => 0,
            'monatlich_min'   => (int) $pkg['price'],
            'monatlich_max'   => (int) $pkg['price'],
            'monatlich_from'  => (bool) $pkg['from'],
            'monatlich_note'  => 'monatlich kündbar · 10 % Rabatt bei jährlicher Vorauszahlung',
            'paket_label'     => $pkg['label'] . '-Paket',
            'recurring_items' => self::SOCIAL_INCL[ $tier ],
            'score'           => min( 100, $score ),
            'drehtage'        => 0,
            'items'           => [],
            'breakdown'       => [ 'product' => 'social', 'tier' => $tier ],
        ];
    }

    private function social_tier( array $quiz ): string {
        $p = (string) ( $quiz['plattformen'] ?? '' );
        $c = (string) ( $quiz['content'] ?? '' );
        $a = (string) ( $quiz['ads'] ?? '' );

        $tier = 'starter';
        if ( $c === '16' || $p === '23' || $a === 'ja' ) {
            $tier = 'standard';
        }
        if ( $p === '45' || $c === '20' || ( $a === 'ja' && ( $p === '23' || $c === '16' ) ) ) {
            $tier = 'performance';
        }
        return $tier;
    }
}
