<?php
// auth/login.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['_csrf'] ?? '')) { $err = 'Invalid CSRF'; }
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';

    if (!$email || !$pass) $err = 'Provide email and password';
    if (!$err) {
        $stmt = $conn->prepare("SELECT id,name,password,role_id,is_active FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s",$email);
        $stmt->execute();
        $stmt->bind_result($id,$name,$hash,$role_id,$is_active);
        if ($stmt->fetch()) {
            if (!$is_active) {
                $err = 'Account deactivated';
            } elseif (password_verify($pass, $hash)) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $id;
                $_SESSION['user_name'] = $name;
                $_SESSION['role_id'] = $role_id;
                header("Location: /bms/index.php");
                exit;
            } else {
                $err = 'Invalid credentials';
            }
        } else {
            $err = 'Invalid credentials';
        }
        $stmt->close();
    }
}
?>
<!doctype html>
<html>
<head>
  <title>Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h4 class="mb-0">Login</h4>
            <a class="btn btn-outline-secondary btn-sm" href="/bms/index.php">Home</a>
          </div>
          <?php if($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>
          <?php if(!empty($_GET['registered'])): ?><div class="alert alert-success">Registered. Login now.</div><?php endif; ?>
          <form method="post">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <div class="mb-2"><input class="form-control" name="email" type="email" placeholder="Email" required></div>
            <div class="mb-3"><input class="form-control" name="password" type="password" placeholder="Password" required></div>
            <button class="btn btn-primary">Login</button>
            <a class="btn btn-link" href="register.php">Register</a>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
