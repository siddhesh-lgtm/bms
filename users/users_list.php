<?php
// users/users_list.php
require_once __DIR__ . '/../auth/auth-check.php';
if (!hasPermission('user.view', $conn)) die('Access denied');

$res = $conn->query("SELECT u.id,u.name,u.email,u.is_active,r.name as role_name FROM users u JOIN roles r ON r.id = u.role_id ORDER BY u.id DESC");
?>
<!doctype html>
<html>
<head>
  <title>Users</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Users</h4>
    <a class="btn btn-outline-secondary btn-sm" href="/bms/index.php">Home</a>
  </div>
  <?php if(hasPermission('user.create',$conn)): ?>
    <a class="btn btn-success mb-3" href="user_create.php">Create User</a>
  <?php endif; ?>
  <table class="table table-striped">
    <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Role</th><th>Active</th><th>Actions</th></tr></thead>
    <tbody>
      <?php while($u = $res->fetch_assoc()): ?>
        <tr>
          <td><?=e($u['id'])?></td>
          <td><?=e($u['name'])?></td>
          <td><?=e($u['email'])?></td>
          <td><?=e($u['role_name'])?></td>
          <td><?= $u['is_active'] ? 'Yes' : 'No' ?></td>
          <td>
            <?php if(hasPermission('user.edit',$conn)): ?><a href="user_edit.php?id=<?=e($u['id'])?>" class="btn btn-sm btn-primary">Edit</a><?php endif; ?>
            <?php if(hasPermission('user.delete',$conn)): ?><a href="user_process.php?action=deactivate&id=<?=e($u['id'])?>" class="btn btn-sm btn-danger" onclick="return confirm('Deactivate?')">Deactivate</a><?php endif; ?>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>
</body>
</html>
