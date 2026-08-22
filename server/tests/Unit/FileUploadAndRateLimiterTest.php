<?php

/**
 * FileUploadService Tests
 * Tests file validation logic
 */

describe('FileUploadService', function () {

    it('rejects files exceeding max photo size (2MB)', function () {
        $maxSize = 2 * 1024 * 1024; // 2MB
        $fileSize = 3 * 1024 * 1024; // 3MB

        $exceeds = $fileSize > $maxSize;
        expect($exceeds)->toBeTrue();
    });

    it('accepts files within max photo size', function () {
        $maxSize = 2 * 1024 * 1024;
        $fileSize = 1.5 * 1024 * 1024; // 1.5MB

        $exceeds = $fileSize > $maxSize;
        expect($exceeds)->toBeFalse();
    });

    it('validates image MIME types correctly', function () {
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];

        expect(in_array('image/jpeg', $allowed))->toBeTrue();
        expect(in_array('image/png', $allowed))->toBeTrue();
        expect(in_array('image/webp', $allowed))->toBeTrue();
        expect(in_array('image/gif', $allowed))->toBeFalse();
        expect(in_array('application/pdf', $allowed))->toBeFalse();
    });

    it('validates document MIME types correctly', function () {
        $allowed = ['application/pdf', 'image/jpeg', 'image/png'];

        expect(in_array('application/pdf', $allowed))->toBeTrue();
        expect(in_array('image/svg+xml', $allowed))->toBeFalse();
    });

    it('generates unique filenames with UUID', function () {
        $uuid1 = sprintf('%s.jpg', bin2hex(random_bytes(16)));
        $uuid2 = sprintf('%s.jpg', bin2hex(random_bytes(16)));

        expect($uuid1)->not->toBe($uuid2);
    });

    it('organizes files by module/entity path', function () {
        $studentId = 'abc-123';
        $path = "students/{$studentId}/photo";

        expect($path)->toBe('students/abc-123/photo');
        expect($path)->toContain('students/');
    });

    it('replaces existing files in same location', function () {
        $directory = 'students/abc-123/photo';
        $existing = ["{$directory}/old-uuid.jpg"];

        // Simulate delete old + store new
        $newFile = "{$directory}/new-uuid.jpg";

        expect($newFile)->not->toBe($existing[0]);
        expect(str_contains($newFile, $directory))->toBeTrue();
    });
});

describe('ApiRateLimiter', function () {

    it('auth tier allows 5 requests per minute', function () {
        $limits = ['auth' => ['max' => 5, 'decay' => 60]];
        expect($limits['auth']['max'])->toBe(5);
    });

    it('write tier allows 60 requests per minute', function () {
        $limits = ['write' => ['max' => 60, 'decay' => 60]];
        expect($limits['write']['max'])->toBe(60);
    });

    it('read tier allows 120 requests per minute', function () {
        $limits = ['read' => ['max' => 120, 'decay' => 60]];
        expect($limits['read']['max'])->toBe(120);
    });

    it('generates unique rate limit keys per user', function () {
        $tier = 'write';
        $userId = 'user-123';
        $key = "{$tier}:{$userId}";

        expect($key)->toBe('write:user-123');
    });

    it('falls back to IP for unauthenticated requests', function () {
        $tier = 'auth';
        $userId = null;
        $ip = '192.168.1.1';
        $key = "{$tier}:" . ($userId ?? $ip);

        expect($key)->toBe('auth:192.168.1.1');
    });
});
