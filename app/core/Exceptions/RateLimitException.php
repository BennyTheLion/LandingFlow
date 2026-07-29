<?php
namespace App\Core\Exceptions;

class RateLimitException extends HttpException
{
    public function __construct(string $message = 'יותר מדי ניסיונות. אנא נסה שוב מאוחר יותר')
    {
        parent::__construct($message, 429);
    }
}
