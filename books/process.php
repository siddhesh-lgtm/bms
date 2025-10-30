<?php
// books/process.php
require_once __DIR__ . '/../auth/auth-check.php';
require_once __DIR__ . '/../includes/helpers.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

function handleCoverUpload($file) {
    if (empty($file) || $file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    $maxSize = 2 * 1024 * 1024; // 2MB
    if ($file['size'] > $maxSize) return null;

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif'];
    if (!isset($allowed[$mime])) return null;

    $ext = $allowed[$mime];
    $name = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $dir = __DIR__ . '/../assets/uploads/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $target = $dir . $name;
    if (move_uploaded_file($file['tmp_name'], $target)) {
        return 'assets/uploads/' . $name; // store absolute path from web root
    }
    return null;
}

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['_csrf'] ?? '')) die('CSRF');
    requirePermission('book.create', $conn);

    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $description = trim($_POST['description'] ?? '');

    $coverPath = null;
    if (!empty($_FILES['cover']['name'])) {
        $coverPath = handleCoverUpload($_FILES['cover']);
        if ($coverPath === null) die('Cover upload failed or invalid file');
    }

    $created_by = currentUserId();
    $stmt = $conn->prepare("INSERT INTO books (title,author,type,description,cover,created_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssi", $title, $author, $type, $description, $coverPath, $created_by);
    $stmt->execute();
    $newId = $stmt->insert_id;
    if (is_ajax()) { header('Content-Type: application/json'); echo json_encode(['ok'=>true,'id'=>$newId]); exit; }
    header("Location: index.php?created=1");
    exit;
}

if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['_csrf'] ?? '')) die('CSRF');
    requirePermission('book.edit', $conn);

    $id = intval($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // optional cover update
    $coverPath = null;
    if (!empty($_FILES['cover']['name'])) {
        $coverPath = handleCoverUpload($_FILES['cover']);
        if ($coverPath === null) die('Cover upload failed/invalid');
    }

    if ($coverPath) {
        $stmt = $conn->prepare("UPDATE books SET title=?,author=?,type=?,description=?,cover=?,updated_at=NOW() WHERE id=?");
        $stmt->bind_param("sssssi", $title,$author,$type,$description,$coverPath,$id);
    } else {
        $stmt = $conn->prepare("UPDATE books SET title=?,author=?,type=?,description=?,updated_at=NOW() WHERE id=?");
        $stmt->bind_param("ssssi", $title,$author,$type,$description,$id);
    }
    $stmt->execute();
    if (is_ajax()) { header('Content-Type: application/json'); echo json_encode(['ok'=>true]); exit; }
    header("Location: index.php?updated=1");
    exit;
}

if ($action === 'delete') {
    // Support AJAX POST with CSRF and fallback GET
    requirePermission('book.delete', $conn);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verify_csrf($_POST['_csrf'] ?? '')) die('CSRF');
        $id = intval($_POST['id'] ?? 0);
    } else {
        $id = intval($_GET['id'] ?? 0);
    }
    $stmt = $conn->prepare("UPDATE books SET deleted_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    if (is_ajax()) { header('Content-Type: application/json'); echo json_encode(['ok'=>true]); exit; }
    header("Location: index.php?deleted=1");
    exit;
}

die('Invalid request');
