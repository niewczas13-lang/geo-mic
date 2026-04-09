<?php
header('Content-Type: application/json; charset=utf-8');

$to = 'geo@geo-mic.radom.pl';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Nieprawidlowe zadanie.']);
    exit;
}

if (!empty($_POST['website'])) {
    echo json_encode(['success' => true]);
    exit;
}

$form_type = trim($_POST['form_type'] ?? 'contact');
$job_title = trim($_POST['job_title'] ?? '');
$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');
$cv_link = trim($_POST['cv_link'] ?? '');

$form_type = str_replace(["\r", "\n", "%0a", "%0d"], '', $form_type);
$job_title = str_replace(["\r", "\n", "%0a", "%0d"], '', $job_title);
$name = str_replace(["\r", "\n", "%0a", "%0d"], '', $name);
$email = str_replace(["\r", "\n", "%0a", "%0d"], '', $email);
$phone = str_replace(["\r", "\n", "%0a", "%0d"], '', $phone);
$cv_link = str_replace(["\r", "\n", "%0a", "%0d"], '', $cv_link);

if ($name === '' || $email === '' || $message === '') {
    echo json_encode(['success' => false, 'error' => 'Wypelnij wymagane pola (imie, e-mail, wiadomosc).']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Podaj poprawny adres e-mail.']);
    exit;
}

if (
    mb_strlen($form_type) > 30 ||
    mb_strlen($job_title) > 200 ||
    mb_strlen($name) > 200 ||
    mb_strlen($phone) > 30 ||
    mb_strlen($email) > 254 ||
    mb_strlen($cv_link) > 500 ||
    mb_strlen($message) > 5000
) {
    echo json_encode(['success' => false, 'error' => 'Przekroczono dopuszczalna dlugosc pol.']);
    exit;
}

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$lock_dir = __DIR__ . '/.form-locks/';
if (!is_dir($lock_dir)) {
    mkdir($lock_dir, 0700, true);
}
$lock_file = $lock_dir . md5($ip) . '.lock';
if (file_exists($lock_file) && (time() - filemtime($lock_file)) < 60) {
    echo json_encode(['success' => false, 'error' => 'Poczekaj chwile przed wyslaniem kolejnej wiadomosci.']);
    exit;
}

$date = date('d.m.Y, H:i');
$subject_prefix = $form_type === 'career' ? 'Aplikacja z formularza' : 'Zapytanie ze strony';
$mail_intro = $form_type === 'career' ? 'Nowa aplikacja ze strony internetowej' : 'Nowe zapytanie ze strony internetowej';
$safe_name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$safe_phone = htmlspecialchars($phone !== '' ? $phone : '-', ENT_QUOTES, 'UTF-8');
$safe_email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
$safe_cv_link = htmlspecialchars($cv_link !== '' ? $cv_link : '-', ENT_QUOTES, 'UTF-8');
$safe_job_title = htmlspecialchars($job_title !== '' ? $job_title : '-', ENT_QUOTES, 'UTF-8');
$safe_message = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
$safe_ip = htmlspecialchars($ip, ENT_QUOTES, 'UTF-8');

$career_rows = '';
if ($form_type === 'career') {
    $career_rows = <<<HTML
                <tr><td style="height:8px;"></td></tr>
                <tr>
                    <td style="padding:12px 16px;background:#141110;border-radius:10px;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td width="50%">
                                    <p style="margin:0 0 4px;font-size:11px;color:#8a7e78;text-transform:uppercase;letter-spacing:0.1em;">Stanowisko</p>
                                    <p style="margin:0;font-size:15px;color:#ede8e3;">{$safe_job_title}</p>
                                </td>
                                <td width="50%">
                                    <p style="margin:0 0 4px;font-size:11px;color:#8a7e78;text-transform:uppercase;letter-spacing:0.1em;">CV / LinkedIn</p>
                                    <p style="margin:0;font-size:15px;color:#d4a043;word-break:break-word;">{$safe_cv_link}</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
HTML;
}

$html_body = <<<HTML
<!DOCTYPE html>
<html lang="pl">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#0c0a08;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#0c0a08;padding:40px 20px;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#1a1614;border-radius:16px;overflow:hidden;border:1px solid rgba(155,145,138,0.15);">
    <tr>
        <td style="background:linear-gradient(135deg,#1a1614,#221e1a);padding:32px 40px;border-bottom:2px solid #d4a043;">
            <h1 style="margin:0;font-size:22px;color:#ede8e3;font-weight:700;letter-spacing:0.05em;">
                GEO<span style="color:#d4a043;">-MIC</span>
            </h1>
            <p style="margin:8px 0 0;font-size:13px;color:#9b918a;letter-spacing:0.03em;">{$mail_intro}</p>
        </td>
    </tr>
    <tr>
        <td style="padding:32px 40px;">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="padding:12px 16px;background:#141110;border-radius:10px;">
                        <p style="margin:0 0 4px;font-size:11px;color:#8a7e78;text-transform:uppercase;letter-spacing:0.1em;">Imie i nazwisko</p>
                        <p style="margin:0;font-size:16px;color:#ede8e3;font-weight:600;">{$safe_name}</p>
                    </td>
                </tr>
                <tr><td style="height:8px;"></td></tr>
                <tr>
                    <td style="padding:12px 16px;background:#141110;border-radius:10px;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td width="50%">
                                    <p style="margin:0 0 4px;font-size:11px;color:#8a7e78;text-transform:uppercase;letter-spacing:0.1em;">Telefon</p>
                                    <p style="margin:0;font-size:15px;color:#ede8e3;">{$safe_phone}</p>
                                </td>
                                <td width="50%">
                                    <p style="margin:0 0 4px;font-size:11px;color:#8a7e78;text-transform:uppercase;letter-spacing:0.1em;">E-mail</p>
                                    <p style="margin:0;font-size:15px;color:#d4a043;"><a href="mailto:{$safe_email}" style="color:#d4a043;text-decoration:none;">{$safe_email}</a></p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                {$career_rows}
                <tr><td style="height:8px;"></td></tr>
                <tr>
                    <td style="padding:16px;background:#141110;border-radius:10px;">
                        <p style="margin:0 0 8px;font-size:11px;color:#8a7e78;text-transform:uppercase;letter-spacing:0.1em;">Wiadomosc</p>
                        <p style="margin:0;font-size:15px;color:#ede8e3;line-height:1.7;">{$safe_message}</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td style="padding:20px 40px;border-top:1px solid rgba(155,145,138,0.12);">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td><p style="margin:0;font-size:12px;color:#8a7e78;">Wyslano: {$date}</p></td>
                    <td align="right"><p style="margin:0;font-size:12px;color:#8a7e78;">IP: {$safe_ip}</p></td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</td></tr>
</table>
</body>
</html>
HTML;

$subject = "=?UTF-8?B?" . base64_encode("{$subject_prefix} - {$name}") . "?=";

$headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "From: GEO-MIC Formularz <noreply@geo-mic.radom.pl>\r\n";
$headers .= "Reply-To: {$name} <{$email}>\r\n";
$headers .= "X-Mailer: GEO-MIC/1.0\r\n";

$sent = mail($to, $subject, $html_body, $headers);

if ($sent) {
    @touch($lock_file);
    echo json_encode(['success' => true]);
} else {
    error_log("GEO-MIC form: mail() failed for {$safe_email} from IP {$safe_ip}");
    echo json_encode(['success' => false, 'error' => 'Nie udalo sie wyslac wiadomosci. Zadzwon: 793 369 234.']);
}
