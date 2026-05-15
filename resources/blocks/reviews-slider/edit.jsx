// All @wordpress/* accessed via wp.* globals — never import from '@wordpress/…'
const { useBlockProps, InspectorControls, MediaUpload, MediaUploadCheck } = wp.blockEditor;
const {
  PanelBody,
  PanelRow,
  SelectControl,
  RangeControl,
  TextControl,
  Button,
  CheckboxControl,
  Spinner,
} = wp.components;
const { __ } = wp.i18n;
const { useState, useEffect } = wp.element;

import './editor.scss';

const STARS = [1, 2, 3, 4, 5];

function StarRating({ value, onChange }) {
  const [hover, setHover] = useState(0);
  return (
    <div style={{ display: 'flex', gap: '3px', cursor: 'pointer', marginBottom: '6px' }}>
      {STARS.map((n) => (
        <svg
          key={n}
          width="16" height="16" viewBox="0 0 24 24"
          fill={(hover || value) >= n ? '#eac612' : 'none'}
          stroke={(hover || value) >= n ? '#eac612' : '#cbd5e1'}
          strokeWidth="1.5"
          style={{ cursor: 'pointer', flexShrink: 0 }}
          onMouseEnter={() => setHover(n)}
          onMouseLeave={() => setHover(0)}
          onClick={() => onChange(n)}
        >
          <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
        </svg>
      ))}
    </div>
  );
}

