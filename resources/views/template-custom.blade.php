{{--
  Template Name: Custom Template
--}}

@extends('layouts.app')

@section('content')
  @while(have_posts()) @php
    the_post();
    $heroPostId = get_the_ID();
    $showHero   = get_post_meta($heroPostId, '_sobe_page_hero', true) && has_post_thumbnail($heroPostId);
    $hideTitle  = (bool) get_post_meta($heroPostId, '_sobe_hide_title', true);
  @endphp
    @if($showHero)
      @include('partials.page-hero')
    @endif
    @if(!$showHero && !$hideTitle)
      @include('partials.page-header')
    @endif
    @include('partials.content-page')
  @endwhile
@endsection
