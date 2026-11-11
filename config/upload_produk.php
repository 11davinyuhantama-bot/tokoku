
<?php

function uploadGambarProduk(array $file): string
{
    if (!isset($file['error']) || is_array($file['error'])) {
        throw new RuntimeException('Upload foto tidak valid.');
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Foto gagal diunggah.');
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('Ukuran foto maksimal 2 MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($allowed[$mime])) {
        throw new RuntimeException(
            'Format foto harus JPG, PNG, atau WEBP.'
        );
    }

    $folder = __DIR__ . '/../assets/img/produk/';

    if (!is_dir($folder)) {
        if (!mkdir($folder, 0755, true) && !is_dir($folder)) {
            throw new RuntimeException('Folder foto tidak dapat dibuat.');
        }
    }

    $namaFile = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];

    if (!move_uploaded_file(
        $file['tmp_name'],
        $folder . $namaFile
    )) {
        throw new RuntimeException('Foto gagal disimpan.');
    }

    return $namaFile;
}

function hapusFotoProduk(?string $namaFile): void
{
    if (!$namaFile) {
        return;
    }

    // Hanya menerima nama file sederhana, bukan path.
    if (basename($namaFile) !== $namaFile) {
        return;
    }

    $path = __DIR__ . '/../assets/img/produk/' . $namaFile;

    if (is_file($path)) {
        unlink($path);
    }
}
