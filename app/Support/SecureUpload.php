<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use ZipArchive;

final class SecureUpload
{
    /** @var array<string, array<int, string>> */
    private const MIME_ALLOWLIST = [
        'pdf' => ['application/pdf'],
        'png' => ['image/png'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'docx' => [
            'application/zip',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ],
        'xlsx' => [
            'application/zip',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ],
        'csv' => ['text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel'],
        'txt' => ['text/plain', 'text/csv'],
    ];

    /**
     * @param array<int, string> $allowedExtensions
     * @return array{extension: string, mime: string, original_name: string, size: int}
     */
    public static function validate(UploadedFile $file, array $allowedExtensions): array
    {
        if (! $file->isValid()) {
            self::fail('A feltöltés nem fejeződött be hibamentesen.');
        }

        $size = (int) $file->getSize();
        if ($size < 1 || $size > (int) config('security.uploads.max_bytes', 10 * 1024 * 1024)) {
            self::fail('A fájl üres vagy túllépi a 10 MB-os korlátot.');
        }

        $originalName = $file->getClientOriginalName();
        if (strlen($originalName) > 180
            || basename(str_replace('\\', '/', $originalName)) !== $originalName
            || str_starts_with($originalName, '.')
            || UntrustedInput::stringViolation($originalName, 'filename')) {
            self::fail('A fájlnév nem biztonságos.');
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, $allowedExtensions, true) || ! isset(self::MIME_ALLOWLIST[$extension])) {
            self::fail('Ez a fájltípus nem engedélyezett.');
        }

        $mime = strtolower((string) $file->getMimeType());
        if (! in_array($mime, self::MIME_ALLOWLIST[$extension], true)) {
            self::fail('A fájl tartalma nem egyezik a kiterjesztésével.');
        }

        $path = $file->getRealPath();
        if (! is_string($path) || $path === '') {
            self::fail('A feltöltött fájl nem olvasható.');
        }

        match ($extension) {
            'pdf' => self::validatePdf($path),
            'png', 'jpg', 'jpeg' => self::validateImage($path, $extension),
            'docx' => self::validateOfficeArchive($path, 'word/document.xml'),
            'xlsx' => self::validateOfficeArchive($path, 'xl/workbook.xml'),
            'csv', 'txt' => self::validateTextFile($path),
            default => self::fail('Ez a fájltípus nem engedélyezett.'),
        };

        return [
            'extension' => $extension,
            'mime' => $mime,
            'original_name' => self::safeDownloadName($originalName),
            'size' => $size,
        ];
    }

    private static function validatePdf(string $path): void
    {
        $contents = file_get_contents($path);
        if ($contents === false || ! str_starts_with($contents, '%PDF-')) {
            self::fail('A PDF fájl fejléce érvénytelen.');
        }

        if (preg_match('/\/(?:JavaScript|JS|Launch|OpenAction|EmbeddedFile)\b/i', $contents) === 1) {
            self::fail('Aktív vagy beágyazott tartalmat tartalmazó PDF nem tölthető fel.');
        }
    }

    private static function validateImage(string $path, string $extension): void
    {
        $info = @getimagesize($path);
        $expected = $extension === 'png' ? IMAGETYPE_PNG : IMAGETYPE_JPEG;
        if ($info === false || ($info[2] ?? null) !== $expected) {
            self::fail('A képfájl szerkezete érvénytelen.');
        }
    }

    private static function validateTextFile(string $path): void
    {
        $contents = file_get_contents($path);
        if ($contents === false || ! mb_check_encoding($contents, 'UTF-8') || str_contains($contents, "\0")) {
            self::fail('A szövegfájl csak érvényes UTF-8 adatot tartalmazhat.');
        }
    }

    private static function validateOfficeArchive(string $path, string $requiredEntry): void
    {
        if (! class_exists(ZipArchive::class)) {
            self::fail('Az Office-fájl ellenőrzéséhez a PHP zip bővítmény szükséges.');
        }

        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            self::fail('Az Office-fájl nem érvényes ZIP csomag.');
        }

        try {
            if ($zip->locateName('[Content_Types].xml') === false || $zip->locateName($requiredEntry) === false) {
                self::fail('Az Office-fájl kötelező részei hiányoznak.');
            }

            $entries = $zip->numFiles;
            if ($entries > (int) config('security.uploads.max_archive_entries', 2000)) {
                self::fail('Az Office-fájl túl sok bejegyzést tartalmaz.');
            }

            $uncompressedBytes = 0;
            for ($index = 0; $index < $entries; $index++) {
                $stat = $zip->statIndex($index);
                $name = (string) ($stat['name'] ?? '');
                $uncompressedBytes += (int) ($stat['size'] ?? 0);

                if ($name === ''
                    || str_starts_with($name, '/')
                    || preg_match('/(?:^|\/)\.\.(?:\/|$)/', $name) === 1
                    || preg_match('/(?:vbaProject\.bin|activeX|customUI)/i', $name) === 1) {
                    self::fail('Az Office-fájl tiltott vagy veszélyes bejegyzést tartalmaz.');
                }
            }

            if ($uncompressedBytes > (int) config('security.uploads.max_uncompressed_bytes', 50 * 1024 * 1024)) {
                self::fail('Az Office-fájl kitömörített mérete túl nagy.');
            }
        } finally {
            $zip->close();
        }
    }

    private static function safeDownloadName(string $name): string
    {
        $name = preg_replace('/[^\pL\pN ._()\-]/u', '_', $name) ?? 'document';

        return mb_substr($name, 0, 180);
    }

    private static function fail(string $message): never
    {
        throw ValidationException::withMessages(['file' => $message]);
    }
}
