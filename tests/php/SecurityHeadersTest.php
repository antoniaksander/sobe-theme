<?php

/**
 * Baseline security headers from app/security.php.
 */

use Brain\Monkey\Functions;

beforeEach(function () {
    Functions\when('add_action')->justReturn(true);
    Functions\when('add_filter')->justReturn(true);
    Functions\when('remove_action')->justReturn(true);
    Functions\when('config')->justReturn('sobe');

    require_once dirname(__DIR__, 2).'/app/security.php';
});

it('sends HSTS only over HTTPS', function () {
    expect(App\sobe_security_headers(true))->toHaveKey('Strict-Transport-Security', 'max-age=31536000');
    expect(App\sobe_security_headers(false))->not->toHaveKey('Strict-Transport-Security');
});

it('sets the baseline headers without includeSubDomains', function () {
    $headers = App\sobe_security_headers(true);

    expect($headers['X-Content-Type-Options'])->toBe('nosniff');
    expect($headers['X-Frame-Options'])->toBe('SAMEORIGIN');
    expect($headers['Referrer-Policy'])->toBe('strict-origin-when-cross-origin');
    expect($headers['Permissions-Policy'])->toBe('camera=(), microphone=(), geolocation=()');
    expect($headers['Strict-Transport-Security'])->not->toContain('includeSubDomains');
});
