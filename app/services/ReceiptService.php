<?php
namespace App\Services;

class ReceiptService
{
    private string $storagePath;

    public function __construct()
    {
        $this->storagePath = STORAGE_PATH . '/receipts';
        if (!is_dir($this->storagePath)) mkdir($this->storagePath, 0755, true);
    }

    public function generatePdf(array $receipt): string
    {
        $date = date('Y/m', strtotime($receipt['receipt_date']));
        $dir = $this->storagePath . '/' . $date;
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $filename = sprintf('receipt_%s_%s.pdf', $receipt['receipt_number'], date('Ymd'));
        $filepath = $dir . '/' . $filename;

        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('LandingFlow');
        $pdf->SetAuthor('LandingFlow');
        $pdf->SetTitle('קבלה #' . $receipt['receipt_number']);
        $pdf->setRTL(true);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->AddPage();

        // Hebrew font
        $pdf->SetFont('freesans', '', 12);

        $html = $this->buildHtml($receipt);
        $pdf->writeHTML($html, true, false, true, false, '');

        $pdf->Output($filepath, 'F');
        return $filepath;
    }

    private function buildHtml(array $r): string
    {
        $num = htmlspecialchars($r['receipt_number']);
        $name = htmlspecialchars($r['customer_name']);
        $desc = nl2br(htmlspecialchars($r['service_description']));
        $amount = number_format((float)$r['amount'], 2);
        $date = date('d/m/Y', strtotime($r['receipt_date']));
        $trans = htmlspecialchars($r['transaction_id'] ?? '-');

        return <<<HTML
<style>
  body{font-family:freesans;direction:rtl;color:#1e293b}
  .header{text-align:center;border-bottom:2px solid #2563EB;padding-bottom:12px;margin-bottom:20px}
  .header h1{font-size:22px;color:#2563EB;margin:0}
  .header p{font-size:12px;color:#64748b;margin:4px 0 0}
  .receipt-title{font-size:18px;font-weight:bold;text-align:center;margin:16px 0;color:#1e40af}
  .info-table{width:100%;border-collapse:collapse;margin-bottom:16px}
  .info-table td{padding:6px 10px;font-size:13px;vertical-align:top}
  .info-table .label{color:#64748b;width:120px}
  .line{border-bottom:1px solid #e2e8f0}
  .service-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px;margin:16px 0}
  .service-box h3{font-size:14px;margin:0 0 8px;color:#1e40af}
  .amount-box{text-align:left;margin-top:16px}
  .amount-box .total{font-size:24px;font-weight:bold;color:#2563EB}
  .footer{text-align:center;font-size:11px;color:#94a3b8;margin-top:30px;border-top:1px solid #e2e8f0;padding-top:12px}
</style>
<div class="header">
  <h1>LandingFlow</h1>
  <p>פיתוח אתרים | אחסון | ניטור | CRM</p>
</div>
<div class="receipt-title">קבלה מס' {$num}</div>
<table class="info-table">
  <tr><td class="label">לקוח:</td><td><b>{$name}</b></td></tr>
  <tr><td class="label">תאריך:</td><td>{$date}</td></tr>
  <tr><td class="label">מס' עסקה:</td><td>{$trans}</td></tr>
</table>
<div class="service-box">
  <h3>פירוט שירות</h3>
  <div style="font-size:13px">{$desc}</div>
</div>
<div class="amount-box">
  <div class="total">₪{$amount}</div>
</div>
<div class="footer">
  LandingFlow | hello@landingflow.co.il | 052-8529448<br>
  תודה שבחרתם בנו!
</div>
HTML;
    }
}
