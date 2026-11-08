<?php

namespace App\Services;

use Aws\S3\S3Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;

/**
 * The only place in the app that touches uploaded files.
 *
 * Files are stored in the S3 bucket S3_BUCKET under the prefix uploads/ (disk "s3" in config/filesystems.php).
 * The app never sends file contents itself: the browser is redirected to a presigned URL and downloads
 * the file straight from S3. There are no access keys in .env: the AWS SDK takes temporary credentials
 * of the server's IAM role (instance profile), so the app can only do what the role's policy allows.
 * Controllers only call save(), delete() and stream(); they never work with keys or URLs themselves.
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

    /** All attachments live under this prefix; the IAM policy of the app allows nothing else. */
    private const PREFIX = 'uploads/';

    /** How long a presigned link works. */
    private const LINK_LIFETIME = '+5 minutes';

    /** Uploads the file under a random name and returns that name (the key is uploads/<name>). */
    public function save(UploadedFile $file): string
    {
        // Never use the user's file name as the key: random name + an extension we trust.
        $mime = $file->getMimeType();
        $name = bin2hex(random_bytes(16)).'.'.self::EXTENSIONS[$mime];

        $this->client()->putObject([
            'Bucket' => $this->bucket(),
            'Key' => self::PREFIX.$name,
            'SourceFile' => $file->getRealPath(),
            'ContentType' => $mime,
        ]);

        return $name;
    }

    /** Deletes uploads/<name>. With versioning on, S3 only adds a delete marker; old versions stay. */
    public function delete(string $name): void
    {
        $this->client()->deleteObject([
            'Bucket' => $this->bucket(),
            'Key' => self::PREFIX.$name,
        ]);
    }

    /**
     * Redirects the browser (302) to a presigned URL that works for 5 minutes.
     * Access rules are checked by the controller before this is called: S3 knows nothing about app users.
     * Images are shown inline; PDFs and "?download=1" are downloaded as attachments under the original name.
     * The link carries these headers, and S3 returns the file with them.
     */
    public function stream(string $name, string $downloadName, string $mime, bool $download): RedirectResponse
    {
        $inline = ! $download && str_starts_with($mime, 'image/');
        $disposition = HeaderUtils::makeDisposition(
            $inline ? HeaderUtils::DISPOSITION_INLINE : HeaderUtils::DISPOSITION_ATTACHMENT,
            $downloadName,
            str_replace('%', '', Str::ascii($downloadName)), // ASCII fallback for old browsers
        );

        $command = $this->client()->getCommand('GetObject', [
            'Bucket' => $this->bucket(),
            'Key' => self::PREFIX.$name,
            'ResponseContentType' => $mime,
            'ResponseContentDisposition' => $disposition,
        ]);

        // Signing is done locally with the role's credentials: no request to S3 is made here.
        $url = (string) $this->client()->createPresignedRequest($command, self::LINK_LIFETIME)->getUri();

        return redirect()->away($url);
    }

    /** The S3 client of the "s3" disk (created once per request and reused by Laravel). */
    private function client(): S3Client
    {
        return Storage::disk('s3')->getClient();
    }

    private function bucket(): string
    {
        return (string) config('filesystems.disks.s3.bucket');
    }
}
