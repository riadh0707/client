<?php
/** api/newsletter.php - Inscription newsletter. */
require_once __DIR__ . '/../includes/bootstrap.php';
$body = json_decode(file_get_contents('php://input'), true) ?: [];
if (is_post()) { csrf_check(); }
$email = trim((string) ($body['email'] ?? ''));
if (!valid_email($email)) { json_response(['ok' => false, 'message' => 'Adresse e-mail invalide.']); }
if (!rate_limit('newsletter', 5, 60)) { json_response(['ok' => false, 'message' => 'Trop de tentatives, réessayez plus tard.']); }
try { Database::run('INSERT INTO newsletter (email) VALUES (?)', [$email]); }
catch (Throwable $e) { json_response(['ok' => true, 'message' => 'Cette adresse est déjà inscrite. Merci !']); }
json_response(['ok' => true, 'message' => 'Merci ! Votre inscription est enregistrée.']);
