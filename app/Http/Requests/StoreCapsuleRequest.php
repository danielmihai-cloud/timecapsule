<?php

namespace App\Http\Requests;

use App\Services\CapsuleStorage;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\ViewErrorBag;

/**
 * Validation for "New capsule".
 * Title and message are already trimmed by Laravel's TrimStrings middleware,
 * and empty strings are turned into null.
 */
class StoreCapsuleRequest extends FormRequest
{
    public function rules(): array
    {
        // "bail" = stop at the first failing rule, so each field shows one message.
        return [
            'title' => ['bail', 'required', 'string', 'max:120'],
            'message' => ['bail', 'required', 'string', 'max:5000'],
            'recipient_email' => ['bail', 'nullable', 'email:filter', 'max:255'],
            'is_public' => ['nullable'],
            // "open_at" and "attachment" are checked in after() below.
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Enter a title.',
            'title.string' => 'Enter a title.',
            'title.max' => 'Title must be at most 120 characters.',
            'message.required' => 'Write a message.',
            'message.string' => 'Write a message.',
            'message.max' => 'Message must be at most 5000 characters.',
            'recipient_email.email' => 'Enter a valid email address.',
            'recipient_email.max' => 'Enter a valid email address.',
        ];
    }

    /**
     * Extra checks that run after the rules above: the open time and the optional attachment.
     * (An after-hook also runs for uploads that PHP already rejected, e.g. a file that is too big.)
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $openAt = $this->openAt();
                if ($openAt === null) {
                    $validator->errors()->add('open_at', 'Choose an open date and time.');
                } elseif ($openAt->lte(now())) {
                    $validator->errors()->add('open_at', 'The open time must be in the future.');
                }
            },
            function (Validator $validator) {
                $error = $this->attachmentError($this->file('attachment'));
                if ($error !== null) {
                    $validator->errors()->add('attachment', $error);
                }
            },
        ];
    }

    /**
     * The chosen open moment in UTC, or null if the form sent nothing usable.
     *
     * The browser script sends the same moment twice: "open_at" = local time as typed
     * ("2027-01-01T13:30") and "open_at_utc" = that moment in UTC ("2027-01-01T11:30:00.000Z").
     * Without JavaScript only "open_at" arrives; then we treat it as UTC.
     * Seconds are dropped: capsules open on a whole minute.
     */
    public function openAt(): ?Carbon
    {
        $utc = $this->input('open_at_utc');
        if (is_string($utc) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2}(\.\d{1,3})?)?Z$/', $utc)) {
            $seconds = (int) substr($utc, 17, 2); // 0 when the value has no seconds
            $moment = $seconds <= 59 ? self::parseMinute(substr($utc, 0, 16)) : null;
            if ($moment !== null) {
                return $moment;
            }
        }

        $local = $this->input('open_at');
        if (is_string($local) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $local)) {
            return self::parseMinute($local);
        }

        return null;
    }

    /** "2027-01-01T13:30" as UTC; null for impossible values like 2027-02-30T25:00. */
    private static function parseMinute(string $value): ?Carbon
    {
        $moment = Carbon::createFromFormat('!Y-m-d\TH:i', $value, 'UTC');

        // createFromFormat() quietly rolls "Feb 30" over to March, so compare with the input.
        return $moment !== null && $moment->format('Y-m-d\TH:i') === $value ? $moment : null;
    }

    private function attachmentError(mixed $file): ?string
    {
        if ($file === null) {
            return null; // no file chosen: attachments are optional
        }

        if (! $file instanceof UploadedFile) {
            return 'Upload failed. Please try again.';
        }

        $maxMb = config('timecapsule.max_upload_mb');

        // PHP itself rejects files above upload_max_filesize (php.ini) with UPLOAD_ERR_INI_SIZE.
        $tooBigForPhp = in_array($file->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true);
        if ($tooBigForPhp || ($file->isValid() && $file->getSize() > $maxMb * 1024 * 1024)) {
            return "File must be at most {$maxMb} MB.";
        }

        if (! $file->isValid()) {
            return 'Upload failed. Please try again.';
        }

        // getMimeType() looks at the file CONTENT (PHP fileinfo), not at the file name or at what
        // the browser claims, so a renamed .exe cannot pass as a .png.
        if (! array_key_exists((string) $file->getMimeType(), CapsuleStorage::EXTENSIONS)) {
            return 'File must be JPG, PNG, GIF, WEBP or PDF.';
        }

        return null;
    }

    /**
     * Laravel's default is "redirect back". We re-render the form directly instead,
     * with the per-field errors and the values the user typed (a file cannot be kept).
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->view('capsules.create', [
            'errors' => (new ViewErrorBag)->put('default', $validator->errors()),
            'values' => $this->except(['_token', 'attachment']),
        ], 422));
    }
}
