<?php
namespace App\Repositories;

use App\Models\Lead;

interface LeadRepositoryInterface
{
    public function findAll(?string $status = null, ?string $search = null): array;
    public function findById(int $id): ?Lead;
    public function create(array $data): int;
    public function update(int $id, array $data): void;
    public function updateStatus(int $id, string $status): void;
    public function delete(int $id): void;
    public function addNote(int $leadId, ?int $userId, string $content, string $type = 'note'): void;
    public function getNotes(int $leadId): array;
    /** CSV export — returns all leads as associative arrays */
    public function exportAll(): array;
}
