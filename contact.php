<?php
declare(strict_types=1);

/* Configure in the hosting panel, never in public source:
 * AEDMEDIA_CONTACT_TO = private administrator inbox
 * AEDMEDIA_FROM_EMAIL = sender address authorized by your host
 */
function finish(int $status, string $title, string $message): void {
    http_response_code($status);
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer');
    header('Cache-Control: no-store');
    $t = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $m = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.$t.' — AEDMedia</title><link rel="stylesheet" href="assets/css/styles.css"></head><body><main class="section" style="min-height:75vh;display:grid;align-content:center"><div class="eyebrow">AEDMedia · Contact</div><h1 style="font:600 clamp(2.8rem,7vw,6rem)/1 var(--display);letter-spacing:-.07em">'.$t.'</h1><p class="lead">'.$m.'</p><p><a class="button" href="contact.html">BACK TO CONTACT ↗</a></p></main></body></html>';
    exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    finish(405, 'Method not allowed', 'Please use the contact form to send an enquiry.');
}
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 12000) {
    finish(413, 'Message too large', 'Please shorten your message and try again.');
}
/* Reject cross-origin browser form posts when Origin is supplied. */
$host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
$origin = (string)($_SERVER['HTTP_ORIGIN'] ?? '');
if ($origin !== '') {
    $originHost = strtolower((string)(parse_url($origin, PHP_URL_HOST) ?? ''));
    if ($host === '' || $originHost === '' || !hash_equals($host, $originHost)) {
        finish(403, 'Request not accepted', 'Please submit this form directly from the AEDMedia website.');
    }
}
/* Hidden honeypot field: normal visitors leave it blank. */
if (trim((string)($_POST['website'] ?? '')) !== '') {
    finish(200, 'Thank you', 'Your enquiry has been received.');
}
$name = trim((string)($_POST['name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$company = trim((string)($_POST['company'] ?? ''));
$type = trim((string)($_POST['type'] ?? 'General enquiry'));
$message = trim((string)($_POST['message'] ?? ''));
if ($name === '' || strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254 || $message === '' || strlen($message) > 8000 || strlen($company) > 160) {
    finish(422, 'Check your details', 'Please provide a valid name, email address and message.');
}
if (preg_match('/[\r\n]/', $name) || preg_match('/[\r\n]/', $email)) {
    finish(422, 'Check your details', 'Please review the information and try again.');
}
$allowedTypes = ['Show / event booking', 'Event production', 'Hosting / talent', 'Brand partnership', 'General enquiry'];
if (!in_array($type, $allowedTypes, true)) $type = 'General enquiry';

/* Basic throttling: at most 3 submissions per IP in 15 minutes. */
$ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
$key = hash('sha256', $ip . '|aedmedia-contact');
$rateFile = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'aedmedia-' . $key . '.json';
$now = time();
$fp = @fopen($rateFile, 'c+');
if ($fp === false) finish(503, 'Temporarily unavailable', 'Please try again later.');
flock($fp, LOCK_EX);
$stored = json_decode(stream_get_contents($fp) ?: '[]', true);
$attempts = is_array($stored) ? array_values(array_filter($stored, static fn($v) => is_int($v) && $v > $now - 900)) : [];
if (count($attempts) >= 3) {
    flock($fp, LOCK_UN); fclose($fp);
    finish(429, 'Please wait a little', 'Too many enquiries were submitted from this connection. Please try again in about 15 minutes.');
}
$attempts[] = $now;
rewind($fp); ftruncate($fp, 0); fwrite($fp, json_encode($attempts)); fflush($fp);
flock($fp, LOCK_UN); fclose($fp);

/* The recipient is private server configuration, never visitor input. */
$recipient = getenv('AEDMEDIA_CONTACT_TO') ?: '';
$sender = getenv('AEDMEDIA_FROM_EMAIL') ?: '';
if (!filter_var($recipient, FILTER_VALIDATE_EMAIL) || !filter_var($sender, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $recipient . $sender)) {
    error_log('AEDMedia contact: private mail environment variables are missing or invalid.');
    finish(503, 'Enquiries temporarily unavailable', 'The website administrator needs to finish the private mail configuration.');
}
$subject = 'AEDMedia website enquiry: ' . $type;
$body = "A new enquiry was submitted through the AEDMedia website.\n\n"
    . "Name: {$name}\nEmail: {$email}\nCompany: " . ($company !== '' ? $company : 'Not provided')
    . "\nEnquiry type: {$type}\n\nMessage:\n{$message}\n";
$headers = [
    'From: AEDMedia Website <' . $sender . '>',
    'Reply-To: ' . $email,
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8'
];
/* Fixed recipient; no open relay and no recipient field in the form. */
$sent = @mail($recipient, $subject, $body, implode("\r\n", $headers));
if (!$sent) {
    error_log('AEDMedia contact: PHP mail() returned false.');
    finish(503, 'Message not sent', 'The mail service could not accept your enquiry just now. Please try again later.');
}
finish(200, 'Thank you', 'Your enquiry has been sent to the AEDMedia team.');
