<?php
session_start();
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
$allowedTypes = [
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/webp',
    'image/tiff',
    'application/pdf',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation'
];

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

// For Office files, also check by extension since MIME detection can be unreliable
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$officeExtensions = ['docx', 'xlsx', 'pptx'];

if ($mimeType === 'application/zip' && in_array($extension, $officeExtensions)) {
    switch ($extension) {
        case 'docx':
            $mimeType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
            break;
        case 'xlsx':
            $mimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
            break;
        case 'pptx':
            $mimeType = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
            break;
    }
}

if (!in_array($mimeType, $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['error' => 'Tipo de archivo no soportado. Formatos permitidos: JPEG, PNG, GIF, WebP, TIFF, PDF, DOCX, XLSX, PPTX']);
    exit;
}

$tempDir = sys_get_temp_dir();
$uniqueId = uniqid('metadata_', true);
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
    case 'application/pdf':
        $success = removePdfMetadata($tempFile, $outputFile);
        break;
    case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
    case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
    case 'application/vnd.openxmlformats-officedocument.presentationml.presentation':
        $success = removeOfficeMetadata($tempFile, $outputFile);
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

echo json_encode([
    'success' => true,
    'token' => $token,
    'filename' => pathinfo($file['name'], PATHINFO_FILENAME) . '_sin_metadatos.' . $extension
]);

// Image metadata removal functions
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

// PDF metadata removal
function removePdfMetadata($input, $output) {
    $content = file_get_contents($input);
    if ($content === false) return false;

    // Remove /Info dictionary entries (Author, Creator, Producer, Title, Subject, Keywords, etc.)
    $content = preg_replace('/\/Author\s*\([^)]*\)/i', '/Author ()', $content);
    $content = preg_replace('/\/Creator\s*\([^)]*\)/i', '/Creator ()', $content);
    $content = preg_replace('/\/Producer\s*\([^)]*\)/i', '/Producer ()', $content);
    $content = preg_replace('/\/Title\s*\([^)]*\)/i', '/Title ()', $content);
    $content = preg_replace('/\/Subject\s*\([^)]*\)/i', '/Subject ()', $content);
    $content = preg_replace('/\/Keywords\s*\([^)]*\)/i', '/Keywords ()', $content);
    $content = preg_replace('/\/CreationDate\s*\([^)]*\)/i', '/CreationDate ()', $content);
    $content = preg_replace('/\/ModDate\s*\([^)]*\)/i', '/ModDate ()', $content);

    // Remove XMP metadata
    $content = preg_replace('/<\?xpacket[^>]*\?>.*?<\?xpacket[^>]*\?>/s', '', $content);
    $content = preg_replace('/<x:xmpmeta[^>]*>.*?<\/x:xmpmeta>/s', '', $content);

    return file_put_contents($output, $content) !== false;
}

// Office documents (DOCX, XLSX, PPTX) metadata removal
function removeOfficeMetadata($input, $output) {
    $zip = new ZipArchive();

    if ($zip->open($input) !== true) {
        return false;
    }

    // Copy to output first
    if (!copy($input, $output)) {
        $zip->close();
        return false;
    }

    $zipOut = new ZipArchive();
    if ($zipOut->open($output) !== true) {
        $zip->close();
        return false;
    }

    // Files to remove or clean
    $metadataFiles = [
        'docProps/core.xml',
        'docProps/app.xml',
        'docProps/custom.xml'
    ];

    // Create clean core.xml (minimal required metadata)
    $cleanCoreXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties"
xmlns:dc="http://purl.org/dc/elements/1.1/"
xmlns:dcterms="http://purl.org/dc/terms/"
xmlns:dcmitype="http://purl.org/dc/dcmitype/"
xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
</cp:coreProperties>';

    // Create clean app.xml (minimal required metadata)
    $cleanAppXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties">
</Properties>';

    // Replace metadata files with clean versions
    if ($zipOut->locateName('docProps/core.xml') !== false) {
        $zipOut->deleteName('docProps/core.xml');
        $zipOut->addFromString('docProps/core.xml', $cleanCoreXml);
    }

    if ($zipOut->locateName('docProps/app.xml') !== false) {
        $zipOut->deleteName('docProps/app.xml');
        $zipOut->addFromString('docProps/app.xml', $cleanAppXml);
    }

    // Remove custom.xml if exists
    if ($zipOut->locateName('docProps/custom.xml') !== false) {
        $zipOut->deleteName('docProps/custom.xml');
    }

    // Remove thumbnail if exists
    if ($zipOut->locateName('docProps/thumbnail.jpeg') !== false) {
        $zipOut->deleteName('docProps/thumbnail.jpeg');
    }

    $zip->close();
    $zipOut->close();

    return true;
}
