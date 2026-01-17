<?php
session_start();

if (!isset($_GET['token'])) {
    http_response_code(400);
    die('Token no proporcionado');
}

$token = $_GET['token'];

if (!isset($_SESSION['download_tokens'][$token])) {
    http_response_code(404);
    die('Archivo no encontrado o enlace expirado');
}

$download = $_SESSION['download_tokens'][$token];

if (time() > $download['expires']) {
    @unlink($download['file']);
    unset($_SESSION['download_tokens'][$token]);
    http_response_code(410);
    die('El enlace de descarga ha expirado');
}

if (!file_exists($download['file'])) {
    unset($_SESSION['download_tokens'][$token]);
    http_response_code(404);
    die('Archivo no encontrado');
}

header('Content-Type: ' . $download['mime']);
header('Content-Disposition: attachment; filename="' . $download['name'] . '"');
header('Content-Length: ' . filesize($download['file']));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

readfile($download['file']);

@unlink($download['file']);
unset($_SESSION['download_tokens'][$token]);

exit;
