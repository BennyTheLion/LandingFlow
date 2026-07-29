<?php
namespace App\Core\Exceptions;

class NotFoundException extends HttpException
{
    public function __construct(string $message = 'העמוד המבוקש לא נמצא')
    {
        parent::__construct($message, 404);
    }
}
