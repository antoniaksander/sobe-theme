// All @wordpress/* accessed via wp.* globals — never import from '@wordpress/…'
const { useBlockProps, InspectorControls } = wp.blockEditor;
const {
  PanelBody,
  PanelRow,
  RangeControl,
  SelectControl,
  TextControl,
} = wp.components;
const { __ } = wp.i18n;
const { useState, useEffect } = wp.element;

export default function Edit({ attributes, setAttributes }) {
  const {
    count,
    orderBy,
    categoryId,
    brandId,
    heading,
    paragraph,
    linkText,
    linkUrl,
    linkType,
  } = attributes;

  const [categories, setCategories] = useState([]);
  const [brands, setBrands] = useState([]);

  useEffect(() => {
    wp.apiFetch({ path: '/wp/v2/product_cat?per_page=100&orderby=name&order=asc' })
      .then((terms) => setCategories(terms))
      .catch(() => {});
    wp.apiFetch({ path: '/wp/v2/product_brand?per_page=100&orderby=name&order=asc' })
      .then((terms) => setBrands(terms))
      .catch(() => {});
  }, []);

  const blockProps = useBlockProps({
    className: 'sobe-product-carousel-editor',
  });

  const categoryOptions = [
    { label: __('All Categories', 'sobe'), value: 0 },
    ...categories.map((cat) => ({ label: cat.name, value: cat.id })),
  ];

  const brandOptions = [
    { label: __('All Brands', 'sobe'), value: 0 },
    ...brands.map((b) => ({ label: b.name, value: b.id })),
  ];

  const orderByOptions = [
    { label: __('Latest',       'sobe'), value: 'latest'       },
    { label: __('Featured',     'sobe'), value: 'featured'     },
    { label: __('Best Selling', 'sobe'), value: 'best_selling' },
    { label: __('Top Rated',    'sobe'), value: 'top_rated'    },
    { label: __('On Sale',      'sobe'), value: 'on_sale'      },
    { label: __('Random',       'sobe'), value: 'random'       },
  ];

  const linkTypeOptions = [
    { label: __('Button — Dark',          'sobe'), value: 'btn-dark'          },
    { label: __('Button — Light',         'sobe'), value: 'btn-light'         },
    { label: __('Button — Outline Dark',  'sobe'), value: 'btn-outline-dark'  },
    { label: __('Button — Outline Light', 'sobe'), value: 'btn-outline-light' },
    { label: __('Link — Dark',            'sobe'), value: 'link-dark'         },
    { label: __('Link — Light',           'sobe'), value: 'link-light'        },
  ];

  const orderByLabel = orderByOptions.find((o) => o.value === orderBy)?.label ?? 'Latest';
  const catLabel =
    categoryId === 0
      ? __('all categories', 'sobe')
      : categories.find((c) => c.id === categoryId)?.name ?? `category ${categoryId}`;
  const brandLabel =
    brandId === 0
      ? null
      : brands.find((b) => b.id === brandId)?.name ?? `brand ${brandId}`;

  const isLinkType = linkType?.startsWith('link-');

  return (
    <>
      <InspectorControls>
        {/* ── Product Source ─────────────────────────────────────────────── */}
        <PanelBody title={__('Product Source', 'sobe')} initialOpen={true}>
          <PanelRow>
            <SelectControl
              label={__('Order By', 'sobe')}
              value={orderBy}
              options={orderByOptions}
              onChange={(val) => setAttributes({ orderBy: val })}
              __nextHasNoMarginBottom
              __next40pxDefaultSize
            />
          </PanelRow>
          <PanelRow>
            <SelectControl
              label={__('Category', 'sobe')}
              value={categoryId}
              options={categoryOptions}
              onChange={(val) => {
                const id = parseInt(val, 10);
                const updates = { categoryId: id };
                if (id > 0) {
                  const term = categories.find((c) => c.id === id);
                  if (term?.link) updates.linkUrl = term.link;
                }
                setAttributes(updates);
              }}
              __nextHasNoMarginBottom
              __next40pxDefaultSize
            />
          </PanelRow>
          <PanelRow>
            <SelectControl
              label={__('Brand', 'sobe')}
              value={brandId}
              options={brandOptions}
              onChange={(val) => {
                const id = parseInt(val, 10);
                const updates = { brandId: id };
                if (id > 0) {
                  const term = brands.find((b) => b.id === id);
                  if (term?.link) updates.linkUrl = term.link;
                }
                setAttributes(updates);
              }}
              __nextHasNoMarginBottom
              __next40pxDefaultSize
            />
          </PanelRow>
          <PanelRow>
            <RangeControl
              label={__('Number of Products', 'sobe')}
              value={count}
              onChange={(val) =>
                setAttributes({ count: Math.min(Math.max(val ?? 8, 1), 12) })
              }
              min={1}
              max={12}
              __nextHasNoMarginBottom
            />
          </PanelRow>
        </PanelBody>

        {/* ── Content ────────────────────────────────────────────────────── */}
        <PanelBody title={__('Content', 'sobe')} initialOpen={false}>
          <TextControl
            label={__('Heading', 'sobe')}
            value={heading}
            onChange={(val) => setAttributes({ heading: val })}
            placeholder={__('Section title…', 'sobe')}
            __nextHasNoMarginBottom
            __next40pxDefaultSize
          />
          <div style={{ marginTop: '12px' }}>
            <TextControl
              label={__('Paragraph', 'sobe')}
              value={paragraph}
              onChange={(val) => setAttributes({ paragraph: val })}
              placeholder={__('Supporting text…', 'sobe')}
              __nextHasNoMarginBottom
              __next40pxDefaultSize
            />
          </div>
          <div style={{ marginTop: '16px', borderTop: '1px solid #e0e0e0', paddingTop: '16px' }}>
            <p style={{ fontSize: '11px', color: '#757575', margin: '0 0 8px', textTransform: 'uppercase', letterSpacing: '0.04em' }}>
              {__('Link / Button', 'sobe')}
            </p>
            <TextControl
              label={__('Label', 'sobe')}
              value={linkText}
              onChange={(val) => setAttributes({ linkText: val })}
              placeholder={__('e.g. Shop All', 'sobe')}
              __nextHasNoMarginBottom
              __next40pxDefaultSize
            />
            <div style={{ marginTop: '8px' }}>
              <TextControl
                label={__('URL', 'sobe')}
                value={linkUrl}
                onChange={(val) => setAttributes({ linkUrl: val })}
                type="url"
                placeholder="https://"
                __nextHasNoMarginBottom
                __next40pxDefaultSize
              />
            </div>
            {linkText && linkUrl && (
              <div style={{ marginTop: '8px' }}>
                <SelectControl
                  label={__('Style', 'sobe')}
                  value={linkType}
                  options={linkTypeOptions}
                  onChange={(val) => setAttributes({ linkType: val })}
                  __nextHasNoMarginBottom
                  __next40pxDefaultSize
                />
              </div>
            )}
          </div>
        </PanelBody>
      </InspectorControls>

      {/* ── Editor preview ────────────────────────────────────────────────── */}
      <div {...blockProps}>
        {(heading || paragraph || (linkText && linkUrl)) && (
          <div style={{ display: 'flex', alignItems: 'flex-end', justifyContent: 'space-between', gap: '16px', marginBottom: '12px', padding: '0 0 12px', borderBottom: '1px solid #e0e0e0' }}>
            <div>
              {heading && (
                <p style={{ margin: '0 0 4px', fontWeight: 700, fontSize: '16px', color: '#0f172a' }}>
                  {heading}
                </p>
              )}
              {paragraph && (
                <p style={{ margin: 0, fontSize: '12px', color: '#64748b' }}>{paragraph}</p>
              )}
            </div>
            {linkText && linkUrl && (
              <span style={
                isLinkType
                  ? { fontSize: '12px', color: '#0f172a', display: 'inline-flex', alignItems: 'center', gap: '4px' }
                  : { fontSize: '11px', padding: '4px 10px', background: '#0f172a', color: '#fff', borderRadius: '4px', whiteSpace: 'nowrap' }
              }>
                {linkText}{isLinkType && ' →'}
              </span>
            )}
          </div>
        )}
        {/* Mock product cards */}
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: '12px' }}>
          {[
            { bg: '#f1f5f9', price: '€49.00' },
            { bg: '#fef9f0', price: '€89.00' },
            { bg: '#f0fdf4', price: '€34.00' },
            { bg: '#fdf4ff', price: '€120.00' },
          ].map(({ bg, price }, i) => (
            <div key={i} style={{ borderRadius: '8px', overflow: 'hidden', background: '#fff', border: '1px solid #e2e8f0', boxShadow: '0 1px 3px rgba(0,0,0,.06)' }}>
              <div style={{ aspectRatio: '1', background: bg, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" strokeWidth="1.2">
                  <rect x="3" y="3" width="18" height="18" rx="2"/>
                  <circle cx="8.5" cy="8.5" r="1.5"/>
                  <path d="M21 15l-5-5L5 21"/>
                </svg>
              </div>
              <div style={{ padding: '10px' }}>
                <div style={{ height: '9px', background: '#e2e8f0', borderRadius: '3px', marginBottom: '5px' }} />
                <div style={{ height: '9px', background: '#e2e8f0', borderRadius: '3px', width: '65%', marginBottom: '8px' }} />
                <div style={{ fontSize: '12px', fontWeight: 700, color: '#0f172a' }}>{price}</div>
              </div>
            </div>
          ))}
        </div>
        <p style={{ margin: '10px 0 0', fontSize: '11px', color: '#94a3b8', textAlign: 'center' }}>
          {[`${count} products`, orderByLabel, catLabel, brandLabel].filter(Boolean).join(' · ')}
        </p>
      </div>
    </>
  );
}
