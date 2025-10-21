<?php
// Simple Cart API (JSON) for logged-in users
// Actions: list (GET), add/update/remove/clear (POST with action)
// If not logged in, returns 401 so client can fallback to localStorage.

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexion.php';

function require_login() {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'not_logged_in']);
        exit;
    }
}

function json_input() {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function respond($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function list_cart(mysqli $conn, int $userId) {
    $sql = "SELECT c.id_producto, COALESCE(p.nombre, '') AS nombre, COALESCE(p.precio, 0) AS precio, p.imagen,
                   SUM(c.cantidad) AS qty
            FROM carrito c
            LEFT JOIN productos p ON p.id_producto = c.id_producto
            WHERE c.id_usuario = ?
            GROUP BY c.id_producto";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $items = [];
    $total = 0.0;
    while ($row = $res->fetch_assoc()) {
        $price = (float)$row['precio'];
        $qty = (int)$row['qty'];
        $items[] = [
            'id_producto' => (int)$row['id_producto'],
            'name' => $row['nombre'],
            'price' => $price,
            'qty' => $qty,
            'img' => $row['imagen'] ?? ''
        ];
        $total += $price * $qty;
    }
    $stmt->close();
    respond(['items' => $items, 'total' => $total]);
}

function ensure_product(mysqli $conn, ?int $id, ?string $name, ?float $price, ?string $img) : int {
    // If id provided and exists, return it
    if ($id) {
        $chk = $conn->prepare('SELECT id_producto FROM productos WHERE id_producto = ? LIMIT 1');
        $chk->bind_param('i', $id);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows === 1) { $chk->close(); return $id; }
        $chk->close();
    }
    // Try by name
    if ($name) {
        $q = $conn->prepare('SELECT id_producto FROM productos WHERE nombre = ? LIMIT 1');
        $q->bind_param('s', $name);
        $q->execute();
        $q->bind_result($pid);
        if ($q->fetch()) { $q->close(); return (int)$pid; }
        $q->close();
    }
    // Insert minimal product (nullable categoria, stock 0)
    $precio = $price ?? 0.0;
    $imagen = $img ?? null;
    $ins = $conn->prepare('INSERT INTO productos (nombre, descripcion, precio, stock, id_categoria, imagen) VALUES (?, NULL, ?, 0, NULL, ?)');
    $nm = $name ?: ('Producto ' . date('His'));
    $ins->bind_param('sds', $nm, $precio, $imagen);
    if (!$ins->execute()) {
        $ins->close();
        http_response_code(500);
        respond(['error' => 'product_insert_failed']);
    }
    $newId = $conn->insert_id;
    $ins->close();
    return (int)$newId;
}

function add_to_cart(mysqli $conn, int $userId, int $productId, int $deltaQty = 1) {
    // If exists, update; else insert
    $sel = $conn->prepare('SELECT id_carrito, cantidad FROM carrito WHERE id_usuario = ? AND id_producto = ? LIMIT 1');
    $sel->bind_param('ii', $userId, $productId);
    $sel->execute();
    $sel->store_result();
    if ($sel->num_rows === 1) {
        $sel->bind_result($idc, $qty);
        $sel->fetch();
        $sel->close();
        $newQty = max(1, (int)$qty + (int)$deltaQty);
        $up = $conn->prepare('UPDATE carrito SET cantidad = ? WHERE id_carrito = ?');
        $up->bind_param('ii', $newQty, $idc);
        $up->execute();
        $up->close();
    } else {
        $sel->close();
        $ins = $conn->prepare('INSERT INTO carrito (id_usuario, id_producto, cantidad) VALUES (?, ?, ?)');
        $q = max(1, (int)$deltaQty);
        $ins->bind_param('iii', $userId, $productId, $q);
        $ins->execute();
        $ins->close();
    }
}

function set_qty(mysqli $conn, int $userId, int $productId, int $qty) {
    if ($qty <= 0) {
        $del = $conn->prepare('DELETE FROM carrito WHERE id_usuario = ? AND id_producto = ?');
        $del->bind_param('ii', $userId, $productId);
        $del->execute();
        $del->close();
        return;
    }
    $upd = $conn->prepare('UPDATE carrito SET cantidad = ? WHERE id_usuario = ? AND id_producto = ?');
    $upd->bind_param('iii', $qty, $userId, $productId);
    $upd->execute();
    if ($conn->affected_rows === 0) {
        // If no row, insert
        $ins = $conn->prepare('INSERT INTO carrito (id_usuario, id_producto, cantidad) VALUES (?, ?, ?)');
        $ins->bind_param('iii', $userId, $productId, $qty);
        $ins->execute();
        $ins->close();
    }
    $upd->close();
}

function remove_item(mysqli $conn, int $userId, int $productId) {
    $del = $conn->prepare('DELETE FROM carrito WHERE id_usuario = ? AND id_producto = ?');
    $del->bind_param('ii', $userId, $productId);
    $del->execute();
    $del->close();
}

function clear_cart(mysqli $conn, int $userId) {
    $del = $conn->prepare('DELETE FROM carrito WHERE id_usuario = ?');
    $del->bind_param('i', $userId);
    $del->execute();
    $del->close();
}

// Routing
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'GET') {
    if (($_GET['action'] ?? 'list') === 'list') {
        require_login();
        $uid = (int)$_SESSION['user_id'];
        list_cart($conn, $uid);
    }
    http_response_code(400);
    respond(['error' => 'bad_request']);
}

if ($method === 'POST') {
    $action = $_GET['action'] ?? ($_POST['action'] ?? '');
    require_login();
    $uid = (int)$_SESSION['user_id'];
    $data = array_merge($_POST, json_input());

    switch ($action) {
        case 'add': {
            $idp = isset($data['id_producto']) ? (int)$data['id_producto'] : null;
            $name = isset($data['name']) ? trim($data['name']) : null;
            $price = isset($data['price']) ? (float)$data['price'] : null;
            $img = isset($data['img']) ? trim($data['img']) : null;
            $pid = ensure_product($conn, $idp, $name, $price, $img);
            add_to_cart($conn, $uid, $pid, 1);
            list_cart($conn, $uid);
        }
        case 'update': {
            $idp = (int)($data['id_producto'] ?? 0);
            $qty = (int)($data['qty'] ?? 1);
            if ($idp <= 0) { http_response_code(400); respond(['error'=>'id_producto_required']); }
            set_qty($conn, $uid, $idp, $qty);
            list_cart($conn, $uid);
        }
        case 'remove': {
            $idp = (int)($data['id_producto'] ?? 0);
            if ($idp <= 0) { http_response_code(400); respond(['error'=>'id_producto_required']); }
            remove_item($conn, $uid, $idp);
            list_cart($conn, $uid);
        }
        case 'clear': {
            clear_cart($conn, $uid);
            list_cart($conn, $uid);
        }
        default:
            http_response_code(400); respond(['error' => 'unknown_action']);
    }
}

http_response_code(405);
respond(['error' => 'method_not_allowed']);
?>
