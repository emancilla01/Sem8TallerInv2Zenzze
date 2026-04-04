function initializeUploadBoxes() {
    const uploadBoxes = document.querySelectorAll<HTMLElement>('[data-upload-box]');

    uploadBoxes.forEach((box) => {
        const inputId = box.getAttribute('for');

        if (!inputId) {
            return;
        }

        const input = document.getElementById(inputId);

        if (!(input instanceof HTMLInputElement) || input.type !== 'file') {
            return;
        }

        const fileNameTarget = box.querySelector<HTMLElement>('[data-upload-filename]');

        const updateSelectedFileName = () => {
            const fileName = input.files && input.files.length > 0
                ? input.files.length === 1
                    ? input.files[0].name
                    : `${input.files.length} archivos seleccionados`
                : 'Ningún archivo seleccionado';

            if (fileNameTarget) {
                fileNameTarget.textContent = fileName;
            }

            box.classList.toggle('has-file', Boolean(input.files && input.files.length > 0));
        };

        const openPicker = () => input.click();

        box.addEventListener('click', (event) => {
            if (event.target instanceof HTMLInputElement) {
                return;
            }

            openPicker();
        });

        box.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' && event.key !== ' ') {
                return;
            }

            event.preventDefault();
            openPicker();
        });

        ['dragenter', 'dragover'].forEach((eventName) => {
            box.addEventListener(eventName, (event) => {
                event.preventDefault();
                event.stopPropagation();
                box.classList.add('is-dragover');
            });
        });

        ['dragleave', 'dragend', 'drop'].forEach((eventName) => {
            box.addEventListener(eventName, (event) => {
                event.preventDefault();
                event.stopPropagation();
                box.classList.remove('is-dragover');
            });
        });

        box.addEventListener('drop', (event: DragEvent) => {
            const droppedFiles = event.dataTransfer?.files;

            if (!droppedFiles || droppedFiles.length === 0) {
                return;
            }

            const dataTransfer = new DataTransfer();

            Array.from(droppedFiles).forEach((file) => {
                dataTransfer.items.add(file);
            });

            input.files = dataTransfer.files;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });

        input.addEventListener('change', updateSelectedFileName);

        updateSelectedFileName();
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeUploadBoxes);
} else {
    initializeUploadBoxes();
}