<?php
namespace App\Core;

/**
 * Validator - Input Validation
 */
class Validator
{
    private array $errors = [];
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function required(string ...$fields): self
    {
        foreach ($fields as $field) {
            $value = $this->data[$field] ?? null;
            if ($value === null || $value === '') {
                $this->errors[$field][] = 'שדה חובה';
            }
        }
        return $this;
    }

    public function email(string ...$fields): self
    {
        foreach ($fields as $field) {
            $value = $this->data[$field] ?? '';
            if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $this->errors[$field][] = 'כתובת אימייל לא תקינה';
            }
        }
        return $this;
    }

    public function phone(string ...$fields): self
    {
        foreach ($fields as $field) {
            $value = $this->data[$field] ?? '';
            if ($value !== '' && !preg_match('/^[0-9\-\+\s\(\)]{7,20}$/', $value)) {
                $this->errors[$field][] = 'מספר טלפון לא תקין';
            }
        }
        return $this;
    }

    public function url(string ...$fields): self
    {
        foreach ($fields as $field) {
            $value = $this->data[$field] ?? '';
            if ($value !== '' && !filter_var($value, FILTER_VALIDATE_URL)) {
                $this->errors[$field][] = 'כתובת URL לא תקינה';
            }
        }
        return $this;
    }

    public function min(string $field, int $length): self
    {
        $value = $this->data[$field] ?? '';
        if (mb_strlen($value) < $length) {
            $this->errors[$field][] = "יש להזין לפחות {$length} תווים";
        }
        return $this;
    }

    public function max(string $field, int $length): self
    {
        $value = $this->data[$field] ?? '';
        if (mb_strlen($value) > $length) {
            $this->errors[$field][] = "יש להזין לכל היותר {$length} תווים";
        }
        return $this;
    }

    public function numeric(string ...$fields): self
    {
        foreach ($fields as $field) {
            $value = $this->data[$field] ?? '';
            if ($value !== '' && !is_numeric($value)) {
                $this->errors[$field][] = 'יש להזין מספר בלבד';
            }
        }
        return $this;
    }

    public function in(string $field, array $allowed): self
    {
        $value = $this->data[$field] ?? '';
        if ($value !== '' && !in_array($value, $allowed, true)) {
            $this->errors[$field][] = 'ערך לא חוקי';
        }
        return $this;
    }

    public function custom(string $field, callable $rule, string $message): self
    {
        $value = $this->data[$field] ?? null;
        if (!$rule($value)) {
            $this->errors[$field][] = $message;
        }
        return $this;
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFirstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    public function throwIfFails(): void
    {
        if ($this->fails()) {
            throw new \App\Core\Exceptions\ValidationException($this->errors);
        }
    }
}
