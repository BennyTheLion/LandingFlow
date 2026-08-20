<?php
namespace App\Repositories;

use App\Core\Database;

/**
 * LeadEngineRepository — pipeline run log and runtime settings (spec §10).
 */
class LeadEngineRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    // ------------------------------------------------------------------- runs

    public function startRun(string $trigger = 'manual'): int
    {
        $this->pdo->prepare("INSERT INTO lead_engine_runs (`trigger`) VALUES (?)")->execute([$trigger]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param array $counters sourced/skipped_duplicate/skipped_dnc/audited/
     *                        below_threshold/enriched/drafted/errors
     * @param array $log      Per-stage detail: what was skipped and why
     */
    public function finishRun(int $runId, array $counters, array $log): void
    {
        $fields = [
            'sourced', 'skipped_duplicate', 'skipped_dnc', 'audited',
            'below_threshold', 'enriched', 'drafted', 'errors',
        ];
        $set = ['finished_at = NOW()'];
        $vals = [];
        foreach ($fields as $field) {
            $set[] = "`$field` = ?";
            $vals[] = (int) ($counters[$field] ?? 0);
        }
        $set[] = '`log_json` = ?';
        $vals[] = json_encode($log, JSON_UNESCAPED_UNICODE);
        $vals[] = $runId;

        $this->pdo->prepare("UPDATE lead_engine_runs SET " . implode(', ', $set) . " WHERE id = ?")
            ->execute($vals);
    }

    public function runs(int $limit = 50): array
    {
        return $this->pdo->query("SELECT * FROM lead_engine_runs ORDER BY started_at DESC LIMIT " . (int) $limit)
            ->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findRun(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM lead_engine_runs WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    // --------------------------------------------------------------- settings

    /** @return array<string,string> */
    public function allSettings(): array
    {
        $rows = $this->pdo->query("SELECT `key`, `value` FROM lead_engine_settings")
            ->fetchAll(\PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $row) {
            $out[$row['key']] = $row['value'];
        }
        return $out;
    }

    public function setSetting(string $key, string $value): void
    {
        // UPSERT syntax differs between MySQL and the SQLite test DB, so do it
        // in two statements rather than branching on the driver.
        $updated = $this->pdo->prepare("UPDATE lead_engine_settings SET `value` = ? WHERE `key` = ?");
        $updated->execute([$value, $key]);
        if ($updated->rowCount() === 0) {
            $exists = $this->pdo->prepare("SELECT COUNT(*) FROM lead_engine_settings WHERE `key` = ?");
            $exists->execute([$key]);
            if ((int) $exists->fetchColumn() === 0) {
                $this->pdo->prepare("INSERT INTO lead_engine_settings (`key`, `value`) VALUES (?, ?)")
                    ->execute([$key, $value]);
            }
        }
    }
}
