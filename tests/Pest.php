<?php

/**
 * Pest bootstrap for PHP unit tests under tests/php.
 *
 * Brain Monkey lets us mock WordPress/WooCommerce functions so the platform's
 * catalog/filter logic can be unit-tested without bootstrapping WordPress.
 */

use Brain\Monkey;

uses()
    ->beforeEach(function () {
        Monkey\setUp();
    })
    ->afterEach(function () {
        Monkey\tearDown();
    })
    ->in('php');

/**
 * Invoke a private/protected method for focused unit testing.
 */
function invokeMethod(object $object, string $method, array $args = []): mixed
{
    // PHP 8.1+ allows invoking non-public methods via reflection without
    // setAccessible(); the theme requires PHP >= 8.4.
    return (new ReflectionMethod($object, $method))->invokeArgs($object, $args);
}
