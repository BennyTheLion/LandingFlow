<?php
namespace App\Core\Exceptions;

class ValidationException extends HttpException
{
    private array $errors;

    public function __construct(array $errors, string $message = 'שגיאת אימות נתונים')
    {
        parent::__construct($message, 422);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
