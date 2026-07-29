<?php
namespace App\Services;

class Mailer
{
    private static string $logFile = '';

    public static function init(): void
    {
        self::$logFile = STORAGE_PATH . '/logs/mail.log';
        if (!is_dir(dirname(self::$logFile))) mkdir(dirname(self::$logFile), 0755, true);
    }

    public static function send(string $to, string $subject, string $body, string $from = '', string $fromName = '', string $attachment = ''): bool
    {
        self::init();
        $from = $from ?: (defined('SMTP_USER') && str_contains(SMTP_USER, '@') ? SMTP_USER : (defined('SMTP_FROM') ? SMTP_FROM : 'noreply@landingflow.co.il'));
        $fromName = $fromName ?: (defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'LandingFlow');
        $host = defined('SMTP_HOST') ? SMTP_HOST : '';
        $port = defined('SMTP_PORT') ? (int)SMTP_PORT : 587;

        if (!empty($host) && $host !== 'localhost' && $host !== '127.0.0.1') {
            return self::viaSMTP($to, $subject, $body, $from, $fromName, $host, $port, $attachment);
        }
        return self::viaMail($to, $subject, $body, $from, $fromName, $attachment);
    }

    private static function viaSMTP(string $to, string $subject, string $body, string $from, string $fromName, string $host, int $port, string $attachment = ''): bool
    {
        $user = defined('SMTP_USER') ? SMTP_USER : '';
        $pass = defined('SMTP_PASS') ? SMTP_PASS : '';
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->Port = $port;
            $mail->SMTPAuth = !empty($user);
            if (!empty($user)) { $mail->Username = $user; $mail->Password = $pass; }
            $mail->SMTPSecure = ($port === 465) ? 'ssl' : 'tls';
            $mail->CharSet = 'UTF-8';
            $mail->setFrom($from, $fromName);
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body = $body;
            if (!empty($attachment) && file_exists($attachment)) {
                $mail->addAttachment($attachment);
            }
            $mail->send();
            self::log($to, $from, $subject, $body, "SENT via $host:$port");
            return true;
        } catch (\Throwable $e) {
            self::log($to, $from, $subject, $body, "SMTP FAILED ($host:$port): ".$e->getMessage());
            return true;
        }
    }

    private static function viaMail(string $to, string $subject, string $body, string $from, string $fromName, string $attachment = ''): bool
    {
        $boundary = md5(time());
        $headers = "From: $fromName <$from>\r\n";
        if (!empty($attachment) && file_exists($attachment)) {
            $fileName = basename($attachment);
            $fileContent = chunk_split(base64_encode(file_get_contents($attachment)));
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
            $message = "--$boundary\r\n";
            $message .= "Content-Type: text/plain; charset=utf-8\r\n\r\n";
            $message .= "$body\r\n";
            $message .= "--$boundary\r\n";
            $message .= "Content-Type: application/pdf; name=\"$fileName\"\r\n";
            $message .= "Content-Disposition: attachment; filename=\"$fileName\"\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $message .= "$fileContent\r\n";
            $message .= "--$boundary--";
        } else {
            $headers .= "Content-Type: text/plain; charset=utf-8\r\n";
            $message = $body;
        }
        $sent = @mail($to, $subject, $message, $headers);
        self::log($to, $from, $subject, $body, $sent ? 'SENT via mail()' : 'mail() FAILED');
        return true;
    }

    private static function log(string $to, string $from, string $subject, string $body, string $status): void
    {
        $entry = str_repeat('=', 60)."\nTo: $to\nFrom: $from\nSubject: $subject\n";
        $entry .= "Date: ".date('Y-m-d H:i:s')."\nStatus: $status\n";
        $entry .= str_repeat('-', 40)."\n$body\n".str_repeat('=', 60)."\n\n";
        @file_put_contents(self::$logFile, $entry, FILE_APPEND | LOCK_EX);
    }
}
