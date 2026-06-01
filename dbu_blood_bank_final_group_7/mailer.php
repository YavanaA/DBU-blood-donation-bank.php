<?php
/**
 * DBU Blood Bank — PHPMailer SMTP Wrapper
 * Sends real Gmail SMTP email when MAIL_ENABLED = true.
 * Falls back gracefully to log-only mode on Replit / unconfigured installs.
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailException;

/**
 * Send an email via Gmail SMTP using PHPMailer.
 *
 * @param  string $to       Recipient email address
 * @param  string $subject  Email subject
 * @param  string $body     Plain-text body (auto-converted to HTML)
 * @param  string $toName   Recipient display name (optional)
 * @return bool             true = sent successfully, false = failed/disabled
 */
function sendEmail(string $to, string $subject, string $body, string $toName = ''): bool
{
    if (!defined('MAIL_ENABLED') || !MAIL_ENABLED) {
        return false; // Log-only mode — no SMTP attempt
    }

    $vendorDir = __DIR__ . '/vendor/PHPMailer/';
    if (!file_exists($vendorDir . 'PHPMailer.php')) {
        error_log('[DBU-MAIL] PHPMailer not found at ' . $vendorDir);
        return false;
    }

    require_once $vendorDir . 'Exception.php';
    require_once $vendorDir . 'PHPMailer.php';
    require_once $vendorDir . 'SMTP.php';

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = (MAIL_SECURE === 'ssl')
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($to, $toName ?: $to);

        $mail->Subject = $subject;
        $mail->isHTML(true);

        // Build responsive HTML body
        $htmlBody = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="font-family:Arial,sans-serif;background:#f4f4f4;padding:30px;">'
            . '<div style="max-width:600px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.1);">'
            . '<div style="background:linear-gradient(135deg,#003087,#7c3aed);padding:24px 30px;">'
            . '<h2 style="color:#fff;margin:0;font-size:20px;">🩸 DBU Blood Bank</h2>'
            . '<p style="color:rgba(255,255,255,.7);margin:4px 0 0;font-size:13px;">Debre Berhan University</p>'
            . '</div>'
            . '<div style="padding:28px 30px;color:#1f2937;line-height:1.7;font-size:15px;">'
            . nl2br(htmlspecialchars($body))
            . '</div>'
            . '<div style="padding:16px 30px;background:#f8fafc;font-size:12px;color:#9ca3af;border-top:1px solid #e5e7eb;">'
            . 'This is an automated message from DBU Blood Bank Management System. Please do not reply to this email.'
            . '</div></div></body></html>';

        $mail->Body    = $htmlBody;
        $mail->AltBody = $body;

        $mail->send();
        return true;

    } catch (MailException $e) {
        error_log('[DBU-MAIL] Send failed to ' . $to . ': ' . $e->getMessage());
        return false;
    } catch (Throwable $e) {
        error_log('[DBU-MAIL] Unexpected error: ' . $e->getMessage());
        return false;
    }
}
?>
