<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The only place in the app that touches uploaded files.
 *
 * Files are saved in a folder on the local disk (config timecapsule.upload_dir).
 * Controllers only call save(), delete() and stream(); they never work with paths themselves,
 * so the way files are stored can be changed here without touching the rest of the app.
 */
class CapsuleStorage
{
    /** Allowed file types: real MIME type (sniffed from the file content) => extension we save with. */
    public const EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];

    /** Saves the uploaded file under a random name and returns that name. */
    public function save(UploadedFile $file): string
    {
        // Never use the user's file name on disk: random name + an extension we trust.
        $name = bin2hex(random_bytes(16)).'.'.self::EXTENSIONS[$file->getMimeType()];
        $this->disk()->putFileAs('', $file, $name);

        return $name;
    }

    public function delete(string $name): void
    {
        $this->disk()->delete($name);
    }

    /**
     * Sends the file to the browser through the app (files are never in the public/ folder,
     * so access rules are always checked first).
     * Images are shown inline; PDFs and "?download=1" are downloaded as attachments.
     */
    public function stream(string $name, string $downloadName, string $mime, bool $download): StreamedResponse
    {
        $disk = $this->disk();
        abort_unless($disk->exists($name), 404);

        $inline = ! $download && str_starts_with($mime, 'image/');

        return $disk->response($name, $downloadName, ['Content-Type' => $mime], $inline ? 'inline' : 'attachment');
    }

    /** A "local" disk whose root is UPLOAD_DIR (the folder is created if it is missing). */
    private function disk(): Filesystem
    {
        return Storage::build([
            'driver' => 'local',
            'root' => config('timecapsule.upload_dir'),
        ]);
    }
}
