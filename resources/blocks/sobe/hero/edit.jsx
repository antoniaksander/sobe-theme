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
  heading: '#111827',
  text: '#1f2937',
  accent: '#374151',
};
const PARA_COLOR_MAP = {
  'fg-muted': 'rgba(250,250,248,0.8)',
  text: '#111827',
  'text-muted': '#6b7280',
  'text-subtle': '#9ca3af',
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
  } = attributes;

  const blockProps = useBlockProps({
    className: 'hero hero--sobe',
    style: {
      minHeight: height ?? '80vh',
      backgroundImage: backgroundImageUrl
        ? `url(${backgroundImageUrl})`
        : 'linear-gradient(135deg, #111827 0%, #374151 100%)',
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
        color: ctaType === 'link-dark' ? '#111827' : '#f9fafb',
        textDecoration: 'none',
        padding: 0,
      }
    : {
        display: 'inline-block',
        padding: '0.75rem 1.5rem',
        background: ctaType?.includes('dark') ? '#111827' : '#f9fafb',
        color: ctaType?.includes('dark') ? '#f9fafb' : '#111827',
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
        <PanelBody title={__('Call to Action', 'sobe')} initialOpen={true}>
          <PanelRow>
            <TextControl
              label={__('Button Text', 'sobe')}
              value={ctaText ?? ''}
              onChange={(val) => setAttributes({ ctaText: val })}
              placeholder={__('Get started', 'sobe')}
              __nextHasNoMarginBottom
              __next40pxDefaultSize
            />
          </PanelRow>
          <PanelRow>
            <TextControl
              label={__('Button URL', 'sobe')}
              help={__('Include https:// for external links.', 'sobe')}
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
              label={__('Button Style', 'sobe')}
              value={ctaType}
              options={[
                {
                  label: __(
                    'Light fill — for dark backgrounds',
                    'sobe',
                  ),
                  value: 'btn-light',
                },
                {
                  label: __('Dark fill — for light backgrounds', 'sobe'),
                  value: 'btn-dark',
                },
                {
                  label: __('Light Outline — for dark backgrounds', 'sobe'),
                  value: 'btn-outline-light',
                },
                {
                  label: __('Dark Outline — for light backgrounds', 'sobe'),
                  value: 'btn-outline-dark',
                },
                {
                  label: __('Link — Dark (for light backgrounds)', 'sobe'),
                  value: 'link-dark',
                },
                {
                  label: __('Link — Light (for dark backgrounds)', 'sobe'),
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
        <PanelBody title={__('Background', 'sobe')} initialOpen={false}>
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
                          {__('Change Image', 'sobe')}
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
                          {__('Remove', 'sobe')}
                        </Button>
                      </div>
                    </div>
                  ) : (
                    <Button
                      variant="secondary"
                      onClick={open}
                      style={{ width: '100%' }}
                    >
                      {__('Select Background Image', 'sobe')}
                    </Button>
                  )
                }
              />
            </MediaUploadCheck>
          </PanelRow>
          <PanelRow>
            <ToggleControl
              label={__('Dark Overlay', 'sobe')}
              help={__(
                'Semi-transparent dark layer for text readability on bright images.',
                'sobe',
              )}
              checked={darkOverlay}
              onChange={(val) => setAttributes({ darkOverlay: val })}
              __nextHasNoMarginBottom
            />
          </PanelRow>
        </PanelBody>

        {/* Panel 3: Typography */}
        <PanelBody title={__('Typography', 'sobe')} initialOpen={false}>
          <PanelRow>
            <SelectControl
              label={__('Heading Colour', 'sobe')}
              value={headingColor}
              options={[
                {
                  label: __(
                    '--c-primary-fg — dark backgrounds',
                    'sobe',
                  ),
                  value: 'fg',
                },
                {
                  label: __(
                    '--c-heading — light backgrounds',
                    'sobe',
                  ),
                  value: 'heading',
                },
                {
                  label: __(
                    '--c-text — light backgrounds',
                    'sobe',
                  ),
                  value: 'text',
                },
                {
                  label: __(
                    '--c-accent  — emphasis',
                    'sobe',
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
              label={__('Paragraph Colour', 'sobe')}
              value={paragraphColor}
              options={[
                {
                  label: __(
                    '--c-primary-fg/80 — dark backgrounds',
                    'sobe',
                  ),
                  value: 'fg-muted',
                },
                {
                  label: __(
                    '--c-text  — light backgrounds',
                    'sobe',
                  ),
                  value: 'text',
                },
                {
                  label: __(
                    '--c-text-muted  — secondary text',
                    'sobe',
                  ),
                  value: 'text-muted',
                },
                {
                  label: __(
                    '--c-text-subtle  — quiet captions',
                    'sobe',
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
              label={__('Heading Size', 'sobe')}
              help={__(
                'Does not apply to the Editorial layout (which uses display scale).',
                'sobe',
              )}
              value={headingSize}
              options={[
                {
                  label: __('Default — text-4xl → text-6xl', 'sobe'),
                  value: 'default',
                },
                {
                  label: __('Large — text-5xl → text-7xl', 'sobe'),
                  value: 'lg',
                },
                { label: __('XL — text-6xl → text-8xl', 'sobe'), value: 'xl' },
              ]}
              onChange={(val) => setAttributes({ headingSize: val })}
              __nextHasNoMarginBottom
              __next40pxDefaultSize
            />
          </PanelRow>
        </PanelBody>

        {/* Panel 4: Layout */}
        <PanelBody title={__('Layout', 'sobe')} initialOpen={false}>
          <PanelRow>
            <SelectControl
              label={__('Content Layout', 'sobe')}
              value={alignment}
              options={[
                {
                  label: __('Left — text column, left-aligned', 'sobe'),
                  value: 'left',
                },
                {
                  label: __('Center — text centered', 'sobe'),
                  value: 'center',
                },
                {
                  label: __('Split Screen — image right, panel left', 'sobe'),
                  value: 'split-screen',
                },
                {
                  label: __(
                    'Editorial — oversized headline, asymmetric',
                    'sobe',
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
              label={__('Height', 'sobe')}
              value={height}
              options={[
                { label: '70vh', value: '70vh' },
                { label: __('80vh (default)', 'sobe'), value: '80vh' },
                { label: '90vh', value: '90vh' },
                { label: __('Full Page — 100vh', 'sobe'), value: '100vh' },
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
                placeholder={__('Your headline here…', 'sobe')}
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
                  placeholder={__('Supporting description…', 'sobe')}
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
                placeholder={__('Your headline here…', 'sobe')}
                value={heading}
                onChange={(val) => setAttributes({ heading: val })}
                style={headingStyle}
              />
              <RichText
                tagName="p"
                placeholder={__('Supporting description…', 'sobe')}
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
