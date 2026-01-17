<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No se recibió ningún archivo válido']);
    exit;
}

$file = $_FILES['file'];
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/tiff'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['error' => 'Tipo de archivo no soportado. Solo se permiten imágenes (JPEG, PNG, GIF, WebP, TIFF)']);
    exit;
}

$tempDir = sys_get_temp_dir();
$uniqueId = uniqid('metadata_', true);
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$tempFile = $tempDir . '/' . $uniqueId . '.' . $extension;
$outputFile = $tempDir . '/' . $uniqueId . '_clean.' . $extension;

if (!move_uploaded_file($file['tmp_name'], $tempFile)) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al procesar el archivo']);
    exit;
}

$success = false;

switch ($mimeType) {
    case 'image/jpeg':
        $success = removeJpegMetadata($tempFile, $outputFile);
        break;
    case 'image/png':
        $success = removePngMetadata($tempFile, $outputFile);
        break;
    case 'image/gif':
        $success = removeGifMetadata($tempFile, $outputFile);
        break;
    case 'image/webp':
        $success = removeWebpMetadata($tempFile, $outputFile);
        break;
    case 'image/tiff':
        $success = removeTiffMetadata($tempFile, $outputFile);
        break;
}

@unlink($tempFile);

if (!$success || !file_exists($outputFile)) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al eliminar los metadatos']);
    exit;
}

$token = bin2hex(random_bytes(32));
$_SESSION['download_tokens'][$token] = [
    'file' => $outputFile,
    'name' => pathinfo($file['name'], PATHINFO_FILENAME) . '_sin_metadatos.' . $extension,
    'mime' => $mimeType,
    'expires' => time() + 300
];

session_start();
$_SESSION['download_tokens'][$token] = [
    'file' => $outputFile,
    'name' => pathinfo($file['name'], PATHINFO_FILENAME) . '_sin_metadatos.' . $extension,
    'mime' => $mimeType,
    'expires' => time() + 300
];

echo json_encode([
    'success' => true,
    'token' => $token,
    'filename' => pathinfo($file['name'], PATHINFO_FILENAME) . '_sin_metadatos.' . $extension
]);

function removeJpegMetadata($input, $output) {
    $img = @imagecreatefromjpeg($input);
    if (!$img) return false;

    $width = imagesx($img);
    $height = imagesy($img);

    $newImg = imagecreatetruecolor($width, $height);
    imagecopy($newImg, $img, 0, 0, 0, 0, $width, $height);

    $result = imagejpeg($newImg, $output, 95);

    imagedestroy($img);
    imagedestroy($newImg);

    return $result;
}

function removePngMetadata($input, $output) {
    $img = @imagecreatefrompng($input);
    if (!$img) return false;

    $width = imagesx($img);
    $height = imagesy($img);

    $newImg = imagecreatetruecolor($width, $height);

    imagealphablending($newImg, false);
    imagesavealpha($newImg, true);
    $transparent = imagecolorallocatealpha($newImg, 0, 0, 0, 127);
    imagefill($newImg, 0, 0, $transparent);

    imagealphablending($newImg, true);
    imagecopy($newImg, $img, 0, 0, 0, 0, $width, $height);
    imagealphablending($newImg, false);

    $result = imagepng($newImg, $output, 9);

    imagedestroy($img);
    imagedestroy($newImg);

    return $result;
}

function removeGifMetadata($input, $output) {
    $img = @imagecreatefromgif($input);
    if (!$img) return false;

    $width = imagesx($img);
    $height = imagesy($img);

    $newImg = imagecreatetruecolor($width, $height);

    $transparentIndex = imagecolortransparent($img);
    if ($transparentIndex >= 0) {
        $transparentColor = imagecolorsforindex($img, $transparentIndex);
        $transparent = imagecolorallocate($newImg, $transparentColor['red'], $transparentColor['green'], $transparentColor['blue']);
        imagefill($newImg, 0, 0, $transparent);
        imagecolortransparent($newImg, $transparent);
    }

    imagecopy($newImg, $img, 0, 0, 0, 0, $width, $height);

    $result = imagegif($newImg, $output);

    imagedestroy($img);
    imagedestroy($newImg);

    return $result;
}

function removeWebpMetadata($input, $output) {
    $img = @imagecreatefromwebp($input);
    if (!$img) return false;

    $width = imagesx($img);
    $height = imagesy($img);

    $newImg = imagecreatetruecolor($width, $height);

    imagealphablending($newImg, false);
    imagesavealpha($newImg, true);
    $transparent = imagecolorallocatealpha($newImg, 0, 0, 0, 127);
    imagefill($newImg, 0, 0, $transparent);

    imagealphablending($newImg, true);
    imagecopy($newImg, $img, 0, 0, 0, 0, $width, $height);
    imagealphablending($newImg, false);

    $result = imagewebp($newImg, $output, 95);

    imagedestroy($img);
    imagedestroy($newImg);

    return $result;
}

function removeTiffMetadata($input, $output) {
    if (!function_exists('imagecreatefromtiff')) {
        return copy($input, $output);
    }

    $img = @imagecreatefromtiff($input);
    if (!$img) return copy($input, $output);

    $width = imagesx($img);
    $height = imagesy($img);

    $newImg = imagecreatetruecolor($width, $height);
    imagecopy($newImg, $img, 0, 0, 0, 0, $width, $height);

    $result = imagepng($newImg, $output);

    imagedestroy($img);
    imagedestroy($newImg);

    return $result;
}
