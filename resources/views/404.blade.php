@extends('layouts.app')

@section('content')
  <h1>{{ __('Not found', config('theme.textdomain')) }}</h1>
  <p>{{ __('The requested page could not be found.', config('theme.textdomain')) }}</p>
@endsection
