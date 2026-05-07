<?php

/**
 * Homepage Showcase Pattern
 *
 * High-end agency homepage with hero, brand carousel, and product features.
 * Follows Five Laws: semantic markup, Tailwind utilities, no div soup.
 */

return <<<'PATTERN'
<!-- wp:sobe/hero {"heading":"Crafting Digital Excellence","paragraph":"We build high-performance WordPress experiences with modern tools and thoughtful design.","ctaText":"View Our Work","ctaUrl":"#","alignment":"center","height":"100vh","align":"full","enableWebgl":false,"headingSize":"xl","ctaType":"btn-light","headingColor":"fg","paragraphColor":"fg-muted"} /-->

<!-- wp:sobe/brand-carousel {"align":"wide","speed":"30s","pauseOnHover":true} /-->

<!-- wp:sobe/product-feature {"heading":"Precision Engineering","paragraph":"Every pixel, every interaction, every millisecond optimized for your users.","ctaText":"Explore","ctaUrl":"#","layout":"product-left","showProductImage":true,"showProductTitle":true,"showProductPrice":true,"showProductBrand":true,"imageRatio":"landscape"} /-->

<!-- wp:sobe/product-feature {"heading":"Fluid Commerce","paragraph":"WooCommerce that feels native. Fast, accessible, and beautifully integrated.","ctaText":"Shop Now","ctaUrl":"#","layout":"product-right","showProductImage":true,"showProductTitle":true,"showProductPrice":true,"showProductBrand":true,"imageRatio":"landscape"} /-->

<!-- wp:group {"className":"py-16 md:py-24 bg-surface-1","layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group py-16 md:py-24 bg-surface-1">
<!-- wp:heading {"level":2,"align":"center","className":"font-heading text-3xl md:text-4xl mb-12 text-heading"} -->
<h2 class="has-text-align-center font-heading text-3xl md:text-4xl mb-12 text-heading">Why Choose Us</h2>
<!-- /wp:heading -->

<!-- wp:columns {"align":"wide","className":"gap-8"} -->
<div class="wp-block-columns alignwide gap-8">
<!-- wp:column {"className":"p-8 bg-surface-2 rounded-lg"} -->
<div class="wp-block-column p-8 bg-surface-2 rounded-lg">
<!-- wp:heading {"level":3,"className":"font-heading text-xl mb-4 text-heading"} -->
<h3 class="font-heading text-xl mb-4 text-heading">Native Architecture</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"text-text-muted"} -->
<p class="text-text-muted">Built on WordPress core APIs, not around them. No bloat, no workarounds.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->

<!-- wp:column {"className":"p-8 bg-surface-2 rounded-lg"} -->
<div class="wp-block-column p-8 bg-surface-2 rounded-lg">
<!-- wp:heading {"level":3,"className":"font-heading text-xl mb-4 text-heading"} -->
<h3 class="font-heading text-xl mb-4 text-heading">Hybrid Power</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"text-text-muted"} -->
<p class="text-text-muted">React in the editor, Blade on the frontend. Best of both worlds.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->

<!-- wp:column {"className":"p-8 bg-surface-2 rounded-lg"} -->
<div class="wp-block-column p-8 bg-surface-2 rounded-lg">
<!-- wp:heading {"level":3,"className":"font-heading text-xl mb-4 text-heading"} -->
<h3 class="font-heading text-xl mb-4 text-heading">Token System</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"text-text-muted"} -->
<p class="text-text-muted">Design tokens cascade through CSS to Tailwind utilities automatically.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"py-20 md:py-32 text-center","layout":{"type":"constrained","contentSize":"800px"}} -->
<div class="wp-block-group py-20 md:py-32 text-center">
<!-- wp:heading {"level":2,"className":"font-heading text-4xl md:text-5xl mb-6 text-heading"} -->
<h2 class="font-heading text-4xl md:text-5xl mb-6 text-heading">Ready to Build Something Great?</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"text-lg mb-8 text-text-muted"} -->
<p class="text-lg mb-8 text-text-muted">Let's turn your vision into a high-performance digital experience.</p>
<!-- /wp:paragraph -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons">
<!-- wp:button {"className":"btn-dark","backgroundColor":"","textColor":""} -->
<div class="wp-block-button btn-dark"><a class="wp-block-button__link" href="#">Start Your Project</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
PATTERN;
