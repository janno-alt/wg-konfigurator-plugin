<?php
declare( strict_types=1 );

namespace WG\Konfigurator\Services;

use WG\Konfigurator\Admin\Settings;

/**
 * Deterministische Preis-Range aus den Kunden-Antworten.
 *
 * Inputs sind feature-orientiert (output_paket, features, video_laenge),
 * nicht aufwandsorientiert (drehtage). Wir mappen das auf konkrete Preis-Ranges.
 *
 * Wichtig: dieselbe Logik existiert client-seitig in App.jsx als Live-Preis
 * (`computePrice`). Wenn du hier etwas änderst, auch dort anpassen.
 */
final class PriceCalculator {

    private const PAKET_MAP = [
        'einzel'   => [ 'days' => 1, 'mult_min' => 1.0,  'mult_max' => 1.0  ],
        'paket'    => [ 'days' => 2, 'mult_min' => 1.35, 'mult_max' => 1.45 ],
        'kampagne' => [ 'days' => 3, 'mult_min' => 1.7,  'mult_max' => 2.0  ],
    ];

    private const LAENGE_MAP = [
        'short'      => 0.85,  // Reel/Short – weniger Schnitt-Aufwand
        'medium'     => 1.0,   // Spot – Standard
        'long'       => 1.20,  // Imagefilm – mehr Story-Bögen
        'extra_long' => 1.40,  // Erklärvideo – Drehbuch + Animation
    ];

    /** Untertitel & lizenzierte Musik sind Standard und im Basispreis enthalten. */
    private const FEATURE_PRICE = [
        'voiceover'    => 290,
        'animation'    => 450,
        'drohne'       => 590,
        'mehrsprachig' => 390,
    ];

    /**
     * @param array<string,mixed> $quiz
     * @return array{
     *   preis_min:int, preis_max:int, express_aufschlag:int, score:int,
     *   drehtage:int, features_aufschlag:int,
     *   breakdown:array<string,mixed>
     * }
     */
    public function calculate( array $quiz ): array {
        $settings = Settings::get();
        $base     = $settings['price_base'] ?? [];

        $typ      = $this->normalize_typ(    (string) ( $quiz['video_typ']    ?? '' ) );
        $paket    = $this->normalize_paket(  (string) ( $quiz['output_paket'] ?? 'einzel' ) );
        $laenge   = $this->normalize_laenge( (string) ( $quiz['video_laenge'] ?? 'medium' ) );
        $features = array_values( array_filter( array_map(
            'strval',
            (array) ( $quiz['features'] ?? [] )
        ) ) );

        $b           = $base[ $typ ] ?? [ 'min' => 1990, 'max' => 3990 ];
        $paket_def   = self::PAKET_MAP[ $paket ];
        $length_mult = self::LAENGE_MAP[ $laenge ];

        // Basis × Paket-Multiplikator × Länge-Multiplikator
        $preis_min = (int) round( $b['min'] * $paket_def['mult_min'] * $length_mult );
        $preis_max = (int) round( $b['max'] * $paket_def['mult_max'] * $length_mult );

        // Feature-Aufschläge (fix)
        $features_aufschlag = 0;
        foreach ( $features as $f ) {
            $features_aufschlag += (int) ( self::FEATURE_PRICE[ $f ] ?? 0 );
        }
        $preis_min += $features_aufschlag;
        $preis_max += $features_aufschlag;

        // Express-Aufschlag
        $express  = (float) ( $settings['express_surcharge'] ?? 0.20 );
        $express_aufschlag = 0;
        if ( ( $quiz['zeitrahmen'] ?? '' ) === 'express' ) {
            $express_aufschlag = (int) round( $preis_max * $express );
            $preis_min        += (int) round( $preis_min * $express );
            $preis_max        += $express_aufschlag;
        }

        // Lead-Score (0–100)
        $score = 40;
        $score += in_array( $typ, [ 'imagefilm', 'recruiting' ], true ) ? 15 : 5;
        $score += $paket === 'kampagne' ? 15 : ( $paket === 'paket' ? 10 : 0 );
        $score += ( $quiz['zeitrahmen'] ?? '' ) === 'flexibel' ? 10 : 5;
        $score += count( $features ) >= 2 ? 10 : 0;
        $score += ! empty( $quiz['website'] ) ? 10 : 0;
        $score  = min( 100, $score );

        return [
            'preis_min'         => $preis_min,
            'preis_max'         => $preis_max,
            'express_aufschlag' => $express_aufschlag,
            'features_aufschlag'=> $features_aufschlag,
            'score'             => $score,
            'drehtage'          => $paket_def['days'],
            'breakdown'         => [
                'video_typ'         => $typ,
                'output_paket'      => $paket,
                'video_laenge'      => $laenge,
                'base_min'          => (int) $b['min'],
                'base_max'          => (int) $b['max'],
                'paket_mult_min'    => $paket_def['mult_min'],
                'paket_mult_max'    => $paket_def['mult_max'],
                'length_mult'       => $length_mult,
                'features'          => $features,
                'features_aufschlag'=> $features_aufschlag,
                'drehtage_intern'   => $paket_def['days'],
            ],
        ];
    }

    private function normalize_typ( string $value ): string {
        $value = strtolower( trim( $value ) );
        $map   = [
            'image'                 => 'imagefilm',
            'imagefilm'             => 'imagefilm',
            'werbung'               => 'werbespot',
            'werbespot'             => 'werbespot',
            'spot'                  => 'werbespot',
            'recruiting'            => 'recruiting',
            'mitarbeitergewinnung'  => 'recruiting',
            'erklaer'               => 'erklaervideo',
            'erklaervideo'          => 'erklaervideo',
            'erklärvideo'           => 'erklaervideo',
        ];
        return $map[ $value ] ?? 'werbespot';
    }

    private function normalize_paket( string $value ): string {
        $value = strtolower( trim( $value ) );
        return in_array( $value, [ 'einzel', 'paket', 'kampagne' ], true ) ? $value : 'einzel';
    }

    private function normalize_laenge( string $value ): string {
        $value = strtolower( trim( $value ) );
        return in_array( $value, [ 'short', 'medium', 'long', 'extra_long' ], true ) ? $value : 'medium';
    }

    /**
     * @param string[] $ids
     * @return string[]
     */
    public static function feature_labels( array $ids ): array {
        $map = [
            'voiceover'    => 'Voiceover / Sprecher:in',
            'animation'    => 'Animierte Texte / Lower-Thirds',
            'drohne'       => 'Drohnen-Aufnahmen',
            'mehrsprachig' => 'Mehrsprachige Versionen',
        ];
        $out = [];
        foreach ( $ids as $id ) {
            if ( isset( $map[ $id ] ) ) {
                $out[] = $map[ $id ];
            }
        }
        return $out;
    }

    public static function paket_label( string $id ): string {
        return [
            'einzel'   => 'Ein fertiges Hauptvideo',
            'paket'    => 'Hauptvideo + Social-Cuts',
            'kampagne' => 'Vollkampagne (Hauptvideo + Social-Cuts + Bonus)',
        ][ $id ] ?? $id;
    }

    public static function length_label( string $id ): string {
        return [
            'short'      => '15–30 Sek. (Reel / Short)',
            'medium'     => '60–90 Sek. (Spot)',
            'long'       => '2–3 Min. (Imagefilm)',
            'extra_long' => '4–5 Min. (Erklärfilm)',
        ][ $id ] ?? $id;
    }
}
