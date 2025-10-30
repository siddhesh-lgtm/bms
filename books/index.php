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
      <?php if(hasPermission('book.create',$conn)): ?><button class="btn btn-success js-open-create">Add Book</button><?php endif; ?>
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
            <?php if(hasPermission('book.edit',$conn)): ?><button class="btn btn-sm btn-warning js-open-edit" data-id="<?=e($b['id'])?>">Edit</button><?php endif; ?>
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
<!-- Modal used for Create/Edit -->
<div class="modal fade" id="bookModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Book</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="text-center text-muted">Loading…</div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function(){
  const csrf = document.getElementById('_csrf')?.value || '';
  const modalEl = document.getElementById('bookModal');
  const modal = new bootstrap.Modal(modalEl);
  const body = modalEl.querySelector('.modal-body');

  async function loadFragment(url) {
    body.innerHTML = '<div class="text-center text-muted">Loading…</div>';
    modal.show();
    try {
      const res = await fetch(url, { headers: { 'Accept': 'text/html' } });
      if (!res.ok) { body.innerHTML = '<div class="text-danger">Unable to load</div>'; return null; }
      const html = await res.text();
      body.innerHTML = html;
      return body;
    } catch (e) { body.innerHTML = '<div class="text-danger">Network error</div>'; return null; }
  }

  document.querySelectorAll('.js-open-create').forEach(function(btn){
    btn.addEventListener('click', function(){
      loadFragment('create.php?ajax=1');
    });
  });

  document.querySelectorAll('.js-open-edit').forEach(function(btn){
    btn.addEventListener('click', function(){
      const id = btn.getAttribute('data-id');
      loadFragment('edit.php?ajax=1&id=' + encodeURIComponent(id)).then(function(container){
        if (!container) return;
        const form = container.querySelector('.js-ajax-edit-form');
        if (form) attachAjaxForm(form);
      });
    });
  });

  function attachAjaxForm(form) {
    form.addEventListener('submit', async function(e){
      e.preventDefault();
      const submitBtn = form.querySelector('button[type=submit]') || form.querySelector('button');
      if (submitBtn) submitBtn.disabled = true;
      const fd = new FormData(form);
      if (!fd.has('_csrf')) fd.append('_csrf', csrf);
      try {
        const res = await fetch('process.php', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } });
        const data = res.headers.get('content-type')?.includes('application/json') ? await res.json() : { ok: res.ok };
        if (data.ok) {
          showToast('Saved', 'success');
          modal.hide();
          window.location.reload();
        } else {
          showToast(data.message || 'Save failed', 'danger');
        }
      } catch (e) { showToast('Network error', 'danger'); }
      finally { if (submitBtn) submitBtn.disabled = false; }
    });
  }

  // Attach create form when modal content inserted
  modalEl.addEventListener('shown.bs.modal', function(){
    const form = modalEl.querySelector('.js-ajax-create-form');
    if (form) attachAjaxForm(form);
    const editForm = modalEl.querySelector('.js-ajax-edit-form');
    if (editForm) attachAjaxForm(editForm);
  });
})();
</script>
</body>
</html>
