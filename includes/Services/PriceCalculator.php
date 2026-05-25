<?php
declare( strict_types=1 );

namespace WG\Konfigurator\Services;

use WG\Konfigurator\Admin\Settings;

/**
 * Server-seitige Preis-Logik. MUSS synchron mit assets/quiz-app/src/pricing.js
 * gehalten werden – derselbe Algorithmus, dieselben Preise.
 *
 * Modelle:
 * - 'flat':       Pauschale × Output-Paket × Länge   (Image/Werbe/Recruiting)
 * - 'per_minute': Basis × Mittelwert-Minuten         (Erklär/Animation)
 * - 'fixed':      Festpreis                          (Reel-Paket)
 */
final class PriceCalculator {

    private const KONZEPT_PAUSCHALE = 1000;

    /** @var array<string,array<string,mixed>>
     *
     * Preis-Range pro Typ ist auf max ~50 % Spread eingestellt, generell etwas
     * zurückhaltend kalkuliert (lieber niedriger als abschreckend hoch).
     */
    private const VIDEO_TYPES = [
        'imagefilm'       => [ 'label' => 'Imagefilm',               'model' => 'flat',       'base_min' => 2000, 'base_max' => 3000, 'has_konzept' => true,  'has_drohne' => true,  'has_voiceover' => true,  'has_animation' => true,  'has_sound' => true,  'has_mehrsprachig' => true,  'has_paket' => true,  'has_laenge' => true  ],
        'werbespot'       => [ 'label' => 'Werbespot',               'model' => 'flat',       'base_min' => 2500, 'base_max' => 3750, 'has_konzept' => true,  'has_drohne' => true,  'has_voiceover' => true,  'has_animation' => true,  'has_sound' => true,  'has_mehrsprachig' => true,  'has_paket' => true,  'has_laenge' => true  ],
        'recruiting'      => [ 'label' => 'Recruiting-Video',        'model' => 'flat',       'base_min' => 2500, 'base_max' => 3750, 'has_konzept' => true,  'has_drohne' => true,  'has_voiceover' => true,  'has_animation' => true,  'has_sound' => true,  'has_mehrsprachig' => true,  'has_paket' => true,  'has_laenge' => true  ],
        'reel_paket'      => [ 'label' => 'Reel-Paket (12 Reels)',   'model' => 'fixed',      'base_min' => 1500, 'base_max' => 1500, 'has_konzept' => false, 'has_drohne' => true,  'has_voiceover' => false, 'has_animation' => true,  'has_sound' => false, 'has_mehrsprachig' => false, 'has_paket' => false, 'has_laenge' => false, 'drehtage' => 0.5, 'payment_note' => '3 × 500 € monatlich' ],
        'erklaer_real'    => [ 'label' => 'Erklärvideo (Real)',      'model' => 'per_minute', 'base_min' => 1000, 'base_max' => 1500, 'has_konzept' => true,  'has_drohne' => true,  'has_voiceover' => true,  'has_animation' => true,  'has_sound' => true,  'has_mehrsprachig' => true,  'has_paket' => false, 'has_laenge' => true  ],
        'erklaer_anim'    => [ 'label' => 'Erklärvideo (2D)',        'model' => 'per_minute', 'base_min' => 1500, 'base_max' => 2250, 'has_konzept' => true,  'has_drohne' => false, 'has_voiceover' => true,  'has_animation' => false, 'has_sound' => true,  'has_mehrsprachig' => true,  'has_paket' => false, 'has_laenge' => true  ],
        'animation_3d'    => [ 'label' => '3D-Animation',            'model' => 'per_minute', 'base_min' => 2000, 'base_max' => 3000, 'has_konzept' => true,  'has_drohne' => false, 'has_voiceover' => true,  'has_animation' => false, 'has_sound' => true,  'has_mehrsprachig' => true,  'has_paket' => false, 'has_laenge' => true  ],
        'animation_tech'  => [ 'label' => 'Technische Animation',    'model' => 'per_minute', 'base_min' => 2500, 'base_max' => 3750, 'has_konzept' => true,  'has_drohne' => false, 'has_voiceover' => true,  'has_animation' => false, 'has_sound' => true,  'has_mehrsprachig' => true,  'has_paket' => false, 'has_laenge' => true  ],
    ];

