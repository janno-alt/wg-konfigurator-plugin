<?php
declare( strict_types=1 );

namespace WG\Konfigurator\Services;

/**
 * Versucht aus einer E-Mail-Adresse eine wahrscheinliche Firmen-Website
 * abzuleiten — solange die Domain keine bekannte Freemail-Domain ist.
 *
 * Mail an anna@pflegedienst-mueller.de → https://pflegedienst-mueller.de
 * Mail an anna@gmail.com              → null (Freemail)
 */
final class EmailDomain {

    private const FREEMAIL_DOMAINS = [
        // Google
        'gmail.com', 'googlemail.com',
        // Microsoft
        'outlook.com', 'outlook.de', 'hotmail.com', 'hotmail.de',
        'live.com', 'live.de', 'msn.com',
        // Yahoo
        'yahoo.com', 'yahoo.de', 'ymail.com', 'rocketmail.com',
        // DE-Mainstream
        'web.de', 'gmx.de', 'gmx.net', 'gmx.com', 'gmx.at', 'gmx.ch',
        't-online.de', 'freenet.de', 'arcor.de', 'mailbox.org',
        // Apple
        'icloud.com', 'me.com', 'mac.com',
        // Sonstige
        'aol.com', 'aol.de', 'mail.com', 'mail.de',
        'protonmail.com', 'proton.me', 'tutanota.com', 'tuta.io',
        'zoho.com', 'fastmail.com',
        // Deutsche Provider
        'posteo.de', 'posteo.net', '1und1.de', 'kabelbw.de',
    ];

    /**
     * Liefert eine wahrscheinliche Firmen-Website-URL aus einer Email,
     * oder null wenn Freemail/ungültig.
     */
    public static function infer_website( string $email ): ?string {
        $email = strtolower( trim( $email ) );
        if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
            return null;
        }

        $domain = substr( $email, strpos( $email, '@' ) + 1 );
        if ( $domain === '' ) {
            return null;
        }

        if ( in_array( $domain, self::FREEMAIL_DOMAINS, true ) ) {
            return null;
        }

        // Optionales Sub-Trimming (z. B. mail.firma.de → firma.de würde manchmal
        // sinnvoll sein, ist aber riskant. Wir lassen die Domain wie sie ist —
        // der Scraper folgt eh Redirects.)
        return 'https://' . $domain;
    }

    public static function is_freemail( string $email ): bool {
        $email = strtolower( trim( $email ) );
        if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
            return false;
        }
        $domain = substr( $email, strpos( $email, '@' ) + 1 );
        return in_array( $domain, self::FREEMAIL_DOMAINS, true );
    }
}
