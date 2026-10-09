<?php

namespace Modules\Website\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaService
{
    /**
     * Simpan dan konversi file media (gambar -> AVIF, video -> AV1) dengan tetap menyimpan file asli.
     */
    public function storeMedia(UploadedFile $file, string $directory, ?string $customSlug = null): string
    {
        $mime = $file->getMimeType();

        if (str_starts_with($mime, 'image/')) {
            return $this->storeImage($file, $directory, $customSlug);
        }

        if (str_starts_with($mime, 'video/')) {
            return $this->storeVideo($file, $directory, $customSlug);
        }

        return $this->storeDocument($file, $directory, $customSlug);
    }

    /**
     * Konversi gambar ke AVIF + simpan file asli di folder 'original/'.
     */
    public function storeImage(UploadedFile $file, string $directory, ?string $customSlug = null, int $quality = 80): string
    {
        $disk = Storage::disk('public');
        $directory = trim($directory, '/');

        // Pastikan folder tujuan ada
        $disk->makeDirectory($directory);
        $disk->makeDirectory("{$directory}/original");

        $baseName = $customSlug
            ? Str::slug($customSlug)
            : Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $timestamp = time();
        $hash = substr(md5(uniqid((string) mt_rand(), true)), 0, 8);
        $filename = "{$baseName}-{$timestamp}-{$hash}";

        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $originalRelativePath = "{$directory}/original/{$filename}.{$extension}";

        // 1. Simpan file asli terlebih dahulu
        $disk->putFileAs("{$directory}/original", $file, "{$filename}.{$extension}");

        // Format yang tidak di-convert (misal SVG atau ICO)
        if (in_array($extension, ['svg', 'ico'])) {
            $destPath = "{$directory}/{$filename}.{$extension}";
            $disk->copy($originalRelativePath, $destPath);
            return $destPath;
        }

        // 2. Konversi ke AVIF menggunakan PHP GD jika tersedia
        $avifRelativePath = "{$directory}/{$filename}.avif";
        $targetFullPath = $disk->path($avifRelativePath);

        $converted = $this->convertToAvif($file->getRealPath(), $file->getMimeType(), $targetFullPath, $quality);

        if ($converted && file_exists($targetFullPath)) {
            return $avifRelativePath;
        }

        // Fallback jika AVIF gagal dibuat: gunakan format asli di folder utama
        $fallbackPath = "{$directory}/{$filename}.{$extension}";
        $disk->copy($originalRelativePath, $fallbackPath);

        return $fallbackPath;
    }

    /**
     * Simpan video dan konversi ke format AV1 jika FFmpeg tersedia di sistem, dengan menyimpan file asli.
     */
    public function storeVideo(UploadedFile $file, string $directory, ?string $customSlug = null): string
    {
        $disk = Storage::disk('public');
        $directory = trim($directory, '/');

        $disk->makeDirectory($directory);
        $disk->makeDirectory("{$directory}/original");

        $baseName = $customSlug
            ? Str::slug($customSlug)
            : Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $timestamp = time();
        $hash = substr(md5(uniqid((string) mt_rand(), true)), 0, 8);
        $filename = "{$baseName}-{$timestamp}-{$hash}";

        $extension = strtolower($file->getClientOriginalExtension() ?: 'mp4');
        $originalRelativePath = "{$directory}/original/{$filename}.{$extension}";

        // 1. Simpan video asli
        $disk->putFileAs("{$directory}/original", $file, "{$filename}.{$extension}");

        // 2. Coba konversi ke AV1 jika FFmpeg tersedia
        $ffmpegAvailable = $this->isFfmpegAvailable();
        if ($ffmpegAvailable) {
            $av1RelativePath = "{$directory}/{$filename}.mp4";
            $inputFullPath = $disk->path($originalRelativePath);
            $outputFullPath = $disk->path($av1RelativePath);

            $converted = $this->convertVideoToAv1($inputFullPath, $outputFullPath);
            if ($converted && file_exists($outputFullPath)) {
                return $av1RelativePath;
            }
        }

        // Jika FFmpeg tidak ada atau konversi gagal, salin video asli ke folder utama
        $mainRelativePath = "{$directory}/{$filename}.{$extension}";
        $disk->copy($originalRelativePath, $mainRelativePath);

        return $mainRelativePath;
    }

    /**
     * Simpan dokumen umum (PDF, DOCX, ZIP, dll).
     */
    public function storeDocument(UploadedFile $file, string $directory, ?string $customSlug = null): string
    {
        $disk = Storage::disk('public');
        $directory = trim($directory, '/');
        $disk->makeDirectory($directory);

        $baseName = $customSlug
            ? Str::slug($customSlug)
            : Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $timestamp = time();
        $hash = substr(md5(uniqid((string) mt_rand(), true)), 0, 8);
        $extension = strtolower($file->getClientOriginalExtension() ?: 'pdf');
        $filename = "{$baseName}-{$timestamp}-{$hash}.{$extension}";

        $disk->putFileAs($directory, $file, $filename);

        return "{$directory}/{$filename}";
    }

