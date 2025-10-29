<?php
// users/user_edit.php
require_once __DIR__ . '/../auth/auth-check.php';
require_once __DIR__ . '/../includes/helpers.php';

if (!hasPermission('user.edit', $conn)) { die('Access denied'); }

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(400); echo 'Invalid ID'; exit; }

$errors = [];
$roles = $conn->query("SELECT id,name FROM roles ORDER BY id");

// Load user
$stmt = $conn->prepare("SELECT id,name,email,role_id,is_active FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();
if (!$user) { http_response_code(404); echo 'User not found'; exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!verify_csrf($_POST['_csrf'] ?? '')) { $errors[] = 'Invalid CSRF token'; }
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $role_id = intval($_POST['role_id'] ?? $user['role_id']);
  $password = $_POST['password'] ?? '';

  if (!$name || !$email) $errors[] = 'Name and email are required';
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email';

  if (empty($errors)) {
    if ($password !== '') {
      $hash = password_hash($password, PASSWORD_DEFAULT);
      $stmt = $conn->prepare("UPDATE users SET name=?, email=?, password=?, role_id=?, updated_at=NOW() WHERE id=?");
      $stmt->bind_param("sssii", $name, $email, $hash, $role_id, $id);
    } else {
      $stmt = $conn->prepare("UPDATE users SET name=?, email=?, role_id=?, updated_at=NOW() WHERE id=?");
      $stmt->bind_param("ssii", $name, $email, $role_id, $id);
    }
    $stmt->execute();
    header("Location: users_list.php?updated=1");
    exit;
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Edit User</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Edit User</h4>
    <div>
      <a class="btn btn-outline-secondary btn-sm" href="/bms/index.php">Home</a>
      <a class="btn btn-outline-secondary btn-sm" href="users_list.php">Back</a>
    </div>
  </div>

  <?php foreach($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>

  <form method="post">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
    <div class="mb-3">
      <label class="form-label">Name</label>
      <input class="form-control" name="name" value="<?= e($user['name']) ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input class="form-control" type="email" name="email" value="<?= e($user['email']) ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Password (leave blank to keep unchanged)</label>
      <input class="form-control" type="password" name="password" placeholder="New password">
    </div>
    <div class="mb-3">
      <label class="form-label">Role</label>
      <select class="form-select" name="role_id">
        <?php while($r = $roles->fetch_assoc()): ?>
          <option value="<?= e($r['id']) ?>" <?= $r['id']==$user['role_id']?'selected':'' ?>><?= e($r['name']) ?></option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Active</label>
      <input class="form-check-input ms-2" type="checkbox" disabled <?= $user['is_active'] ? 'checked' : '' ?>>
      <div class="form-text">Use Deactivate/Activate actions to change status.</div>
    </div>
    <button class="btn btn-primary">Save</button>
    <a class="btn btn-link" href="users_list.php">Cancel</a>
  </form>
</div>
</body>
</html>