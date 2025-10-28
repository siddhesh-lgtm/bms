<?php

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';

/**
 * Authentication helpers
 */
function isLoggedIn() {
    return !empty($_SESSION['user_id']);
}
function currentUserId() {
    return $_SESSION['user_id'] ?? null;
}
function currentUserRoleId() {
    return $_SESSION['role_id'] ?? null;
}
function currentUserName() {
    return $_SESSION['user_name'] ?? '';
}

/**
 * Role & permission checking using DB-driven RBAC
 * hasPermission('book.create', $conn)
 */
function hasPermission($code, $conn) {
    if (!isLoggedIn()) return false;
    $roleId = currentUserRoleId();
    $sql = "SELECT 1 FROM permissions p
            JOIN role_permissions rp ON rp.permission_id = p.id
            WHERE rp.role_id = ? AND p.code = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $roleId, $code);
    $stmt->execute();
    $stmt->store_result();
    $ok = $stmt->num_rows > 0;
    $stmt->close();
    return $ok;
}

/**
 * getPermissionsForRole($roleId, $conn) => array of permission codes
 */
function getPermissionsForRole($roleId, $conn) {
    $sql = "SELECT p.code FROM permissions p
            JOIN role_permissions rp ON rp.permission_id = p.id
            WHERE rp.role_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $roleId);
    $stmt->execute();
    $res = $stmt->get_result();
    $perms = [];
    while ($r = $res->fetch_assoc()) $perms[] = $r['code'];
    $stmt->close();
    return $perms;
}

/**
 * updateRolePermissions($roleId, array $permissionIds)
 * -- deletes all existing role_permissions then inserts the provided ones.
 */
function updateRolePermissions($roleId, $permissionIds, $conn) {
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("DELETE FROM role_permissions WHERE role_id = ?");
        $stmt->bind_param("i", $roleId);
        $stmt->execute();
        $stmt->close();

        if (!empty($permissionIds)) {
            $stmt = $conn->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
            foreach ($permissionIds as $pid) {
                $pid_i = intval($pid);
                $stmt->bind_param("ii", $roleId, $pid_i);
                $stmt->execute();
            }
            $stmt->close();
        }
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}

/**
 * Utility: escape for HTML
 */
function e($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Very lightweight CSRF token
 */
function csrf_token() {
    if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['_csrf'];
}
function verify_csrf($token) {
    return !empty($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], $token);
}