    /**
     * Hapus file hasil konversi dan file aslinya di disk public jika ada.
     */
    public function deleteMedia(mixed $path): void
    {
        if (!$path) {
            return;
        }

        if (is_array($path)) {
            foreach ($path as $p) {
                $this->deleteMedia($p);
            }
            return;
        }

        if (is_string($path)) {
            $trimmed = trim($path);
            if ((str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']')) || (str_starts_with($trimmed, '{') && str_ends_with($trimmed, '}'))) {
                $decoded = json_decode($trimmed, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $p) {
                        $this->deleteMedia($p);
                    }
                    return;
                }
            }

            if (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://')) {
                return;
            }

            $disk = Storage::disk('public');
            $cleanPath = $this->cleanPath($trimmed);
            if (!$cleanPath) {
                return;
            }

            // Hapus file utama
            if ($disk->exists($cleanPath)) {
                $disk->delete($cleanPath);
            }

            // Cari dan hapus file asli yang tersimpan di subfolder /original
            $dir = dirname($cleanPath);
            $filenameWithoutExt = pathinfo($cleanPath, PATHINFO_FILENAME);
            $originalFolder = $dir === '.' ? 'original' : "{$dir}/original";

            if ($disk->exists($originalFolder)) {
                $files = $disk->files($originalFolder);
                foreach ($files as $f) {
                    if (str_starts_with(basename($f), $filenameWithoutExt)) {
                        $disk->delete($f);
                    }
                }
            }
        }
    }


    /**
     * Konversi gambar lokal ke format AVIF menggunakan PHP GD.
     */
    protected function convertToAvif(string $sourcePath, string $mime, string $targetPath, int $quality = 80): bool
    {
        if (!function_exists('imageavif')) {
            return false;
        }

        try {
            $image = match ($mime) {
                'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($sourcePath),
                'image/png'               => @imagecreatefrompng($sourcePath),
                'image/webp'              => @imagecreatefromwebp($sourcePath),
                'image/gif'               => @imagecreatefromgif($sourcePath),
                'image/avif'              => @imagecreatefromavif($sourcePath),
                'image/bmp'               => @imagecreatefrombmp($sourcePath),
                default                   => null,
            };

            if (!$image) {
                // Fallback attempt with string data
                $data = @file_get_contents($sourcePath);
                if ($data) {
                    $image = @imagecreatefromstring($data);
                }
            }

            if (!$image) {
                return false;
            }

            // Pertahankan transparansi PNG / WEBP / AVIF
            imagealphablending($image, false);
            imagesavealpha($image, true);

            $result = imageavif($image, $targetPath, $quality);
            imagedestroy($image);

            return (bool) $result;
        } catch (\Throwable $e) {
            Log::warning('MediaService: Gagal konversi gambar ke AVIF: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cek apakah executable ffmpeg tersedia di server.
     */
    protected function isFfmpegAvailable(): bool
    {
        try {
            $checkCmd = PHP_OS_FAMILY === 'Windows' ? 'where ffmpeg 2>nul' : 'which ffmpeg 2>/dev/null';
            exec($checkCmd, $output, $returnCode);
            return $returnCode === 0;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Jalankan konversi video ke AV1 menggunakan ffmpeg.
     */
    protected function convertVideoToAv1(string $inputFullPath, string $outputFullPath): bool
    {
        try {
            // Gunakan libsvtav1 jika tersedia, fallback ke libaom-av1
            $cmd = sprintf(
                'ffmpeg -y -i %s -c:v libsvtav1 -crf 32 -b:v 0 -c:a libopus -b:a 128k -preset 6 %s 2>&1',
                escapeshellarg($inputFullPath),
                escapeshellarg($outputFullPath)
            );

            exec($cmd, $output, $returnCode);

            if ($returnCode !== 0) {
                // Coba fallback codec libaom-av1
                $cmdFallback = sprintf(
                    'ffmpeg -y -i %s -c:v libaom-av1 -crf 34 -b:v 0 -strict experimental %s 2>&1',
                    escapeshellarg($inputFullPath),
                    escapeshellarg($outputFullPath)
                );
                exec($cmdFallback, $output, $returnCode);
            }

            return $returnCode === 0;
        } catch (\Throwable $e) {
            Log::warning('MediaService: Gagal konversi video ke AV1: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Bersihkan prefix /storage/ atau storage/ agar tersimpan sebagai path database murni.
     */
    public function cleanPath(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, '/storage/')) {
            return substr($path, 9);
        }

        if (str_starts_with($path, 'storage/')) {
            return substr($path, 8);
        }

        return ltrim($path, '/');
    }

    /**
     * Dapatkan URL publik lengkap (/storage/...) dari path yang tersimpan di database.
     */
    public function getUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return '/storage/' . ltrim($this->cleanPath($path), '/');
    }
}
