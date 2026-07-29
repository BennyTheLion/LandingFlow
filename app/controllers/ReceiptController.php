<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Services\ReceiptService;
use App\Services\Mailer;

class ReceiptController extends Controller
{
    public function index(): string
    {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        $search = $_GET['search'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;

        $where = '';
        $params = [];
        if (!empty($search)) {
            $where = " WHERE (customer_name LIKE :s OR receipt_number LIKE :s2 OR DATE_FORMAT(receipt_date, '%d/%m/%Y') LIKE :s3)";
            $params['s'] = "%$search%";
            $params['s2'] = "%$search%";
            $params['s3'] = "%$search%";
        }

        // Count matching receipts
        $tq = "SELECT COUNT(*) FROM receipts $where";
        $stmt = $conn->prepare($tq);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();
        $totalPages = max(1, (int)ceil($total / $perPage));
        $offset = ($page - 1) * $perPage;

        $q = "SELECT * FROM receipts $where ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
        $stmt = $conn->prepare($q);
        $stmt->execute($params);
        $receipts = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $this->render('admin/receipts/index', [
            'pageTitle' => 'קבלות — LandingFlow',
            'receipts' => $receipts,
            'search' => $search,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'flashMsg' => Session::flash('flash'),
        ]);
    }

    public function create(): string
    {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        $nextNum = ((int) $conn->query("SELECT MAX(CAST(SUBSTRING(receipt_number, 4) AS UNSIGNED)) FROM receipts")->fetchColumn() ?: 0) + 1;
        $nextNum = str_pad($nextNum, 6, '0', STR_PAD_LEFT);

        return $this->render('admin/receipts/create', [
            'pageTitle' => 'יצירת קבלה — LandingFlow',
            'nextNumber' => $nextNum,
            'flashMsg' => Session::flash('flash'),
        ]);
    }

    public function store(): void
    {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        $receiptNumber = trim($_POST['receipt_number'] ?? '');
        $customerName = trim($_POST['customer_name'] ?? '');
        $customerEmail = trim($_POST['customer_email'] ?? '');
        $transactionId = trim($_POST['transaction_id'] ?? '');
        $serviceDesc = trim($_POST['service_description'] ?? '');
        $amount = (float) ($_POST['amount'] ?? 0);
        $receiptDate = $_POST['receipt_date'] ?? date('Y-m-d');

        // Validation
        $errors = [];
        if (empty($customerName)) $errors[] = 'נא להזין שם לקוח';
        if (empty($customerEmail) || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) $errors[] = 'נא להזין אימייל תקין';
        if (empty($serviceDesc)) $errors[] = 'נא להזין תיאור שירות';
        if ($amount <= 0) $errors[] = 'נא להזין סכום תקין';

        // Check unique receipt number
        $exists = $conn->prepare("SELECT id FROM receipts WHERE receipt_number = ?");
        $exists->execute([$receiptNumber]);
        if ($exists->fetch()) $errors[] = 'מספר קבלה זה כבר קיים במערכת';

        if (!empty($errors)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => implode('<br>', $errors)];
            header('Location: ' . $this->url('admin/receipts/create'));
            exit;
        }

        try {
            $data = [
                'receipt_number' => $receiptNumber,
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'transaction_id' => $transactionId,
                'service_description' => $serviceDesc,
                'amount' => $amount,
                'receipt_date' => $receiptDate,
            ];

            // Generate PDF
            $service = new ReceiptService();
            $pdfPath = $service->generatePdf($data);

            // Save to DB
            $stmt = $conn->prepare(
                "INSERT INTO receipts (receipt_number, customer_name, customer_email, transaction_id, service_description, amount, receipt_date, pdf_path, emailed_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([
                $receiptNumber, $customerName, $customerEmail, $transactionId,
                $serviceDesc, $amount, $receiptDate, $pdfPath
            ]);

            // Send email
            $subject = "קבלה מספר $receiptNumber";
            $body = "שלום $customerName,\n\n"
                  . "תודה על התשלום!\n"
                  . "מצורף קובץ הקבלה שלך.\n\n"
                  . "אנו מעריכים את אמונך בנו.\n"
                  . "צוות LandingFlow\n\n"
                  . "לקבלת שירות, אנא צור קשר:\n"
                  . "📞 052-8529448 | ✉️ hello@landingflow.co.il";

            Mailer::send($customerEmail, $subject, $body, '', '', $pdfPath);

            $_SESSION['flash'] = ['type' => 'success', 'message' => "קבלה #$receiptNumber נוצרה ונשלחה ל-$customerEmail"];
        } catch (\Throwable $e) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'שגיאה ביצירת הקבלה: ' . $e->getMessage()];
        }

        header('Location: ' . $this->url('admin/receipts'));
        exit;
    }

    public function download(string $id): void
    {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("SELECT * FROM receipts WHERE id = ?");
        $stmt->execute([(int)$id]);
        $receipt = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$receipt || empty($receipt['pdf_path']) || !file_exists($receipt['pdf_path'])) {
            http_response_code(404);
            echo 'הקובץ לא נמצא';
            exit;
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($receipt['pdf_path']) . '"');
        header('Content-Length: ' . filesize($receipt['pdf_path']));
        readfile($receipt['pdf_path']);
        exit;
    }

    public function resend(string $id): void
    {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("SELECT * FROM receipts WHERE id = ?");
        $stmt->execute([(int)$id]);
        $receipt = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$receipt) {
            http_response_code(404);
            exit;
        }

        $subject = "קבלה מספר {$receipt['receipt_number']}";
        $body = "שלום {$receipt['customer_name']},\n\n"
              . "תודה על התשלום!\n"
              . "מצורף קובץ הקבלה.\n\n"
              . "אנו מעריכים את אמונך בנו.\n"
              . "צוות LandingFlow\n\n"
              . "לקבלת שירות, אנא צור קשר:\n"
              . "📞 052-8529448 | ✉️ hello@landingflow.co.il";

        Mailer::send($receipt['customer_email'], $subject, $body, '', '', $receipt['pdf_path']);

        // Update emailed_at
        $conn->prepare("UPDATE receipts SET emailed_at = NOW(), updated_at = NOW() WHERE id = ?")->execute([(int)$id]);

        $_SESSION['flash'] = ['type' => 'success', 'message' => "הקבלה נשלחה מחדש ל-{$receipt['customer_email']}"];
        header('Location: ' . $this->url('admin/receipts'));
        exit;
    }
}
