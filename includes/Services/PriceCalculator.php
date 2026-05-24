<?php
declare( strict_types=1 );

namespace WG\Konfigurator\Services;

use WG\Konfigurator\Admin\Settings;

/**
 * Deterministische Preis-Range aus Quiz-Antworten.
 * KI tut hier NICHT mit – Preise sind transparent und nachvollziehbar.
 */
final class PriceCalculator {

    /**
     * @param array<string,mixed> $quiz
     * @return array{preis_min:int,preis_max:int,express_aufschlag:int,score:int,breakdown:array<string,mixed>}
     */
    public function calculate( array $quiz ): array {
        $settings = Settings::get();
        $base     = $settings['price_base'] ?? [];
        $per_day  = (int) ( $settings['price_per_day'] ?? 850 );
        $express  = (float) ( $settings['express_surcharge'] ?? 0.20 );

        $typ = $this->normalize_typ( (string) ( $quiz['video_typ'] ?? '' ) );
        $b   = $base[ $typ ] ?? [ 'min' => 1990, 'max' => 3990 ];

        $drehtage = max( 1, min( 5, (int) ( $quiz['drehtage'] ?? 1 ) ) );
        $extra    = ( $drehtage - 1 ) * $per_day;

        $preis_min = (int) $b['min'] + $extra;
        $preis_max = (int) $b['max'] + $extra;

        $express_aufschlag = 0;
        if ( ( $quiz['zeitrahmen'] ?? '' ) === 'express' ) {
            $express_aufschlag = (int) round( $preis_max * $express );
            $preis_min        += (int) round( $preis_min * $express );
            $preis_max        += $express_aufschlag;
        }

        // Lead-Score (0–100): wie passend ist die Anfrage?
        $score = 50;
        $score += in_array( $typ, [ 'imagefilm', 'recruiting' ], true ) ? 15 : 0;
        $score += $drehtage >= 2 ? 10 : 0;
        $score += ( $quiz['zeitrahmen'] ?? '' ) === 'flexibel' ? 10 : 0;
        $score += ! empty( $quiz['website'] ) ? 10 : 0;
        $score  = min( 100, $score );

        return [
            'preis_min'         => $preis_min,
            'preis_max'         => $preis_max,
            'express_aufschlag' => $express_aufschlag,
            'score'             => $score,
            'breakdown'         => [
                'video_typ' => $typ,
                'base_min'  => (int) $b['min'],
                'base_max'  => (int) $b['max'],
                'drehtage'  => $drehtage,
                'per_day'   => $per_day,
                'extra'     => $extra,
            ],
        ];
    }

    private function normalize_typ( string $value ): string {
        $value = strtolower( trim( $value ) );
        $map   = [
            'image'            => 'imagefilm',
            'imagefilm'        => 'imagefilm',
            'werbung'          => 'werbespot',
            'werbespot'        => 'werbespot',
            'spot'             => 'werbespot',
            'recruiting'       => 'recruiting',
            'mitarbeitergewinnung' => 'recruiting',
            'erklaer'          => 'erklaervideo',
            'erklaervideo'     => 'erklaervideo',
            'erklärvideo'      => 'erklaervideo',
        ];
        return $map[ $value ] ?? 'werbespot';
    }
}
