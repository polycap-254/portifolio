<?php
/**
 * contact.php
 * ------------------------------------------------------------
 * Handles the contact form POST from the home page.
 * Stores the message in the `messages` table and redirects back
 * with a flash message.
 *
 * Security:
 *  - CSRF token verified
 *  - Server-side validation
 *  - Prepared statements (no SQL injection)
 *  - Output escaped when displayed (admin side)
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

// Only accept POST
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    redirect(url('/#contact'));
}

// ---- CSRF ----
if (!verifyCsrf()) {
    redirect(url('/#contact'));
}

// ---- Collect + trim ----
$name    = clean($_POST['name']    ?? '');
$email   = clean($_POST['email']   ?? '');
$subject = clean($_POST['subject'] ?? '');
$message = cleanText($_POST['message'] ?? '');

// Keep old input on error
setOld([
    'name'    => $name,
    'email'   => $email,
    'subject' => $subject,
    'message' => $message,
]);

// ---- Validate ----
$errors = [];

if ($name === '' || mb_strlen($name) < 2) {
    $errors[] = 'Please enter your full name (at least 2 characters).';
} elseif (mb_strlen($name) > 120) {
    $errors[] = 'Name is too long (max 120 characters).';
}

if ($email === '') {
    $errors[] = 'Email is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
} elseif (mb_strlen($email) > 120) {
    $errors[] = 'Email is too long (max 120 characters).';
}

if ($subject === '' || mb_strlen($subject) < 3) {
    $errors[] = 'Please enter a subject (at least 3 characters).';
} elseif (mb_strlen($subject) > 180) {
    $errors[] = 'Subject is too long (max 180 characters).';
}

if ($message === '' || mb_strlen($message) < 10) {
    $errors[] = 'Please write a message (at least 10 characters).';
} elseif (mb_strlen($message) > 5000) {
    $errors[] = 'Message is too long (max 5000 characters).';
}

// Simple honeypot / rate-limit: block if same IP + email posted within 30 sec
if (empty($errors)) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM messages
             WHERE email = :e AND ip_address = :ip
               AND created_at > (NOW() - INTERVAL 30 SECOND)'
        );
        $stmt->execute([
            ':e'  => $email,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
        if ((int)$stmt->fetchColumn() > 0) {
            $errors[] = 'You just sent a message — please wait a moment before sending another.';
        }
    } catch (Throwable $e) {
        // Non-fatal; continue
    }
}

// ---- On error: flash + back to form ----
if ($errors) {
    foreach ($errors as $err) {
        setFlash('error', $err);
    }
    redirect(url('/#contact'));
}

// ---- Insert ----
try {
    $pdo = getDB();
    $stmt = $pdo->prepare(
        'INSERT INTO messages (name, email, subject, message, status, ip_address)
         VALUES (:name, :email, :subject, :message, "unread", :ip)'
    );
    $stmt->execute([
        ':name'    => $name,
        ':email'   => $email,
        ':subject' => $subject,
        ':message' => $message,
        ':ip'      => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);

    clearOld();
    $_SESSION['contact_sent'] = true;
    setFlash('success', 'Thanks, ' . $name . '! Your message has been sent. I will reply soon.');
} catch (Throwable $e) {
    if (APP_ENV === 'development') {
        setFlash('error', 'Database error: ' . $e->getMessage());
    } else {
        setFlash('error', 'Sorry, something went wrong while sending your message. Please try again later.');
    }
}

redirect(url('/#contact'));
