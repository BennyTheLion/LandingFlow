<?php
namespace App\LeadEngine;

/**
 * ApprovalToken — signed, single-use, 72-hour approval links (spec §9).
 *
 * The token in the email is a random 32-byte secret bound to one draft_id and
 * signed with HMAC-SHA256. Only the hash is stored, so a database leak does not
 * yield usable approval links.
 *
 * Note what this token does NOT do: it never sends. It only opens the preview
 * page. Mail scanners and inbox previews prefetch links, so the send is a
 * separate POST with a CSRF token (§9 "כלל אבטחה קריטי").
 */
class ApprovalToken
{
    /**
     * @return array{token:string,hash:string,expires_at:string}
     *         `token` goes in the email link, `hash` goes in the database
     */
    public static function issue(int $draftId): array
    {
        $secret = self::secret();
        $random = bin2hex(random_bytes(32));
        $expiresAt = gmdate('Y-m-d H:i:s', time() + LeadEngineConfig::TOKEN_TTL_HOURS * 3600);

        return [
            'token'      => $random,
            'hash'       => self::hash($draftId, $random, $secret),
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Recomputes the stored hash for a presented token.
     *
     * Whether the hash is current, unused, and unexpired is decided by
     * OutreachRepository::consumeToken() in a single conditional UPDATE — that
     * is what makes the token single-use under concurrent clicks.
     */
    public static function hashFor(int $draftId, string $token): string
    {
        return self::hash($draftId, $token, self::secret());
    }

    /** Constant-time comparison for callers that need a direct check */
    public static function matches(int $draftId, string $token, string $storedHash): bool
    {
        if ($token === '' || $storedHash === '') {
            return false;
        }
        return hash_equals($storedHash, self::hash($draftId, $token, self::secret()));
    }

    private static function hash(int $draftId, string $token, string $secret): string
    {
        return hash_hmac('sha256', 'draft:' . $draftId . ':' . $token, $secret);
    }

    /**
     * A missing APPROVAL_TOKEN_SECRET is a configuration error, not something
     * to paper over with a default — an empty key would make every token
     * forgeable.
     */
    private static function secret(): string
    {
        $secret = LeadEngineConfig::tokenSecret();
        if (strlen($secret) < 32) {
            throw new \RuntimeException(
                'APPROVAL_TOKEN_SECRET is missing or shorter than 32 characters — '
                . 'set it in config/.env.php before using the approval flow.'
            );
        }
        return $secret;
    }
}
