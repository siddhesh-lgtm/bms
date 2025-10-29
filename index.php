<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

if (!isLoggedIn()) {
    header('Location: auth/login.php');
    exit;
}

// Fetch role name for header display
$roleName = '';
$roleId = currentUserRoleId();
$stmt = $conn->prepare("SELECT name FROM roles WHERE id = ?");
$stmt->bind_param("i", $roleId);
$stmt->execute();
$stmt->bind_result($roleName);
$stmt->fetch();
$stmt->close();
?>
<!doctype html>

<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="p-4 bg-light">
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Welcome, <?= e(currentUserName()) ?> (<?= e($roleName ?: ('role #' . (int)$roleId)) ?>)</h4>
    <a class="btn btn-outline-danger" href="auth/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
  </div>

  <div class="row g-3">
    <div class="col-md-4">
      <div class="card h-100 shadow-sm">
        <div class="card-body">
          <h5 class="card-title">Books</h5>
          <p class="text-muted">Browse, create, edit, and delete books (based on permissions).</p>
          <div class="d-flex gap-2">
            <a href="books/index.php" class="btn btn-primary btn-sm"><i class="bi bi-journal-bookmark"></i> View Books</a>
            <?php if (hasPermission('book.create', $conn)): ?>
              <a href="books/create.php" class="btn btn-success btn-sm"><i class="bi bi-plus-lg"></i> Add Book</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card h-100 shadow-sm">
        <div class="card-body">
          <h5 class="card-title">Users</h5>
          <p class="text-muted">Manage application users.</p>
          <div class="d-flex gap-2">
            <?php if (hasPermission('user.view', $conn)): ?>
              <a href="users/users_list.php" class="btn btn-primary btn-sm"><i class="bi bi-people"></i> View Users</a>
            <?php else: ?>
              <button class="btn btn-secondary btn-sm" disabled>View Users</button>
            <?php endif; ?>
            <?php if (hasPermission('user.create', $conn)): ?>
              <a href="users/user_create.php" class="btn btn-success btn-sm"><i class="bi bi-person-plus"></i> Create User</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card h-100 shadow-sm">
        <div class="card-body">
          <h5 class="card-title">Roles & Permissions</h5>
          <p class="text-muted">RBAC administration.</p>
          <div class="d-flex gap-2">
            <?php if (hasPermission('role.manage', $conn)): ?>
              <a href="roles/index.php" class="btn btn-primary btn-sm"><i class="bi bi-shield-lock"></i> Manage Roles</a>
              <a href="roles/permissions_list.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-key"></i> Permissions</a>
            <?php else: ?>
              <button class="btn btn-secondary btn-sm" disabled>Manage Roles</button>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="mt-4">
    <div class="alert alert-info mb-0">
      <!-- Tip: Use the Roles & Permissions section to assign capabilities to each role. After updates, log out and back in to refresh your session. -->
    </div>
  </div>
</div>

</body>
</html>