export default function Edit({ attributes, setAttributes }) {
  const {
    dataMode,
    productIds,
    wcReviewCount,
    autoplayDelay,
    heading,
    paragraph,
    headerAlignment,
    reviews,
  } = attributes;

  const [products, setProducts] = useState(null); // null = loading
  const [previewIndex, setPreviewIndex] = useState(0);

  // Fetch products for the "pick products" mode.
  useEffect(() => {
    if (dataMode === 'manual') return;
    if (products !== null) return;
    wp.apiFetch({ path: '/wp/v2/product?per_page=100&status=publish&orderby=title&order=asc' })
      .then((res) => setProducts(res))
      .catch(() => setProducts([]));
  }, [dataMode]);

  const blockProps = useBlockProps({ className: 'sobe-reviews-slider-editor' });

  // ── Manual review helpers ───────────────────────────────────────────────
  function addReview() {
    const next = [
      ...reviews,
      { id: Date.now().toString(), rating: 5, text: '', author: '', productTitle: '', productUrl: '', imageId: 0, imageUrl: '', imageAlt: '' },
    ];
    setAttributes({ reviews: next });
    setPreviewIndex(next.length - 1);
  }

  function removeReview(i) {
    setAttributes({ reviews: reviews.filter((_, idx) => idx !== i) });
    setPreviewIndex((p) => Math.max(0, Math.min(p, reviews.length - 2)));
  }

  function updateReview(i, patch) {
    setAttributes({ reviews: reviews.map((r, idx) => (idx === i ? { ...r, ...patch } : r)) });
  }

  function toggleProduct(id) {
    const next = productIds.includes(id)
      ? productIds.filter((p) => p !== id)
      : [...productIds, id];
    setAttributes({ productIds: next });
  }

  const delayOptions = [
    { label: '3 s', value: 3000 },
    { label: '4 s', value: 4000 },
    { label: '5 s — Default', value: 5000 },
    { label: '7 s', value: 7000 },
    { label: '10 s', value: 10000 },
    { label: '15 s', value: 15000 },
  ];

  const previewReviews = dataMode === 'manual' ? reviews : [];
  const current = previewReviews[previewIndex] ?? null;
  const total = previewReviews.length;

  return (
    <>
      <InspectorControls>

        {/* ── Data Source ──────────────────────────────────────────────── */}
        <PanelBody title={__('Reviews Source', 'sobe')} initialOpen={true}>
          <PanelRow>
            <SelectControl
              label={__('Mode', 'sobe')}
              value={dataMode}
              options={[
                { label: __('Auto — latest WooCommerce reviews', 'sobe'), value: 'auto' },
                { label: __('Pick products', 'sobe'),                     value: 'products' },
                { label: __('Manual entries',  'sobe'),                   value: 'manual' },
              ]}
              onChange={(val) => setAttributes({ dataMode: val })}
              __nextHasNoMarginBottom
              __next40pxDefaultSize
            />
          </PanelRow>

          {/* Auto mode: just a count control */}
          {dataMode === 'auto' && (
            <PanelRow>
              <RangeControl
                label={__('Number of reviews', 'sobe')}
                value={wcReviewCount}
                onChange={(val) => setAttributes({ wcReviewCount: Math.max(1, Math.min(val ?? 8, 20)) })}
                min={1}
                max={20}
                __nextHasNoMarginBottom
              />
            </PanelRow>
          )}

          {/* Products mode: checkbox list */}
          {dataMode === 'products' && (
            <>
              <PanelRow>
                <RangeControl
                  label={__('Reviews per product', 'sobe')}
                  value={wcReviewCount}
                  onChange={(val) => setAttributes({ wcReviewCount: Math.max(1, Math.min(val ?? 4, 10)) })}
                  min={1}
                  max={10}
                  __nextHasNoMarginBottom
                />
              </PanelRow>
              <div style={{ marginTop: '12px' }}>
                <p style={{ fontSize: '11px', color: '#757575', margin: '0 0 8px', textTransform: 'uppercase', letterSpacing: '0.04em' }}>
                  {__('Products', 'sobe')}
                </p>
                {products === null ? (
                  <div style={{ display: 'flex', alignItems: 'center', gap: '8px', color: '#757575', fontSize: '12px' }}>
                    <Spinner /> {__('Loading products…', 'sobe')}
                  </div>
                ) : products.length === 0 ? (
                  <p style={{ fontSize: '12px', color: '#999', margin: 0 }}>
                    {__('No published products found.', 'sobe')}
                  </p>
                ) : (
                  <div style={{ maxHeight: '260px', overflowY: 'auto', border: '1px solid #e0e0e0', borderRadius: '4px', padding: '6px 8px' }}>
                    {products.map((product) => (
                      <CheckboxControl
                        key={product.id}
                        label={product.title?.rendered ?? product.title ?? `#${product.id}`}
                        checked={productIds.includes(product.id)}
                        onChange={() => toggleProduct(product.id)}
                        __nextHasNoMarginBottom
                      />
                    ))}
                  </div>
                )}
              </div>
            </>
          )}
        </PanelBody>

        {/* ── Autoplay ────────────────────────────────────────────────── */}
        <PanelBody title={__('Autoplay', 'sobe')} initialOpen={false}>
          <PanelRow>
            <SelectControl
              label={__('Slide interval', 'sobe')}
              value={autoplayDelay}
              options={delayOptions}
              onChange={(val) => setAttributes({ autoplayDelay: parseInt(val, 10) })}
              __nextHasNoMarginBottom
              __next40pxDefaultSize
            />
          </PanelRow>
        </PanelBody>

        {/* ── Section header ───────────────────────────────────────────── */}
        <PanelBody title={__('Section Header', 'sobe')} initialOpen={false}>
          <SelectControl
            label={__('Header alignment', 'sobe')}
            value={headerAlignment}
            options={[
              { label: __('Left', 'sobe'), value: 'left' },
              { label: __('Center', 'sobe'), value: 'center' },
              { label: __('Right', 'sobe'), value: 'right' },
            ]}
            onChange={(val) => setAttributes({ headerAlignment: val })}
            __nextHasNoMarginBottom
            __next40pxDefaultSize
          />
          <div style={{ marginTop: '8px' }}>
          <TextControl
            label={__('Heading', 'sobe')}
            value={heading}
            onChange={(val) => setAttributes({ heading: val })}
            placeholder={__('What Our Customers Say', 'sobe')}
            __nextHasNoMarginBottom
            __next40pxDefaultSize
          />
          </div>
          <div style={{ marginTop: '8px' }}>
            <TextControl
              label={__('Sub-text', 'sobe')}
              value={paragraph}
              onChange={(val) => setAttributes({ paragraph: val })}
              placeholder={__('Real reviews from real people.', 'sobe')}
              __nextHasNoMarginBottom
              __next40pxDefaultSize
            />
          </div>
        </PanelBody>

        {/* ── Manual reviews list ──────────────────────────────────────── */}
        {dataMode === 'manual' && (
          <PanelBody title={__('Reviews', 'sobe')} initialOpen={true}>
            {reviews.map((review, i) => (
              <div
                key={review.id || i}
                style={{ border: '1px solid #e0e0e0', borderRadius: '4px', padding: '10px', marginBottom: '8px' }}
              >
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '6px' }}>
                  <strong style={{ fontSize: '11px', color: '#555' }}>
                    {review.author || `Review ${i + 1}`}
                  </strong>
                  <Button variant="link" isDestructive onClick={() => removeReview(i)} style={{ padding: 0, minHeight: 0, fontSize: '11px' }}>
                    {__('Remove', 'sobe')}
                  </Button>
                </div>

                <p style={{ fontSize: '10px', color: '#757575', margin: '0 0 3px', textTransform: 'uppercase', letterSpacing: '0.04em' }}>
                  {__('Rating', 'sobe')}
                </p>
                <StarRating value={review.rating ?? 5} onChange={(val) => updateReview(i, { rating: val })} />

                <TextControl
                  label={__('Reviewer name', 'sobe')}
                  value={review.author}
                  onChange={(val) => updateReview(i, { author: val })}
                  placeholder="Jane D."
                  __nextHasNoMarginBottom
                  __next40pxDefaultSize
                />
                <div style={{ marginTop: '6px' }}>
                  <TextControl
                    label={__('Review text', 'sobe')}
                    value={review.text}
                    onChange={(val) => updateReview(i, { text: val })}
                    placeholder={__('Write the review…', 'sobe')}
                    __nextHasNoMarginBottom
                    __next40pxDefaultSize
                  />
                </div>

                <div style={{ marginTop: '8px', borderTop: '1px solid #f0f0f0', paddingTop: '8px' }}>
                  <p style={{ fontSize: '10px', color: '#757575', margin: '0 0 5px', textTransform: 'uppercase', letterSpacing: '0.04em' }}>
                    {__('Product (right column)', 'sobe')}
                  </p>

                  <MediaUploadCheck>
                    <MediaUpload
                      onSelect={(media) => updateReview(i, { imageId: media.id, imageUrl: media.url, imageAlt: media.alt || '' })}
                      allowedTypes={['image']}
                      value={review.imageId}
                      render={({ open }) =>
                        review.imageUrl ? (
                          <div style={{ marginBottom: '6px' }}>
                            <img src={review.imageUrl} alt="" style={{ maxHeight: '60px', display: 'block', marginBottom: '4px', objectFit: 'cover', borderRadius: '3px' }} />
                            <Button variant="link" onClick={open} style={{ fontSize: '11px', padding: 0, minHeight: 0 }}>
                              {__('Change image', 'sobe')}
                            </Button>
                            {' · '}
                            <Button variant="link" isDestructive onClick={() => updateReview(i, { imageId: 0, imageUrl: '', imageAlt: '' })} style={{ fontSize: '11px', padding: 0, minHeight: 0 }}>
                              {__('Remove', 'sobe')}
                            </Button>
                          </div>
                        ) : (
                          <Button variant="secondary" onClick={open} style={{ width: '100%', marginBottom: '6px', fontSize: '11px' }}>
                            {__('Upload product image', 'sobe')}
                          </Button>
                        )
                      }
                    />
                  </MediaUploadCheck>

                  <TextControl
                    label={__('Product name', 'sobe')}
                    value={review.productTitle}
                    onChange={(val) => updateReview(i, { productTitle: val })}
                    placeholder={__('e.g. Merino Wool Scarf', 'sobe')}
                    __nextHasNoMarginBottom
                    __next40pxDefaultSize
                  />
                  <div style={{ marginTop: '6px' }}>
                    <TextControl
                      label={__('Product URL', 'sobe')}
                      value={review.productUrl}
                      onChange={(val) => updateReview(i, { productUrl: val })}
                      type="url"
                      placeholder="https://"
                      __nextHasNoMarginBottom
                      __next40pxDefaultSize
                    />
                  </div>
                </div>
              </div>
            ))}
            <Button variant="secondary" onClick={addReview} style={{ width: '100%', marginTop: '4px' }}>
              {__('+ Add Review', 'sobe')}
            </Button>
          </PanelBody>
        )}

      </InspectorControls>

      {/* ── Editor canvas ─────────────────────────────────────────────────── */}
      <div {...blockProps}>

        {(heading || paragraph) && (
          <div style={{ marginBottom: '24px', textAlign: headerAlignment }}>
            {heading && <p style={{ margin: '0 0 4px', fontWeight: 700, fontSize: '18px', color: '#0f0f1c' }}>{heading}</p>}
            {paragraph && <p style={{ margin: 0, fontSize: '13px', color: '#6b6b82' }}>{paragraph}</p>}
          </div>
        )}

        {/* WooCommerce modes — informational placeholder */}
        {(dataMode === 'auto' || dataMode === 'products') && (
          <div style={{ border: '1px solid #e2e2dc', borderRadius: '8px', padding: '32px', textAlign: 'center', color: '#6b6b82', fontSize: '13px' }}>
            <div style={{ fontSize: '28px', marginBottom: '8px' }}>⭐</div>
            {dataMode === 'auto' && (
              <>
                <strong style={{ display: 'block', color: '#0f0f1c', marginBottom: '4px' }}>
                  {__('Auto mode — latest WooCommerce reviews', 'sobe')}
                </strong>
                <span>{__('Shows the ', 'sobe')}{wcReviewCount}{__(' most recent approved reviews across all products.', 'sobe')}</span>
              </>
            )}
            {dataMode === 'products' && (
              <>
                <strong style={{ display: 'block', color: '#0f0f1c', marginBottom: '4px' }}>
                  {__('Product reviews mode', 'sobe')}
                </strong>
                {productIds.length === 0
                  ? <span>{__('Select products in the sidebar to show their reviews.', 'sobe')}</span>
                  : <span>{productIds.length}{__(' product(s) selected · up to ', 'sobe')}{wcReviewCount}{__(' reviews each.', 'sobe')}</span>
                }
              </>
            )}
          </div>
        )}

        {/* Manual mode — interactive preview */}
        {dataMode === 'manual' && total === 0 && (
          <div style={{ border: '2px dashed #e2e2dc', borderRadius: '8px', padding: '32px', textAlign: 'center', color: '#6b6b82', fontSize: '13px' }}>
            <div style={{ fontSize: '28px', marginBottom: '8px' }}>💬</div>
            <strong style={{ display: 'block', color: '#0f0f1c', marginBottom: '4px' }}>
              {__('No reviews yet', 'sobe')}
            </strong>
            {__('Add reviews using the sidebar panel.', 'sobe')}
          </div>
        )}

        {dataMode === 'manual' && total > 0 && (
          <>
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', overflow: 'hidden', minHeight: '540px' }}>

              {/* Left — review text */}
              <div style={{ padding: '40px 36px 32px 0', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                <div style={{ width: '100%', maxWidth: '30rem' }}>
                  <div style={{ display: 'flex', gap: '4px', marginBottom: '20px' }}>
                    {STARS.map((n) => (
                      <svg key={n} width="18" height="18" viewBox="0 0 24 24"
                        fill={n <= (current?.rating ?? 5) ? 'var(--c-stars)' : 'none'}
                        stroke={n <= (current?.rating ?? 5) ? 'var(--c-stars)' : 'var(--c-text-subtle)'}
                        strokeWidth="1.5">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                      </svg>
                    ))}
                  </div>
                  <p style={{ fontSize: 'var(--text-xl)', lineHeight: '1.65', fontStyle: 'italic', color: 'var(--c-text)', margin: '0 0 24px', letterSpacing: '-0.01em' }}>
                    "{current?.text || __('Add review text in the sidebar…', 'sobe')}"
                  </p>
                  <p style={{ margin: '0 0 20px', fontSize: 'var(--text-xs)', fontWeight: 600, color: 'var(--c-text-muted)', textTransform: 'uppercase', letterSpacing: '0.08em' }}>
                    — {current?.author || __('Reviewer name', 'sobe')}
                  </p>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                    <button type="button" onClick={(e) => { e.preventDefault(); setPreviewIndex((p) => (p - 1 + total) % total); }}
                      style={{ width: '40px', height: '40px', borderRadius: '50%', border: '1px solid var(--c-border)', background: 'transparent', color: 'var(--c-text)', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                    </button>
                    <button type="button" onClick={(e) => { e.preventDefault(); setPreviewIndex((p) => (p + 1) % total); }}
                      style={{ width: '40px', height: '40px', borderRadius: '50%', border: '1px solid var(--c-border)', background: 'transparent', color: 'var(--c-text)', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                    </button>
                  </div>
                </div>
              </div>

              {/* Right — product image */}
              <a
                href={current?.productUrl || '#'}
                onClick={(e) => e.preventDefault()}
                style={{ position: 'relative', background: 'var(--c-surface-2)', minHeight: '540px', display: 'flex', alignItems: 'center', justifyContent: 'center', flexDirection: 'column', color: 'inherit', textDecoration: 'none' }}
              >
                {current?.imageUrl ? (
                  <img src={current.imageUrl} alt="" style={{ width: '100%', height: '100%', objectFit: 'cover', display: 'block', position: 'absolute', inset: 0 }} />
                ) : (
                  <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', gap: '8px', color: 'var(--c-text-subtle)' }}>
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.2">
                      <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/>
                    </svg>
                    <span style={{ fontSize: '11px' }}>{__('Product image', 'sobe')}</span>
                  </div>
                )}
                {(current?.productTitle || current?.productUrl) && (
                  <div style={{ position: 'absolute', bottom: 0, left: 0, right: 0, background: 'linear-gradient(to top, var(--c-overlay) 0%, transparent 100%)', padding: '16px', color: 'var(--c-overlay-light-full)' }}>
                    {current.productTitle && <p style={{ margin: '0 0 4px', fontSize: 'var(--text-sm)', fontWeight: 600 }}>{current.productTitle}</p>}
                    {current.productUrl && <span style={{ fontSize: 'var(--text-xs)', color: 'var(--c-overlay-light-high)' }}>{__('Shop now →', 'sobe')}</span>}
                  </div>
                )}
              </a>
            </div>

            {total > 1 && (
              <div style={{ display: 'flex', justifyContent: 'center', gap: '6px', marginTop: '16px' }}>
                {previewReviews.map((_, i) => (
                  <button key={i} type="button" onClick={(e) => { e.preventDefault(); setPreviewIndex(i); }}
                    style={{ width: i === previewIndex ? '20px' : '6px', height: '6px', borderRadius: '3px', background: i === previewIndex ? 'var(--c-text)' : 'var(--c-border)', border: 'none', cursor: 'pointer', padding: 0 }}
                  />
                ))}
              </div>
            )}
          </>
        )}
      </div>
    </>
  );
}
