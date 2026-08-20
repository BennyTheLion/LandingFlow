<?php
namespace App\Repositories;

use App\Core\Database;
use App\Core\Logger;
use App\LeadEngine\PoliteFetcher;

/**
 * DoNotContactRepository — the suppression list (spec §4, §11.3).
 *
 * Checked before drafting AND again immediately before the actual send (§9).
 * A removal request lands here automatically, no questions asked.
 */
class DoNotContactRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * True when any identifier is suppressed.
     *
     * Fails CLOSED: if the table cannot be read we report "blocked" rather than
     * risk mailing someone who asked not to be contacted.
     */
    public function isBlocked(?string $domain = null, ?string $email = null, ?string $phone = null): bool
    {
        $where = [];
        $params = [];

        if ($domain !== null && $domain !== '') {
            $where[] = 'domain = ?';
            $params[] = PoliteFetcher::domainKey($domain);
        }
        if ($email !== null && $email !== '') {
            $where[] = 'email = ?';
            $params[] = strtolower(trim($email));
        }
        if ($phone !== null && $phone !== '') {
            $where[] = 'phone = ?';
            $params[] = preg_replace('/[^0-9]/', '', $phone);
        }
        if ($where === []) {
            return false;
        }

        try {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM do_not_contact WHERE " . implode(' OR ', $where)
            );
            $stmt->execute($params);
            return (int) $stmt->fetchColumn() > 0;
        } catch (\Throwable $e) {
            Logger::error('leadengine: do_not_contact check failed — treating as blocked', [
                'message' => $e->getMessage(),
            ]);
            return true;
        }
    }

    public function add(?string $domain, ?string $email, ?string $phone, string $reason): void
    {
        $this->pdo->prepare(
            "INSERT INTO do_not_contact (domain, email, phone, reason) VALUES (?, ?, ?, ?)"
        )->execute([
            $domain !== null && $domain !== '' ? PoliteFetcher::domainKey($domain) : null,
            $email !== null && $email !== '' ? strtolower(trim($email)) : null,
            $phone !== null && $phone !== '' ? preg_replace('/[^0-9]/', '', $phone) : null,
            $reason,
        ]);

        Logger::info('leadengine: added to do_not_contact', [
            'domain' => $domain, 'email' => $email, 'reason' => $reason,
        ]);
    }

    public function all(int $limit = 500): array
    {
        return $this->pdo->query("SELECT * FROM do_not_contact ORDER BY added_at DESC LIMIT " . (int) $limit)
            ->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function remove(int $id): void
    {
        $this->pdo->prepare("DELETE FROM do_not_contact WHERE id = ?")->execute([$id]);
    }

    public function count(): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM do_not_contact")->fetchColumn();
    }
}
