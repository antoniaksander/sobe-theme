<!doctype html>
<html @php(language_attributes())>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php(wp_head())
    @vite(['resources/css/app.css', 'resources/js/app.js'])
  </head>

  <body @php(body_class())>
    @php(wp_body_open())
    @php(do_action('get_header'))

    <main id="app">
      @yield('content')
    </main>

    @php(do_action('get_footer'))
    @php(wp_footer())
  </body>
</html>
