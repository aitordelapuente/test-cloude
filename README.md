# Metadata Remover

Aplicación web para eliminar metainformación de imágenes.

## Características

- Sube imágenes (JPEG, PNG, GIF, WebP, TIFF)
- Elimina automáticamente todos los metadatos (EXIF, IPTC, XMP, etc.)
- Los archivos se eliminan inmediatamente después de la descarga
- Interfaz drag & drop
- Diseño responsive

## Requisitos

- PHP 7.4 o superior
- Extensión GD habilitada
- Servidor web (Apache, Nginx, o PHP built-in server)

## Uso

1. Inicia un servidor PHP:
   ```bash
   php -S localhost:8000
   ```

2. Abre `http://localhost:8000` en tu navegador

3. Sube una imagen arrastrándola o haciendo clic en el área de subida

4. Haz clic en "Eliminar Metadatos y Descargar"

5. El archivo limpio se descargará automáticamente

## Seguridad

- Los archivos temporales se eliminan inmediatamente después de la descarga
- Los enlaces de descarga expiran en 5 minutos
- Solo se aceptan archivos de imagen válidos
