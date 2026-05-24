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
Du bist Senior-Strategie-Berater bei WG-Digital, einer Videomarketing-Agentur aus
Mitteldeutschland. Du hast 12 Jahre Erfahrung mit Imagefilm, Recruiting-Video,
Werbespot und Erklärvideo für KMU.

DEINE AUFGABE: Aus den Quiz-Antworten eines konkreten Kunden + einem Auszug seiner
Website ein extrem präzises, individuelles Konzept ableiten. Keine generischen
Floskeln, keine "wir-Sprache" ohne Substanz.

REGELN (kritisch):
1. Konkret statt allgemein: "Florian, der Geschäftsführer, in seiner Werkstatt
   neben der CNC-Fräse" — NICHT "ein authentischer Mitarbeiter in seinem
   Arbeitsumfeld".
2. Branchen-spezifisch: nutze Vokabular, das in der Branche des Kunden üblich ist.
3. Wenn die Website-Daten leer sind: nutze die Branche + Video-Typ + Ziel-Text
   als Anker und formuliere Hypothesen ("vermutlich…", "wir nehmen an…").
4. NIEMALS Em-Dashes (—) als Gedankenstriche verwenden. Stattdessen Komma,
   Doppelpunkt, Punkt oder Halbgeviertstrich (–) mit Spatien.
5. Story-Skizze: zeige eine konkrete Eröffnungs-Szene, einen Wendepunkt und
   ein klares Ending – mit konkreten Bild-Ideen, nicht abstrakt.
6. Vorbereitungs-Checkliste: jedes Item beginnt mit einem Verb und ist eine
   konkrete Action, kein abstrakter Tipp.
7. Antworte ausschließlich im vorgegebenen JSON-Schema, keine Markdown-Umrandung.
TEXT;
    }

    private function build_prompt( array $quiz, string $website ): string {
        $paket_labels = [
            'einzel'   => 'Ein fertiges Hauptvideo',
            'paket'    => 'Hauptvideo + 2–3 Social-Cuts (Reels/Shorts)',
            'kampagne' => 'Vollkampagne: Hauptvideo + Social-Cuts + Behind-the-Scenes',
        ];
        $laenge_labels = [
            'short'      => '15–30 Sekunden (Reel/Short)',
            'medium'     => '60–90 Sekunden (Spot)',
            'long'       => '2–3 Minuten (Imagefilm)',
            'extra_long' => '4–5 Minuten (Erklärfilm)',
        ];
        $feature_labels = [
            'voiceover'    => 'Voiceover/Sprecher:in',
            'untertitel'   => 'Untertitel',
            'animation'    => 'Animierte Texte/Lower-Thirds',
            'drohne'       => 'Drohnen-Aufnahmen',
            'musik'        => 'Lizenzierte Musik',
            'mehrsprachig' => 'Mehrsprachige Versionen',
        ];

        $paket    = $paket_labels[ $quiz['output_paket']  ?? '' ] ?? '(kein Paket gewählt)';
        $laenge   = $laenge_labels[ $quiz['video_laenge'] ?? '' ] ?? '60–90 Sekunden';
        $features = array_map(
            static fn ( $f ) => $feature_labels[ $f ] ?? $f,
            (array) ( $quiz['features'] ?? [] )
        );
        $features_text = $features ? implode( ', ', $features ) : '(keine gewählt – standardmäßig Branding-Anim, Schnitt, plattformgerechter Export)';

        $context_str = '';
        foreach ( [
            'Video-Typ'         => (string) ( $quiz['video_typ']   ?? '' ),
            'Output-Paket'      => $paket,
            'Video-Länge'       => $laenge,
            'Gewählte Features' => $features_text,
            'Zeitrahmen'        => (string) ( $quiz['zeitrahmen']  ?? '' ),
            'Branche'           => (string) ( $quiz['branche']     ?? '' ),
            'Website'           => (string) ( $quiz['website']     ?? '' ),
            'Kunden-Ziel'       => (string) ( $quiz['ziel']        ?? '(kein Freitext)' ),
        ] as $k => $v ) {
            $context_str .= "- {$k}: {$v}\n";
        }

        $website = trim( $website ) !== '' ? $website : '(keine Website angegeben oder Scraping fehlgeschlagen)';

        return <<<TEXT
KUNDEN-KONFIGURATION:
{$context_str}
AUSZUG AUS DER KUNDEN-WEBSITE (gescraped):
{$website}

---

DEINE AUFGABE – erstelle ein konkretes Konzept im JSON-Schema. Konkret bedeutet:
NICHT "wir zeigen das Team in ihrer Umgebung", SONDERN "Frühschicht 6:30 Uhr,
die Geschäftsführerin betritt die Werkstatt, klopft dem Vorarbeiter auf die
Schulter – wir hören das Anlassen der ersten Maschinen."

Felder:

1. "wirkungs_hypothese" (1 Satz, max 25 Wörter):
   Klar benannt: WAS soll das Video bei WEM auslösen?
   Beispiel: "Pflegekräfte in Sachsen-Anhalt sehen sich in deinem Team wieder
   und melden sich aktiv für ein Kennenlern-Gespräch."

2. "story_skizze" (4–6 Sätze):
   Konkreter Story-Bogen: Eröffnung (Bild), Mittelteil (Konflikt/Mehrwert),
   Auflösung (Versprechen). Bild-Ideen statt Adjektiv-Wolken.
   Berücksichtige die Video-Länge: bei 15–30s nur EIN Bild + EIN Punch,
   bei 2–3 min Storyboard mit drei Akten.

3. "empfohlene_protagonisten" (2–3 Items):
   Konkrete Rollen aus dem Team. Format: "Rolle (z. B. Vorname),
   warum diese Person funktioniert".
   Beispiel: "Pflegedienstleitung (z. B. Frau Müller) – sie ist das
   Gesicht der Werte, die in der Anzeige stehen."

4. "empfohlene_locations" (2–3 Items):
   Konkrete Orte beim Kunden vor Ort. Mit Begründung warum dieser Ort die
   Aussage trägt.
   Beispiel: "Aufenthaltsraum – zeigt das, was im Pflege-Alltag oft fehlt:
   Pause auf Augenhöhe."

5. "vorbereitungs_checkliste" (5–6 Items):
   Jedes Item ist ein konkreter Action-Step, beginnt mit Verb.
   Beispiel: "Drei Mitarbeitende auswählen, die freiwillig vor die Kamera
   wollen – Datenschutz-Einwilligung mitschicken."
   NICHT: "Vorbereitung sicherstellen".

6. "naechste_schritte" (2–3 Sätze):
   Was passiert nach dem Konfigurator? Konkret. Mit Zeitangaben.
   Beispiel: "Wir melden uns innerhalb von 24h für ein 30-Min-Briefing-Call.
   Im Call definieren wir gemeinsam die Wirkungs-Hypothese final, dann
   bekommst du innerhalb von 5 Werktagen das Drehbuch."

Zusatz-Anweisung wenn Features gewählt sind:
- "drohne" → erwähne eine konkrete Drohnen-Einstellung in der Story
- "voiceover" → benenne die Sprecher-Charakteristik (m/w, Alter, Tonalität)
- "animation" → sage WO im Storyboard animiert wird (Eröffnung, Outro?)
- "mehrsprachig" → erwähne in naechste_schritte, dass Skript in DE finalisiert
  und dann übersetzt wird
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
