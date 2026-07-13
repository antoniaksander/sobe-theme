<?php

/**
 * Header wishlist count badge (components/wishlist-icon.blade.php).
 *
 * YITH's own `/yith/wishlist/v1/lists/` REST route returns full wishlist
 * objects (name, token, visibility...) rather than a plain item count, and
 * 401s for a guest with no session yet — fine for YITH's own product-card
 * buttons (each already handles that), but not a clean fit for "just tell me
 * a number" on every page load. `yith_wcwl_count_all_products()` is YITH's
 * own public helper for exactly this and returns 0 for a guest with no
 * wishlist activity rather than an error, so a plain admin-ajax endpoint
 * (works for guests via nopriv, unlike most REST routes) is a better fit
 * than the REST API here.
 *
 * This is a client-side fetch (resources/js/app.js resyncWishlistCount), not
 * baked into server-rendered HTML, so it's correct regardless of full-page
 * caching.
 */

namespace App;

add_action('wp_ajax_sobe_wishlist_count', __NAMESPACE__.'\\sobe_wishlist_count_ajax');
add_action('wp_ajax_nopriv_sobe_wishlist_count', __NAMESPACE__.'\\sobe_wishlist_count_ajax');

function sobe_wishlist_count_ajax(): void
{
    $count = function_exists('yith_wcwl_count_all_products') ? (int) yith_wcwl_count_all_products() : 0;

    wp_send_json(['count' => $count]);
}
