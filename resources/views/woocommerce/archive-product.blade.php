{{--
The Template for displaying product archives, including the main shop page which is a post type archive

This template can be overridden by copying it to yourtheme/woocommerce/archive-product.php.

HOWEVER, on occasion WooCommerce will need to update template files and you
(the theme developer) will need to copy the new files to your theme to
maintain compatibility. We try to do this as little as possible, but it does
happen. When this occurs the version of the template file will be bumped and
the readme will list any important changes.

@see https://docs.woocommerce.com/document/template-structure/
@package WooCommerce/Templates
@version 3.4.0
--}}

@extends('layouts.app')

@section('content')
  @php
    $showSidebar = get_theme_mod(config('theme.prefix') . '_shop_sidebar_enabled', true);
    do_action('get_header', 'shop');
    do_action('woocommerce_before_main_content');
  @endphp

  {{-- Main Layout Wrapper --}}
  <div class="max-w-[var(--layout-grid)] mx-auto px-4 {{ $showSidebar ? 'shop-with-sidebar flex flex-col lg:flex-row gap-8' : '' }}">

    {{-- Conditionally Render Sidebar --}}
    @if ($showSidebar)
      <aside class="shop-sidebar w-full lg:w-64 xl:w-72 flex-shrink-0 order-2 lg:order-1">
        @include('sections.sidebar-shop')
      </aside>
    @endif

    {{-- Main Content Wrapper --}}
    <div class="shop-main {{ $showSidebar ? 'flex-1 min-w-0 order-1 lg:order-2' : 'w-full' }}">

      <header class="woocommerce-products-header">
        @if (apply_filters('woocommerce_show_page_title', true))
          <h1 class="woocommerce-products-header__title page-title">{!! woocommerce_page_title(false) !!}</h1>
        @endif

        @php
          do_action('woocommerce_archive_description')
        @endphp
      </header>

      @if (woocommerce_product_loop())
        {{-- Flex toolbar keeps result-count + ordering side-by-side without floats --}}
        <div class="flex items-center justify-between mb-6">
          @php do_action('woocommerce_before_shop_loop'); @endphp
        </div>

        @php woocommerce_product_loop_start(); @endphp

        @if (wc_get_loop_prop('total'))
          @while (have_posts())
            @php
              the_post();
              do_action('woocommerce_shop_loop');
              wc_get_template_part('content', 'product');
            @endphp
          @endwhile
        @endif

        @php woocommerce_product_loop_end(); @endphp
        <div data-pagination>
          @php do_action('woocommerce_after_shop_loop'); @endphp
        </div>
      @else
        @php
          do_action('woocommerce_no_products_found')
        @endphp
      @endif

    </div> {{-- Close shop-main --}}
  </div> {{-- Close layout wrapper --}}

  @php
    do_action('woocommerce_after_main_content');
    do_action('get_footer', 'shop');
  @endphp
@endsection
