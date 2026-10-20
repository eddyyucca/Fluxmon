import QRCode from 'qrcode';

// Fill every <canvas data-qr="..."> on the voucher sheet with its QR code.
document.querySelectorAll('canvas[data-qr]').forEach((canvas) => {
    QRCode.toCanvas(canvas, canvas.dataset.qr, { margin: 0, width: canvas.width, errorCorrectionLevel: 'M' });
});
