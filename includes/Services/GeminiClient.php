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

    /**
     * Konzept für die fokussierten Produkte (recruiting, social). Nutzt dasselbe
     * Response-Schema, aber produkt-spezifische System-/User-Prompts.
     *
     * @param array<string,mixed> $quiz
     * @return array<string,mixed>
     */
    public function generate_product_concept( array $quiz, string $website_excerpt, string $product ): array {
        $settings = Settings::get();
        $api_key  = trim( (string) $settings['gemini_api_key'] );
        if ( $api_key === '' ) {
            throw new RuntimeException( 'Gemini API-Key fehlt in den Plugin-Einstellungen.' );
        }

        $payload = [
            'contents'          => [ [ 'role' => 'user', 'parts' => [ [ 'text' => $this->product_prompt( $quiz, $website_excerpt, $product ) ] ] ] ],
            'systemInstruction' => [ 'parts' => [ [ 'text' => $this->product_system_prompt( $product ) ] ] ],
            'generationConfig'  => [
                'temperature'      => 0.6,
                'topP'             => 0.9,
                'responseMimeType' => 'application/json',
                'responseSchema'   => $this->response_schema(),
            ],
            'safetySettings'    => $this->safety_settings(),
        ];

        $url = sprintf( self::ENDPOINT, rawurlencode( (string) $settings['gemini_model'] ), rawurlencode( $api_key ) );
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
        $text = json_decode( $body, true )['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if ( ! is_string( $text ) ) {
            throw new RuntimeException( 'Gemini-Response ohne erwarteten Text-Part.' );
        }
        $concept = json_decode( $text, true );
        if ( ! is_array( $concept ) ) {
            throw new RuntimeException( 'Gemini-Response war kein gültiges JSON.' );
        }
        return $concept;
    }

    private function product_system_prompt( string $product ): string {
        $rolle = $product === 'social'
            ? 'Senior-Social-Media-Stratege bei WG-Digital. Du betreust laufend Social-Media-Kanäle (Instagram, Facebook, LinkedIn, TikTok) für KMU in Mitteldeutschland.'
            : 'Senior-Recruiting-Stratege bei WG-Digital. Du gewinnst für KMU in Mitteldeutschland mit Recruiting-Videos und Social-Recruiting-Kampagnen passende Bewerber.';
        return <<<TEXT
Du bist {$rolle}

DEINE AUFGABE: Aus den Antworten eines konkreten Kunden plus einem Auszug seiner
Website eine präzise, individuelle Einschätzung ableiten. Keine Floskeln.

REGELN (kritisch):
1. Konkret statt allgemein, mit Branchen-Vokabular des Kunden.
2. Wenn Website-Daten leer sind: aus Branche + Zielen Hypothesen ableiten und als
   "vermutlich…" / "wir nehmen an…" kennzeichnen.
3. NIEMALS Em-Dashes (—) verwenden. Stattdessen Komma, Doppelpunkt oder Punkt.
4. Antworte ausschließlich im vorgegebenen JSON-Schema, keine Markdown-Umrandung.
TEXT;
    }

    private function product_prompt( array $quiz, string $website, string $product ): string {
        $website = trim( $website ) !== '' ? $website : '(keine Website angegeben oder Scraping fehlgeschlagen)';
        $branche = (string) ( $quiz['branche'] ?? '' );
        $ziel    = (string) ( $quiz['ziel'] ?? '(kein Freitext)' );

        if ( $product === 'social' ) {
            $ctx = "- Branche: {$branche}\n"
                 . '- Plattform-Umfang: ' . (string) ( $quiz['plattformen'] ?? '' ) . "\n"
                 . '- Content-Menge/Monat: ' . (string) ( $quiz['content'] ?? '' ) . "\n"
                 . '- Werbeanzeigen gewünscht: ' . (string) ( $quiz['ads'] ?? '' ) . "\n"
                 . "- Freitext: {$ziel}\n";
            $felder = <<<TEXT
Fülle die Felder für eine SOCIAL-MEDIA-BETREUUNG (kein Video-Dreh):
- "wirkungs_hypothese": 1 Satz, was die Betreuung bei wem auslösen soll.
- "typ_empfehlung_begruendung": warum der gewählte Paket-Umfang zu diesem Kunden passt.
- "unternehmens_analyse": 3-5 Sätze konkrete Beobachtungen.
- "video_botschaften": 4-6 konkrete Content-Ideen/Rubriken für die Kanäle (z. B. Behind-the-Scenes, Mitarbeiter-Spotlight).
- "marketing_strategie": 3-5 Sätze, wie Organisch + Ads zusammenspielen, welche Kanäle, wie Erfolg gemessen wird.
- "empfohlene_protagonisten": 2-3 Personen/Rollen im Team, die Content liefern können.
- "empfohlene_locations": 2-3 wiederkehrende Content-Anlässe oder Drehorte im Betrieb.
- "vorbereitungs_checkliste": 5-6 konkrete Onboarding-Schritte (mit Verb beginnend).
- "naechste_schritte": 2-3 Sätze mit Zeitangabe.
TEXT;
        } else {
            $ctx = "- Branche/Berufsfeld: {$branche}\n"
                 . '- Offene Stellen: ' . (string) ( $quiz['stellen'] ?? '' ) . "\n"
                 . '- Video-Umfang: ' . (string) ( $quiz['rec_video'] ?? '' ) . "\n"
                 . '- Kampagne gewünscht: ' . (string) ( $quiz['rec_kampagne'] ?? '' ) . "\n"
                 . '- Bewerber-Landingpage: ' . (string) ( $quiz['rec_lp'] ?? '' ) . "\n"
                 . "- Freitext: {$ziel}\n";
            $felder = <<<TEXT
Fülle die Felder für ein RECRUITING-VIDEO mit optionaler Social-Recruiting-Kampagne:
- "wirkungs_hypothese": 1 Satz, welche Bewerber:innen sich nach dem Sehen melden sollen.
- "typ_empfehlung_begruendung": warum dieses Recruiting-Setup für die Branche passt.
- "unternehmens_analyse": 3-5 Sätze, was diesen Arbeitgeber attraktiv macht.
- "video_botschaften": 4-6 konkrete Punkte, die im Recruiting-Video gezeigt werden sollten.
- "marketing_strategie": 3-5 Sätze zur Ausspielung (Kanäle, Targeting, Bewerber-Landingpage, Messung).
- "empfohlene_protagonisten": 2-3 echte Rollen aus dem Team vor der Kamera.
- "empfohlene_locations": 2-3 konkrete Drehorte im Betrieb.
- "vorbereitungs_checkliste": 5-6 konkrete Action-Steps (mit Verb beginnend).
- "naechste_schritte": 2-3 Sätze mit Zeitangabe.
TEXT;
        }

        return <<<TEXT
KUNDEN-KONFIGURATION:
{$ctx}
AUSZUG AUS DER KUNDEN-WEBSITE (gescraped):
{$website}

---
{$felder}

WICHTIG: Standard-Inklusivleistungen nicht als Botschaft auflisten.
TEXT;
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
        $type_label  = \WG\Konfigurator\Services\PriceCalculator::type_label( (string) ( $quiz['video_typ'] ?? '' ) );
        $paket_label = $quiz['output_paket']
            ? \WG\Konfigurator\Services\PriceCalculator::paket_label( (string) $quiz['output_paket'] )
            : '(kein Paket gewählt – kein klassisches Drehprojekt)';
        $laenge_label = $quiz['video_laenge']
            ? \WG\Konfigurator\Services\PriceCalculator::length_label( (string) $quiz['video_laenge'] )
            : '60–90 Sekunden';
        $goal_label   = \WG\Konfigurator\Services\Recommender::goal_label( (string) ( $quiz['goal']   ?? '' ) );
        $budget_label = \WG\Konfigurator\Services\Recommender::budget_label( (string) ( $quiz['budget'] ?? 'unknown' ) );

        $feature_labels = \WG\Konfigurator\Services\PriceCalculator::feature_labels(
            (array) ( $quiz['features'] ?? [] )
        );
        $features_text = $feature_labels
            ? implode( ', ', $feature_labels )
            : '(keine zusätzlichen Features gewählt – Standard: Untertitel + lizenzierte Musik immer inklusive)';

        $rec_reasoning = (string) ( $quiz['recommendation_reasoning'] ?? '' );

        $context_str = '';
        foreach ( [
            'KUNDEN-ZIEL'             => $goal_label,
            'BUDGET-RAHMEN'           => $budget_label,
            'EMPFOHLENER VIDEO-TYP'   => $type_label,
            'EMPFEHLUNGS-BEGRÜNDUNG (Plugin-Auto)' => $rec_reasoning,
            'Empfohlenes Output-Paket'=> $paket_label,
            'Empfohlene Länge'        => $laenge_label,
            'Empfohlene Features'     => $features_text,
            'Zeitrahmen'              => (string) ( $quiz['zeitrahmen']  ?? '' ),
            'Branche'                 => (string) ( $quiz['branche']     ?? '' ),
            'Website'                 => (string) ( $quiz['website']     ?? '' ),
            'Freitext-Hinweis Kunde'  => (string) ( $quiz['ziel']        ?? '(kein Freitext)' ),
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

DEINE AUFGABE – analysiere das Unternehmen und leite ab, welche Punkte im
gewünschten Video herausgestellt werden sollten. Keine konkrete Story erfinden!
Wir wollen ANALYSE, nicht Drehbuch.

Felder:

1. "wirkungs_hypothese" (1 Satz, max 25 Wörter):
   Klar benannt: WAS soll das Video bei WEM auslösen?
   Beispiel: "Pflegekräfte in Sachsen-Anhalt sehen sich in deinem Team wieder
   und melden sich aktiv für ein Kennenlern-Gespräch."

1b. "typ_empfehlung_begruendung" (3–5 Sätze) — NEU IN v0.9:
   Warum genau dieser Video-Typ für DIESES KUNDEN-ZIEL und DIESE BRANCHE
   die richtige Wahl ist. Die Plugin-Auto-Begründung ist generisch — du
   personalisierst sie auf die Branche, Ziel und ggf. Website-Inhalte.
   Beispiel: "Für eure Pflegedienstleistung in Sachsen-Anhalt empfehlen wir
   einen Imagefilm in 2-3-Min.-Form: Pflege ist ein Vertrauens-Markt, in dem
   eure Werte und Persönlichkeit am ehesten überzeugen. Die längere Form
   gibt Raum, drei konkrete Mitarbeiter:innen zu zeigen — die wahre Differenzierung
   gegenüber Wettbewerbern, die nur Werbe-Slogans liefern."

1c. "marketing_strategie" (3–5 Sätze) — NEU IN v0.9:
   Wie sich dieses Video in eine ganze Marketing-Strategie einbettet.
   Konkrete Hebel: WO wird es eingesetzt? Welche Kanäle? Welche
   Folge-Maßnahmen sind sinnvoll? Wie misst man Erfolg?
   Beispiel: "Spielt den Imagefilm auf eurer Karriere-Seite als Hero-Video aus,
   schneidet ihn zu 3 LinkedIn-Posts für die nächsten Monate und verwendet
   einzelne Statements als Hooks für gezielte Instagram-Ads in einem
   30-km-Radius um Magdeburg. Messt Bewerbungen pro Quelle — der Spot wird
   sich nach ca. 4–6 Wochen rechnen."

2. "unternehmens_analyse" (3–5 Sätze):
   Was macht dieses Unternehmen konkret? Was sind die Stärken, die im
   gewählten Video-Typ (Imagefilm/Recruiting/Spot/Erklär) als Differenzierung
   funktionieren? Wenn Website-Daten vorhanden: zitiere konkret. Wenn nicht:
   leite aus Branche und Ziel-Text plausible Hypothesen ab und kennzeichne
   als "vermutlich…" / "wir nehmen an…".
   NICHT: Marketing-Floskeln. SONDERN: konkrete Beobachtungen.
   Beispiel: "Müller Pflegedienst betreut nach eigener Aussage 'jede Person
   wie ein eigenes Familienmitglied'. Dieses Versprechen ist im Recruiting-
   Markt selten konkret belegt — der Imagefilm sollte genau das in Szene
   setzen: drei Pflegekräfte zeigen, woran sie diesen Anspruch im Alltag
   festmachen."

3. "video_botschaften" (4–6 Bullet-Punkte):
   Welche konkreten Punkte sollten im gewählten Video herausgestellt werden,
   um das Wirkungs-Ziel zu erreichen? Jeder Bullet ist ein konkreter
   Inhalts-Punkt, kein Adjektiv.
   Beispiele:
   - "Echte Pflegekräfte als O-Ton-Geber:innen statt Stock-Footage."
   - "Konkrete Schicht-Modelle und Familien-Freundlichkeit zeigen, statt
     allgemein 'Work-Life-Balance' zu sagen."
   - "Die Pflegedienstleitung soll persönlich auftreten — gibt dem
     Versprechen ein Gesicht."

4. "empfohlene_protagonisten" (2–3 Items):
   Konkrete Rollen aus dem Team. Format: "Rolle, warum diese Person funktioniert".
   Beispiel: "Pflegedienstleitung – sie ist das Gesicht der Werte, die in
   der Stellenanzeige stehen."

5. "empfohlene_locations" (2–3 Items):
   Konkrete Orte beim Kunden vor Ort. Mit Begründung.
   Beispiel: "Aufenthaltsraum – zeigt, was im Pflege-Alltag oft fehlt:
   Pause auf Augenhöhe."

6. "vorbereitungs_checkliste" (5–6 Items):
   Jedes Item ist ein konkreter Action-Step, beginnt mit Verb.
   Beispiel: "Drei Mitarbeitende auswählen, die freiwillig vor die Kamera
   wollen — Datenschutz-Einwilligung mitschicken."

7. "naechste_schritte" (2–3 Sätze):
   Was passiert nach dem Konfigurator? Mit Zeitangaben.
   Beispiel: "Wir melden uns innerhalb von 24h für ein 30-Min-Briefing-Call.
   Im Call definieren wir gemeinsam die Wirkungs-Hypothese final, dann
   bekommst du innerhalb von 5 Werktagen das Drehbuch."

Zusatz-Anweisung wenn Features gewählt sind:
- "drohne" → erwähne eine konkrete Drohnen-Idee in video_botschaften
- "voiceover" → benenne die Sprecher-Charakteristik (m/w, Alter, Tonalität)
  als Bullet in video_botschaften
- "animation" → sage WO animiert werden sollte (z. B. "Lower-Thirds mit
  Mitarbeiter-Namen und Berufsbezeichnung")
- "mehrsprachig" → erwähne in naechste_schritte, dass Skript in DE finalisiert
  und dann übersetzt wird

WICHTIG: Standard-Inklusivleistungen NICHT als video_botschaft auflisten —
Untertitel, lizenzierte Musik, plattformgerechter Export und Branding sind
bei uns immer dabei und müssen NICHT in den Botschaften auftauchen.
TEXT;
    }

    /** @return array<string,mixed> */
    private function response_schema(): array {
        return [
            'type'       => 'object',
            'properties' => [
                'wirkungs_hypothese'           => [ 'type' => 'string' ],
                'typ_empfehlung_begruendung'   => [ 'type' => 'string' ],  // NEU v0.9
                'unternehmens_analyse'         => [ 'type' => 'string' ],
                'video_botschaften'            => [
                    'type'  => 'array',
                    'items' => [ 'type' => 'string' ],
                ],
                'marketing_strategie'          => [ 'type' => 'string' ],  // NEU v0.9
                'empfohlene_protagonisten'     => [
                    'type'  => 'array',
                    'items' => [ 'type' => 'string' ],
                ],
                'empfohlene_locations'         => [
                    'type'  => 'array',
                    'items' => [ 'type' => 'string' ],
                ],
                'vorbereitungs_checkliste'     => [
                    'type'  => 'array',
                    'items' => [ 'type' => 'string' ],
                ],
                'naechste_schritte'            => [ 'type' => 'string' ],
            ],
            'required'   => [
                'wirkungs_hypothese',
                'typ_empfehlung_begruendung',
                'unternehmens_analyse',
                'video_botschaften',
                'marketing_strategie',
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
