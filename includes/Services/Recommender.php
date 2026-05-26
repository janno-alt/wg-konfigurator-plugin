<?php
declare( strict_types=1 );

namespace WG\Konfigurator\Services;

/**
 * Server-Side-Recommendation. Synchron mit assets/quiz-app/src/recommendation.js
 * gehalten. Source-of-Truth fürs PDF + CRM-Payload.
 */
final class Recommender {

    /** @return array{video_typ:string, output_paket:string, video_laenge:string, features:string[], reasoning_short:string} */
    public static function recommend( string $goal, string $budget ): array {
        $isLow     = $budget === 'low';
        $isHigh    = $budget === 'high';
        $isPremium = $budget === 'premium';

        $default = [
            'video_typ'       => 'werbespot',
            'output_paket'    => 'einzel',
            'video_laenge'    => 'medium',
            'features'        => [],
            'reasoning_short' => '',
        ];

        switch ( $goal ) {
            case 'awareness':
                if ( $isLow ) {
                    return [
                        'video_typ' => 'reel_paket', 'output_paket' => '', 'video_laenge' => '',
                        'features' => [ 'drohne' ],
                        'reasoning_short' => 'Bei schmalem Budget bekommst du mit dem Reel-Paket maximale Reichweite – 12 Kurzvideos für 3 Monate Social-Sichtbarkeit.',
                    ];
                }
                if ( $isPremium ) {
                    return [
                        'video_typ' => 'werbespot', 'output_paket' => 'kampagne', 'video_laenge' => 'medium',
                        'features' => [ 'voiceover', 'sound', 'drohne' ],
                        'reasoning_short' => 'Eine komplette Kampagne: Hauptspot + Kurzvideos + Bonus-Material. Damit bespielst du alle Kanäle mit einem Drehtag.',
                    ];
                }
                return [
                    'video_typ' => 'werbespot', 'output_paket' => 'paket', 'video_laenge' => 'medium',
                    'features' => [ 'voiceover', 'sound' ],
                    'reasoning_short' => 'Werbespot mit Kurzvideos für Social Media – klassische Werbeflächen + organische Reichweite in einem Aufwasch.',
                ];

            case 'brand':
                if ( $isLow ) {
                    return [
                        'video_typ' => 'imagefilm', 'output_paket' => 'einzel', 'video_laenge' => 'medium',
                        'features' => [ 'voiceover' ],
                        'reasoning_short' => 'Ein kompakter Imagefilm (60–90 Sek.) für deine Website – Vertrauen aufbauen, ohne dein Budget zu sprengen.',
                    ];
                }
                if ( $isPremium ) {
                    return [
                        'video_typ' => 'imagefilm', 'output_paket' => 'kampagne', 'video_laenge' => 'long',
                        'features' => [ 'voiceover', 'sound', 'drohne' ],
                        'reasoning_short' => 'Ein vollwertiger 2–3-Min.-Imagefilm + Social-Cuts + Behind-the-Scenes. Genug Raum, eure Werte und Persönlichkeit zu zeigen.',
                    ];
                }
                return [
                    'video_typ' => 'imagefilm', 'output_paket' => 'einzel', 'video_laenge' => 'long',
                    'features' => [ 'voiceover', 'sound' ],
                    'reasoning_short' => 'Ein Imagefilm in der bewährten 2–3-Min.-Form gibt Raum für Story und Persönlichkeit – wirkt langfristig auf Vertrauen.',
                ];

            case 'recruiting':
                if ( $isLow ) {
                    return [
                        'video_typ' => 'recruiting', 'output_paket' => 'einzel', 'video_laenge' => 'medium',
                        'features' => [],
                        'reasoning_short' => 'Ein fokussiertes Recruiting-Video für deine Karriere-Seite und Stellenanzeigen – schlank und auf den Punkt.',
                    ];
                }
                if ( $isPremium ) {
                    return [
                        'video_typ' => 'recruiting', 'output_paket' => 'kampagne', 'video_laenge' => 'long',
                        'features' => [ 'voiceover', 'drohne' ],
                        'reasoning_short' => 'Vollkampagne: Hauptvideo + Kurzvideos für Social Recruiting + Bonus-Material. Maximaler Bewerber-Funnel.',
                    ];
                }
                return [
                    'video_typ' => 'recruiting', 'output_paket' => 'paket', 'video_laenge' => 'medium',
                    'features' => [ 'voiceover' ],
                    'reasoning_short' => 'Recruiting-Video + Kurzvideos für Social Media: Authentische Einblicke ins Team plus Hooks für LinkedIn und Instagram.',
                ];

            case 'social':
                return [
                    'video_typ' => 'reel_paket', 'output_paket' => '', 'video_laenge' => '',
                    'features' => ( $isHigh || $isPremium ) ? [ 'drohne' ] : [],
                    'reasoning_short' => '12 Kurzvideos für 30–60 Sek. in einem halben Drehtag – konstanter Content für Instagram, TikTok und LinkedIn über 3 Monate.',
                ];

            case 'explain':
                if ( $isLow ) {
                    return [
                        'video_typ' => 'erklaer_anim', 'output_paket' => '', 'video_laenge' => 'short',
                        'features' => [ 'voiceover' ],
                        'reasoning_short' => 'Eine kurze animierte Erklärung (15–30 Sek.) ist günstig produziert und perfekt für Social-Hooks.',
                    ];
                }
                if ( $isPremium ) {
                    return [
                        'video_typ' => 'erklaer_real', 'output_paket' => '', 'video_laenge' => 'extra_long',
                        'features' => [ 'voiceover', 'animation', 'sound' ],
                        'reasoning_short' => 'Längeres Erklärvideo mit Real-Material: Glaubwürdiger als reine Animation und genug Zeit, das Thema sauber aufzubauen.',
                    ];
                }
                return [
                    'video_typ' => 'erklaer_real', 'output_paket' => '', 'video_laenge' => 'long',
                    'features' => [ 'voiceover' ],
                    'reasoning_short' => 'Erklärvideo mit echtem Material (2–3 Min.): Glaubwürdiger als reine Animation und ausreichend Zeit, das Thema verständlich aufzubauen.',
                ];

            case 'technical':
                if ( $isPremium ) {
                    return [
                        'video_typ' => 'animation_tech', 'output_paket' => '', 'video_laenge' => 'long',
                        'features' => [ 'voiceover', 'sound' ],
                        'reasoning_short' => 'Technische Animation visualisiert, was Realbild nicht zeigt – Schnitte durchs Bauteil, Materialflüsse, unsichtbare Vorgänge.',
                    ];
                }
                if ( $isHigh ) {
                    return [
                        'video_typ' => 'animation_tech', 'output_paket' => '', 'video_laenge' => 'medium',
                        'features' => [ 'voiceover' ],
                        'reasoning_short' => 'Eine fokussierte technische Animation (60–90 Sek.) – ideal für Produkt-Detail-Seiten und Messe-Loops.',
                    ];
                }
                return [
                    'video_typ' => 'animation_3d', 'output_paket' => '', 'video_laenge' => 'medium',
                    'features' => [ 'voiceover' ],
                    'reasoning_short' => 'Eine 3D-Produkt-Animation in 60–90 Sek. zeigt dein Produkt von allen Seiten – schon mit moderatem Budget realistisch.',
                ];

            case 'sales':
                if ( $isLow ) {
                    return [
                        'video_typ' => 'werbespot', 'output_paket' => 'einzel', 'video_laenge' => 'medium',
                        'features' => [ 'voiceover' ],
                        'reasoning_short' => 'Ein klassischer 60-Sek.-Werbespot konvertiert – einfache Botschaft, klarer Call-to-Action.',
                    ];
                }
                if ( $isPremium ) {
                    return [
                        'video_typ' => 'werbespot', 'output_paket' => 'kampagne', 'video_laenge' => 'medium',
                        'features' => [ 'voiceover', 'sound', 'drohne' ],
                        'reasoning_short' => 'Komplette Performance-Kampagne: Hauptspot + Kurzvideos + A/B-Material für Meta-Ads, YouTube und LinkedIn.',
                    ];
                }
                return [
                    'video_typ' => 'werbespot', 'output_paket' => 'paket', 'video_laenge' => 'medium',
                    'features' => [ 'voiceover', 'sound' ],
                    'reasoning_short' => 'Werbespot + Kurzvideos für Performance-Anzeigen: Hauptvideo für die Landingpage, Social-Cuts für Meta- und YouTube-Ads.',
                ];
        }

        return $default;
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

    public static function budget_label( string $budget ): string {
        return [
            'low'     => 'Bis 2.500 €',
            'medium'  => '2.500 – 5.000 €',
            'high'    => '5.000 – 10.000 €',
            'premium' => 'Über 10.000 €',
            'unknown' => 'Budget noch offen',
        ][ $budget ] ?? $budget;
    }
}
