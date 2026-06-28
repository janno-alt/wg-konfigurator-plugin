<?php
declare( strict_types=1 );

namespace WG\Konfigurator\Services;

/**
 * Trägt einwilligende Leads in eine MailPoet-Liste ein.
 *
 * Den Double-Opt-in (Bestätigungsmail + Nachweis) übernimmt MailPoet selbst,
 * solange dort die Anmelde-Bestätigung aktiv ist. Wir setzen daher
 * send_confirmation_email = true und tragen den Abonnenten als "unconfirmed"
 * ein – MailPoet schickt die Bestätigung und setzt ihn nach Klick auf
 * "subscribed".
 *
 * Nutzt die offizielle MailPoet-API: \MailPoet\API\API::MP('v1').
 */
final class MailPoetSync {

    public static function is_available(): bool {
        return class_exists( '\MailPoet\API\API' );
    }

    /**
     * Verfügbare Listen (id => name) für die Settings-Auswahl.
     *
     * @return array<int,string>
     */
    public static function lists(): array {
        if ( ! self::is_available() ) {
            return [];
        }
        try {
            $mp  = \MailPoet\API\API::MP( 'v1' );
            $out = [];
            foreach ( (array) $mp->getLists() as $list ) {
                $id = (int) ( $list['id'] ?? 0 );
                if ( $id > 0 ) {
                    $out[ $id ] = (string) ( $list['name'] ?? ( 'Liste ' . $id ) );
                }
            }
            return $out;
        } catch ( \Throwable $e ) {
            return [];
        }
    }

    /**
     * @param array<string,mixed> $lead   sanitisierter Lead (email, vorname, nachname)
     * @return array{ok:bool,status:string,message:string}
     */
    public static function subscribe( array $lead, int $list_id ): array {
        if ( $list_id <= 0 ) {
            return [ 'ok' => false, 'status' => 'disabled', 'message' => 'Keine MailPoet-Liste konfiguriert.' ];
        }
        if ( ! self::is_available() ) {
            return [ 'ok' => false, 'status' => 'unavailable', 'message' => 'MailPoet ist nicht aktiv.' ];
        }

        $email = sanitize_email( (string) ( $lead['email'] ?? '' ) );
        if ( ! is_email( $email ) ) {
            return [ 'ok' => false, 'status' => 'invalid_email', 'message' => 'Ungültige E-Mail-Adresse.' ];
        }

        $options = [
            'send_confirmation_email' => true,   // -> MailPoet Double-Opt-in
            'schedule_welcome_email'  => true,
        ];

        try {
            $mp = \MailPoet\API\API::MP( 'v1' );

            // Bestehenden Abonnenten nur der Liste zuordnen, sonst neu anlegen.
            $existing = null;
            try {
                $existing = $mp->getSubscriber( $email );
            } catch ( \Throwable $e ) {
                $existing = null;
            }

            if ( is_array( $existing ) && ! empty( $existing['id'] ) ) {
                $mp->subscribeToList( (int) $existing['id'], $list_id, $options );
                return [ 'ok' => true, 'status' => 'added_to_list', 'message' => 'Bestehender Abonnent zur Liste hinzugefügt.' ];
            }

            $mp->addSubscriber(
                [
                    'email'      => $email,
                    'first_name' => (string) ( $lead['vorname']  ?? '' ),
                    'last_name'  => (string) ( $lead['nachname'] ?? '' ),
                ],
                [ $list_id ],
                $options
            );
            return [ 'ok' => true, 'status' => 'subscribed', 'message' => 'In MailPoet eingetragen, Double-Opt-in ausgelöst.' ];

        } catch ( \Throwable $e ) {
            return [ 'ok' => false, 'status' => 'error', 'message' => $e->getMessage() ];
        }
    }
}
