<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Metadata Remover</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Eliminar Metadatos</h1>
        <p class="description">Sube un archivo para eliminar toda su metainformación (EXIF, IPTC, XMP, propiedades de documento, etc.)</p>
        <p class="supported-formats">Formatos soportados: JPEG, PNG, GIF, WebP, TIFF, PDF, DOCX, XLSX, PPTX</p>

        <div class="upload-area" id="uploadArea">
            <div class="upload-icon">📁</div>
            <p>Arrastra tu archivo aquí o haz clic para seleccionar</p>
            <input type="file" id="fileInput" accept="image/*,.pdf,.docx,.xlsx,.pptx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.openxmlformats-officedocument.presentationml.presentation" hidden>
        </div>

        <div class="file-info" id="fileInfo" style="display: none;">
            <p>Archivo seleccionado: <span id="fileName"></span></p>
            <p>Tamaño: <span id="fileSize"></span></p>
        </div>

        <button id="processBtn" class="process-btn" disabled>Eliminar Metadatos y Descargar</button>

        <div class="loader" id="loader" style="display: none;">
            <div class="spinner"></div>
            <p>Procesando...</p>
        </div>

        <div class="message" id="message"></div>
    </div>

    <script src="script.js"></script>
</body>
</html>
