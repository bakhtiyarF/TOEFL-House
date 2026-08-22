<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * File Upload Service
 *
 * Handles file uploads for student photos, certificates, book covers, etc.
 * Stores files in organized directories per module/entity.
 */
class FileUploadService
{
    private const MAX_PHOTO_SIZE = 2 * 1024 * 1024;  // 2MB
    private const MAX_DOCUMENT_SIZE = 10 * 1024 * 1024; // 10MB

    private const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
    private const ALLOWED_DOCUMENT_TYPES = ['application/pdf', 'image/jpeg', 'image/png'];

    /**
     * Upload a student profile photo
     */
    public function uploadStudentPhoto(UploadedFile $file, string $studentId): string
    {
        $this->validateFile($file, self::MAX_PHOTO_SIZE, self::ALLOWED_IMAGE_TYPES);

        $path = "students/{$studentId}/photo";
        return $this->store($file, $path);
    }

    /**
     * Upload a certificate document
     */
    public function uploadCertificate(UploadedFile $file, string $certificateId): string
    {
        $this->validateFile($file, self::MAX_DOCUMENT_SIZE, self::ALLOWED_DOCUMENT_TYPES);

        $path = "certificates/{$certificateId}";
        return $this->store($file, $path);
    }

    /**
     * Upload a book cover image
     */
    public function uploadBookCover(UploadedFile $file, string $bookId): string
    {
        $this->validateFile($file, self::MAX_PHOTO_SIZE, self::ALLOWED_IMAGE_TYPES);

        $path = "books/{$bookId}/cover";
        return $this->store($file, $path);
    }

    /**
     * Upload a user profile photo
     */
    public function uploadUserProfilePhoto(UploadedFile $file, string $userId): string
    {
        $this->validateFile($file, self::MAX_PHOTO_SIZE, self::ALLOWED_IMAGE_TYPES);

        $path = "users/{$userId}/photo";
        return $this->store($file, $path);
    }

    /**
     * Upload a campaign/impact image
     */
    public function uploadCampaignImage(UploadedFile $file, string $campaignId): string
    {
        $this->validateFile($file, self::MAX_DOCUMENT_SIZE, self::ALLOWED_IMAGE_TYPES);

        $path = "campaigns/{$campaignId}/image";
        return $this->store($file, $path);
    }

    /**
     * Delete a file by path
     */
    public function delete(string $path): bool
    {
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }
        return false;
    }

    /**
     * Get the public URL for a file
     */
    public function url(string $path): string
    {
        return Storage::disk('public')->url($path);
    }

    /**
     * Validate uploaded file
     */
    private function validateFile(UploadedFile $file, int $maxSize, array $allowedTypes): void
    {
        if ($file->getSize() > $maxSize) {
            $maxMB = round($maxSize / 1024 / 1024, 1);
            throw new \RuntimeException("File size exceeds maximum of {$maxMB}MB");
        }

        if (!in_array($file->getMimeType(), $allowedTypes)) {
            $allowed = implode(', ', array_map(fn($t) => explode('/', $t)[1] ?? $t, $allowedTypes));
            throw new \RuntimeException("Invalid file type. Allowed: {$allowed}");
        }

        if (!$file->isValid()) {
            throw new \RuntimeException('File upload failed validation');
        }
    }

    /**
     * Store file with unique name
     */
    private function store(UploadedFile $file, string $directory): string
    {
        // Delete existing file in same location (replace behavior)
        $existing = Storage::disk('public')->files($directory);
        foreach ($existing as $old) {
            Storage::disk('public')->delete($old);
        }

        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;
        $path = "{$directory}/{$filename}";

        Storage::disk('public')->putFileAs($directory, $file, $filename);

        return $path;
    }
}
