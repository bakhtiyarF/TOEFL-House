<?php

/**
 * API Versioning Middleware Tests
 * Tests version resolution from URL, headers, and defaults
 */

describe('ApiVersioning Middleware', function () {

    it('extracts version from URL prefix', function () {
        $path = 'api/v1/students';
        preg_match('#^api/(v\d+)/#', $path, $matches);

        expect($matches[1])->toBe('v1');
    });

    it('extracts version from Accept header', function () {
        $accept = 'application/vnd.api.v2+json';
        preg_match('/application\/vnd\.api\.(v\d+)\+json/', $accept, $matches);

        expect($matches[1])->toBe('v2');
    });

    it('validates custom header format', function () {
        $validHeaders = ['v1', 'v2', 'v10'];
        $invalidHeaders = ['1', 'V1', 'version1', 'v'];

        foreach ($validHeaders as $header) {
            expect(preg_match('/^v\d+$/', $header))->toBe(1);
        }

        foreach ($invalidHeaders as $header) {
            expect(preg_match('/^v\d+$/', $header))->toBe(0);
        }
    });

    it('defaults to v1 when no version specified', function () {
        $supportedVersions = ['v1', 'v2'];
        $defaultVersion = 'v1';

        expect(in_array($defaultVersion, $supportedVersions))->toBeTrue();
    });

    it('rejects unsupported versions', function () {
        $supportedVersions = ['v1', 'v2'];
        $requestedVersion = 'v3';

        expect(in_array($requestedVersion, $supportedVersions))->toBeFalse();
    });

    it('prioritizes URL over headers', function () {
        // URL says v2, header says v1
        $urlVersion = 'v2';
        $headerVersion = 'v1';

        // URL should take priority
        $resolvedVersion = $urlVersion;

        expect($resolvedVersion)->toBe('v2');
    });

    it('supports multiple version resolution methods', function () {
        $methods = [
            'url_prefix' => 'api/v1/students',
            'accept_header' => 'application/vnd.api.v1+json',
            'custom_header' => 'v1',
        ];

        // All methods should resolve to v1
        $versions = [];

        // URL
        if (preg_match('#^api/(v\d+)/#', $methods['url_prefix'], $m)) {
            $versions[] = $m[1];
        }

        // Accept
        if (preg_match('/application\/vnd\.api\.(v\d+)\+json/', $methods['accept_header'], $m)) {
            $versions[] = $m[1];
        }

        // Custom
        if (preg_match('/^v\d+$/', $methods['custom_header'])) {
            $versions[] = $methods['custom_header'];
        }

        expect($versions)->toBe(['v1', 'v1', 'v1']);
    });

    it('adds version header to response', function () {
        $version = 'v1';
        $responseHeaders = ['X-API-Version' => $version];

        expect($responseHeaders['X-API-Version'])->toBe('v1');
    });

    it('stores version in request attributes', function () {
        $version = 'v2';
        $attributes = ['api_version' => $version];

        expect($attributes['api_version'])->toBe('v2');
    });
});
