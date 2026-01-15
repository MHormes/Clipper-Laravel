import libheif from 'libheif-js';

/**
 * Universal Image Converter
 * Converts HEIC, PNG, and others to a standard JPEG.
 */
export const ensureJpg = async (file) => {
    const fileName = file.name.toLowerCase();
    const isHeic = fileName.endsWith('.heic') || fileName.endsWith('.heif') || file.type.includes('heic');

    // 1. Handle HEIC via libheif-js
    if (isHeic) {
        try {
            const buffer = await file.arrayBuffer();
            const decoder = new libheif.HeifDecoder();
            const data = decoder.decode(buffer);
            const image = data[0];
            const width = image.get_width();
            const height = image.get_height();

            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            const ctx = canvas.getContext('2d');
            const imageData = ctx.createImageData(width, height);

            return new Promise((resolve) => {
                image.display(imageData, (displayData) => {
                    ctx.putImageData(displayData, 0, 0);
                    canvas.toBlob((blob) => {
                        resolve(new File([blob], file.name.replace(/\.[^/.]+$/, ".jpg"), { type: 'image/jpeg' }));
                    }, 'image/jpeg', 0.85); // 0.85 is the sweet spot for quality vs size
                });
            });
        } catch (e) {
            console.error("HEIC conversion failed:", e);
            return file;
        }
    }

    // 2. Handle everything else (PNG, WEBP, etc.) via standard Canvas
    return new Promise((resolve) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            const img = new Image();
            img.onload = () => {
                const canvas = document.createElement('canvas');
                canvas.width = img.width;
                canvas.height = img.height;
                const ctx = canvas.getContext('2d');

                // Fill background white (prevents black backgrounds on transparent PNGs)
                ctx.fillStyle = "#FFFFFF";
                ctx.fillRect(0, 0, canvas.width, canvas.height);

                ctx.drawImage(img, 0, 0);
                canvas.toBlob((blob) => {
                    const newName = file.name.replace(/\.[^/.]+$/, ".jpg");
                    resolve(new File([blob], newName, { type: 'image/jpeg' }));
                }, 'image/jpeg', 0.85);
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    });
};