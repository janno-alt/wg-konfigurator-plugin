<?php
declare( strict_types=1 );

namespace WG\Konfigurator\Rest;

use Throwable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WG\Konfigurator\Admin\Settings;
use WG\Konfigurator\Services\EmailDomain;
use WG\Konfigurator\Services\GeminiClient;
use WG\Konfigurator\Services\Mailer;
use WG\Konfigurator\Services\PdfGenerator;
use WG\Konfigurator\Services\PriceCalculator;
use WG\Konfigurator\Services\Recommender;
use WG\Konfigurator\Services\WebhookSender;
use WG\Konfigurator\Services\WebsiteScraper;

/**
 * POST /wp-json/wg-konfigurator/v1/generate
 *
 * Erwartet:
 *   - lead.vorname (string, required)
 *   - lead.email   (email, required)
 *   - lead.marketing_opt_in (bool)
 *   - quiz.video_typ, quiz.drehtage, quiz.zeitrahmen, quiz.branche, quiz.website, quiz.ziel
 *   - tracking.msclkid, tracking.utm_source, tracking.utm_campaign
 *   - recaptcha_token (optional, wenn reCAPTCHA aktiviert ist)
 *
 * Antwort:
 *   200 { ok:true, pdf_url, preis_min, preis_max, naechste_schritte }
 *   429 / 400 / 500 mit { code, message }
 */
final class GenerateEndpoint {

