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
    heading,
    paragraph,
    linkText,
    linkUrl,
    linkType,
  } = attributes;

  const [categories, setCategories] = useState([]);

  useEffect(() => {
    wp.apiFetch({
      path: '/wp/v2/product_cat?per_page=100&orderby=name&order=asc',
    })
      .then((terms) => setCategories(terms))
      .catch(() => {});
  }, []);

  const blockProps = useBlockProps({
    className: 'sobe-product-carousel-editor',
  });

  const categoryOptions = [
    { label: __('All Categories', 'sage'), value: 0 },
    ...categories.map((cat) => ({ label: cat.name, value: cat.id })),
  ];

  const orderByOptions = [
    { label: __('Latest',       'sage'), value: 'latest'       },
    { label: __('Featured',     'sage'), value: 'featured'     },
    { label: __('Best Selling', 'sage'), value: 'best_selling' },
    { label: __('Top Rated',    'sage'), value: 'top_rated'    },
    { label: __('On Sale',      'sage'), value: 'on_sale'      },
    { label: __('Random',       'sage'), value: 'random'       },
  ];

  const linkTypeOptions = [
    { label: __('Button — Dark',          'sage'), value: 'btn-dark'          },
    { label: __('Button — Light',         'sage'), value: 'btn-light'         },
    { label: __('Button — Outline Dark',  'sage'), value: 'btn-outline-dark'  },
    { label: __('Button — Outline Light', 'sage'), value: 'btn-outline-light' },
    { label: __('Link — Dark',            'sage'), value: 'link-dark'         },
    { label: __('Link — Light',           'sage'), value: 'link-light'        },
  ];

  const orderByLabel = orderByOptions.find((o) => o.value === orderBy)?.label ?? 'Latest';
  const catLabel =
    categoryId === 0
      ? __('all categories', 'sage')
      : categories.find((c) => c.id === categoryId)?.name ?? `category ${categoryId}`;

  const isLinkType = linkType?.startsWith('link-');

  return (
    <>
      <InspectorControls>
        {/* ── Product Source ─────────────────────────────────────────────── */}
        <PanelBody title={__('Product Source', 'sage')} initialOpen={true}>
          <PanelRow>
            <SelectControl
              label={__('Order By', 'sage')}
              value={orderBy}
              options={orderByOptions}
              onChange={(val) => setAttributes({ orderBy: val })}
              __nextHasNoMarginBottom
              __next40pxDefaultSize
            />
          </PanelRow>
          <PanelRow>
            <SelectControl
              label={__('Category', 'sage')}
              value={categoryId}
              options={categoryOptions}
              onChange={(val) => setAttributes({ categoryId: parseInt(val, 10) })}
              __nextHasNoMarginBottom
              __next40pxDefaultSize
            />
          </PanelRow>
          <PanelRow>
            <RangeControl
              label={__('Number of Products', 'sage')}
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
        <PanelBody title={__('Content', 'sage')} initialOpen={false}>
          <TextControl
            label={__('Heading', 'sage')}
            value={heading}
            onChange={(val) => setAttributes({ heading: val })}
            placeholder={__('Section title…', 'sage')}
            __nextHasNoMarginBottom
            __next40pxDefaultSize
          />
          <div style={{ marginTop: '12px' }}>
            <TextControl
              label={__('Paragraph', 'sage')}
              value={paragraph}
              onChange={(val) => setAttributes({ paragraph: val })}
              placeholder={__('Supporting text…', 'sage')}
              __nextHasNoMarginBottom
              __next40pxDefaultSize
            />
          </div>
          <div style={{ marginTop: '16px', borderTop: '1px solid #e0e0e0', paddingTop: '16px' }}>
            <p style={{ fontSize: '11px', color: '#757575', margin: '0 0 8px', textTransform: 'uppercase', letterSpacing: '0.04em' }}>
              {__('Link / Button', 'sage')}
            </p>
            <TextControl
              label={__('Label', 'sage')}
              value={linkText}
              onChange={(val) => setAttributes({ linkText: val })}
              placeholder={__('e.g. Shop All', 'sage')}
              __nextHasNoMarginBottom
              __next40pxDefaultSize
            />
            <div style={{ marginTop: '8px' }}>
              <TextControl
                label={__('URL', 'sage')}
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
                  label={__('Style', 'sage')}
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
        <div style={{ padding: '2.5rem', background: '#f8f9fa', border: '2px dashed #cbd5e1', textAlign: 'center', borderRadius: '0.5rem' }}>
          <p style={{ margin: '0 0 4px', fontWeight: 600, color: '#0f172a', fontSize: '14px' }}>
            {__('Product Carousel', 'sage')}
          </p>
          <p style={{ margin: 0, color: '#64748b', fontSize: '12px' }}>
            {`${count} products · ${orderByLabel} · ${catLabel}`}
          </p>
        </div>
      </div>
    </>
  );
}
