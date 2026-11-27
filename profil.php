<?php
/** Profil sendiri, dapat dibuka Admin maupun Petugas. */
require __DIR__ . '/includes/init.php';

$user = require_login();
$formError = '';

function delete_profile_photo(?string $name): void
{
    $name = basename((string) $name);
    if (preg_match('/^u[0-9]+_[a-f0-9]{16}\.(jpg|png|webp)$/', $name)) {
        $path = __DIR__ . '/uploads/profil/' . $name;
        if (is_file($path)) {
            unlink($path);
        }
    }
}

function save_profile_photo(array $file, int $userId): string
{
    if (in_array($file['error'] ?? null, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
        throw new RuntimeException('Ukuran foto maksimal 2 MB.');
    }
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !isset($file['tmp_name']) || !is_string($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Pilih foto yang berhasil diunggah.');
    }
    if ((int) $file['size'] > 2 * 1024 * 1024 || (int) $file['size'] <= 0) {
        throw new RuntimeException('Ukuran foto maksimal 2 MB.');
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $dimensions = @getimagesize($file['tmp_name']);
    if (!isset($types[$mime]) || $dimensions === false || $dimensions['mime'] !== $mime) {
        throw new RuntimeException('Foto harus berupa gambar JPG, PNG, atau WebP yang valid.');
    }
    if ($dimensions[0] < 1 || $dimensions[1] < 1 || $dimensions[0] * $dimensions[1] > 20000000) {
        throw new RuntimeException('Resolusi foto terlalu besar.');
    }

    $dir = __DIR__ . '/uploads/profil';
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        throw new RuntimeException('Folder foto tidak dapat dibuat.');
    }
    $name = 'u' . $userId . '_' . bin2hex(random_bytes(8)) . '.' . $types[$mime];
    $path = $dir . '/' . $name;

    if (extension_loaded('gd')) {
        $loaders = ['image/jpeg' => 'imagecreatefromjpeg', 'image/png' => 'imagecreatefrompng', 'image/webp' => 'imagecreatefromwebp'];
        $writers = ['image/jpeg' => 'imagejpeg', 'image/png' => 'imagepng', 'image/webp' => 'imagewebp'];
        if (function_exists($loaders[$mime]) && function_exists($writers[$mime])) {
            $loader = $loaders[$mime];
            $source = @$loader($file['tmp_name']);
            if ($source === false) {
                throw new RuntimeException('Foto tidak dapat diproses.');
            }
            $scale = min(1, 400 / $dimensions[0], 400 / $dimensions[1]);
            $width = max(1, (int) round($dimensions[0] * $scale));
            $height = max(1, (int) round($dimensions[1] * $scale));
            $target = imagecreatetruecolor($width, $height);
            if ($mime !== 'image/jpeg') {
                imagealphablending($target, false);
                imagesavealpha($target, true);
                $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
                imagefill($target, 0, 0, $transparent);
            }
            imagecopyresampled($target, $source, 0, 0, 0, 0, $width, $height, $dimensions[0], $dimensions[1]);
            $writer = $writers[$mime];
            $saved = $mime === 'image/jpeg' ? imagejpeg($target, $path, 85) : $writer($target, $path);
            imagedestroy($source);
            imagedestroy($target);
            if (!$saved) {
                if (is_file($path)) {
                    unlink($path);
                }
                throw new RuntimeException('Foto tidak dapat disimpan.');
            }
            return $name;
        }
    }
    if (!move_uploaded_file($file['tmp_name'], $path)) {
        throw new RuntimeException('Foto tidak dapat disimpan.');
    }
    return $name;
}

if (is_post()) {
    verify_csrf();
    $aksi = post('aksi');
    if ($aksi === 'upload_foto') {
        $newName = null;
        try {
            $newName = save_profile_photo($_FILES['foto'] ?? [], (int) $user['UserID']);
            db_exec('UPDATE tb_user SET foto = ? WHERE UserID = ?', [$newName, $user['UserID']]);
            delete_profile_photo($user['foto']);
            flash('Foto profil berhasil diperbarui.');
            redirect('profil.php');
        } catch (PDOException $ex) {
            if ($newName !== null) {
                delete_profile_photo($newName);
            }
            throw $ex;
        } catch (RuntimeException $ex) {
            if ($newName !== null) {
                delete_profile_photo($newName);
            }
            $formError = $ex->getMessage();
        }
    } elseif ($aksi === 'hapus_foto') {
        db_exec('UPDATE tb_user SET foto = NULL WHERE UserID = ?', [$user['UserID']]);
        delete_profile_photo($user['foto']);
        flash('Foto profil berhasil diperbarui.');
        redirect('profil.php');
    } else {
        $formError = 'Aksi tidak valid.';
    }
}

render('profil', ['user' => $user, 'formError' => $formError, 'activeModule' => 'profil']);
