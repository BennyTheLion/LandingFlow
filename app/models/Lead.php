<?php
namespace App\Models;

class Lead
{
    public ?int $id = null;
    public string $name;
    public ?string $email = null;
    public ?string $phone = null;
    public ?string $company = null;
    public ?string $website = null;
    public string $source = 'website';
    public ?string $sourceDetail = null;
    public string $status = 'new';
    public int $score = 0;
    public ?string $interest = null;
    public ?float $budget = null;
    public ?string $notes = null;
    public ?int $assignedTo = null;
    public bool $consentGiven = false;
    public ?string $consentDate = null;
    public string $createdAt;
    public string $updatedAt;

    public static function fromRow(array $row): self
    {
        $l = new self();
        $l->id           = isset($row['id']) ? (int) $row['id'] : null;
        $l->name         = $row['name'] ?? '';
        $l->email        = $row['email'] ?? null;
        $l->phone        = $row['phone'] ?? null;
        $l->company      = $row['company'] ?? null;
        $l->website      = $row['website'] ?? null;
        $l->source       = $row['source'] ?? 'website';
        $l->sourceDetail = $row['source_detail'] ?? null;
        $l->status       = $row['status'] ?? 'new';
        $l->score        = isset($row['score']) ? (int) $row['score'] : 0;
        $l->interest     = $row['interest'] ?? null;
        $l->budget       = isset($row['budget']) ? (float) $row['budget'] : null;
        $l->notes        = $row['notes'] ?? null;
        $l->assignedTo   = isset($row['assigned_to']) ? (int) $row['assigned_to'] : null;
        $l->consentGiven = (bool) ($row['consent_given'] ?? false);
        $l->consentDate  = $row['consent_date'] ?? null;
        $l->createdAt    = $row['created_at'] ?? '';
        $l->updatedAt    = $row['updated_at'] ?? '';
        return $l;
    }

    public function toArray(): array
    {
        return json_decode(json_encode($this), true);
    }
}
