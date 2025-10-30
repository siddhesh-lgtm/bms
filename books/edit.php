<?php
// books/edit.php
require_once __DIR__ . '/../auth/auth-check.php';
require_once __DIR__ . '/../includes/helpers.php';

if (!hasPermission('book.edit', $conn)) { die('Access denied'); }

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

// If requested as an AJAX fragment, return only the form HTML so it can be loaded into a modal.
if (isset($_GET['ajax']) && $_GET['ajax']) {
  ?>
  <form action="process.php" method="post" enctype="multipart/form-data" class="js-ajax-edit-form">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="edit">
    <input type="hidden" name="id" value="<?= e($book['id']) ?>">

    <div class="mb-3">
      <label class="form-label">Title</label>
      <input class="form-control" name="title" value="<?= e($book['title']) ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Author</label>
      <input class="form-control" name="author" value="<?= e($book['author']) ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Type</label>
      <input class="form-control" name="type" value="<?= e($book['type']) ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Description</label>
      <textarea class="form-control" name="description" rows="5"><?= e($book['description']) ?></textarea>
    </div>
    <div class="mb-3">
      <label class="form-label">Replace Cover (optional)</label>
      <input class="form-control" type="file" name="cover" accept="image/*">
      <div class="form-text">JPEG/PNG/GIF up to 2MB.</div>
    </div>

    <div class="d-flex gap-2">
      <button class="btn btn-primary">Save Changes</button>
      <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
    </div>
  </form>
  <?php
  exit;
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Edit Book</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Edit Book</h4>
    <div>
      <a class="btn btn-outline-secondary btn-sm" href="/bms/index.php">Home</a>
      <a class="btn btn-outline-secondary btn-sm" href="view.php?id=<?= e($book['id']) ?>">View</a>
      <a class="btn btn-outline-secondary btn-sm" href="index.php">Back</a>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-md-3">
      <div class="mb-2">
      <?php
$coverRel = $book['cover'];
$coverFs  = __DIR__ . '/../' . $coverRel;
$coverUrl = '/bms/' . $coverRel;
?>
<?php if (!empty($coverRel) && file_exists($coverFs)): ?>
  <img src="<?= e($coverUrl) ?>" class="img-fluid img-thumbnail" alt="cover">
<?php else: ?>
  <div class="text-muted">No cover</div>
<?php endif; ?>
      </div>
      <div class="small text-muted">
        Created by: <?= e($book['creator_name'] ?: '—') ?><br>
        Created at: <?= e($book['created_at']) ?>
      </div>
    </div>

    <div class="col-md-9">
      <form action="process.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" value="<?= e($book['id']) ?>">

        <div class="mb-3">
          <label class="form-label">Title</label>
          <input class="form-control" name="title" value="<?= e($book['title']) ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Author</label>
          <input class="form-control" name="author" value="<?= e($book['author']) ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Type</label>
          <input class="form-control" name="type" value="<?= e($book['type']) ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Description</label>
          <textarea class="form-control" name="description" rows="5"><?= e($book['description']) ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Replace Cover (optional)</label>
          <input class="form-control" type="file" name="cover" accept="image/*">
          <div class="form-text">JPEG/PNG/GIF up to 2MB.</div>
        </div>

        <div class="d-flex gap-2">
          <button class="btn btn-primary">Save Changes</button>
          <a class="btn btn-link" href="index.php">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>
</body>
</html>