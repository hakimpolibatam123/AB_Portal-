<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

/**
 * Uploaded files. Stored outside the web root under storage/app/private/<yyyy>/<mm>/ with a random
 * name and served only to a signed-in session (/files/{id}).
 */
final class Attachment
{
    public const DIRECTORY = '/storage/app/private';

    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * Checks an uploaded image ($_FILES entry) by size, real image header and magic bytes; the
     * extension and the browser's MIME type are not trusted.
     * @param array<string, string> $types allowed MIME type => stored extension
     * @return array{0: ?array{tmp: string, mime: string, ext: string, name: string, size: int}, 1: ?string} [file, error]
     */
    public static function checkImage(array $upload, array $types, int $maxBytes): array
    {
        $error = (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE);
        if (in_array($error, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) || (int) ($upload['size'] ?? 0) > $maxBytes) {
            return [null, 'Ukuran gambar maksimal ' . round($maxBytes / 1048576, 1) . ' MB.'];
        }
        if ($error !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($upload['tmp_name'] ?? ''))) {
            return [null, 'Gambar gagal diunggah. Coba pilih file lagi.'];
        }
        $tmp = (string) $upload['tmp_name'];
        $info = @getimagesize($tmp);
        $mime = is_array($info) ? (string) ($info['mime'] ?? '') : '';
        $head = (string) file_get_contents($tmp, false, null, 0, 12);
        $magic = match ($mime) {
            'image/png' => str_starts_with($head, "\x89PNG\r\n\x1a\n"),
            'image/jpeg' => str_starts_with($head, "\xFF\xD8\xFF"),
            'image/webp' => str_starts_with($head, 'RIFF') && substr($head, 8, 4) === 'WEBP',
            default => false,
        };
        if (!isset($types[$mime]) || !$magic || $info[0] < 1 || $info[1] < 1 || $info[0] > 4096 || $info[1] > 4096) {
            return [null, 'Pilih gambar PNG, JPG, atau WebP.'];
        }
        $name = mb_substr(basename(str_replace('\\', '/', (string) ($upload['name'] ?? 'gambar'))), 0, 255);
        return [['tmp' => $tmp, 'mime' => $mime, 'ext' => $types[$mime], 'name' => $name, 'size' => (int) filesize($tmp)], null];
    }

    /** Moves a checked upload into private storage and records it. @param array{tmp: string, mime: string, ext: string, name: string, size: int} $file */
    public function store(array $file, string $ownerType): int
    {
        $relative = date('Y') . '/' . date('m') . '/' . bin2hex(random_bytes(16)) . '.' . $file['ext'];
        $target = BASE_PATH . self::DIRECTORY . '/' . $relative;
        if (!is_dir(dirname($target)) && !mkdir(dirname($target), 0750, true) && !is_dir(dirname($target))) {
            throw new \RuntimeException('Folder penyimpanan file tidak dapat dibuat.');
        }
        if (!move_uploaded_file($file['tmp'], $target)) {
            throw new \RuntimeException('File unggahan tidak dapat disimpan.');
        }
        $this->pdo->prepare('INSERT INTO attachments (owner_type, original_name, stored_path, mime_type, size_bytes, sha256) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$ownerType, $file['name'], $relative, $file['mime'], $file['size'], hash_file('sha256', $target)]);
        return (int) $this->pdo->lastInsertId();
    }

    public function attachTo(int $id, int $ownerId): void
    {
        $this->pdo->prepare('UPDATE attachments SET owner_id = ? WHERE id = ?')->execute([$ownerId, $id]);
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT id, owner_type, original_name, stored_path, mime_type, size_bytes FROM attachments WHERE id = ? AND deleted_at IS NULL');
        $statement->execute([$id]);
        return $statement->fetch() ?: null;
    }

    /** Absolute path of a stored file, or null when it is missing or outside private storage. */
    public static function path(array $attachment): ?string
    {
        $root = realpath(BASE_PATH . self::DIRECTORY);
        $file = realpath(BASE_PATH . self::DIRECTORY . '/' . $attachment['stored_path']);
        return $root !== false && $file !== false && str_starts_with($file, $root . DIRECTORY_SEPARATOR) && is_file($file) ? $file : null;
    }
}