    private const PAKET = [
        'einzel'   => [ 'mult_min' => 1.0,  'mult_max' => 1.0,  'drehtage' => 1, 'label' => 'Ein Hauptvideo' ],
        'paket'    => [ 'mult_min' => 1.30, 'mult_max' => 1.35, 'drehtage' => 2, 'label' => 'Hauptvideo + Social-Cuts' ],
        'kampagne' => [ 'mult_min' => 1.50, 'mult_max' => 1.65, 'drehtage' => 3, 'label' => 'Vollkampagne' ],
    ];

    private const LAENGE = [
        'short'      => [ 'mult' => 0.9,  'minutes' => 0.4,  'label' => '15–30 Sek.' ],
        'medium'     => [ 'mult' => 1.0,  'minutes' => 1.25, 'label' => '60–90 Sek.' ],
        'long'       => [ 'mult' => 1.15, 'minutes' => 2.5,  'label' => '2–3 Min.' ],
        'extra_long' => [ 'mult' => 1.25, 'minutes' => 4.5,  'label' => '4–5 Min.' ],
    ];

    private const FEATURES = [
        'voiceover'    => [ 'price' => 400 ],
        'animation'    => [ 'price' => 450 ],
        'drohne'       => [ 'price_per_day' => 200 ],
        'sound'        => [ 'price_per_min' => 250 ],
        'mehrsprachig' => [ 'price' => 390 ],
    ];

    private const EXPRESS_MULT = 0.20;

    /**
     * @param array<string,mixed> $quiz
     * @return array{
     *   preis_min:int, preis_max:int, express_aufschlag:int, score:int,
     *   drehtage:int|float, features_aufschlag:int,
     *   breakdown:array<string,mixed>,
     *   items:array<int,array<string,mixed>>
     * }
     */
    public function calculate( array $quiz ): array {
        $typ      = $this->normalize_typ( (string) ( $quiz['video_typ'] ?? '' ) );
        $type_def = self::VIDEO_TYPES[ $typ ] ?? self::VIDEO_TYPES['werbespot'];
        $features = $this->normalize_features( (array) ( $quiz['features'] ?? [] ) );
        $items    = [];
        $drehtage = 1;
        $minutes  = 0.0;

        if ( $type_def['model'] === 'fixed' ) {
            $items[] = [
                'key'   => 'fixed',
                'label' => $type_def['label'],
                'min'   => (int) $type_def['base_min'],
                'max'   => (int) $type_def['base_max'],
            ];
            $drehtage = (int) ceil( (float) ( $type_def['drehtage'] ?? 1 ) );
            $this->add_features( $items, $features, $type_def, $drehtage, 0.0 );

        } elseif ( $type_def['model'] === 'flat' ) {
            $paket  = self::PAKET[ $this->normalize_paket( (string) ( $quiz['output_paket'] ?? 'einzel' ) ) ];
            $length = self::LAENGE[ $this->normalize_laenge( (string) ( $quiz['video_laenge'] ?? 'medium' ) ) ];
            $drehtage = (int) $paket['drehtage'];
            $minutes  = (float) $length['minutes'];

            $items[] = [
                'key'   => 'base',
                'label' => 'Basis · ' . $type_def['label'],
                'min'   => (int) $type_def['base_min'],
                'max'   => (int) $type_def['base_max'],
            ];

            if ( $paket['mult_min'] !== 1.0 || $paket['mult_max'] !== 1.0 ) {
                $items[] = [
                    'key'   => 'paket',
                    'label' => '+ ' . $paket['label'],
                    'min'   => (int) round( $type_def['base_min'] * ( $paket['mult_min'] - 1 ) ),
                    'max'   => (int) round( $type_def['base_max'] * ( $paket['mult_max'] - 1 ) ),
                ];
            }

            if ( $length['mult'] !== 1.0 ) {
                $sum_min0 = $type_def['base_min'] * $paket['mult_min'];
                $sum_max0 = $type_def['base_max'] * $paket['mult_max'];
                $sign     = $length['mult'] > 1.0 ? '+' : '−';
                $items[] = [
                    'key'   => 'length',
                    'label' => "{$sign} Länge {$length['label']}",
                    'min'   => (int) round( $sum_min0 * ( $length['mult'] - 1 ) ),
                    'max'   => (int) round( $sum_max0 * ( $length['mult'] - 1 ) ),
                ];
            }

            $this->add_konzept( $items, $type_def );
            $this->add_features( $items, $features, $type_def, $drehtage, $minutes );

        } else { // per_minute
            $length  = self::LAENGE[ $this->normalize_laenge( (string) ( $quiz['video_laenge'] ?? 'medium' ) ) ];
            $minutes = (float) $length['minutes'];
            $drehtage = 1;

            $items[] = [
                'key'   => 'base',
                'label' => $type_def['label'] . ' · ' . $length['label'],
                'min'   => (int) round( $type_def['base_min'] * $minutes ),
                'max'   => (int) round( $type_def['base_max'] * $minutes ),
            ];

            $this->add_konzept( $items, $type_def );
            $this->add_features( $items, $features, $type_def, $drehtage, $minutes );
        }

        $this->add_express( $items, $quiz );

        $sum = $this->sum_items( $items );

        return [
            'preis_min'         => $sum['min'],
            'preis_max'         => $sum['max'],
            'express_aufschlag' => $this->find_item( $items, 'express' )['max'] ?? 0,
            'features_aufschlag'=> $this->sum_feature_items( $items ),
            'score'             => $this->compute_score( $quiz, $typ, $features ),
            'drehtage'          => $drehtage,
            'items'             => $items,
            'breakdown'         => [
                'video_typ'      => $typ,
                'video_typ_label'=> $type_def['label'],
                'model'          => $type_def['model'],
                'output_paket'   => $quiz['output_paket'] ?? null,
                'video_laenge'   => $quiz['video_laenge'] ?? null,
                'features'       => $features,
                'minutes'        => $minutes,
                'drehtage'       => $drehtage,
            ],
        ];
    }

