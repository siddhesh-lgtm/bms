<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['_csrf'] ?? '')) { $errors[] = 'Invalid CSRF'; }
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';

    if (!$name || !$email || !$pass) $errors[] = 'All fields required';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email';
    if ($pass !== $pass2) $errors[] = 'Passwords don\'t match';
    if (strlen($pass) < 6) $errors[] = 'Password must be 6+ chars';

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s",$email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = 'Email already registered';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $role_id = 3; // default user
            $insert = $conn->prepare("INSERT INTO users (name,email,password,role_id) VALUES (?,?,?,?)");
            $insert->bind_param("sssi",$name,$email,$hash,$role_id);
            $insert->execute();
            header("Location: login.php?registered=1");
            exit;
        }
        $stmt->close();
    }
}
?>
<!doctype html>
<html>
<head>
  <title>Register</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="card-title mb-0">Register</h4>
            <a class="btn btn-outline-secondary btn-sm" href="/bms/index.php">Home</a>
          </div>
          <?php foreach($errors as $err): ?>
            <div class="alert alert-danger"><?= e($err) ?></div>
          <?php endforeach; ?>
          <form method="post">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <div class="mb-2"><input class="form-control" name="name" placeholder="Full name" required></div>
            <div class="mb-2"><input class="form-control" name="email" type="email" placeholder="Email" required></div>
            <div class="mb-2"><input class="form-control" name="password" type="password" placeholder="Password" required></div>
            <div class="mb-3"><input class="form-control" name="password2" type="password" placeholder="Confirm password" required></div>
            <button class="btn btn-primary">Register</button>
            <a href="login.php" class="btn btn-link">Login</a>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
