<?php
// books/create.php
require_once __DIR__ . '/../auth/auth-check.php';
requirePermission('book.create', $conn);
?>
<!doctype html>
<html>
<head>
  <title>Create Book</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Create Book</h4>
    <a class="btn btn-outline-secondary btn-sm" href="/bms/index.php">Home</a>
  </div>
  <form action="process.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="create">
    <div class="mb-2"><input class="form-control" name="title" placeholder="Title" required></div>
    <div class="mb-2"><input class="form-control" name="author" placeholder="Author"></div>
    <div class="mb-2"><input class="form-control" name="type" placeholder="Type"></div>
    <div class="mb-2"><textarea class="form-control" name="description" placeholder="Description"></textarea></div>
    <div class="mb-2"><input class="form-control" type="file" name="cover" accept="image/*"></div>
    <button class="btn btn-primary">Create</button>
    <a class="btn btn-link" href="index.php">Back</a>
  </form>
</div>
</body>
</html>
