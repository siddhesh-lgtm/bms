<?php
// books/index.php
require_once __DIR__ . '/../auth/auth-check.php';
$rows = $conn->query("SELECT b.*, u.name as creator FROM books b LEFT JOIN users u ON b.created_by = u.id WHERE b.deleted_at IS NULL ORDER BY b.id DESC");
?>
<!doctype html>
<html>
<head>
  <title>Books</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">
  <input type="hidden" id="_csrf" value="<?= e(csrf_token()) ?>">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Books</h3>
    <div>
      <?php if(hasPermission('book.create',$conn)): ?><a class="btn btn-success" href="create.php">Add Book</a><?php endif; ?>
      <a class="btn btn-outline-secondary" href="/bms/index.php">Home</a>
    </div>
  </div>

  <table id="booksTable" class="table table-striped">
    <thead><tr><th>Cover</th><th>Title</th><th>Author</th><th>Type</th><th>Actions</th></tr></thead>
    <tbody>
      <?php while($b = $rows->fetch_assoc()): ?>
        <tr data-book-id="<?=e($b['id'])?>">
          <td style="width:80px;">
          <?php
$coverRel = $b['cover']; // e.g. assets/uploads/xxx.jpg
$coverFs  = __DIR__ . '/../' . $coverRel;
$coverUrl = '/bms/' . $coverRel;
?>
<?php if (!empty($coverRel) && file_exists($coverFs)): ?>
  <img src="<?= e($coverUrl) ?>" style="height:70px; width:50px; object-fit:cover;" class="img-thumbnail" alt="cover">
<?php else: ?>
  <div class="text-muted small">No cover</div>
<?php endif; ?>
          </td>
          <td><?= e($b['title']) ?></td>
          <td><?= e($b['author']) ?></td>
          <td><?= e($b['type']) ?></td>
          <td>
            <a class="btn btn-sm btn-primary" href="view.php?id=<?=e($b['id'])?>">View</a>
            <?php if(hasPermission('book.edit',$conn)): ?><a class="btn btn-sm btn-warning" href="edit.php?id=<?=e($b['id'])?>">Edit</a><?php endif; ?>
            <?php if(hasPermission('book.delete',$conn)): ?><button class="btn btn-sm btn-danger js-book-delete">Delete</button><?php endif; ?>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function(){ $('#booksTable').DataTable(); });
</script>
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

document.addEventListener('DOMContentLoaded', function(){
  const csrf = document.getElementById('_csrf')?.value || '';
  document.querySelectorAll('tr[data-book-id]').forEach(function(row){
    const id = row.getAttribute('data-book-id');
    const btn = row.querySelector('.js-book-delete');
    if (!btn) return;
    btn.addEventListener('click', async function(){
      if (!confirm('Delete this book?')) return;
      btn.disabled = true;
      try {
        const res = await fetch('process.php', {
          method: 'POST',
          headers: { 'Accept': 'application/json' },
          body: new URLSearchParams({ action: 'delete', id, _csrf: csrf })
        });
        const data = res.headers.get('content-type')?.includes('application/json') ? await res.json() : { ok: res.ok };
        if (data.ok) { row.remove(); showToast('Book deleted', 'success'); }
        else { showToast('Delete failed', 'danger'); }
      } catch(e){ showToast('Network error', 'danger'); }
      finally { btn.disabled = false; }
    });
  });
});
</script>
</body>
</html>
