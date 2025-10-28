<?php
// roles/index.php
require_once __DIR__ . '/../auth/auth-check.php';
if (!hasPermission('role.manage', $conn)) {
    die('Access denied');
}

$res = $conn->query("SELECT * FROM roles ORDER BY id");
?>
<!doctype html>
<html>
<head>
  <title>Roles</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">
  <h3>Roles</h3>
  <table class="table table-striped">
    <thead><tr><th>ID</th><th>Name</th><th>Description</th><th>Actions</th></tr></thead>
    <tbody>
      <?php while($r = $res->fetch_assoc()): ?>
        <tr>
          <td><?=e($r['id'])?></td>
          <td><?=e($r['name'])?></td>
          <td><?=e($r['description'])?></td>
          <td><a class="btn btn-sm btn-primary" href="edit_permissions.php?role_id=<?=e($r['id'])?>">Edit Permissions</a></td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
  <a href="/index.php" class="btn btn-link">Home</a>
</div>
</body>
</html>
