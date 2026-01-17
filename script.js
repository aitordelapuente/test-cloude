document.addEventListener('DOMContentLoaded', function() {
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('fileInput');
    const fileInfo = document.getElementById('fileInfo');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const processBtn = document.getElementById('processBtn');
    const loader = document.getElementById('loader');
    const message = document.getElementById('message');

    let selectedFile = null;

    uploadArea.addEventListener('click', () => fileInput.click());

    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');

        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFile(files[0]);
        }
    });

    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            handleFile(e.target.files[0]);
        }
    });

    function handleFile(file) {
        if (!file.type.startsWith('image/')) {
            showMessage('Por favor, selecciona un archivo de imagen válido.', 'error');
            return;
        }

        selectedFile = file;
        fileName.textContent = file.name;
        fileSize.textContent = formatFileSize(file.size);
        fileInfo.style.display = 'block';
        processBtn.disabled = false;
        hideMessage();
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    processBtn.addEventListener('click', async () => {
        if (!selectedFile) return;

        processBtn.disabled = true;
        loader.style.display = 'block';
        hideMessage();

        const formData = new FormData();
        formData.append('file', selectedFile);

        try {
            const response = await fetch('process.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showMessage('Metadatos eliminados correctamente. Descargando...', 'success');

                const downloadLink = document.createElement('a');
                downloadLink.href = 'download.php?token=' + data.token;
                downloadLink.download = data.filename;
                document.body.appendChild(downloadLink);
                downloadLink.click();
                document.body.removeChild(downloadLink);

                setTimeout(() => {
                    resetForm();
                }, 2000);
            } else {
                showMessage(data.error || 'Error al procesar el archivo.', 'error');
                processBtn.disabled = false;
            }
        } catch (error) {
            showMessage('Error de conexión. Por favor, inténtalo de nuevo.', 'error');
            processBtn.disabled = false;
        } finally {
            loader.style.display = 'none';
        }
    });

    function showMessage(text, type) {
        message.textContent = text;
        message.className = 'message ' + type;
    }

    function hideMessage() {
        message.className = 'message';
        message.textContent = '';
    }

    function resetForm() {
        selectedFile = null;
        fileInput.value = '';
        fileInfo.style.display = 'none';
        processBtn.disabled = true;
        hideMessage();
    }
});
