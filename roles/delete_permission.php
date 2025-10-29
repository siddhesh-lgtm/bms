<?php
// roles/delete_permission.php
require_once __DIR__ . '/../auth/auth-check.php';
require_once __DIR__ . '/../includes/helpers.php';

// Only Super Admins (or users with 'role.manage') can access this page
if (!hasPermission('role.manage', $conn)) {
    die('Access denied');
}

$permission_id = intval($_GET['id'] ?? 0);
$deleted = false;
$error = '';

if (!$permission_id) {
    $error = "Invalid permission ID.";
} else {
    // Handle POST confirmation
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verify_csrf($_POST['_csrf'] ?? '')) {
            $error = "Invalid CSRF token.";
        } else {
            // Before deleting, remove role_permissions mappings to prevent FK constraint error
            $conn->begin_transaction();
            try {
                $delMap = $conn->prepare("DELETE FROM role_permissions WHERE permission_id = ?");
                $delMap->bind_param("i", $permission_id);
                $delMap->execute();
                $delMap->close();

                $delPerm = $conn->prepare("DELETE FROM permissions WHERE id = ?");
                $delPerm->bind_param("i", $permission_id);
                $delPerm->execute();
                $delPerm->close();

                $conn->commit();
                $deleted = true;
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Error deleting permission: " . $e->getMessage();
            }
        }
    } else {
        // Fetch permission details for confirmation
        $stmt = $conn->prepare("SELECT code, description FROM permissions WHERE id = ?");
        $stmt->bind_param("i", $permission_id);
        $stmt->execute();
        $stmt->bind_result($code, $desc);
        if (!$stmt->fetch()) {
            $error = "Permission not found.";
        }
        $stmt->close();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Delete Permission</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card shadow-sm">
        <div class="card-body">
          <?php if ($deleted): ?>
            <div class="alert alert-success">
              ✅ Permission deleted successfully.
            </div>
            <a href="/bms/index.php" class="btn btn-outline-secondary">Home</a>
            <a href="permissions_list.php" class="btn btn-primary">Back to Permissions</a>
          <?php elseif ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
            <a href="/bms/index.php" class="btn btn-outline-secondary">Home</a>
            <a href="permissions_list.php" class="btn btn-secondary">Back</a>
          <?php else: ?>
            <h5 class="card-title mb-3 text-danger">Confirm Deletion</h5>
            <p>Are you sure you want to delete this permission?</p>
            <ul>
              <li><strong>Code:</strong> <code><?= e($code) ?></code></li>
              <li><strong>Description:</strong> <?= e($desc) ?></li>
            </ul>
            <form method="post">
              <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
              <a href="/bms/index.php" class="btn btn-outline-secondary">Home</a>
              <button type="submit" class="btn btn-danger">Yes, Delete</button>
              <a href="permissions_list.php" class="btn btn-secondary">Cancel</a>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
