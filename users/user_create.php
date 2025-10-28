<?php
// users/user_create.php
require_once __DIR__ . '/../auth/auth-check.php';
if (!hasPermission('user.create',$conn) && !hasPermission('user.edit',$conn)) {
    die('Access denied');
}
$roles = $conn->query("SELECT id,name FROM roles ORDER BY id");
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['_csrf'] ?? '')) die('CSRF');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role_id = intval($_POST['role_id'] ?? 3);

    if (!$name || !$email || !$password) $errors[] = 'All fields required';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email';
    if (empty($errors)) {
        $h = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (name,email,password,role_id) VALUES (?,?,?,?)");
        $stmt->bind_param("sssi",$name,$email,$h,$role_id);
        if ($stmt->execute()) {
            header("Location: users_list.php?created=1");
            exit;
        } else {
            $errors[] = 'DB error: ' . $conn->error;
        }
    }
}
?>
<!doctype html>
<html>
<head><title>Create user</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">
  <h4>Create user</h4>
  <?php foreach($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>
  <form method="post">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
    <div class="mb-2"><input class="form-control" name="name" placeholder="Name" required></div>
    <div class="mb-2"><input class="form-control" name="email" type="email" placeholder="Email" required></div>
    <div class="mb-2"><input class="form-control" name="password" type="password" placeholder="Password" required></div>
    <div class="mb-2">
      <select class="form-select" name="role_id">
        <?php while($r = $roles->fetch_assoc()): ?>
          <option value="<?=e($r['id'])?>"><?=e($r['name'])?></option>
        <?php endwhile; ?>
      </select>
    </div>
    <button class="btn btn-primary">Create</button>
    <a class="btn btn-link" href="users_list.php">Back</a>
  </form>
</div>
</body>
</html>
