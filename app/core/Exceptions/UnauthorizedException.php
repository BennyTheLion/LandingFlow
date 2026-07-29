<?php
namespace App\Core\Exceptions;

class UnauthorizedException extends HttpException
{
    public function __construct(string $message = 'עליך להתחבר כדי לצפות בעמוד זה')
    {
        parent::__construct($message, 401);
    }
}
