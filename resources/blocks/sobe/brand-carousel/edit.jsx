// All @wordpress/* accessed via wp.* globals — never import from '@wordpress/…'
const { useBlockProps, InspectorControls, MediaUpload, MediaUploadCheck } = wp.blockEditor;
const { PanelBody, PanelRow, TextControl, SelectControl, ToggleControl, Button } = wp.components;
const { __ } = wp.i18n;
const { useState, useEffect } = wp.element;

import './editor.scss';

export default function Edit({ attributes, setAttributes }) {
  const { useManualEntry, brands, speed, pauseOnHover, showImages } = attributes;

  // WooCommerce taxonomy terms — fetched once when manual mode is enabled.
  const [wcTerms, setWcTerms] = useState([]);

  useEffect(() => {
    if (!useManualEntry) return;
    wp.apiFetch({ path: '/wp/v2/product_brand?per_page=100&orderby=name&order=asc' })
      .then((terms) => setWcTerms(terms))
      .catch(() => {}); // WooCommerce not active — degrade silently
  }, [useManualEntry]);

  const blockProps = useBlockProps({ className: 'brand-carousel brand-carousel--sobe' });

  function addBrandFromTerm(term) {
    if (brands.some((b) => b.wcTermId === term.id)) return; // already added
    setAttributes({
      brands: [
        ...brands,
        {
          id:       Date.now().toString(),
          wcTermId: term.id,
          imageId:  0,
          imageUrl: '',
          imageAlt: '',
          name:     term.name,
          link:     term.link || '',
        },
      ],
    });
  }

  function addBlankBrand() {
    setAttributes({
      brands: [
        ...brands,
        { id: Date.now().toString(), imageId: 0, imageUrl: '', imageAlt: '', name: '', link: '' },
      ],
    });
  }

  function removeBrand(index) {
    setAttributes({ brands: brands.filter((_, i) => i !== index) });
  }

  function updateBrand(index, patch) {
    setAttributes({ brands: brands.map((b, i) => (i === index ? { ...b, ...patch } : b)) });
  }

  // Terms not yet added — used for suggestion chips.
  const suggestions = wcTerms.filter((t) => !brands.some((b) => b.wcTermId === t.id));

  return (
    <>
      <InspectorControls>

        {/* ── Data Source ─────────────────────────────────────────────────── */}
        <PanelBody title={__('Data Source', 'sobe')} initialOpen={true}>
          <PanelRow>
            <ToggleControl
              label={__('Use Manual Entry', 'sobe')}
              help={
                useManualEntry
                  ? __('Brands defined below are shown in the carousel.', 'sobe')
                  : __('Reads from WooCommerce product_brand taxonomy automatically.', 'sobe')
              }
              checked={useManualEntry}
              onChange={(val) => setAttributes({ useManualEntry: val })}
              __nextHasNoMarginBottom
            />
          </PanelRow>
        </PanelBody>

        {/* ── Manual brand list ────────────────────────────────────────────── */}
        {useManualEntry && (
          <PanelBody title={__('Brands', 'sobe')} initialOpen={true}>

            {/* Quick-add chips from WooCommerce */}
            {suggestions.length > 0 && (
              <div style={{ marginBottom: '16px' }}>
                <p style={{ fontSize: '11px', color: '#757575', margin: '0 0 6px', textTransform: 'uppercase', letterSpacing: '0.04em' }}>
                  {__('Add from WooCommerce', 'sobe')}
                </p>
                <div style={{ display: 'flex', flexWrap: 'wrap', gap: '4px' }}>
                  {suggestions.map((term) => (
                    <Button
                      key={term.id}
                      variant="secondary"
                      onClick={() => addBrandFromTerm(term)}
                      style={{ fontSize: '11px', padding: '2px 8px', height: 'auto' }}
                    >
                      + {term.name}
                    </Button>
                  ))}
                </div>
              </div>
            )}

            {/* Existing brand items */}
            {brands.map((brand, index) => (
              <div
                key={brand.id || index}
                style={{ border: '1px solid #e0e0e0', borderRadius: '4px', padding: '10px', marginBottom: '8px' }}
              >
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '8px' }}>
                  <strong style={{ fontSize: '11px', color: '#555' }}>
                    {brand.name || `Brand ${index + 1}`}
                  </strong>
                  <Button variant="link" isDestructive onClick={() => removeBrand(index)} style={{ padding: 0, minHeight: 0, fontSize: '11px' }}>
                    {__('Remove', 'sobe')}
                  </Button>
                </div>

                <MediaUploadCheck>
                  <MediaUpload
                    onSelect={(media) =>
                      updateBrand(index, { imageId: media.id, imageUrl: media.url, imageAlt: media.alt || '' })
                    }
                    allowedTypes={['image']}
                    value={brand.imageId}
                    render={({ open }) =>
                      brand.imageUrl ? (
                        <div style={{ marginBottom: '8px' }}>
                          <img src={brand.imageUrl} alt="" style={{ maxHeight: '40px', display: 'block', marginBottom: '4px', objectFit: 'contain' }} />
                          <Button variant="link" onClick={open} style={{ fontSize: '11px', padding: 0, minHeight: 0 }}>
                            {__('Change logo', 'sobe')}
                          </Button>
                          {' · '}
                          <Button variant="link" isDestructive onClick={() => updateBrand(index, { imageId: 0, imageUrl: '', imageAlt: '' })} style={{ fontSize: '11px', padding: 0, minHeight: 0 }}>
                            {__('Remove', 'sobe')}
                          </Button>
                        </div>
                      ) : (
                        <Button variant="secondary" onClick={open} style={{ width: '100%', marginBottom: '8px', fontSize: '11px' }}>
                          {__('Upload logo', 'sobe')}
                        </Button>
                      )
                    }
                  />
                </MediaUploadCheck>

                <TextControl
                  label={__('Name', 'sobe')}
                  value={brand.name}
                  onChange={(val) => updateBrand(index, { name: val })}
                  placeholder={__('Brand name (shown if no logo)', 'sobe')}
                  __nextHasNoMarginBottom
                  __next40pxDefaultSize
                />
                <div style={{ marginTop: '6px' }}>
                  <TextControl
                    label={__('Link', 'sobe')}
                    value={brand.link}
                    onChange={(val) => updateBrand(index, { link: val })}
                    type="url"
                    placeholder="https://"
                    __nextHasNoMarginBottom
                    __next40pxDefaultSize
                  />
                </div>
              </div>
            ))}

            <Button variant="secondary" onClick={addBlankBrand} style={{ width: '100%', marginTop: '4px' }}>
              {__('+ Add Brand', 'sobe')}
            </Button>
          </PanelBody>
        )}

        {/* ── Carousel Settings ────────────────────────────────────────────── */}
        <PanelBody title={__('Carousel', 'sobe')} initialOpen={false}>
          <PanelRow>
            <SelectControl
              label={__('Speed', 'sobe')}
              value={speed}
              options={[
                { label: '15 s — Fast',   value: '15s' },
                { label: '20 s',          value: '20s' },
                { label: '30 s — Default',value: '30s' },
                { label: '40 s',          value: '40s' },
                { label: '60 s — Slow',   value: '60s' },
              ]}
              onChange={(val) => setAttributes({ speed: val })}
              __nextHasNoMarginBottom
              __next40pxDefaultSize
            />
          </PanelRow>
          <PanelRow>
            <ToggleControl
              label={__('Show Brand Images', 'sobe')}
              help={showImages
                ? __('Brand logos are shown; falls back to name if no image.', 'sobe')
                : __('Brand names shown as text regardless of image.', 'sobe')}
              checked={showImages}
              onChange={(val) => setAttributes({ showImages: val })}
              __nextHasNoMarginBottom
            />
          </PanelRow>
          <PanelRow>
            <ToggleControl
              label={__('Pause on Hover', 'sobe')}
              checked={pauseOnHover}
              onChange={(val) => setAttributes({ pauseOnHover: val })}
              __nextHasNoMarginBottom
            />
          </PanelRow>
        </PanelBody>

      </InspectorControls>

      {/* ── Editor preview ────────────────────────────────────────────────── */}
      <div {...blockProps}>
        <div style={{ padding: '1.5rem 2rem', background: '#f7f7f7', textAlign: 'center' }}>
          <p style={{ margin: 0, fontSize: '12px', color: '#888' }}>
            <strong style={{ color: '#333' }}>Brand Carousel</strong>
            {' — '}
            {useManualEntry
              ? `${brands.length} brand${brands.length !== 1 ? 's' : ''}`
              : 'WooCommerce product_brand'}
          </p>
          {useManualEntry && brands.length > 0 && (
            <div style={{ display: 'flex', gap: '1rem', flexWrap: 'wrap', justifyContent: 'center', marginTop: '0.75rem' }}>
              {brands.slice(0, 8).map((b, i) => (
                <div key={b.id || i} style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: '40px', padding: '0 0.75rem', background: '#fff', border: '1px solid #e0e0e0', borderRadius: '4px' }}>
                  {b.imageUrl
                    ? <img src={b.imageUrl} alt="" style={{ maxHeight: '28px', maxWidth: '72px', objectFit: 'contain' }} />
                    : <span style={{ fontSize: '11px', fontWeight: 600 }}>{b.name || `Brand ${i + 1}`}</span>
                  }
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </>
  );
}
