<?php
// users/user_process.php
require_once __DIR__ . '/../auth/auth-check.php';
require_once __DIR__ . '/../includes/helpers.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) { http_response_code(400); echo 'Invalid ID'; exit; }

if ($action === 'deactivate') {
  if (!hasPermission('user.delete', $conn)) { die('Access denied'); }
  $stmt = $conn->prepare("UPDATE users SET is_active = 0, updated_at = NOW() WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  header("Location: users_list.php?deactivated=1");
  exit;
}

if ($action === 'activate') {
  if (!hasPermission('user.edit', $conn)) { die('Access denied'); }
  $stmt = $conn->prepare("UPDATE users SET is_active = 1, updated_at = NOW() WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  header("Location: users_list.php?activated=1");
  exit;
}

if ($action === 'delete') {
  if (!hasPermission('user.delete', $conn)) { die('Access denied'); }
  // Hard delete (optional). Prefer deactivate in most cases.
  $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  header("Location: users_list.php?deleted=1");
  exit;
}

http_response_code(400);
echo 'Invalid action';