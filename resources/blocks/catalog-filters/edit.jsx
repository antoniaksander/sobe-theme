const { useBlockProps, InspectorControls } = wp.blockEditor;
const { PanelBody, ToggleControl, TextControl } = wp.components;
const { __ } = wp.i18n;

export default function Edit({ attributes, setAttributes }) {
  const {
    brandsTaxonomy,
    showCategories,
    showBrands,
    showAttributes,
    showPriceRange,
    collapseByDefault,
  } = attributes;

  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Filter Options', 'sobe')}>
          <ToggleControl
            label={__('Show Categories', 'sobe')}
            checked={showCategories}
            onChange={(val) => setAttributes({ showCategories: val })}
          />
          <ToggleControl
            label={__('Show Brands', 'sobe')}
            checked={showBrands}
            onChange={(val) => setAttributes({ showBrands: val })}
          />
          <TextControl
            label={__('Brands Taxonomy', 'sobe')}
            value={brandsTaxonomy}
            onChange={(val) => setAttributes({ brandsTaxonomy: val })}
            help={__('Slug of the brand taxonomy (e.g. product_brand)', 'sobe')}
          />
          <ToggleControl
            label={__('Show Attributes', 'sobe')}
            checked={showAttributes}
            onChange={(val) => setAttributes({ showAttributes: val })}
          />
          <ToggleControl
            label={__('Show Price Range', 'sobe')}
            checked={showPriceRange}
            onChange={(val) => setAttributes({ showPriceRange: val })}
          />
          <ToggleControl
            label={__('Collapse Sections by Default', 'sobe')}
            checked={collapseByDefault}
            onChange={(val) => setAttributes({ collapseByDefault: val })}
          />
        </PanelBody>
      </InspectorControls>

      <div {...useBlockProps({ className: 'sobe-catalog-filters-editor-preview' })}>
        <p style={{ fontSize: '0.875rem', color: '#666', margin: 0 }}>
          {__('Catalog Filters', 'sobe')} —{' '}
          {[
            showCategories && __('Categories', 'sobe'),
            showBrands && __('Brands', 'sobe'),
            showAttributes && __('Attributes', 'sobe'),
            showPriceRange && __('Price', 'sobe'),
          ]
            .filter(Boolean)
            .join(', ')}
        </p>
      </div>
    </>
  );
}
