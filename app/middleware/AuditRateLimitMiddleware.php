<?php
namespace App\Middleware;

/**
 * Rate limit for the public audit endpoints.
 *
 * These are the only unauthenticated routes that send mail: every completed scan
 * emails a report with a PDF attached, and each verification-code request is a
 * second message. All of it goes through one SMTP account with a daily cap, so an
 * unthrottled endpoint can exhaust the quota for the whole app — monitoring alerts
 * and lead notifications share that account.
 *
 * 5 requests / 10 min per IP: a real visitor needs 2 (code, then scan) and might
 * retry a few times; anything past that is not a person filling in a form.
 */
class AuditRateLimitMiddleware extends RateLimitMiddleware
{
    public function __construct()
    {
        parent::__construct(5, 600, 'audit');
    }
}
