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
        <div style={{ background: '#f8fafc', border: '1px solid #e2e8f0', borderRadius: '8px', padding: '16px', display: 'flex', flexDirection: 'column', gap: '16px' }}>

          {showCategories && (
            <div>
              <p style={{ margin: '0 0 8px', fontWeight: 600, fontSize: '11px', color: '#0f172a', textTransform: 'uppercase', letterSpacing: '0.06em' }}>{__('Category', 'sobe')}</p>
              {['All Products', 'Clothing', 'Electronics', 'Home & Garden'].map((item, i) => (
                <div key={item} style={{ display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '5px' }}>
                  <div style={{ width: '13px', height: '13px', border: '1.5px solid ' + (i === 0 ? '#0f172a' : '#cbd5e1'), borderRadius: '3px', background: i === 0 ? '#0f172a' : 'transparent', flexShrink: 0 }} />
                  <span style={{ fontSize: '12px', color: '#475569' }}>{item}</span>
                </div>
              ))}
            </div>
          )}

          {showBrands && (
            <div>
              <p style={{ margin: '0 0 8px', fontWeight: 600, fontSize: '11px', color: '#0f172a', textTransform: 'uppercase', letterSpacing: '0.06em' }}>{__('Brand', 'sobe')}</p>
              {['Acme', 'Globex', 'Initech'].map((item) => (
                <div key={item} style={{ display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '5px' }}>
                  <div style={{ width: '13px', height: '13px', border: '1.5px solid #cbd5e1', borderRadius: '3px', flexShrink: 0 }} />
                  <span style={{ fontSize: '12px', color: '#475569' }}>{item}</span>
                </div>
              ))}
            </div>
          )}

          {showPriceRange && (
            <div>
              <p style={{ margin: '0 0 10px', fontWeight: 600, fontSize: '11px', color: '#0f172a', textTransform: 'uppercase', letterSpacing: '0.06em' }}>{__('Price Range', 'sobe')}</p>
              <div style={{ position: 'relative', height: '4px', background: '#e2e8f0', borderRadius: '4px', margin: '0 0 8px' }}>
                <div style={{ position: 'absolute', left: '18%', right: '28%', height: '100%', background: '#0f172a', borderRadius: '4px' }} />
                <div style={{ position: 'absolute', left: 'calc(18% - 6px)', top: '-4px', width: '12px', height: '12px', borderRadius: '50%', background: '#fff', border: '2px solid #0f172a' }} />
                <div style={{ position: 'absolute', right: 'calc(28% - 6px)', top: '-4px', width: '12px', height: '12px', borderRadius: '50%', background: '#fff', border: '2px solid #0f172a' }} />
              </div>
              <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '11px', color: '#64748b' }}>
                <span>€20</span><span>€180</span>
              </div>
            </div>
          )}

          {showAttributes && (
            <div>
              <p style={{ margin: '0 0 8px', fontWeight: 600, fontSize: '11px', color: '#0f172a', textTransform: 'uppercase', letterSpacing: '0.06em' }}>{__('Size', 'sobe')}</p>
              <div style={{ display: 'flex', flexWrap: 'wrap', gap: '6px' }}>
                {['XS', 'S', 'M', 'L', 'XL'].map((s, i) => (
                  <span key={s} style={{ padding: '3px 10px', border: '1.5px solid ' + (i === 2 ? '#0f172a' : '#e2e8f0'), borderRadius: '4px', fontSize: '11px', color: i === 2 ? '#0f172a' : '#94a3b8', fontWeight: i === 2 ? 600 : 400, background: i === 2 ? '#f8fafc' : 'transparent' }}>{s}</span>
                ))}
              </div>
            </div>
          )}

        </div>
      </div>
    </>
  );
}
