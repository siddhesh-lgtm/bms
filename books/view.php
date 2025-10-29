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
        <a class="btn btn-warning btn-sm" href="edit.php?id=<?= e($book['id']) ?>">Edit</a>
      <?php endif; ?>
      <?php if (hasPermission('book.delete', $conn)): ?>
        <a class="btn btn-danger btn-sm" href="process.php?action=delete&id=<?= e($book['id']) ?>"
           onclick="return confirm('Delete this book?')">Delete</a>
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
</body>
</html>