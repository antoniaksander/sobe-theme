// Returning null is intentional: this is a dynamic block.
// WordPress never uses the client-side save output — the Blade template
// at resources/views/blocks/product-categories-grid.blade.php renders the frontend HTML
// via the render_callback registered in app/setup.php.
export default function save() {
  return null;
}
