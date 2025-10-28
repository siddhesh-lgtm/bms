<?php
// books/create.php
require_once __DIR__ . '/../auth/auth-check.php';
if (!hasPermission('book.create', $conn)) die('Access denied');
?>
<!doctype html>
<html>
<head>
  <title>Create Book</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">
  <h4>Create Book</h4>
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