    public function register_routes(): void {
        register_rest_route(
            'wg-konfigurator/v1',
            '/generate',
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'handle' ],
                'permission_callback' => '__return_true',
                'args'                => $this->args_schema(),
            ]
        );
    }

    /**
     * @return array<string,mixed>
     */
    private function args_schema(): array {
        return [
            'lead'           => [ 'required' => true, 'type' => 'object' ],
            'quiz'           => [ 'required' => true, 'type' => 'object' ],
            'tracking'       => [ 'required' => false, 'type' => 'object' ],
            'recaptcha_token'=> [ 'required' => false, 'type' => 'string' ],
        ];
    }

    public function handle( WP_REST_Request $request ) {
        $settings = Settings::get();

        // ----- Rate-Limit -----
        $ip       = $this->client_ip( $request );
        $limit    = max( 1, (int) $settings['rate_limit_per_h'] );
        $key      = 'wg_konfig_rl_' . md5( $ip );
        $current  = (int) get_transient( $key );
        if ( $current >= $limit ) {
            return new WP_Error( 'rate_limit', 'Zu viele Anfragen. Bitte später erneut.', [ 'status' => 429 ] );
        }
        set_transient( $key, $current + 1, HOUR_IN_SECONDS );

        // ----- Input -----
        $lead     = (array) $request->get_param( 'lead' );
        $quiz     = (array) $request->get_param( 'quiz' );
        $tracking = (array) ( $request->get_param( 'tracking' ) ?? [] );

        // Vollname (Vor- und Nachname). Wir splitten Vor-/Nachname für CRM-Kompat.
        $full_name = trim( (string) ( $lead['name'] ?? $lead['vorname'] ?? '' ) );
        $full_name = sanitize_text_field( $full_name );
        $name_parts = preg_split( '/\s+/', $full_name, 2 );
        $vorname  = $name_parts[0] ?? '';
        $nachname = $name_parts[1] ?? '';

        $lead = [
            'name'             => $full_name,
            'vorname'          => $vorname,
            'nachname'         => $nachname,
            'email'            => sanitize_email( (string) ( $lead['email'] ?? '' ) ),
            'dsgvo_opt_in'     => ! empty( $lead['dsgvo_opt_in'] ),
            'marketing_opt_in' => ! empty( $lead['marketing_opt_in'] ),
        ];

        if ( $full_name === '' || $nachname === '' || ! is_email( $lead['email'] ) ) {
            return new WP_Error( 'invalid_lead', 'Vor- und Nachname sowie gültige E-Mail erforderlich.', [ 'status' => 400 ] );
        }

        // Neue feature-orientierte Felder
        $features_raw = (array) ( $quiz['features'] ?? [] );
        $features     = array_values( array_filter( array_map(
            'sanitize_text_field',
            array_map( 'strval', $features_raw )
        ) ) );

        // Website normalisieren: User darf "deine-firma.de" eingeben ohne https://
        $raw_website = trim( (string) ( $quiz['website'] ?? '' ) );
        if ( $raw_website !== '' && ! preg_match( '#^https?://#i', $raw_website ) ) {
            $raw_website = 'https://' . ltrim( $raw_website, '/' );
        }
        $website = $raw_website !== '' ? esc_url_raw( $raw_website ) : '';

        // v0.9: Goal + Budget kommen vom Client. Wir berechnen die Recommendation
        // server-seitig (Source of Truth) und überschreiben damit ggf. die
        // Client-Werte (falls die JS-Logik mal aus dem Sync läuft).
        $goal   = sanitize_text_field( (string) ( $quiz['goal']   ?? '' ) );
        $budget = sanitize_text_field( (string) ( $quiz['budget'] ?? 'unknown' ) );

        $rec = $goal !== '' ? Recommender::recommend( $goal, $budget ) : null;

        $quiz = [
            // Roh-Antworten
            'goal'         => $goal,
            'budget'       => $budget,
            'zeitrahmen'   => sanitize_text_field( (string) ( $quiz['zeitrahmen'] ?? '' ) ),
            'branche'      => sanitize_text_field( (string) ( $quiz['branche']    ?? '' ) ),
            'website'      => $website,
            'ziel'         => sanitize_textarea_field( (string) ( $quiz['ziel']    ?? '' ) ),
            // Recommendation-Output (Source of Truth = Server)
            'video_typ'    => $rec ? $rec['video_typ']    : sanitize_text_field( (string) ( $quiz['video_typ']    ?? 'werbespot' ) ),
            'output_paket' => $rec ? $rec['output_paket'] : sanitize_text_field( (string) ( $quiz['output_paket'] ?? '' ) ),
            'video_laenge' => $rec ? $rec['video_laenge'] : sanitize_text_field( (string) ( $quiz['video_laenge'] ?? 'medium' ) ),
            'features'     => $rec ? $rec['features']     : $features,
            // Reasoning für KI-Prompt
            'recommendation_reasoning' => $rec['reasoning_short'] ?? '',
        ];

        $tracking = [
            'msclkid'      => sanitize_text_field( (string) ( $tracking['msclkid']      ?? '' ) ),
            'utm_source'   => sanitize_text_field( (string) ( $tracking['utm_source']   ?? '' ) ),
            'utm_medium'   => sanitize_text_field( (string) ( $tracking['utm_medium']   ?? '' ) ),
            'utm_campaign' => sanitize_text_field( (string) ( $tracking['utm_campaign'] ?? '' ) ),
        ];

        // ----- reCAPTCHA (optional) -----
        $recaptcha_check = $this->verify_recaptcha(
            (string) $request->get_param( 'recaptcha_token' ),
            $ip,
            $settings
        );
        if ( $recaptcha_check instanceof WP_Error ) {
            return $recaptcha_check;
        }

        // ----- Pipeline -----
        try {
            // Website-Priorität: User-Eingabe > Email-Domain-Auto-Discovery
            $website_for_scrape = $quiz['website'];
            $website_from_email = false;
            if ( $website_for_scrape === '' ) {
                $inferred = EmailDomain::infer_website( $lead['email'] );
                if ( $inferred !== null ) {
                    $website_for_scrape = $inferred;
                    $website_from_email = true;
                }
            }

            $scraper = new WebsiteScraper();
            $excerpt = $website_for_scrape !== '' ? $scraper->scrape( $website_for_scrape ) : '';

            // Wenn nichts gescraped werden konnte (weder User-Website noch Email-Domain
            // hat Inhalte geliefert), kein Gemini-Call — wir generieren das PDF mit
            // einem Hinweis-Block statt einem schwachen Konzept.
            $has_website_context = trim( $excerpt ) !== '';

            if ( $has_website_context ) {
                $gemini  = new GeminiClient();
                $concept = $gemini->generate_concept( $quiz, $excerpt );
            } else {
                $concept = $this->fallback_concept_no_website( $quiz );
            }

            // Im Quiz für die spätere Anzeige (PDF/Mail) das tatsächlich verwendete
            // Website-Feld setzen, damit klar ist, woher wir die Daten haben.
            if ( $website_from_email && $has_website_context ) {
                $quiz['website'] = $website_for_scrape;
                $quiz['website_source'] = 'email_domain';
            } elseif ( $quiz['website'] !== '' ) {
                $quiz['website_source'] = 'user';
            } else {
                $quiz['website_source'] = 'none';
            }

            $calc    = new PriceCalculator();
            $pricing = $calc->calculate( $quiz );

            $pdfgen  = new PdfGenerator();
            $pdf     = $pdfgen->render( [
                'lead'                  => $lead,
                'quiz'                  => $quiz,
                'pricing'               => $pricing,
                'concept'               => $concept,
                'generated_at'          => gmdate( 'c' ),
                'placeholder_cover_path'=> WG_KONFIGURATOR_DIR . 'assets/img/pdf-cover-placeholder.png',
            ] );

            $mailer = new Mailer();
            $mailer->send_customer( $lead, $pdf, $concept );
            $mailer->send_admin( $lead, $quiz, $pricing, $pdf );

            // ---- CRM-Mapping ---------------------------------------------------
            // Das CRM erwartet ein strikteres Schema (drehtage:int, kein output_paket).
            // Wir mappen unsere feature-orientierten Antworten in das alte Schema und
            // hängen die neuen Felder zusätzlich an, damit das CRM sie im rawPayload
            // mitspeichert.
            $features_label = PriceCalculator::feature_labels( $quiz['features'] );
            $type_label     = PriceCalculator::type_label( $quiz['video_typ'] );
            $paket_label    = $quiz['output_paket'] ? PriceCalculator::paket_label( $quiz['output_paket'] ) : '';
            $length_label   = $quiz['video_laenge'] ? PriceCalculator::length_label( $quiz['video_laenge'] ) : '';

            $ziel_kombiniert = trim( implode( ' | ', array_filter( [
                'Typ: ' . $type_label,
                $paket_label,
                $length_label ? 'Länge: ' . $length_label : '',
                $features_label ? 'Features: ' . implode( ', ', $features_label ) : '',
                $quiz['ziel'] ?: '',
            ] ) ) );

            $quiz_for_crm = [
                'video_typ'    => $quiz['video_typ'],
                'drehtage'     => (int) $pricing['drehtage'],     // intern abgeleitet
                'zeitrahmen'   => $quiz['zeitrahmen'],
                'branche'      => $quiz['branche'],
                'website'      => $quiz['website'],
                'ziel'         => $ziel_kombiniert,
                // Zusatz-Felder (gehen ins rawPayload-JSONB)
                'output_paket' => $quiz['output_paket'],
                'video_laenge' => $quiz['video_laenge'],
                'features'     => $quiz['features'],
            ];

            // Webhook synchron senden (auf Mittwald + DISABLE_WP_CRON ist async unzuverlässig).
            // session_id wenn vorhanden = der gleiche idempotency_key wie beim
            // konfigurator.started Event → CRM kann den started-Lead auf
            // completed updaten statt neu anzulegen.
            $session_id   = sanitize_text_field( (string) ( $request->get_param( 'session_id' ) ?? '' ) );
            $idempotency  = $session_id ?: wp_generate_uuid4();
            $webhook      = new WebhookSender();
            $webhook_result = $webhook->dispatch( [
                'event'           => 'konfigurator.completed',
                'idempotency_key' => $idempotency,
                'session_id'      => $session_id ?: null,
                'lead'            => $lead,
                'quiz'            => $quiz_for_crm,
                'berechnung'      => $pricing,
                'ki_konzept'      => $concept,
                'tracking'        => $tracking,
                'pdf_url'         => $pdf['url'],
                'generated_at'    => gmdate( 'c' ),
            ] );

            if ( ! $webhook_result['ok'] ) {
                error_log( sprintf(
                    '[wg-konfigurator] Webhook nicht erfolgreich: status=%d, attempts=%d, body=%s',
                    $webhook_result['status'],
                    $webhook_result['attempts'],
                    substr( $webhook_result['body'], 0, 300 )
                ) );
            }

        } catch ( Throwable $e ) {
            error_log( '[wg-konfigurator] Pipeline-Fehler: ' . $e->getMessage() );
            return new WP_Error(
                'pipeline_error',
                'Etwas ist schiefgelaufen. Wir wurden benachrichtigt.',
                [ 'status' => 500 ]
            );
        }

        return new WP_REST_Response( [
            'ok'                => true,
            'pdf_url'           => $pdf['url'],
            'preis_min'         => $pricing['preis_min'],
            'preis_max'         => $pricing['preis_max'],
            'express_aufschlag' => $pricing['express_aufschlag'],
            'naechste_schritte' => $concept['naechste_schritte'] ?? '',
        ], 200 );
    }

    /**
     * Wenn weder User-Website noch Email-Domain Inhalte geliefert haben,
     * generieren wir KEIN KI-Konzept (das wäre nur Schauspielerei).
     * Stattdessen liefert das PDF einen klaren Hinweis + Termin-CTA.
     *
     * @return array<string,mixed>
     */
    private function fallback_concept_no_website( array $quiz ): array {
        $branche = $quiz['branche'] ?: 'deinem Unternehmen';
        return [
            '_no_website' => true,
            'wirkungs_hypothese'         => sprintf(
                'Für ein wirklich passgenaues Konzept brauchen wir Einblick in %s. Lass uns 30 Minuten reden.',
                $branche
            ),
            'typ_empfehlung_begruendung' => '',
            'unternehmens_analyse'       => 'Eine individuelle Analyse konnten wir hier nicht durchführen, weil uns keine Website-Information vorlag (weder im Feld Website noch über die E-Mail-Domain). Wir würden uns nicht erlauben, an dieser Stelle Hypothesen über dein Geschäft zu erfinden – das wäre weder ehrlich noch hilfreich.',
            'video_botschaften'          => [
                'Buche einen 30-Min-Termin – dann hörst du eine konkrete Einschätzung statt KI-Vermutungen.',
                'Schicke uns kurz einen Link zu eurer Website oder eurem Profil per Mail – wir liefern dir das Konzept dann persönlich nach.',
            ],
            'marketing_strategie'        => '',
            'empfohlene_protagonisten'   => [],
            'empfohlene_locations'       => [],
            'vorbereitungs_checkliste'   => [],
            'naechste_schritte'          => 'Buche dir direkt einen unverbindlichen 30-Minuten-Slot – dort definieren wir gemeinsam das Konzept und du bekommst eine konkrete Einschätzung.',
        ];
    }

    private function client_ip( WP_REST_Request $request ): string {
        $candidates = [
            $request->get_header( 'cf-connecting-ip' ),
            $request->get_header( 'x-forwarded-for' ),
            $_SERVER['REMOTE_ADDR'] ?? '',
        ];
        foreach ( $candidates as $c ) {
            $c = trim( explode( ',', (string) $c )[0] ?? '' );
            if ( filter_var( $c, FILTER_VALIDATE_IP ) ) {
                return $c;
            }
        }
        return '0.0.0.0';
    }

    private function verify_recaptcha( string $token, string $ip, array $settings ) {
        $secret = (string) ( $settings['recaptcha_secret'] ?? '' );
        if ( $secret === '' ) {
            return null; // nicht aktiviert
        }
        if ( $token === '' ) {
            return new WP_Error( 'recaptcha_missing', 'Captcha fehlt.', [ 'status' => 400 ] );
        }

        $response = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', [
            'timeout' => 8,
            'body'    => [
                'secret'   => $secret,
                'response' => $token,
                'remoteip' => $ip,
            ],
        ] );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'recaptcha_unavailable', 'Captcha-Verifizierung nicht möglich.', [ 'status' => 503 ] );
        }
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $body['success'] ) || ( isset( $body['score'] ) && $body['score'] < 0.4 ) ) {
            return new WP_Error( 'recaptcha_failed', 'Captcha-Prüfung fehlgeschlagen.', [ 'status' => 400 ] );
        }
        return null;
    }
}
