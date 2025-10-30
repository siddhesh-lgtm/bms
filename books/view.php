<?php
// books/view.php
require_once __DIR__ . '/../auth/auth-check.php';
require_once __DIR__ . '/../includes/helpers.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(400); echo 'Invalid ID'; exit; }

$stmt = $conn->prepare("
  SELECT b.*, u.name AS creator_name
  FROM books b
  LEFT JOIN users u ON u.id = b.created_by
  WHERE b.id = ? AND b.deleted_at IS NULL
  LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$book = $res->fetch_assoc();
$stmt->close();

if (!$book) {
  http_response_code(404);
  echo 'Book not found';
  exit;
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>View Book</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><?= e($book['title']) ?></h4>
    <div>
      <a class="btn btn-outline-secondary btn-sm" href="/bms/index.php">Home</a>
      <?php if (hasPermission('book.edit', $conn)): ?>
        <button class="btn btn-warning btn-sm js-open-edit" data-id="<?= e($book['id']) ?>">Edit</button>
      <?php endif; ?>
      <?php if (hasPermission('book.delete', $conn)): ?>
        <button class="btn btn-danger btn-sm js-ajax-delete" data-id="<?= e($book['id']) ?>">Delete</button>
      <?php endif; ?>
      <a class="btn btn-outline-secondary btn-sm" href="index.php">Back</a>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-md-3">
    <?php
$coverRel = $book['cover']; // e.g. assets/uploads/xxx.jpg
$coverFs  = __DIR__ . '/../' . $coverRel;
$coverUrl = '/bms/' . $coverRel;
?>
<?php
$coverRel = $book['cover'];            // not $b
$coverFs  = __DIR__ . '/../' . $coverRel;
$coverUrl = '/bms/' . $coverRel;
?>
<?php if (!empty($coverRel) && file_exists($coverFs)): ?>
  <img src="<?= e($coverUrl) ?>" class="img-fluid img-thumbnail" alt="cover">
<?php else: ?>
  <div class="text-muted">No cover</div>
<?php endif; ?>
    </div>
    <div class="col-md-9">
      <dl class="row">
        <dt class="col-sm-3">Title</dt>
        <dd class="col-sm-9"><?= e($book['title']) ?></dd>

        <dt class="col-sm-3">Author</dt>
        <dd class="col-sm-9"><?= e($book['author']) ?></dd>

        <dt class="col-sm-3">Type</dt>
        <dd class="col-sm-9"><?= e($book['type']) ?></dd>

        <dt class="col-sm-3">Description</dt>
        <dd class="col-sm-9"><pre class="mb-0" style="white-space:pre-wrap;word-break:break-word;"><?= e($book['description']) ?></pre></dd>

        <dt class="col-sm-3">Created by</dt>
        <dd class="col-sm-9"><?= e($book['creator_name'] ?: '—') ?></dd>

        <dt class="col-sm-3">Created at</dt>
        <dd class="col-sm-9"><?= e($book['created_at']) ?></dd>
      </dl>
    </div>
  </div>
</div>
  <!-- Edit modal (bootstrap) -->
  <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Book</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="text-center text-muted">Loading…</div>
        </div>
      </div>
    </div>
  </div>

  <input type="hidden" id="_csrf" value="<?= e(csrf_token()) ?>">

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
  // small toast helper
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

  (function(){
    const csrf = document.getElementById('_csrf')?.value || '';
    const editModalEl = document.getElementById('editModal');
    const editModal = new bootstrap.Modal(editModalEl);

    // Open edit form in modal
    document.querySelectorAll('.js-open-edit').forEach(function(btn){
      btn.addEventListener('click', async function(){
        const id = btn.getAttribute('data-id');
        const body = document.querySelector('#editModal .modal-body');
        body.innerHTML = '<div class="text-center text-muted">Loading…</div>';
        editModal.show();
        try {
          const res = await fetch('edit.php?ajax=1&id=' + encodeURIComponent(id), { headers: { 'Accept': 'text/html' } });
          if (!res.ok) { body.innerHTML = '<div class="text-danger">Unable to load form</div>'; return; }
          const html = await res.text();
          body.innerHTML = html;

          // attach ajax submit handler
          const form = body.querySelector('.js-ajax-edit-form');
          if (form) {
            form.addEventListener('submit', async function(e){
              e.preventDefault();
              const submitBtn = form.querySelector('button[type=submit]') || form.querySelector('button');
              if (submitBtn) submitBtn.disabled = true;
              const fd = new FormData(form);
              // ensure CSRF present
              if (!fd.has('_csrf')) fd.append('_csrf', csrf);
              try {
                const r = await fetch('process.php', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } });
                const data = r.headers.get('content-type')?.includes('application/json') ? await r.json() : { ok: r.ok };
                if (data.ok) {
                  showToast('Book updated', 'success');
                  editModal.hide();
                  // reload to show updated details
                  window.location.reload();
                } else {
                  showToast(data.message || 'Update failed', 'danger');
                }
              } catch (ex) { showToast('Network error', 'danger'); }
              finally { if (submitBtn) submitBtn.disabled = false; }
            });
          }
        } catch (err) {
          body.innerHTML = '<div class="text-danger">Error loading form</div>';
        }
      });
    });

    // Delete via AJAX
    document.querySelectorAll('.js-ajax-delete').forEach(function(btn){
      btn.addEventListener('click', async function(){
        const id = btn.getAttribute('data-id');
        if (!confirm('Delete this book?')) return;
        btn.disabled = true;
        try {
          const res = await fetch('process.php', {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body: new URLSearchParams({ action: 'delete', id: id, _csrf: csrf })
          });
          const data = res.headers.get('content-type')?.includes('application/json') ? await res.json() : { ok: res.ok };
          if (data.ok) { showToast('Book deleted', 'success'); window.location.href = 'index.php'; }
          else showToast('Delete failed', 'danger');
        } catch (e) { showToast('Network error', 'danger'); }
        finally { btn.disabled = false; }
      });
    });
  })();
  </script>
</body>
</html>