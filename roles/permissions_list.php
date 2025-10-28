<?php
// roles/permissions_list.php
require_once __DIR__ . '/../auth/auth-check.php';
require_once __DIR__ . '/../includes/helpers.php';

// Permission check — only super_admin (or anyone with 'role.manage') can access
if (!hasPermission('role.manage', $conn)) {
    die('Access denied');
}

// Handle form submission
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['_csrf'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $code = trim($_POST['code'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        
        if (!$code) $errors[] = 'Permission code is required.';
        if (empty($errors)) {
            // Check if permission already exists
            $check = $conn->prepare("SELECT id FROM permissions WHERE code = ?");
            $check->bind_param("s", $code);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $errors[] = 'Permission code already exists.';
            } else {
                $stmt = $conn->prepare("INSERT INTO permissions (code, description) VALUES (?, ?)");
                $stmt->bind_param("ss", $code, $desc);
                if ($stmt->execute()) {
                    $success = "Permission <strong>$code</strong> added successfully!";
                } else {
                    $errors[] = "Database error: " . $conn->error;
                }
                $stmt->close();
            }
        }
    }
}

// Fetch all permissions
$perms = $conn->query("SELECT * FROM permissions ORDER BY id ASC");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Permissions</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Manage Permissions</h4>
    <div>
      <a href="index.php" class="btn btn-outline-secondary btn-sm">Back to Roles</a>
    </div>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
  <?php endif; ?>
  <?php foreach ($errors as $err): ?>
    <div class="alert alert-danger"><?= e($err) ?></div>
  <?php endforeach; ?>

  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <h5 class="card-title mb-3">Add New Permission</h5>
      <form method="post">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <div class="mb-3">
          <label class="form-label">Permission Code</label>
          <input type="text" name="code" class="form-control" placeholder="e.g. book.create" required>
          <small class="text-muted">Use a consistent naming convention like <code>module.action</code>.</small>
        </div>
        <div class="mb-3">
          <label class="form-label">Description (optional)</label>
          <input type="text" name="description" class="form-control" placeholder="Describe this permission">
        </div>
        <button class="btn btn-primary">Add Permission</button>
      </form>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <h5 class="card-title mb-3">Existing Permissions</h5>
      <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
        <thead class="table-light">
  <tr>
    <th>ID</th>
    <th>Code</th>
    <th>Description</th>
    <th>Actions</th> <!-- ✅ Add this -->
  </tr>
</thead>

          <tbody>
  <?php while($p = $perms->fetch_assoc()): ?>
    <tr>
      <td><?= e($p['id']) ?></td>
      <td><code><?= e($p['code']) ?></code></td>
      <td><?= e($p['description']) ?></td>
      <td>
        <a href="delete_permission.php?id=<?= e($p['id']) ?>" 
           class="btn btn-sm btn-danger"
           onclick="return confirm('Delete this permission?')">
           Delete
        </a>
      </td>
    </tr>
  <?php endwhile; ?>
</tbody>

          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</body>
</html>
