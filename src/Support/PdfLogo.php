<?php

namespace VentureDrake\LaravelCrm\Support;

use finfo;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Resolves the uploaded brand logo into a form DomPDF can embed.
 *
 * The logo is uploaded to the disk named by `laravel-crm.upload_disk` (`public` by default) and the `logo_file` setting
 * holds its disk-relative path (e.g. `laravel-crm/acme.png`). A browser
 * page can render that straight through `asset('storage/'.$logo)`, but a
 * PDF cannot: DomPDF refuses to fetch http(s) URLs unless the host sets
 * `dompdf.enable_remote`, which is false by default — so every PDF came
 * out with a broken-image box where the logo should be. An unencoded
 * space in the filename compounds the failure.
 *
 * Inlining the file bytes as a `data:` URI side-steps both issues and
 * needs no host configuration. The PDF templates detect the leading
 * `data:` prefix and use the value as-is instead of prefixing it with
 * `storage/`.
 *
 * Only the PDF render paths go through here. The portal's on-screen
 * `show` views keep passing the raw path, because a browser fetches
 * `asset('storage/...')` perfectly well and inlining there would bloat
 * the HTML on every page load.
 */
class PdfLogo
{
    /**
     * The MIME types a document logo may have, read from the BYTES of the file (plan CRM 2.1i, crm-2-1i-1): raster images
     * only. An SVG (scripts, external references, a temporary file of the SVG library past every engine option) is no logo.
     */
    private const RASTER_MIME_TYPES = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];

    /**
     * Resolve the logo currently configured in settings, ready to drop
     * into a PDF view's `logo` variable.
     */
    public static function fromSettings(): ?string
    {
        return self::src(app('laravel-crm.settings')->get('logo_file', null));
    }

    /**
     * Inline `$logo` (a path relative to the upload disk) as a data URI.
     *
     * Returns null when there is no logo, when the file has gone missing, when
     * the disk cannot be read, or when the bytes are not a PNG, JPEG, GIF or WebP image (the type is read from the
     * content, never from the name: an SVG under a .png name is no logo) — the templates then fall back to
     * rendering the organisation name as text, which reads better than a
     * broken-image box.
     */
    public static function src(?string $logo): ?string
    {
        if (! $logo) {
            return null;
        }

        try {
            $disk = Storage::disk((string) config('laravel-crm.upload_disk', 'public'));

            if (! $disk->exists($logo)) {
                return null; // nosemgrep: semgrep.rules.php-silent-exception-swallow -- upstream 2.4.7 debt, README-FORK
            }

            $contents = $disk->get($logo);

            if ($contents === null || $contents === '') {
                return null; // nosemgrep: semgrep.rules.php-silent-exception-swallow -- upstream 2.4.7 debt, README-FORK
            }

        } catch (Throwable $e) {
            return null; // nosemgrep: semgrep.rules.php-silent-exception-swallow -- upstream 2.4.7 debt, README-FORK
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($contents);

        if (! in_array($mime, self::RASTER_MIME_TYPES, true)) {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }
}