    /* ---------- Helpers ---------- */

    private function add_konzept( array &$items, array $type_def ): void {
        if ( empty( $type_def['has_konzept'] ) ) {
            return;
        }
        $items[] = [
            'key'   => 'konzept',
            'label' => '+ Konzept-Workshop (Storyboard, Drehplan)',
            'min'   => self::KONZEPT_PAUSCHALE,
            'max'   => self::KONZEPT_PAUSCHALE,
        ];
    }

    private function add_features( array &$items, array $features, array $type_def, $drehtage, float $minutes ): void {
        foreach ( $features as $f ) {
            if ( ! isset( self::FEATURES[ $f ] ) ) {
                continue;
            }
            if ( $f === 'voiceover'    && empty( $type_def['has_voiceover'] ) ) continue;
            if ( $f === 'animation'    && empty( $type_def['has_animation'] ) ) continue;
            if ( $f === 'drohne'       && empty( $type_def['has_drohne'] ) ) continue;
            if ( $f === 'sound'        && empty( $type_def['has_sound'] ) ) continue;
            if ( $f === 'mehrsprachig' && empty( $type_def['has_mehrsprachig'] ) ) continue;

            if ( $f === 'drohne' ) {
                $tage  = max( 1, (int) ceil( (float) $drehtage ) );
                $total = $tage * (int) self::FEATURES['drohne']['price_per_day'];
                $items[] = [
                    'key'   => 'feat-drohne',
                    'label' => sprintf( '+ Drohnen-Aufnahmen (%d %s)', $tage, $tage === 1 ? 'Drehtag' : 'Drehtage' ),
                    'min'   => $total,
                    'max'   => $total,
                ];
                continue;
            }

            if ( $f === 'sound' ) {
                $total = (int) round( $minutes * (int) self::FEATURES['sound']['price_per_min'] );
                $items[] = [
                    'key'   => 'feat-sound',
                    'label' => sprintf( '+ Sound Design (%s Min.)', number_format( $minutes, 1, ',', '' ) ),
                    'min'   => $total,
                    'max'   => $total,
                ];
                continue;
            }

            $price = (int) self::FEATURES[ $f ]['price'];
            $items[] = [
                'key'   => 'feat-' . $f,
                'label' => '+ ' . self::feature_label( $f ),
                'min'   => $price,
                'max'   => $price,
            ];
        }
    }

