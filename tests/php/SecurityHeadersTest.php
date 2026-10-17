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

it('drops only the users sitemap provider', function () {
    Functions\when('apply_filters')->returnArg(2);

    expect(App\sobe_sitemap_provider('provider', 'users'))->toBeFalse();
    expect(App\sobe_sitemap_provider('provider', 'posts'))->toBe('provider');
});

it('keeps the users sitemap when author enumeration blocking is off', function () {
    Functions\when('apply_filters')->justReturn(false);

    expect(App\sobe_sitemap_provider('provider', 'users'))->toBe('provider');
});

it('sets the baseline headers without includeSubDomains', function () {
    $headers = App\sobe_security_headers(true);

    expect($headers['X-Content-Type-Options'])->toBe('nosniff');
    expect($headers['X-Frame-Options'])->toBe('SAMEORIGIN');
    expect($headers['Referrer-Policy'])->toBe('strict-origin-when-cross-origin');
    expect($headers['Permissions-Policy'])->toBe('camera=(), microphone=(), geolocation=()');
    expect($headers['Strict-Transport-Security'])->not->toContain('includeSubDomains');
});
