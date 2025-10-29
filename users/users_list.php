<?php
// users/users_list.php
require_once __DIR__ . '/../auth/auth-check.php';
requirePermission('user.view', $conn);

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
  <input type="hidden" id="_csrf" value="<?= e(csrf_token()) ?>">
  <?php if(hasPermission('user.create',$conn)): ?>
    <a class="btn btn-success mb-3" href="user_create.php">Create User</a>
  <?php endif; ?>
  <table class="table table-striped">
    <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Role</th><th>Active</th><th>Actions</th></tr></thead>
    <tbody>
      <?php while($u = $res->fetch_assoc()): ?>
        <tr data-user-id="<?=e($u['id'])?>">
          <td><?=e($u['id'])?></td>
          <td><?=e($u['name'])?></td>
          <td><?=e($u['email'])?></td>
          <td><?=e($u['role_name'])?></td>
          <td class="js-active"><?= $u['is_active'] ? 'Yes' : 'No' ?></td>
          <td>
            <?php if(hasPermission('user.edit',$conn)): ?><a href="user_edit.php?id=<?=e($u['id'])?>" class="btn btn-sm btn-primary">Edit</a><?php endif; ?>
            <?php if(hasPermission('user.edit',$conn)): ?>
              <?php if($u['is_active']): ?>
                <button class="btn btn-sm btn-warning js-deactivate">Deactivate</button>
              <?php else: ?>
                <button class="btn btn-sm btn-success js-activate">Activate</button>
              <?php endif; ?>
            <?php endif; ?>
            <?php if(hasPermission('user.delete',$conn)): ?>
              <button class="btn btn-sm btn-danger js-delete">Delete</button>
            <?php endif; ?>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>
<script>
function showToast(message, type) {
  const toast = document.createElement('div');
  toast.className = 'alert alert-' + (type || 'info');
  toast.style.position = 'fixed';
  toast.style.top = '16px';
  toast.style.right = '16px';
  toast.style.zIndex = '9999';
  toast.textContent = message;
  document.body.appendChild(toast);
  setTimeout(() => toast.remove(), 2500);
}

document.addEventListener('DOMContentLoaded', function() {
  const csrf = document.getElementById('_csrf')?.value || '';
  document.querySelectorAll('tr[data-user-id]').forEach(function(row){
    const id = row.getAttribute('data-user-id');
    const activeCell = row.querySelector('.js-active');

    const btnDeactivate = row.querySelector('.js-deactivate');
    if (btnDeactivate) btnDeactivate.addEventListener('click', async function(){
      btnDeactivate.disabled = true;
      try {
        const res = await fetch('user_process.php', {
          method: 'POST',
          headers: { 'Accept': 'application/json' },
          body: new URLSearchParams({ action: 'deactivate', id, _csrf: csrf })
        });
        const data = res.headers.get('content-type')?.includes('application/json') ? await res.json() : { ok: res.ok };
        if (data.ok) {
          activeCell.textContent = 'No';
          if (btnDeactivate) btnDeactivate.replaceWith(createActivateButton(id, activeCell, csrf));
          showToast('User deactivated', 'success');
        } else {
          showToast('Failed to deactivate', 'danger');
        }
      } catch(e){ showToast('Network error', 'danger'); }
      finally { btnDeactivate.disabled = false; }
    });

    const btnActivate = row.querySelector('.js-activate');
    if (btnActivate) btnActivate.addEventListener('click', async function(){
      btnActivate.disabled = true;
      try {
        const res = await fetch('user_process.php', {
          method: 'POST',
          headers: { 'Accept': 'application/json' },
          body: new URLSearchParams({ action: 'activate', id, _csrf: csrf })
        });
        const data = res.headers.get('content-type')?.includes('application/json') ? await res.json() : { ok: res.ok };
        if (data.ok) {
          activeCell.textContent = 'Yes';
          if (btnActivate) btnActivate.replaceWith(createDeactivateButton(id, activeCell, csrf));
          showToast('User activated', 'success');
        } else {
          showToast('Failed to activate', 'danger');
        }
      } catch(e){ showToast('Network error', 'danger'); }
      finally { btnActivate.disabled = false; }
    });

    const btnDelete = row.querySelector('.js-delete');
    if (btnDelete) btnDelete.addEventListener('click', async function(){
      if (!confirm('Delete this user?')) return;
      btnDelete.disabled = true;
      try {
        const res = await fetch('user_process.php', {
          method: 'POST',
          headers: { 'Accept': 'application/json' },
          body: new URLSearchParams({ action: 'delete', id, _csrf: csrf })
        });
        const data = res.headers.get('content-type')?.includes('application/json') ? await res.json() : { ok: res.ok };
        if (data.ok) {
          row.remove();
          showToast('User deleted', 'success');
        } else {
          showToast('Failed to delete', 'danger');
        }
      } catch(e){ showToast('Network error', 'danger'); }
      finally { btnDelete.disabled = false; }
    });
  });

  function createActivateButton(id, activeCell, csrf) {
    const b = document.createElement('button');
    b.className = 'btn btn-sm btn-success js-activate';
    b.textContent = 'Activate';
    b.addEventListener('click', async function(){
      b.disabled = true;
      try {
        const res = await fetch('user_process.php', { method:'POST', headers:{'Accept':'application/json'}, body:new URLSearchParams({action:'activate', id, _csrf: csrf}) });
        const data = res.headers.get('content-type')?.includes('application/json') ? await res.json() : { ok: res.ok };
        if (data.ok) { activeCell.textContent = 'Yes'; b.replaceWith(createDeactivateButton(id, activeCell, csrf)); showToast('User activated', 'success'); }
        else { showToast('Failed to activate', 'danger'); }
      } catch(e){ showToast('Network error', 'danger'); }
      finally { b.disabled = false; }
    });
    return b;
  }

  function createDeactivateButton(id, activeCell, csrf) {
    const b = document.createElement('button');
    b.className = 'btn btn-sm btn-warning js-deactivate';
    b.textContent = 'Deactivate';
    b.addEventListener('click', async function(){
      b.disabled = true;
      try {
        const res = await fetch('user_process.php', { method:'POST', headers:{'Accept':'application/json'}, body:new URLSearchParams({action:'deactivate', id, _csrf: csrf}) });
        const data = res.headers.get('content-type')?.includes('application/json') ? await res.json() : { ok: res.ok };
        if (data.ok) { activeCell.textContent = 'No'; b.replaceWith(createActivateButton(id, activeCell, csrf)); showToast('User deactivated', 'success'); }
        else { showToast('Failed to deactivate', 'danger'); }
      } catch(e){ showToast('Network error', 'danger'); }
      finally { b.disabled = false; }
    });
    return b;
  }
});
</script>
</body>
</html>
