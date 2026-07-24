<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Neutral file-level helper for image uploads to the configured private disk.
 *
 * The concrete disk is resolved from config('mygarden.images.disk') so that
 * swapping from local to S3 or any other Laravel-supported driver requires
 * only an environment variable change – no code changes.
 *
 * Responsibilities: MIME validation, safe filename generation, storing a
 * validated image file, existence checks, private response delivery, and
 * best-effort deletion of stored paths.
 *
 * Not responsible for: policies, authorization, DB writes, domain limits
 * (per-entry image counts, sort order, etc.), or transaction management.
 */
class ImageStorage
{
    /**
     * Allowed MIME types and their safe file extensions.
     * Used for both upload validation and image serving whitelists.
     *
     * @var array<string, string>
     */
    public const MIME_EXTENSION_MAP = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * The name of the configured filesystem disk for images.
     */
    public function diskName(): string
    {
        return config('mygarden.images.disk');
    }

    /**
     * Stores a validated image file under the given directory on the
     * configured image disk. The filename is a UUID derived from the
     * server-detected MIME type – the client-supplied filename and extension
     * are ignored.
     *
     * @throws \InvalidArgumentException when the MIME type is not in MIME_EXTENSION_MAP
     * @throws \RuntimeException when the filesystem rejects the write
     */
    public function store(UploadedFile $file, string $directory): StoredImage
    {
        $mime = $file->getMimeType();
        $ext = self::MIME_EXTENSION_MAP[$mime]
            ?? throw new \InvalidArgumentException("Nicht erlaubter MIME-Typ: {$mime}");

        $path = $file->storeAs($directory, Str::uuid().'.'.$ext, $this->diskName());

        if ($path === false) {
            throw new \RuntimeException('Datei konnte nicht gespeichert werden.');
        }

        return new StoredImage(
            path: $path,
            mimeType: $mime,
            size: $file->getSize(),
        );
    }

    /**
     * Returns true when the given path exists on the configured image disk.
     */
    public function exists(string $path): bool
    {
        return $this->disk()->exists($path);
    }

    /**
     * Streams a private image response with security headers.
     * The Content-Type is set from the caller-supplied (DB-stored) mimeType.
     */
    public function privateResponse(string $path, string $mimeType): StreamedResponse
    {
        return $this->disk()->response($path, null, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Deletes multiple paths from the configured image disk.
     * Missing files are silently ignored. Storage errors are logged
     * via report() so a single failure does not abort the remaining deletions.
     *
     * @param  string[]  $paths
     */
    public function deletePaths(array $paths): void
    {
        $disk = $this->disk();

        foreach ($paths as $path) {
            try {
                $disk->delete($path);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    private function disk(): Filesystem
    {
        return Storage::disk($this->diskName());
    }
}
