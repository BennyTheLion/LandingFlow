<?php
namespace App\Services;

use App\Core\Logger;
use App\Repositories\LeadRepositoryInterface;

class LeadService
{
    private LeadRepositoryInterface $repo;

    public function __construct(LeadRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function list(?string $status = null, ?string $search = null): array
    {
        return $this->repo->findAll($status, $search);
    }

    public function get(int $id): array
    {
        $lead = $this->repo->findById($id);
        if (!$lead) {
            throw new \App\Core\Exceptions\NotFoundException('הליד לא נמצא');
        }
        $notes = $this->repo->getNotes($id);
        return ['lead' => $lead, 'notes' => $notes];
    }

    public function create(array $data): int
    {
        $data['consent_date'] = date('Y-m-d H:i:s');
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['consent_given'] = ($data['consent_given'] ?? false) ? 1 : 0;
        $data['status'] = $data['status'] ?? 'new';

        Logger::info('LeadService: lead created', ['name' => $data['name'] ?? '']);
        return $this->repo->create($data);
    }

    public function update(int $id, array $data): void
    {
        $this->repo->update($id, $data);
        Logger::info('LeadService: lead updated', ['id' => $id]);
    }

    public function updateStatus(int $id, string $status): void
    {
        $this->repo->updateStatus($id, $status);
        Logger::info('LeadService: status changed', ['id' => $id, 'status' => $status]);
    }

    public function addNote(int $leadId, ?int $userId, string $content, string $type = 'note'): void
    {
        $this->repo->addNote($leadId, $userId, $content, $type);
    }

    public function delete(int $id): void
    {
        $this->repo->delete($id);
        Logger::info('LeadService: lead deleted', ['id' => $id]);
    }

    /** Capture lead from public website (registration, audit, contact) */
    public function captureFromWebsite(string $name, string $phone, string $email = '', string $source = 'website', string $interest = '', string $notes = ''): void
    {
        $this->repo->create([
            'name'          => $name,
            'phone'         => $phone,
            'email'         => $email,
            'source'        => $source,
            'interest'      => $interest,
            'notes'         => $notes,
            'status'        => 'new',
            'consent_given' => 1,
            'consent_date'  => date('Y-m-d H:i:s'),
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        // Non-critical notifications
        try {
            $this->sendAdminNotification($name, $phone, $email, $source, $interest, $notes);
            if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->sendCustomerConfirmation($name, $email);
            }
        } catch (\Exception $e) {
            Logger::error('LeadService: notification failed', ['error' => $e->getMessage()]);
        }
    }

    public function exportCsv(): array
    {
        return $this->repo->exportAll();
    }

    private function sendAdminNotification(string $name, string $phone, string $email, string $source, string $interest, string $notes): void
    {
        $to = 'support@landingflow.co.il';
        $subject = "ליד חדש — LandingFlow: $name";
        $body = "ליד חדש התקבל ב-LandingFlow:\n\n";
        $body .= "שם: $name\nטלפון: $phone\n";
        if ($email) $body .= "אימייל: $email\n";
        $body .= "מקור: $source\n";
        if ($interest) $body .= "עניין: $interest\n";
        if ($notes) $body .= "הערות: $notes\n";
        Mailer::send($to, $subject, $body);
    }

    private function sendCustomerConfirmation(string $name, string $email): void
    {
        $subject = "תודה שפנית ל-LandingFlow, $name!";
        $body = "היי $name,\n\nתודה על פנייתך! נציג יחזור אליך בהקדם.\n\nבברכה,\nצוות LandingFlow\n052-8529448";
        Mailer::send($email, $subject, $body);
    }
}
