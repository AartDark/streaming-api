<?php

require_once __DIR__ . '/../app/DataStore.php';
require_once __DIR__ . '/../app/Response.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Controllers/ContentController.php';
require_once __DIR__ . '/../app/Controllers/FavoritesController.php';
require_once __DIR__ . '/../app/Controllers/ProgressController.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$store = new DataStore(__DIR__ . '/../data');
$auth = new Auth($store);
$authController = new AuthController($store);
$contentController = new ContentController($store);
$favoritesController = new FavoritesController($store, $auth);
$progressController = new ProgressController($store, $auth);

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$body = json_decode(file_get_contents('php://input') ?: '{}', true);
$body = is_array($body) ? $body : [];

if ($method === 'GET' && $path === '/api') {
    Response::json(['nombre' => 'Streaming API', 'estado' => 'activo']);
}

if ($method === 'POST' && $path === '/api/auth/registro') {
    $authController->register($body);
}
if ($method === 'POST' && $path === '/api/auth/login') {
    $authController->login($body);
}
if ($method === 'GET' && $path === '/api/contenidos') {
    $contentController->index();
}
if ($method === 'POST' && $path === '/api/contenidos') {
    $auth->requireUser();
    $contentController->create($body);
}
if (preg_match('#^/api/contenidos/(\d+)$#', $path, $matches)) {
    $id = (int)$matches[1];
    if ($method === 'GET') {
        $contentController->show($id);
    }
    if ($method === 'PUT') {
        $auth->requireUser();
        $contentController->update($id, $body);
    }
    if ($method === 'DELETE') {
        $auth->requireUser();
        $contentController->delete($id);
    }
}
if ($method === 'GET' && $path === '/api/favoritos') {
    $favoritesController->index();
}
if ($method === 'POST' && $path === '/api/favoritos') {
    $favoritesController->create($body);
}
if (preg_match('#^/api/favoritos/(\d+)$#', $path, $matches) && $method === 'DELETE') {
    $favoritesController->delete((int)$matches[1]);
}
if ($method === 'GET' && $path === '/api/progreso') {
    $progressController->index();
}
if ($method === 'POST' && $path === '/api/progreso') {
    $progressController->save($body);
}
if (preg_match('#^/api/progreso/(\d+)$#', $path, $matches) && $method === 'DELETE') {
    $progressController->delete((int)$matches[1]);
}

Response::json(['mensaje' => 'Ruta no encontrada'], 404);
