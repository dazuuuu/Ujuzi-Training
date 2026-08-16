<?php

namespace App\Services;

use App\Core\Env;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class MailerException extends \Exception {}

/**
 * SMTP mailer (PHPMailer) for OTP login codes, password-reset codes, and
 * order confirmations. Reads credentials from .env — see .env.example.
 */
class MailerService
{
    /**
     * A misconfigured/unreachable SMTP host must fail fast rather than hang
     * the request for PHPMailer's 300s default — matters most right after
     * .env is first set up with placeholder credentials.
     */
    private static function configured(): PHPMailer
    {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Timeout = 10;
        $mail->SMTPKeepAlive = false;
        $mail->Host = Env::get('MAIL_HOST');
        $mail->SMTPAuth = true;
        $mail->Username = Env::get('MAIL_USERNAME');
        $mail->Password = Env::get('MAIL_PASSWORD');
        $encryption = Env::get('MAIL_ENCRYPTION', 'tls');
        $mail->SMTPSecure = $encryption === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = (int) Env::get('MAIL_PORT', 587);
        $mail->setFrom(Env::get('MAIL_FROM_ADDRESS', 'no-reply@pentagoncollections.com'), Env::get('MAIL_FROM_NAME', 'Pentagon Collections'));
        return $mail;
    }

    public static function sendOtp(string $toEmail, string $code, string $purpose = 'login'): void
    {
        $mail = self::configured();
        try {
            $mail->addAddress($toEmail);
            $isReset = $purpose === 'password_reset';
            $mail->isHTML(true);
            $mail->Subject = $isReset ? 'Your Pentagon Collections password reset code' : 'Your Pentagon Collections login code';
            $mail->Body = self::otpHtml($code, $isReset);
            $mail->AltBody = ($isReset ? 'Your password reset code is: ' : 'Your login code is: ') . $code . ' (expires in 10 minutes).';
            $mail->send();
        } catch (PHPMailerException $e) {
            throw new MailerException('Could not send email: ' . $mail->ErrorInfo);
        }
    }

    public static function sendOrderConfirmation(string $toEmail, array $order, array $items): void
    {
        $mail = self::configured();
        try {
            $mail->addAddress($toEmail);
            $mail->isHTML(true);
            $mail->Subject = 'Your Pentagon Collections order ' . $order['order_ref'] . ' is confirmed';
            $mail->Body = self::orderConfirmationHtml($order, $items);
            $mail->AltBody = 'Thank you for your order ' . $order['order_ref'] . '. Total: ' . $order['total'] . ' ' . $order['currency'] . '.';
            $mail->send();
        } catch (PHPMailerException $e) {
            throw new MailerException('Could not send order confirmation: ' . $mail->ErrorInfo);
        }
    }

    private static function otpHtml(string $code, bool $isReset): string
    {
        $heading = $isReset ? 'Reset your password' : 'Your one-time login code';
        $blurb = $isReset
            ? 'Use the code below to verify it\'s you and set a new password.'
            : 'Use the code below to sign in and track your Pentagon Collections orders.';
        return '
        <div style="font-family: Arial, sans-serif; background:#faf9f6; padding:32px;">
          <div style="max-width:420px;margin:0 auto;background:#ffffff;border:1px solid #e5e5e5;border-radius:12px;overflow:hidden;">
            <div style="background:#0a0a0a;padding:20px 24px;">
              <span style="color:#fcd34d;font-weight:bold;letter-spacing:2px;font-size:14px;">PENTAGON COLLECTIONS</span>
            </div>
            <div style="padding:28px 24px;">
              <h1 style="font-size:18px;color:#0a0a0a;margin:0 0 8px;">' . htmlspecialchars($heading) . '</h1>
              <p style="font-size:13px;color:#555;line-height:1.5;margin:0 0 20px;">' . htmlspecialchars($blurb) . '</p>
              <div style="background:#f5f5f5;border:1px solid #d4d4d4;border-radius:8px;padding:16px;text-align:center;margin-bottom:20px;">
                <span style="font-size:28px;letter-spacing:8px;font-weight:bold;color:#0a0a0a;">' . htmlspecialchars($code) . '</span>
              </div>
              <p style="font-size:12px;color:#888;margin:0;">This code expires in 10 minutes. If you didn\'t request this, you can safely ignore this email.</p>
            </div>
          </div>
        </div>';
    }

    private static function orderConfirmationHtml(array $order, array $items): string
    {
        $rows = '';
        foreach ($items as $item) {
            $rows .= '<tr>
              <td style="padding:8px 0;border-bottom:1px solid #eee;font-size:12px;color:#333;">' . htmlspecialchars($item['product_name']) . ' &times; ' . (int) $item['quantity'] . '</td>
              <td style="padding:8px 0;border-bottom:1px solid #eee;font-size:12px;color:#8b1c1c;text-align:right;font-weight:bold;">' . htmlspecialchars($item['currency']) . ' ' . number_format($item['unit_price'] * $item['quantity']) . '</td>
            </tr>';
        }
        return '
        <div style="font-family: Arial, sans-serif; background:#faf9f6; padding:32px;">
          <div style="max-width:460px;margin:0 auto;background:#ffffff;border:1px solid #e5e5e5;border-radius:12px;overflow:hidden;">
            <div style="background:#0a0a0a;padding:20px 24px;">
              <span style="color:#fcd34d;font-weight:bold;letter-spacing:2px;font-size:14px;">PENTAGON COLLECTIONS</span>
            </div>
            <div style="padding:28px 24px;">
              <h1 style="font-size:18px;color:#0a0a0a;margin:0 0 8px;">Thank you for your order</h1>
              <p style="font-size:13px;color:#555;line-height:1.5;margin:0 0 16px;">Order <strong>' . htmlspecialchars($order['order_ref']) . '</strong> has been received and is being prepared.</p>
              <table style="width:100%;border-collapse:collapse;margin-bottom:16px;">' . $rows . '</table>
              <table style="width:100%;font-size:13px;color:#333;">
                <tr><td>Total Paid</td><td style="text-align:right;font-weight:bold;color:#8b1c1c;">' . htmlspecialchars($order['currency']) . ' ' . number_format($order['total']) . '</td></tr>
              </table>
              <p style="font-size:12px;color:#888;margin-top:20px;">Track this order any time by signing in with this email at our order tracking page.</p>
            </div>
          </div>
        </div>';
    }
}
