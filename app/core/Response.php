<?php
namespace App\Core;

/**
 * HTTP Response Handler
 */
class Response
{
    private int $statusCode = 200;
    private array $headers = [];

    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        http_response_code($code);
        return $this;
    }

    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function send(mixed $content): void
    {
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }
        if (!isset($this->headers['Content-Type'])) {
            header('Content-Type: text/html; charset=utf-8');
        }
        echo $content;
    }

    public function json(array $data, int $statusCode = 200): never
    {
        $this->setStatusCode($statusCode);
        $this->setHeader('Content-Type', 'application/json; charset=utf-8');
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    public function redirect(string $url, int $statusCode = 302): never
    {
        header("Location: {$url}", true, $statusCode);
        if (defined('TEST_MODE') && TEST_MODE) {
            throw new \App\Core\Exceptions\RedirectException($url);
        }
        exit;
    }
}