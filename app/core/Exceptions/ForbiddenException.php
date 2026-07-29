<?php
namespace App\Core\Exceptions;

class ForbiddenException extends HttpException
{
    public function __construct(string $message = 'אין לך הרשאה לצפות בעמוד זה')
    {
        parent::__construct($message, 403);
    }
}
