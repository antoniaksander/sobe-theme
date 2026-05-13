const { useBlockProps, InspectorControls, RichText } = wp.blockEditor;
const { PanelBody, PanelRow, SelectControl, TextControl, ToggleControl } =
  wp.components;
const { useSelect } = wp.data;
const { __ } = wp.i18n; // <-- ADDED: Needed for translation strings

export default function Edit({ attributes, setAttributes }) {
  const {
    productId,
    layout,
    imageRatio,
    showProductImage,
    showProductTitle,
    showProductPrice,
    showProductBrand,
    customBrandText,
    heading,
    paragraph,
    ctaText,
    ctaUrl,
    ctaType,
  } = attributes;

  const blockProps = useBlockProps({ className: 'sobe-product-feature' });

  // FIXED: Fetch BOTH the list of products (for the dropdown)
  // and the single selected product (for the CTA fallback link).
  const { products, product } = useSelect(
    (select) => {
      const { getEntityRecords, getEntityRecord } = select('core');
      return {
        products: getEntityRecords('postType', 'product', {
          per_page: 100,
          status: 'publish',
        }),
        product: productId
          ? getEntityRecord('postType', 'product', productId)
          : null,
      };
    },
    [productId],
  );

  const previewCtaUrl = ctaUrl || product?.link;
  const selectedProduct = (products ?? []).find((p) => p.id === productId);

  const productOptions = [
    {
      label: products === null ? __('Loading products…', 'sobe') : __('— Select a product —', 'sobe'),
      value: '',
    },
    ...(products ?? []).map((p) => ({
      label: p.title.rendered,
      value: String(p.id),
    })),
  ];

  const isReversed = layout === 'product-right';

  // ── Canvas preview styles ──────────────────────────────────────────────────
  // Inline only — no Tailwind — so editor rendering is immune to theme CSS
  // load order.
  const canvasGrid = {
    display: 'grid',
    gridTemplateColumns: '1fr 1fr',
    gap: '2rem',
    alignItems: 'center',
  };

  const productPreviewStyle = {
    padding: '1.5rem',
    background: '#f5f5f2',
    borderRadius: '8px',
    textAlign: 'center',
  };

  const productPlaceholderStyle = {
    padding: '2.5rem 1.5rem',
    background: '#f0f0f0',
    borderRadius: '8px',
    textAlign: 'center',
    color: '#aaa',
    fontSize: '14px',
    border: '2px dashed #ddd',
  };

  const isLinkCta = ctaType?.startsWith('link-');
  const ctaPreviewStyle = isLinkCta
    ? {
        display: 'inline-flex',
        alignItems: 'center',
        gap: '0.375rem',
        fontWeight: 500,
        fontSize: '14px',
        color: ctaType === 'link-dark' ? '#1a1a2e' : '#f4f3f2',
        textDecoration: 'none',
        padding: 0,
      }
    : {
        display: 'inline-block',
        padding: '0.5rem 1.25rem',
        background: ctaType?.includes('light') ? '#f4f3f2' : '#1a1a2e',
        color: ctaType?.includes('light') ? '#1a1a2e' : '#fff',
        borderRadius: '4px',
        fontSize: '14px',
        fontWeight: 600,
        textDecoration: 'none',
        ...(ctaType?.includes('outline')
          ? { background: 'transparent', border: '2px solid currentColor' }
          : {}),
      };

  const hiddenBadgeStyle = {
    fontSize: '10px',
    color: '#aaa',
    fontStyle: 'italic',
  };

  return (
    <>
      <InspectorControls>
        {/* ── Product ─────────────────────────────────────────────── */}
        <PanelBody title={__('Product', 'sobe')} initialOpen={true}>
          <PanelRow>
            <SelectControl
              label={__('Product', 'sobe')}
              value={productId ? String(productId) : ''}
              options={productOptions}
              onChange={(val) =>
                setAttributes({ productId: parseInt(val, 10) || 0 })
              }
              __next40pxDefaultSize
              __nextHasNoMarginBottom
            />
          </PanelRow>
          <PanelRow>
            <SelectControl
              label={__('Image Aspect Ratio', 'sobe')}
              value={imageRatio}
              options={[
                { label: __('Original', 'sobe'), value: 'original' },
                { label: __('Square (1:1)', 'sobe'), value: 'square' },
                { label: __('Landscape (4:3)', 'sobe'), value: 'landscape' },
              ]}
              onChange={(val) => setAttributes({ imageRatio: val })}
              __next40pxDefaultSize
              __nextHasNoMarginBottom
            />
          </PanelRow>
        </PanelBody>

        {/* ── Product Display Settings ─────────────────────────────── */}
        <PanelBody title={__('Product Display Settings', 'sobe')} initialOpen={false}>
          <PanelRow>
            <ToggleControl
              label={__('Show Product Image', 'sobe')}
              checked={showProductImage}
              onChange={(val) => setAttributes({ showProductImage: val })}
              __nextHasNoMarginBottom
            />
          </PanelRow>
          <PanelRow>
            <ToggleControl
              label={__('Show Product Brand', 'sobe')}
              checked={showProductBrand}
              onChange={(val) => setAttributes({ showProductBrand: val })}
              __nextHasNoMarginBottom
            />
          </PanelRow>
          {showProductBrand && (
            <PanelRow>
              <TextControl
                label={__('Custom Brand Label', 'sobe')}
                help={__("Overrides the product's brand. Leave blank to use the WooCommerce brand.", 'sobe')}
                value={customBrandText}
                onChange={(val) => setAttributes({ customBrandText: val })}
                __next40pxDefaultSize
                __nextHasNoMarginBottom
              />
            </PanelRow>
          )}
          <PanelRow>
            <ToggleControl
              label={__('Show Product Title', 'sobe')}
              checked={showProductTitle}
              onChange={(val) => setAttributes({ showProductTitle: val })}
              __nextHasNoMarginBottom
            />
          </PanelRow>
          <PanelRow>
            <ToggleControl
              label={__('Show Product Price', 'sobe')}
              checked={showProductPrice}
              onChange={(val) => setAttributes({ showProductPrice: val })}
              __nextHasNoMarginBottom
            />
          </PanelRow>
        </PanelBody>

        {/* ── Layout ──────────────────────────────────────────────── */}
        <PanelBody title={__('Layout', 'sobe')} initialOpen={false}>
          <PanelRow>
            <SelectControl
              label={__('Column Order', 'sobe')}
              value={layout}
              options={[
                { label: __('Product left, content right', 'sobe'), value: 'product-left' },
                {
                  label: __('Product right, content left', 'sobe'),
                  value: 'product-right',
                },
              ]}
              onChange={(val) => setAttributes({ layout: val })}
              __next40pxDefaultSize
              __nextHasNoMarginBottom
            />
          </PanelRow>
        </PanelBody>

        {/* ── CTA Link ────────────────────────────────────────────── */}
        <PanelBody title={__('CTA Link', 'sobe')} initialOpen={false}>
          <PanelRow>
            <TextControl
              label={__('Button Text', 'sobe')}
              value={ctaText}
              onChange={(val) => setAttributes({ ctaText: val })}
              __next40pxDefaultSize
              __nextHasNoMarginBottom
            />
          </PanelRow>
          <PanelRow>
            <TextControl
              label={__('CTA URL', 'sobe')}
              help={__('Leave blank to default to the product link.', 'sobe')}
              value={ctaUrl ?? ''}
              onChange={(val) => setAttributes({ ctaUrl: val })}
              type="url"
              placeholder={product?.link ? product.link : 'https://'}
              __nextHasNoMarginBottom
              __next40pxDefaultSize
            />
          </PanelRow>
          <PanelRow>
            <SelectControl
              label={__('Button Style', 'sobe')}
              value={ctaType ?? 'btn-dark'}
              options={[
                { label: __('Button — Dark',          'sobe'), value: 'btn-dark'          },
                { label: __('Button — Light',         'sobe'), value: 'btn-light'         },
                { label: __('Button — Outline Dark',  'sobe'), value: 'btn-outline-dark'  },
                { label: __('Button — Outline Light', 'sobe'), value: 'btn-outline-light' },
                { label: __('Link — Dark',            'sobe'), value: 'link-dark'         },
                { label: __('Link — Light',           'sobe'), value: 'link-light'        },
              ]}
              onChange={(val) => setAttributes({ ctaType: val })}
              __nextHasNoMarginBottom
              __next40pxDefaultSize
            />
          </PanelRow>
        </PanelBody>
      </InspectorControls>

      {/* ── Canvas ──────────────────────────────────────────────────── */}
      <div {...blockProps}>
        <div style={canvasGrid}>
          {/* Product column */}
          <div style={{ order: isReversed ? 2 : 1 }}>
            {productId > 0 ? (
              <div style={productPreviewStyle}>
                <p
                  style={{
                    fontSize: '11px',
                    color: '#888',
                    margin: 0,
                    textTransform: 'uppercase',
                    letterSpacing: '0.05em',
                  }}
                >
                  Product preview
                </p>
                <p
                  style={{
                    fontWeight: 600,
                    margin: '0.5rem 0 0',
                    fontSize: '1rem',
                  }}
                >
                  {products === null
                    ? 'Loading…'
                    : selectedProduct?.title.rendered ?? `ID: ${productId}`}
                </p>
                <p
                  style={{
                    fontSize: '11px',
                    color: '#aaa',
                    margin: '0.25rem 0 0',
                  }}
                >
                  Ratio: {imageRatio}
                </p>
                {(!showProductImage ||
                  !showProductTitle ||
                  !showProductPrice ||
                  !showProductBrand) && (
                  <p style={{ ...hiddenBadgeStyle, marginTop: '0.5rem' }}>
                    Some elements hidden — see "Product Display Settings"
                  </p>
                )}
              </div>
            ) : (
              <div style={productPlaceholderStyle}>
                {isReversed ? '→' : '←'} Select a product in the sidebar
              </div>
            )}
          </div>

          {/* Content column — inline editable */}
          <div
            style={{
              order: isReversed ? 1 : 2,
              display: 'flex',
              flexDirection: 'column',
              gap: '0.75rem',
            }}
          >
            <RichText
              tagName="h2"
              placeholder="Heading…"
              value={heading}
              onChange={(val) => setAttributes({ heading: val })}
              style={{ margin: 0 }}
            />
            <RichText
              tagName="p"
              placeholder="Description…"
              value={paragraph}
              onChange={(val) => setAttributes({ paragraph: val })}
              style={{ margin: 0 }}
            />
            {ctaText && (
              <div>
                <a
                  href={previewCtaUrl}
                  style={ctaPreviewStyle}
                  onClick={(e) => e.preventDefault()}
                >
                  {ctaText}
                </a>
              </div>
            )}
          </div>
        </div>
      </div>
    </>
  );
}
