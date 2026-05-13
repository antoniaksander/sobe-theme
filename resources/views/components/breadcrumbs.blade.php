{{-- Breadcrumbs: Smart wrapper for SEO plugin breadcrumbs --}}
{{-- Falls back to basic trail if no SEO plugin is active --}}
@php
    $hasBreadcrumbs = false;
    $breadcrumbs = '';
    
    // Check for Yoast SEO
    if (function_exists('yoast_breadcrumb') && !is_wp_error(yoast_breadcrumb('', false))) {
        $hasBreadcrumbs = true;
        $breadcrumbs = yoast_breadcrumb('', false);
    }
    // Check for RankMath
    elseif (class_exists('RankMath') && function_exists('rank_math_get_breadcrumbs')) {
        $hasBreadcrumbs = true;
        $breadcrumbs = rank_math_get_breadcrumbs();
    }
@endphp

@if ($hasBreadcrumbs)
    <nav aria-label="{{ __('Breadcrumb', 'sobe') }}" class="breadcrumbs">
        {!! $breadcrumbs !!}
    </nav>
@else
    {{-- Fallback: Basic Home > Page trail --}}
    <nav aria-label="{{ __('Breadcrumb', 'sobe') }}" class="breadcrumbs breadcrumbs--fallback">
        <ol class="breadcrumbs__list">
            <li class="breadcrumbs__item">
                <a href="{{ home_url('/') }}" class="breadcrumbs__link">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v9a2.25 2.25 0 0 0 2.25 2.25h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25H9a2.25 2.25 0 0 0-2.25 2.25v.75m-6-4.5h6m-6 0V6a2.25 2.25 0 0 1 2.25-2.25h3a2.25 2.25 0 0 1 2.25 2.25v.75m-9 0h9" />
                    </svg>
                    <span class="sr-only">{{ __('Home', 'sobe') }}</span>
                </a>
            </li>
            @if (is_singular())
                @php $post = get_post(); @endphp
                <li class="breadcrumbs__separator" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </li>
                <li class="breadcrumbs__item breadcrumbs__item--current">
                    <span aria-current="page">{{ get_the_title() }}</span>
                </li>
            @elseif (is_archive())
                <li class="breadcrumbs__separator" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </li>
                <li class="breadcrumbs__item breadcrumbs__item--current">
                    <span aria-current="page">{{ single_post_title('', false) ?: __('Archive', 'sobe') }}</span>
                </li>
            @elseif (is_search())
                <li class="breadcrumbs__separator" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </li>
                <li class="breadcrumbs__item breadcrumbs__item--current">
                    <span aria-current="page">{!! __('Search: ', 'sobe') !!}{{ get_search_query() }}</span>
                </li>
            @elseif (is_404())
                <li class="breadcrumbs__separator" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </li>
                <li class="breadcrumbs__item breadcrumbs__item--current">
                    <span aria-current="page">{!! __('404', 'sobe') !!}</span>
                </li>
            @elseif (is_page())
                @php $post = get_post(); @endphp
                @if ($post->post_parent)
                    @php $ancestors = get_post_ancestors($post); @endphp
                    <li class="breadcrumbs__separator" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </li>
                    <li class="breadcrumbs__item breadcrumbs__item--current">
                        <span aria-current="page">{{ get_the_title() }}</span>
                    </li>
                @else
                    <li class="breadcrumbs__separator" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </li>
                    <li class="breadcrumbs__item breadcrumbs__item--current">
                        <span aria-current="page">{{ get_the_title() }}</span>
                    </li>
                @endif
            @endif
        </ol>
    </nav>
@endif