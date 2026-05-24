<?php
declare( strict_types=1 );

namespace WG\Konfigurator\Services;

use RuntimeException;
use WG\Konfigurator\Admin\Settings;

/**
 * Thin client für Google Gemini (generateContent REST API).
 *
 * Wir nutzen kein offizielles SDK, sondern wp_remote_post — weniger Abhängigkeiten,
 * besser kontrollierbar. JSON-Mode + Schema, damit das LLM strukturiert antwortet.
 */
final class GeminiClient {

    private const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s';

    /**
     * @param array<string,mixed> $quiz   Quiz-Antworten (video_typ, drehtage, zeitrahmen, branche, website, ziel)
     * @param string              $website_excerpt  Auszug aus der gescrapeten Kunden-Website
     * @return array<string,mixed> Strukturiertes Konzept (siehe Schema).
     */
    public function generate_concept( array $quiz, string $website_excerpt ): array {
        $settings = Settings::get();
        $api_key  = trim( (string) $settings['gemini_api_key'] );

        if ( $api_key === '' ) {
            throw new RuntimeException( 'Gemini API-Key fehlt in den Plugin-Einstellungen.' );
        }

        $prompt = $this->build_prompt( $quiz, $website_excerpt );
        $schema = $this->response_schema();

        $payload = [
            'contents'         => [
                [
                    'role'  => 'user',
                    'parts' => [ [ 'text' => $prompt ] ],
                ],
            ],
            'systemInstruction'=> [
                'parts' => [ [ 'text' => $this->system_prompt() ] ],
            ],
            'generationConfig' => [
                'temperature'     => 0.6,
                'topP'            => 0.9,
                'responseMimeType'=> 'application/json',
                'responseSchema'  => $schema,
            ],
            'safetySettings'   => $this->safety_settings(),
        ];

        $url = sprintf(
            self::ENDPOINT,
            rawurlencode( (string) $settings['gemini_model'] ),
            rawurlencode( $api_key )
        );

        $response = wp_remote_post( $url, [
            'timeout' => 45,
            'headers' => [ 'Content-Type' => 'application/json' ],
            'body'    => wp_json_encode( $payload ),
        ] );

        if ( is_wp_error( $response ) ) {
            throw new RuntimeException( 'Gemini-Request fehlgeschlagen: ' . $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );

        if ( $code !== 200 ) {
            throw new RuntimeException( "Gemini HTTP {$code}: " . substr( $body, 0, 500 ) );
        }

        $decoded = json_decode( $body, true );
        $text    = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if ( ! is_string( $text ) ) {
            throw new RuntimeException( 'Gemini-Response ohne erwarteten Text-Part.' );
        }

        $concept = json_decode( $text, true );
        if ( ! is_array( $concept ) ) {
            throw new RuntimeException( 'Gemini-Response war kein gültiges JSON.' );
        }

        return $concept;
    }

    private function system_prompt(): string {
        return <<<TEXT
Du bist Strategie-Berater für Videomarketing bei WG-Digital. Deine Aufgabe:
Aus den Quiz-Antworten eines potenziellen Kunden und einem Auszug seiner Website
ein präzises, individuelles Konzept ableiten. Tonalität: direkt, ehrlich, konkret,
auf Augenhöhe. Keine Marketing-Floskeln, keine Em-Dashes als Gedankenstriche.
Antworte ausschließlich im vorgegebenen JSON-Schema – keine Erklärungen drumherum.
TEXT;
    }

    private function build_prompt( array $quiz, string $website ): string {
        $paket_labels = [
            'einzel'   => 'Ein fertiges Hauptvideo',
            'paket'    => 'Hauptvideo + 2–3 Social-Cuts (Reels/Shorts)',
            'kampagne' => 'Vollkampagne: Hauptvideo + Social-Cuts + Behind-the-Scenes',
        ];
        $feature_labels = [
            'voiceover'    => 'Voiceover/Sprecher:in',
            'untertitel'   => 'Untertitel',
            'animation'    => 'Animierte Texte/Lower-Thirds',
            'drohne'       => 'Drohnen-Aufnahmen',
            'musik'        => 'Lizenzierte Musik',
            'mehrsprachig' => 'Mehrsprachige Versionen',
        ];

        $paket    = $paket_labels[ $quiz['output_paket'] ?? '' ] ?? '(kein Paket gewählt)';
        $features = array_map(
            static fn ( $f ) => $feature_labels[ $f ] ?? $f,
            (array) ( $quiz['features'] ?? [] )
        );
        $features_text = $features ? implode( ', ', $features ) : '(keine gewählt)';

        $context = [
            'Video-Typ'      => (string) ( $quiz['video_typ']   ?? '' ),
            'Output-Paket'   => $paket,
            'Gewählte Features' => $features_text,
            'Zeitrahmen'     => (string) ( $quiz['zeitrahmen']  ?? '' ),
            'Branche'        => (string) ( $quiz['branche']     ?? '' ),
            'Website'        => (string) ( $quiz['website']     ?? '' ),
            'Kunden-Ziel'    => (string) ( $quiz['ziel']        ?? '(kein Freitext)' ),
        ];
        $context_str = '';
        foreach ( $context as $k => $v ) {
            $context_str .= "- {$k}: {$v}\n";
        }

        $website = trim( $website ) !== '' ? $website : '(keine Website angegeben oder Scraping fehlgeschlagen)';

        return <<<TEXT
KUNDEN-KONFIGURATION:
{$context_str}
AUSZUG AUS DER KUNDEN-WEBSITE:
{$website}

ANWEISUNG:
Leite daraus ein konkretes Videokonzept ab. Achte auf:
- "wirkungs_hypothese": 1 prägnanter Satz, der das Wirkungs-Ziel benennt.
- "story_skizze": 3–5 Sätze Story-Outline, kein Drehbuch.
- "empfohlene_protagonisten": 2–3 Rollen aus dem Team des Kunden.
- "empfohlene_locations": 2–3 Drehorte beim Kunden vor Ort.
- "vorbereitungs_checkliste": 4–6 konkrete Items, die der Kunde vor dem Drehtag erledigen sollte.
- "naechste_schritte": 2–3 Sätze, was nach Anfrage passiert.

Wenn der Kunde Features wie Drohne oder Voiceover gewählt hat, berücksichtige das in story_skizze und checkliste.
TEXT;
    }

    /** @return array<string,mixed> */
    private function response_schema(): array {
        return [
            'type'       => 'object',
            'properties' => [
                'wirkungs_hypothese'        => [ 'type' => 'string' ],
                'story_skizze'              => [ 'type' => 'string' ],
                'empfohlene_protagonisten'  => [
                    'type'  => 'array',
                    'items' => [ 'type' => 'string' ],
                ],
                'empfohlene_locations'      => [
                    'type'  => 'array',
                    'items' => [ 'type' => 'string' ],
                ],
                'vorbereitungs_checkliste'  => [
                    'type'  => 'array',
                    'items' => [ 'type' => 'string' ],
                ],
                'naechste_schritte'         => [ 'type' => 'string' ],
            ],
            'required'   => [
                'wirkungs_hypothese',
                'story_skizze',
                'empfohlene_protagonisten',
                'empfohlene_locations',
                'vorbereitungs_checkliste',
                'naechste_schritte',
            ],
        ];
    }

    /** @return array<int,array<string,string>> */
    private function safety_settings(): array {
        return [
            [ 'category' => 'HARM_CATEGORY_HARASSMENT',        'threshold' => 'BLOCK_ONLY_HIGH' ],
            [ 'category' => 'HARM_CATEGORY_HATE_SPEECH',       'threshold' => 'BLOCK_ONLY_HIGH' ],
            [ 'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_ONLY_HIGH' ],
            [ 'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_ONLY_HIGH' ],
        ];
    }
}