    private function add_express( array &$items, array $quiz ): void {
        if ( ( $quiz['zeitrahmen'] ?? '' ) !== 'express' ) {
            return;
        }
        $sub = $this->sum_items( $items );
        $items[] = [
            'key'   => 'express',
            'label' => '+ Express-Aufschlag (+' . (int) ( self::EXPRESS_MULT * 100 ) . ' %)',
            'min'   => (int) round( $sub['min'] * self::EXPRESS_MULT ),
            'max'   => (int) round( $sub['max'] * self::EXPRESS_MULT ),
        ];
    }

    /** @return array{min:int,max:int} */
    private function sum_items( array $items ): array {
        $min = 0;
        $max = 0;
        foreach ( $items as $it ) {
            $min += (int) $it['min'];
            $max += (int) $it['max'];
        }
        return [ 'min' => $min, 'max' => $max ];
    }

    private function sum_feature_items( array $items ): int {
        $sum = 0;
        foreach ( $items as $it ) {
            if ( strpos( (string) $it['key'], 'feat-' ) === 0 ) {
                $sum += (int) $it['max'];
            }
        }
        return $sum;
    }

    /** @return array{min:int,max:int}|null */
    private function find_item( array $items, string $key ): ?array {
        foreach ( $items as $it ) {
            if ( $it['key'] === $key ) {
                return [ 'min' => (int) $it['min'], 'max' => (int) $it['max'] ];
            }
        }
        return null;
    }

    private function compute_score( array $quiz, string $typ, array $features ): int {
        $score = 40;
        $score += in_array( $typ, [ 'imagefilm', 'recruiting', 'animation_tech', 'animation_3d' ], true ) ? 15 : 5;
        $score += ( $quiz['output_paket'] ?? '' ) === 'kampagne' ? 15 : ( ( $quiz['output_paket'] ?? '' ) === 'paket' ? 10 : 0 );
        $score += ( $quiz['zeitrahmen']   ?? '' ) === 'flexibel' ? 10 : 5;
        $score += count( $features ) >= 2 ? 10 : 0;
        $score += ! empty( $quiz['website'] ) ? 10 : 0;
        return min( 100, $score );
    }

    private function normalize_typ( string $value ): string {
        $value = strtolower( trim( $value ) );
        return isset( self::VIDEO_TYPES[ $value ] ) ? $value : 'werbespot';
    }

    private function normalize_paket( string $value ): string {
        $value = strtolower( trim( $value ) );
        return in_array( $value, [ 'einzel', 'paket', 'kampagne' ], true ) ? $value : 'einzel';
    }

    private function normalize_laenge( string $value ): string {
        $value = strtolower( trim( $value ) );
        return in_array( $value, [ 'short', 'medium', 'long', 'extra_long' ], true ) ? $value : 'medium';
    }

    private function normalize_features( array $ids ): array {
        $out = [];
        foreach ( $ids as $f ) {
            $f = strtolower( trim( (string) $f ) );
            if ( isset( self::FEATURES[ $f ] ) ) {
                $out[] = $f;
            }
        }
        return array_values( array_unique( $out ) );
    }

    /* ---------- Public Static Helpers (für Templates) ---------- */

    public static function feature_label( string $id ): string {
        return [
            'voiceover'    => 'Voiceover / Sprecher:in',
            'animation'    => 'Animierte Texte / Lower-Thirds',
            'drohne'       => 'Drohnen-Aufnahmen',
            'sound'        => 'Sound Design (Atmo, SFX)',
            'mehrsprachig' => 'Mehrsprachige Versionen',
        ][ $id ] ?? $id;
    }

    /** @param string[] $ids */
    public static function feature_labels( array $ids ): array {
        $out = [];
        foreach ( $ids as $id ) {
            $out[] = self::feature_label( $id );
        }
        return $out;
    }

    public static function paket_label( string $id ): string {
        return [
            'einzel'   => 'Ein fertiges Hauptvideo',
            'paket'    => 'Hauptvideo + Social-Cuts',
            'kampagne' => 'Vollkampagne',
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

    public static function type_label( string $id ): string {
        return self::VIDEO_TYPES[ $id ]['label'] ?? $id;
    }

    /** @return string[] valid video_typ IDs */
    public static function type_ids(): array {
        return array_keys( self::VIDEO_TYPES );
    }
}
