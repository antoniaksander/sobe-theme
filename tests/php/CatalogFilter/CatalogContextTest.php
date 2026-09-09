<?php

/**
 * Unit tests for the immutable base-context value object every filtered
 * request (direct GET, AJAX, load-more) must agree on before any mutable
 * filter state is applied.
 */

use App\WooCommerce\CatalogFilter\CatalogContext;
use Brain\Monkey\Functions;

beforeEach(function () {
    Functions\when('sanitize_key')->alias(fn ($v) => strtolower((string) $v));
    Functions\when('sanitize_title')->alias(fn ($v) => strtolower(trim((string) $v)));
    Functions\when('sanitize_text_field')->alias(fn ($v) => trim((string) $v));
});

it('builds a shop context from a plain shop query', function () {
    Functions\when('is_search')->justReturn(false);
    Functions\when('is_product_taxonomy')->justReturn(false);

    $context = CatalogContext::fromCurrentQuery();

    expect($context->type)->toBe(CatalogContext::TYPE_SHOP);
});

it('builds a taxonomy context from the queried WP_Term object, not from $_GET', function () {
    Functions\when('is_search')->justReturn(false);
    Functions\when('is_product_taxonomy')->justReturn(true);

    $term = Mockery::mock(WP_Term::class);
    $term->taxonomy = 'product_brand';
    $term->slug = 'samelin';
    $term->term_id = 693;
    Functions\when('get_queried_object')->justReturn($term);

    $context = CatalogContext::fromCurrentQuery();

    expect($context->type)->toBe(CatalogContext::TYPE_TAXONOMY);
    expect($context->taxonomy)->toBe('product_brand');
    expect($context->termSlug)->toBe('samelin');
    expect($context->termId)->toBe(693);
});

it('builds a search context from the current search query', function () {
    Functions\when('is_search')->justReturn(true);
    Functions\when('get_search_query')->justReturn('boots');

    $context = CatalogContext::fromCurrentQuery();

    expect($context->type)->toBe(CatalogContext::TYPE_SEARCH);
    expect($context->search)->toBe('boots');
});

// ── fromArray() — the AJAX/load-more path ───────────────────────────────────
//
// A claimed taxonomy context is validated against the real term (taxonomy
// exists, term exists in it, and a posted term ID must match what the slug
// actually resolves to) — see taxonomyFromArray(). Failure must produce
// TYPE_INVALID, never TYPE_SHOP: falling back to shop would let a malformed
// or forged context WIDEN a request that was supposed to be scoped to one
// archive term into the whole catalog.

function mockRealTerm(string $taxonomy, string $slug, int $termId): void
{
    $term = Mockery::mock(WP_Term::class);
    $term->term_id = $termId;
    $term->slug = $slug;
    $term->taxonomy = $taxonomy;
    Functions\when('get_term_by')->justReturn($term);
}

it('rebuilds a taxonomy context from a posted filter_context payload that matches a real term', function () {
    Functions\when('taxonomy_exists')->justReturn(true);
    mockRealTerm('product_brand', 'samelin', 693);

    $context = CatalogContext::fromArray([
        'contextType' => 'taxonomy',
        'archiveTaxonomy' => 'product_brand',
        'archiveTerm' => 'samelin',
        'queriedObjectId' => 693,
    ]);

    expect($context->type)->toBe(CatalogContext::TYPE_TAXONOMY);
    expect($context->taxonomy)->toBe('product_brand');
    expect($context->termSlug)->toBe('samelin');
    expect($context->termId)->toBe(693);
});

it('is valid even without a posted term ID, as long as the slug resolves to a real term', function () {
    Functions\when('taxonomy_exists')->justReturn(true);
    mockRealTerm('product_brand', 'samelin', 693);

    $context = CatalogContext::fromArray([
        'contextType' => 'taxonomy',
        'archiveTaxonomy' => 'product_brand',
        'archiveTerm' => 'samelin',
    ]);

    expect($context->type)->toBe(CatalogContext::TYPE_TAXONOMY);
    expect($context->termId)->toBe(693);
});

it('fails CLOSED (invalid, not shop) when a claimed taxonomy does not exist', function () {
    Functions\when('taxonomy_exists')->justReturn(false);

    $context = CatalogContext::fromArray([
        'contextType' => 'taxonomy',
        'archiveTaxonomy' => 'not_a_real_taxonomy',
        'archiveTerm' => 'x',
    ]);

    expect($context->type)->toBe(CatalogContext::TYPE_INVALID);
    expect($context->isInvalid())->toBeTrue();
    expect($context->isTaxonomy())->toBeFalse();
});

it('fails CLOSED when the taxonomy exists but no term with that slug does', function () {
    Functions\when('taxonomy_exists')->justReturn(true);
    Functions\when('get_term_by')->justReturn(false);

    $context = CatalogContext::fromArray([
        'contextType' => 'taxonomy',
        'archiveTaxonomy' => 'product_brand',
        'archiveTerm' => 'not-a-real-brand',
    ]);

    expect($context->type)->toBe(CatalogContext::TYPE_INVALID);
});

it('fails CLOSED when the posted term ID does not match what the slug actually resolves to', function () {
    Functions\when('taxonomy_exists')->justReturn(true);
    // Slug "samelin" really resolves to term_id 693, but the payload claims 1.
    mockRealTerm('product_brand', 'samelin', 693);

    $context = CatalogContext::fromArray([
        'contextType' => 'taxonomy',
        'archiveTaxonomy' => 'product_brand',
        'archiveTerm' => 'samelin',
        'queriedObjectId' => 1,
    ]);

    expect($context->type)->toBe(CatalogContext::TYPE_INVALID);
});

it('never widens: an invalid taxonomy claim must not equal a legitimate shop context', function () {
    Functions\when('taxonomy_exists')->justReturn(false);

    $invalid = CatalogContext::fromArray(['contextType' => 'taxonomy', 'archiveTaxonomy' => 'x', 'archiveTerm' => 'y']);
    $shop = CatalogContext::shop();

    expect($invalid->type)->not->toBe($shop->type);
});

it('treats an absent/non-taxonomy contextType as a legitimate shop request, not invalid', function () {
    expect(CatalogContext::fromArray([])->type)->toBe(CatalogContext::TYPE_SHOP);
    expect(CatalogContext::fromArray(['contextType' => 'bogus'])->type)->toBe(CatalogContext::TYPE_SHOP);
});

it('rebuilds a search context from a posted payload', function () {
    $context = CatalogContext::fromArray(['contextType' => 'search', 'search' => 'boots']);

    expect($context->type)->toBe(CatalogContext::TYPE_SEARCH);
    expect($context->search)->toBe('boots');
});

it('round-trips toArray() back into an equivalent context via fromArray()', function () {
    Functions\when('taxonomy_exists')->justReturn(true);
    mockRealTerm('product_brand', 'samelin', 693);

    $original = CatalogContext::taxonomy('product_brand', 'samelin', 693);
    $rebuilt = CatalogContext::fromArray($original->toArray());

    expect($rebuilt->type)->toBe($original->type);
    expect($rebuilt->taxonomy)->toBe($original->taxonomy);
    expect($rebuilt->termSlug)->toBe($original->termSlug);
    expect($rebuilt->termId)->toBe($original->termId);
});
