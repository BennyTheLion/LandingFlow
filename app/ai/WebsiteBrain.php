<?php

namespace App\Ai;

class WebsiteBrain
{
    protected array $config = [];

    public function loadConfig(array $config): void
    {
        $this->config = $config;
    }

    /*
     * MAIN FUNCTION
     * Converts config into structured "understanding"
     */
    public function analyze(): array
    {
        return [
            "business" => $this->getBusiness(),
            "audience" => $this->getAudience(),
            "services" => $this->getServices(),
            "intent" => $this->getIntent(),
            "llm_summary" => $this->getLLMSummary(),
        ];
    }

    protected function getBusiness(): string
    {
        return $this->config['site']['business'] ?? 'Unknown business';
    }

    protected function getAudience(): string
    {
        return $this->config['site']['audience'] ?? 'General users';
    }

    protected function getServices(): array
    {
        return $this->config['structure']['services'] ?? [];
    }

    /*
     * USER INTENT = what visitor wants
     */
    protected function getIntent(): string
    {
        return $this->config['site']['primary_goal'] ?? 'Get leads';
    }

    /*
     * LLM SUMMARY = most important part
     */
    protected function getLLMSummary(): string
    {
        $business = $this->getBusiness();
        $audience = $this->getAudience();
        $intent = $this->getIntent();

        return "This website is for $business. "
             . "It targets $audience. "
             . "Primary goal is: $intent.";
    }
}