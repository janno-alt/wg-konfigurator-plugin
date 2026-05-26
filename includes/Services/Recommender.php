<?php
declare( strict_types=1 );

namespace WG\Konfigurator\Services;

/**
 * Server-Side-Recommendation v0.10
 * Input: Ziel + Ausspiel-Kanäle (Multi)
 * Output: Default-Konfig (Video-Typ + Paket + Länge + Features)
 *
 * Synchron mit assets/quiz-app/src/recommendation.js gehalten.
 */
final class Recommender {

    /**
     * @param string   $goal
     * @param string[] $channels
     * @return array{video_typ:string, output_paket:string, video_laenge:string, features:string[], reasoning_short:string}
     */
    public static function recommend( string $goal, array $channels = [] ): array {
        $hasSocial  = in_array( 'social',  $channels, true );
        $hasTv      = in_array( 'tv',      $channels, true );
        $hasMesse   = in_array( 'messe',   $channels, true );
        $hasAds     = in_array( 'ads',     $channels, true );

        switch ( $goal ) {
            case 'awareness':
                return [
                    'video_typ'       => 'werbespot',
                    'output_paket'    => ( $hasSocial || $hasAds ) ? 'paket' : 'einzel',
                    'video_laenge'    => $hasMesse ? 'short' : 'medium',
                    'features'        => $hasMesse ? [ 'animation', 'sound' ] : [ 'voiceover', 'sound' ],
                    'reasoning_short' => $hasSocial
                        ? 'Werbespot + Kurzvideos für Social Media: klassische Werbeflächen plus organische Reichweite.'
                        : 'Klassischer Werbespot, der deine Hauptbotschaft auf den Punkt bringt.',
                ];

            case 'brand':
                return [
                    'video_typ'       => 'imagefilm',
                    'output_paket'    => $hasSocial ? 'paket' : 'einzel',
                    'video_laenge'    => ( $hasMesse || $hasSocial ) ? 'medium' : 'long',
                    'features'        => $hasMesse ? [ 'sound', 'animation' ] : [ 'voiceover', 'sound' ],
                    'reasoning_short' => 'Ein Imagefilm baut Vertrauen auf — Raum für Werte und Persönlichkeit.',
                ];

            case 'recruiting':
                return [
                    'video_typ'       => 'recruiting',
                    'output_paket'    => $hasSocial ? 'paket' : 'einzel',
                    'video_laenge'    => 'medium',
                    'features'        => [ 'voiceover' ],
                    'reasoning_short' => $hasSocial
                        ? 'Recruiting-Video + Kurzvideos: Hauptvideo für die Karriere-Seite, kurze Hooks für LinkedIn und Instagram.'
                        : 'Fokussiertes Recruiting-Video für deine Karriere-Seite und Stellenanzeigen.',
                ];

            case 'social':
                return [
                    'video_typ'       => 'reel_paket',
                    'output_paket'    => '',
                    'video_laenge'    => '',
                    'features'        => [],
                    'reasoning_short' => '12 Kurzvideos in einem ½ Drehtag — konstante Sichtbarkeit auf Social Media über 3 Monate.',
                ];

            case 'explain':
                return [
                    'video_typ'       => 'erklaer_real',
                    'output_paket'    => '',
                    'video_laenge'    => $hasSocial ? 'medium' : 'long',
                    'features'        => [ 'voiceover' ],
                    'reasoning_short' => 'Erklärvideo mit echtem Material wirkt glaubwürdiger als reine Animation.',
                ];

            case 'technical':
                return [
                    'video_typ'       => 'animation_tech',
                    'output_paket'    => '',
                    'video_laenge'    => $hasMesse ? 'medium' : 'long',
                    'features'        => $hasMesse ? [ 'sound' ] : [ 'voiceover', 'sound' ],
                    'reasoning_short' => 'Technische Animation zeigt, was Realbild nicht zeigen kann — Schnitte durchs Bauteil, Materialflüsse, unsichtbare Vorgänge.',
                ];

            case 'sales':
                return [
                    'video_typ'       => 'werbespot',
                    'output_paket'    => ( $hasAds || $hasSocial ) ? 'paket' : 'einzel',
                    'video_laenge'    => 'medium',
                    'features'        => [ 'voiceover' ],
                    'reasoning_short' => $hasAds
                        ? 'Werbespot + Kurzvideos: Hauptvideo für die Landingpage, Social-Cuts für Meta- und YouTube-Ads.'
                        : 'Klassischer Werbespot mit klarem Call-to-Action — direkt für die Landingpage.',
                ];
        }

        return [
            'video_typ'       => 'werbespot',
            'output_paket'    => 'einzel',
            'video_laenge'    => 'medium',
            'features'        => [ 'voiceover' ],
            'reasoning_short' => '',
        ];
    }

    public static function goal_label( string $goal ): string {
        return [
            'awareness'  => 'Mehr Aufmerksamkeit und Neukunden',
            'brand'      => 'Marke und Vertrauen aufbauen',
            'recruiting' => 'Mehr Bewerber:innen gewinnen',
            'social'     => 'Reichweite auf Social Media',
            'explain'    => 'Komplexes Produkt oder Thema erklären',
            'technical'  => 'Technisches Produkt visualisieren',
            'sales'      => 'Online verkaufen / Conversion steigern',
        ][ $goal ] ?? $goal;
    }

    public static function channel_label( string $id ): string {
        return [
            'website' => 'Eigene Website / Landingpage',
            'youtube' => 'YouTube',
            'social'  => 'Social Media',
            'ads'     => 'Bezahlte Anzeigen',
            'messe'   => 'Messe / Display',
            'tv'      => 'TV / Streaming',
        ][ $id ] ?? $id;
    }

    /** @param string[] $ids */
    public static function channel_labels( array $ids ): array {
        $out = [];
        foreach ( $ids as $id ) {
            $out[] = self::channel_label( $id );
        }
        return $out;
    }
}
