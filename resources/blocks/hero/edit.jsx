// All @wordpress/* accessed via wp.* globals — never import from '@wordpress/…'
const {
  useBlockProps,
  InspectorControls,
  RichText,
  MediaUpload,
  MediaUploadCheck,
} = wp.blockEditor;
const {
  PanelBody,
  PanelRow,
  TextControl,
  SelectControl,
  ToggleControl,
  Button,
} = wp.components;
const { __ } = wp.i18n;

import './editor.scss';

// Maps token attribute values → CSS colours for editor-only inline preview.
const HEADING_COLOR_MAP = {
  fg: '#fafaf8',
  heading: '#0f0f1c',
  text: '#1a1a2e',
  accent: '#db2b39',
};
const PARA_COLOR_MAP = {
  'fg-muted': 'rgba(250,250,248,0.8)',
  text: '#1a1a2e',
  'text-muted': '#6b6b82',
  'text-subtle': '#9494a8',
};
const HEADING_SIZE_MAP = {
  default: 'clamp(2rem, 5vw, 4rem)',
  lg: 'clamp(2.5rem, 6vw, 5rem)',
  xl: 'clamp(3rem, 7vw, 6rem)',
};

export default function Edit({ attributes, setAttributes }) {
  const {
    heading,
    paragraph,
    ctaText,
    ctaUrl,
    ctaType,
    headingColor,
    paragraphColor,
    headingSize,
    alignment,
    height,
    backgroundImageId,
    backgroundImageUrl,
    darkOverlay,
    enableWebgl,
  } = attributes;

  const blockProps = useBlockProps({
    className: 'sobe-hero',
    style: {
      minHeight: height ?? '80vh',
      backgroundImage: backgroundImageUrl
        ? `url(${backgroundImageUrl})`
        : 'linear-gradient(135deg, #1a1a2e 0%, #2d2d47 100%)',
      backgroundSize: 'cover',
      backgroundPosition: 'center',
      position: 'relative',
      overflow: 'hidden',
      display: 'flex',
    },
  });

  const contentWrapStyle = {
    position: 'relative',
    zIndex: 1,
    padding: '5rem 2.5rem',
    display: 'flex',
    flexDirection: 'column',
    gap: '1.25rem',
    ...(alignment === 'center'
      ? {
          alignItems: 'center',
          textAlign: 'center',
          width: '100%',
          maxWidth: '48rem',
          margin: '0 auto',
        }
      : alignment === 'split-screen'
      ? { alignItems: 'flex-start', width: '50%' }
      : alignment === 'editorial'
      ? {
          alignItems: 'flex-start',
          width: '100%',
          alignSelf: 'stretch',
          justifyContent: 'space-between',
        }
      : { alignItems: 'flex-start', width: '100%', maxWidth: '36rem' }),
  };

  const hColor = HEADING_COLOR_MAP[headingColor] ?? '#fafaf8';
  const pColor = PARA_COLOR_MAP[paragraphColor] ?? 'rgba(250,250,248,0.8)';
  const hFontSize = HEADING_SIZE_MAP[headingSize] ?? HEADING_SIZE_MAP.default;

  const headingStyle = {
    color: hColor,
    margin: 0,
    ...(alignment === 'editorial'
      ? {
          fontSize: 'clamp(4rem, 12vw, 8rem)',
          lineHeight: 0.9,
          letterSpacing: '-0.04em',
          fontWeight: 900,
        }
      : { fontSize: hFontSize, fontWeight: 700, lineHeight: 1.1 }),
  };

  const paraStyle = {
    color: pColor,
    margin: 0,
    fontSize: '1.125rem',
    lineHeight: 1.6,
  };

  const isLinkStyle = ctaType?.startsWith('link-');
  const ctaPreviewStyle = isLinkStyle
    ? {
        display: 'inline-flex',
        alignItems: 'center',
        gap: '0.375rem',
        fontWeight: 500,
        fontSize: '0.875rem',
        color: ctaType === 'link-dark' ? '#1a1a2e' : '#f4f3f2',
        textDecoration: 'none',
        padding: 0,
      }
    : {
        display: 'inline-block',
        padding: '0.75rem 1.5rem',
        background: ctaType?.includes('dark') ? '#1a1a2e' : '#f4f3f2',
        color: ctaType?.includes('dark') ? '#f4f3f2' : '#1a1a2e',
        borderRadius: '0.5rem',
        fontWeight: 600,
        fontSize: '0.875rem',
        textDecoration: 'none',
        cursor: 'pointer',
        ...(ctaType?.includes('outline')
          ? { background: 'transparent', border: '2px solid currentColor' }
          : {}),
      };

  return (
    <>
      <InspectorControls>
        {/* Panel 1: CTA */}
        <PanelBody title={__('Call to Action', 'sage')} initialOpen={true}>
          <PanelRow>
            <TextControl
              label={__('Button Text', 'sage')}
              value={ctaText ?? ''}
              onChange={(val) => setAttributes({ ctaText: val })}
              placeholder={__('Get started', 'sage')}
              __nextHasNoMarginBottom
              __next40pxDefaultSize
            />
          </PanelRow>
          <PanelRow>
            <TextControl
              label={__('Button URL', 'sage')}
              help={__('Include https:// for external links.', 'sage')}
              value={ctaUrl ?? ''}
              onChange={(val) => setAttributes({ ctaUrl: val })}
              type="url"
              placeholder="https://"
              __nextHasNoMarginBottom
              __next40pxDefaultSize
            />
          </PanelRow>
          <PanelRow>
            <SelectControl
              label={__('Button Style', 'sage')}
              value={ctaType}
              options={[
                {
                  label: __(
                    'Light (cream fill) — for dark backgrounds',
                    'sage',
                  ),
                  value: 'btn-light',
                },
                {
                  label: __('Dark (navy fill) — for light backgrounds', 'sage'),
                  value: 'btn-dark',
                },
                {
                  label: __('Light Outline — for dark backgrounds', 'sage'),
                  value: 'btn-outline-light',
                },
                {
                  label: __('Dark Outline — for light backgrounds', 'sage'),
                  value: 'btn-outline-dark',
                },
                {
                  label: __('Link — Dark (for light backgrounds)', 'sage'),
                  value: 'link-dark',
                },
                {
                  label: __('Link — Light (for dark backgrounds)', 'sage'),
                  value: 'link-light',
                },
              ]}
              onChange={(val) => setAttributes({ ctaType: val })}
              __nextHasNoMarginBottom
              __next40pxDefaultSize
            />
          </PanelRow>
        </PanelBody>

        {/* Panel 2: Background */}
        <PanelBody title={__('Background', 'sage')} initialOpen={false}>
          <PanelRow>
            <MediaUploadCheck>
              <MediaUpload
                onSelect={(media) =>
                  setAttributes({
                    backgroundImageId: media.id,
                    backgroundImageUrl: media.url,
                  })
                }
                allowedTypes={['image']}
                value={backgroundImageId}
                render={({ open }) =>
                  backgroundImageUrl ? (
                    <div style={{ width: '100%' }}>
                      <img
                        src={backgroundImageUrl}
                        alt=""
                        style={{
                          maxWidth: '100%',
                          borderRadius: '4px',
                          marginBottom: '8px',
                          display: 'block',
                        }}
                      />
                      <div
                        style={{
                          display: 'flex',
                          gap: '8px',
                          flexWrap: 'wrap',
                        }}
                      >
                        <Button variant="secondary" onClick={open}>
                          {__('Change Image', 'sage')}
                        </Button>
                        <Button
                          variant="link"
                          isDestructive
                          onClick={() =>
                            setAttributes({
                              backgroundImageId: 0,
                              backgroundImageUrl: '',
                            })
                          }
                        >
                          {__('Remove', 'sage')}
                        </Button>
                      </div>
                    </div>
                  ) : (
                    <Button
                      variant="secondary"
                      onClick={open}
                      style={{ width: '100%' }}
                    >
                      {__('Select Background Image', 'sage')}
                    </Button>
                  )
                }
              />
            </MediaUploadCheck>
          </PanelRow>
          <PanelRow>
            <ToggleControl
              label={__('Dark Overlay', 'sage')}
              help={__(
                'Semi-transparent dark layer for text readability on bright images.',
                'sage',
              )}
              checked={darkOverlay}
              onChange={(val) => setAttributes({ darkOverlay: val })}
              __nextHasNoMarginBottom
            />
          </PanelRow>
          <PanelRow>
            <ToggleControl
              label={__('Enable WebGL Cursor Effect', 'sage')}
              help={__(
                'Interactive aurora glow that reacts to mouse movement. Best on dark images.',
                'sage',
              )}
              checked={enableWebgl}
              onChange={(val) => setAttributes({ enableWebgl: val })}
              __nextHasNoMarginBottom
            />
          </PanelRow>
        </PanelBody>

        {/* Panel 3: Typography */}
        <PanelBody title={__('Typography', 'sage')} initialOpen={false}>
          <PanelRow>
            <SelectControl
              label={__('Heading Colour', 'sage')}
              value={headingColor}
              options={[
                {
                  label: __(
                    '--c-primary-fg  (#fafaf8 cream) — dark backgrounds',
                    'sage',
                  ),
                  value: 'fg',
                },
                {
                  label: __(
                    '--c-heading     (#0f0f1c navy) — light backgrounds',
                    'sage',
                  ),
                  value: 'heading',
                },
                {
                  label: __(
                    '--c-text        (#1a1a2e body) — light backgrounds',
                    'sage',
                  ),
                  value: 'text',
                },
                {
                  label: __(
                    '--c-accent      (#db2b39 red)  — emphasis',
                    'sage',
                  ),
                  value: 'accent',
                },
              ]}
              onChange={(val) => setAttributes({ headingColor: val })}
              __nextHasNoMarginBottom
              __next40pxDefaultSize
            />
          </PanelRow>
          <PanelRow>
            <SelectControl
              label={__('Paragraph Colour', 'sage')}
              value={paragraphColor}
              options={[
                {
                  label: __(
                    '--c-primary-fg/80  (cream 80%) — dark backgrounds',
                    'sage',
                  ),
                  value: 'fg-muted',
                },
                {
                  label: __(
                    '--c-text           (#1a1a2e)  — light backgrounds',
                    'sage',
                  ),
                  value: 'text',
                },
                {
                  label: __(
                    '--c-text-muted     (#6b6b82)  — secondary text',
                    'sage',
                  ),
                  value: 'text-muted',
                },
                {
                  label: __(
                    '--c-text-subtle    (#9494a8)  — quiet captions',
                    'sage',
                  ),
                  value: 'text-subtle',
                },
              ]}
              onChange={(val) => setAttributes({ paragraphColor: val })}
              __nextHasNoMarginBottom
              __next40pxDefaultSize
            />
          </PanelRow>
          <PanelRow>
            <SelectControl
              label={__('Heading Size', 'sage')}
              help={__(
                'Does not apply to the Editorial layout (which uses display scale).',
                'sage',
              )}
              value={headingSize}
              options={[
                {
                  label: __('Default — text-4xl → text-6xl', 'sage'),
                  value: 'default',
                },
                {
                  label: __('Large — text-5xl → text-7xl', 'sage'),
                  value: 'lg',
                },
                { label: __('XL — text-6xl → text-8xl', 'sage'), value: 'xl' },
              ]}
              onChange={(val) => setAttributes({ headingSize: val })}
              __nextHasNoMarginBottom
              __next40pxDefaultSize
            />
          </PanelRow>
        </PanelBody>

        {/* Panel 4: Layout */}
        <PanelBody title={__('Layout', 'sage')} initialOpen={false}>
          <PanelRow>
            <SelectControl
              label={__('Content Layout', 'sage')}
              value={alignment}
              options={[
                {
                  label: __('Left — text column, left-aligned', 'sage'),
                  value: 'left',
                },
                {
                  label: __('Center — text centered', 'sage'),
                  value: 'center',
                },
                {
                  label: __('Split Screen — image right, panel left', 'sage'),
                  value: 'split-screen',
                },
                {
                  label: __(
                    'Editorial — oversized headline, asymmetric',
                    'sage',
                  ),
                  value: 'editorial',
                },
              ]}
              onChange={(val) => setAttributes({ alignment: val })}
              __nextHasNoMarginBottom
              __next40pxDefaultSize
            />
          </PanelRow>
          <PanelRow>
            <SelectControl
              label={__('Height', 'sage')}
              value={height}
              options={[
                { label: '70vh', value: '70vh' },
                { label: __('80vh (default)', 'sage'), value: '80vh' },
                { label: '90vh', value: '90vh' },
                { label: __('Full Page — 100vh', 'sage'), value: '100vh' },
              ]}
              onChange={(val) => setAttributes({ height: val })}
              __nextHasNoMarginBottom
              __next40pxDefaultSize
            />
          </PanelRow>
        </PanelBody>
      </InspectorControls>

      {/* ── Editor canvas preview ─────────────────────────────────────────── */}
      <div {...blockProps}>
        {darkOverlay && (
          <div
            aria-hidden="true"
            style={{
              position: 'absolute',
              inset: 0,
              background: 'rgba(0,0,0,0.5)',
              pointerEvents: 'none',
            }}
          />
        )}

        <div style={contentWrapStyle}>
          {alignment === 'editorial' ? (
            <>
              <RichText
                tagName="h1"
                placeholder={__('Your headline here…', 'sage')}
                value={heading}
                onChange={(val) => setAttributes({ heading: val })}
                style={headingStyle}
              />
              <div
                style={{
                  alignSelf: 'flex-end',
                  textAlign: 'right',
                  maxWidth: '22rem',
                  display: 'flex',
                  flexDirection: 'column',
                  gap: '1rem',
                }}
              >
                <RichText
                  tagName="p"
                  placeholder={__('Supporting description…', 'sage')}
                  value={paragraph}
                  onChange={(val) => setAttributes({ paragraph: val })}
                  style={paraStyle}
                />
                {ctaText && (
                  <div>
                    <a
                      href={ctaUrl || '#'}
                      style={ctaPreviewStyle}
                      onClick={(e) => e.preventDefault()}
                    >
                      {ctaText}
                    </a>
                  </div>
                )}
              </div>
            </>
          ) : (
            <>
              <RichText
                tagName="h1"
                placeholder={__('Your headline here…', 'sage')}
                value={heading}
                onChange={(val) => setAttributes({ heading: val })}
                style={headingStyle}
              />
              <RichText
                tagName="p"
                placeholder={__('Supporting description…', 'sage')}
                value={paragraph}
                onChange={(val) => setAttributes({ paragraph: val })}
                style={paraStyle}
              />
              {ctaText && (
                <div>
                  <a
                    href={ctaUrl || '#'}
                    style={ctaPreviewStyle}
                    onClick={(e) => e.preventDefault()}
                  >
                    {ctaText}
                  </a>
                </div>
              )}
            </>
          )}
        </div>
      </div>
    </>
  );
}
