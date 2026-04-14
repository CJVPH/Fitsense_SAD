<?php
/**
 * FitSense — SMTP Mailer
 *
 * Lightweight SMTP client using PHP streams (no external library).
 * Sends HTML + plain-text emails via Gmail STARTTLS.
 */

require_once __DIR__ . '/../config/database.php';

class Mailer
{
    private string $host;
    private int    $port;
    private string $username;
    private string $password;
    private string $fromEmail;
    private string $fromName;

    public function __construct()
    {
        $this->host      = MAIL_HOST;
        $this->port      = MAIL_PORT;
        $this->username  = MAIL_USERNAME;
        $this->password  = MAIL_PASSWORD;
        $this->fromEmail = MAIL_FROM_EMAIL;
        $this->fromName  = MAIL_FROM_NAME;
    }

    /**
     * Send an email.
     *
     * @param  string $toEmail   Recipient email address.
     * @param  string $toName    Recipient display name.
     * @param  string $subject   Email subject.
     * @param  string $htmlBody  HTML body.
     * @param  string $textBody  Plain-text fallback.
     * @return array  ['success' => bool, 'error' => string|null]
     */
    public function send(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): array
    {
        if (empty($this->username) || empty($this->password)) {
            error_log('Mailer: SMTP credentials not configured.');
            return ['success' => false, 'error' => 'SMTP not configured.'];
        }

        if ($textBody === '') {
            $textBody = strip_tags($htmlBody);
        }

        try {
            $socket = fsockopen('tcp://' . $this->host, $this->port, $errno, $errstr, 10);
            if (!$socket) {
                throw new \RuntimeException("Connection failed: $errstr ($errno)");
            }

            $this->expect($socket, '220');
            $this->cmd($socket, "EHLO fitsense.local");
            $this->expect($socket, '250');

            // STARTTLS
            $this->cmd($socket, "STARTTLS");
            $this->expect($socket, '220');

            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

            $this->cmd($socket, "EHLO fitsense.local");
            $this->expect($socket, '250');

            // AUTH LOGIN
            $this->cmd($socket, "AUTH LOGIN");
            $this->expect($socket, '334');
            $this->cmd($socket, base64_encode($this->username));
            $this->expect($socket, '334');
            $this->cmd($socket, base64_encode($this->password));
            $this->expect($socket, '235');

            // Envelope
            $this->cmd($socket, "MAIL FROM:<{$this->fromEmail}>");
            $this->expect($socket, '250');
            $this->cmd($socket, "RCPT TO:<{$toEmail}>");
            $this->expect($socket, '250');

            // Data
            $this->cmd($socket, "DATA");
            $this->expect($socket, '354');

            $boundary = md5(uniqid('', true));
            $headers  = implode("\r\n", [
                "From: {$this->fromName} <{$this->fromEmail}>",
                "To: {$toName} <{$toEmail}>",
                "Subject: {$subject}",
                "MIME-Version: 1.0",
                "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
                "X-Mailer: FitSense/1.0",
            ]);

            $body = "--{$boundary}\r\n"
                  . "Content-Type: text/plain; charset=UTF-8\r\n\r\n"
                  . $textBody . "\r\n"
                  . "--{$boundary}\r\n"
                  . "Content-Type: text/html; charset=UTF-8\r\n\r\n"
                  . $htmlBody . "\r\n"
                  . "--{$boundary}--";

            fwrite($socket, $headers . "\r\n\r\n" . $body . "\r\n.\r\n");
            $this->expect($socket, '250');

            $this->cmd($socket, "QUIT");
            fclose($socket);

            return ['success' => true, 'error' => null];

        } catch (\Throwable $e) {
            error_log('Mailer error: ' . $e->getMessage());
            if (isset($socket) && is_resource($socket)) {
                fclose($socket);
            }
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function cmd($socket, string $command): void
    {
        fwrite($socket, $command . "\r\n");
    }

    private function expect($socket, string $code): string
    {
        $response = '';
        while ($line = fgets($socket, 512)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        if (substr($response, 0, 3) !== $code) {
            throw new \RuntimeException("Expected {$code}, got: " . trim($response));
        }
        return $response;
    }

    // ── Email Templates ───────────────────────────────────────────────────────

    /**
     * Send welcome email with login credentials to a new member.
     */
    public static function sendWelcome(string $toEmail, string $toName, string $username, string $password): array
    {
        $mailer  = new self();
        $subject = 'Welcome to FitSense — Your Login Credentials';

        $html = '<!DOCTYPE html><html><body style="background:#000;color:#fff;font-family:sans-serif;padding:32px;">
<div style="max-width:480px;margin:0 auto;background:#18181b;border:1px solid #3f3f46;border-radius:16px;padding:32px;">
  <div style="text-align:center;margin-bottom:24px;">
    <span style="color:#facc15;font-size:28px;font-weight:900;letter-spacing:-1px;">FitSense</span>
  </div>
  <h2 style="color:#fff;margin-bottom:8px;">Welcome, ' . htmlspecialchars($toName) . '!</h2>
  <p style="color:#a1a1aa;font-size:14px;line-height:1.6;">Your FitSense account has been created. Use the credentials below to log in for the first time.</p>
  <div style="background:#000;border:1px solid #3f3f46;border-radius:12px;padding:20px;margin:24px 0;">
    <p style="color:#a1a1aa;font-size:12px;margin:0 0 4px;">Username</p>
    <p style="color:#facc15;font-size:18px;font-weight:700;margin:0 0 16px;letter-spacing:1px;">' . htmlspecialchars($username) . '</p>
    <p style="color:#a1a1aa;font-size:12px;margin:0 0 4px;">Temporary Password</p>
    <p style="color:#facc15;font-size:18px;font-weight:700;margin:0;letter-spacing:1px;">' . htmlspecialchars($password) . '</p>
  </div>
  <p style="color:#a1a1aa;font-size:13px;line-height:1.6;">You will be asked to <strong style="color:#fff;">change your password</strong> on first login, then complete your health profile so your AI coach can personalise your experience.</p>
  <div style="text-align:center;margin-top:24px;">
    <a href="' . BASE_URL . 'login.php" style="background:#facc15;color:#000;font-weight:700;padding:14px 32px;border-radius:12px;text-decoration:none;font-size:15px;">Log In Now</a>
  </div>
  <p style="color:#52525b;font-size:11px;text-align:center;margin-top:24px;">If you did not expect this email, please ignore it.</p>
</div></body></html>';

        $text = "Welcome to FitSense, {$toName}!\n\n"
              . "Your account has been created.\n\n"
              . "Username: {$username}\n"
              . "Temporary Password: {$password}\n\n"
              . "Log in at: " . BASE_URL . "login.php\n\n"
              . "You will be asked to change your password on first login.";

        return $mailer->send($toEmail, $toName, $subject, $html, $text);
    }
}
