// Access WordPress packages as globals — do NOT import from '@wordpress/...'
const { useBlockProps, InspectorControls } = wp.blockEditor;
const { PanelBody, PanelRow, ToggleControl } = wp.components;
const { __ } = wp.i18n;

import './editor.scss';

export default function Edit({ attributes, setAttributes }) {
  const { showLogos, hideEmpty } = attributes;

  const blockProps = useBlockProps({
    className: 'sobe-our-brands-editor',
  });

  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Display', 'sage')} initialOpen={true}>
          <PanelRow>
            <ToggleControl
              label={__('Show brand logos', 'sage')}
              help={showLogos
                ? __('Logos shown where available.', 'sage')
                : __('Brand names only.', 'sage')}
              checked={showLogos}
              onChange={(val) => setAttributes({ showLogos: val })}
              __nextHasNoMarginBottom
            />
          </PanelRow>
          <PanelRow>
            <ToggleControl
              label={__('Hide brands with no products', 'sage')}
              checked={hideEmpty}
              onChange={(val) => setAttributes({ hideEmpty: val })}
              __nextHasNoMarginBottom
            />
          </PanelRow>
        </PanelBody>
      </InspectorControls>

      <div {...blockProps}>
        <div style={{
          padding: '2.5rem',
          background: '#f8f9fa',
          border: '2px dashed #cbd5e1',
          borderRadius: '0.5rem',
          textAlign: 'center',
        }}>
          <p style={{ margin: '0 0 4px', fontWeight: 600, color: '#0f172a', fontSize: '14px' }}>
            {__('Our Brands — A–Z directory', 'sage')}
          </p>
          <p style={{ margin: 0, color: '#64748b', fontSize: '12px' }}>
            {showLogos
              ? __('Logos + names · alphabetical', 'sage')
              : __('Names only · alphabetical', 'sage')}
            {hideEmpty ? '' : __(` · including empty`, 'sage')}
          </p>
        </div>
      </div>
    </>
  );
}
