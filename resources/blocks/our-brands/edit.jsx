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
        {/* Alphabet nav */}
        <div style={{ display: 'flex', flexWrap: 'wrap', gap: '8px', borderBottom: '1px solid #e2e8f0', paddingBottom: '10px', marginBottom: '16px' }}>
          {'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('').map((l, i) => (
            <span key={l} style={{ fontSize: '11px', fontWeight: 500, color: i === 0 ? '#0f172a' : '#94a3b8', borderBottom: i === 0 ? '2px solid #e11d48' : '2px solid transparent', paddingBottom: '2px', letterSpacing: '0.05em' }}>{l}</span>
          ))}
        </div>

        {/* Letter section A */}
        <div style={{ marginBottom: '20px' }}>
          <div style={{ fontSize: '40px', fontWeight: 700, color: '#0f172a', lineHeight: 1, marginBottom: '12px' }}>A</div>
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(5, 1fr)', gap: '8px 16px' }}>
            {['Acme Corp', 'Aero', 'Apex', 'Arken', 'Axon'].map((name) => (
              <span key={name} style={{ fontSize: '12px', color: '#475569' }}>
                {showLogos && <span style={{ display: 'block', width: '48px', height: '20px', background: '#e2e8f0', borderRadius: '3px', marginBottom: '4px' }} />}
                {name}
              </span>
            ))}
          </div>
        </div>

        {/* Letter section B */}
        <div>
          <div style={{ fontSize: '40px', fontWeight: 700, color: '#0f172a', lineHeight: 1, marginBottom: '12px' }}>B</div>
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(5, 1fr)', gap: '8px 16px' }}>
            {['Belux', 'Bravo', 'Bridget', 'Bronx'].map((name) => (
              <span key={name} style={{ fontSize: '12px', color: '#475569' }}>
                {showLogos && <span style={{ display: 'block', width: '48px', height: '20px', background: '#e2e8f0', borderRadius: '3px', marginBottom: '4px' }} />}
                {name}
              </span>
            ))}
          </div>
        </div>
      </div>
    </>
  );
}
