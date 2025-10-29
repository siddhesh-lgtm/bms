<?php

require_once __DIR__ . '/../auth/auth-check.php';
requirePermission('role.manage', $conn);

$role_id = intval($_GET['role_id'] ?? 0);
if (!$role_id) { echo "Invalid role"; exit; }

// handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['_csrf'] ?? '')) { die('CSRF'); }
    $permIds = $_POST['perms'] ?? []; // array of permission ids
    $ok = updateRolePermissions($role_id, $permIds, $conn);
    if (is_ajax()) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => (bool)$ok]);
        exit;
    } else {
        header("Location: edit_permissions.php?role_id={$role_id}&saved=1");
        exit;
    }
}

// fetch all permissions
$permsRes = $conn->query("SELECT id,code,description FROM permissions ORDER BY code");
$assigned = getPermissionsForRole($role_id, $conn);
?>
<!doctype html>
<html>
<head>
  <title>Edit Permissions</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">
  <h4>Edit Permissions for Role #<?=e($role_id)?></h4>
  <?php if(!empty($_GET['saved'])): ?><div class="alert alert-success">Saved</div><?php endif; ?>
  <div id="alertBox"></div>
  <form method="post" id="permForm">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
    <div class="list-group">
    <?php while($p = $permsRes->fetch_assoc()): 
        $checked = in_array($p['code'], $assigned) ? 'checked' : '';
    ?>
      <label class="list-group-item">
        <input type="checkbox" name="perms[]" value="<?=e($p['id'])?>" <?= $checked ?>> <?= e($p['code']) ?> - <?= e($p['description']) ?>
      </label>
    <?php endwhile; ?>
    </div>
    <div class="mt-3">
      <button class="btn btn-primary" id="saveBtn">Save</button>
      <a href="index.php" class="btn btn-link">Back</a>
    </div>
  </form>
</div>
</body>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('permForm');
  const saveBtn = document.getElementById('saveBtn');
  const alertBox = document.getElementById('alertBox');
  if (!form) return;
  form.addEventListener('submit', async function(e) {
    e.preventDefault();
    saveBtn.disabled = true;
    const formData = new FormData(form);
    try {
      const res = await fetch(window.location.href, {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body: formData
      });
      let ok = false;
      if (res.headers.get('content-type')?.includes('application/json')) {
        const data = await res.json();
        ok = !!data.ok;
      } else {
        ok = res.ok;
      }
      alertBox.innerHTML = '<div class="alert ' + (ok ? 'alert-success' : 'alert-danger') + '">' + (ok ? 'Saved' : 'Save failed') + '</div>';
    } catch (err) {
      alertBox.innerHTML = '<div class="alert alert-danger">Network error</div>';
    } finally {
      saveBtn.disabled = false;
    }
  });
});
</script>
</html>

