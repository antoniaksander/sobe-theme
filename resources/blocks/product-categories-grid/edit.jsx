// All @wordpress/* accessed via wp.* globals — never import from '@wordpress/…'
const {
  useBlockProps,
  InspectorControls,
  MediaUpload,
  MediaUploadCheck,
} = wp.blockEditor;
const {
  PanelBody,
  PanelRow,
  SelectControl,
  ToggleControl,
  Button,
  Dropdown,
  CheckboxControl,
  TextControl,
} = wp.components;
const { __, sprintf, _n } = wp.i18n;
const { useState, useEffect } = wp.element;
const { useSelect } = wp.data;

import './editor.scss';

const TERMS_PER_PAGE = 100;
const TERMS_MAX_PAGES = 200;

async function fetchAllProductCategories() {
  const all = [];
  let page = 1;

  while (page <= TERMS_MAX_PAGES) {
    const path =
      `/wp/v2/product_cat?per_page=${TERMS_PER_PAGE}&page=${page}` +
      '&orderby=name&order=asc&hide_empty=false';

    let batch;

    try {
      batch = await wp.apiFetch({ path });
    } catch (err) {
      // WordPress returns this once we request the page after the last full page.
      if (page > 1 && err?.code === 'rest_post_invalid_page_number') {
        break;
      }
      throw err;
    }

    if (!Array.isArray(batch) || batch.length === 0) {
      break;
    }

    all.push(...batch);

    if (batch.length < TERMS_PER_PAGE) {
      break;
    }

    page += 1;
  }

  return all;
}

function normalizeItems(items) {
  if (!Array.isArray(items)) {
    return [];
  }
  return items
    .map((row) => ({
      termId: parseInt(row.termId, 10) || 0,
      imageId: parseInt(row.imageId, 10) || 0,
    }))
    .filter((r) => r.termId > 0);
}

function CategoryCardEditor({ row, terms, onRemove, onImage, onClearOverride }) {
  const term = terms.find((t) => t.id === row.termId);
  const name = term?.name ?? sprintf(__('Category #%d', 'sobe'), row.termId);
  const count = typeof term?.count === 'number' ? term.count : 0;

  const media = useSelect(
    (select) => {
      if (!row.imageId) {
        return null;
      }
      return select('core').getMedia(row.imageId);
    },
    [row.imageId],
  );

  const wcSrc =
    term && term.image && typeof term.image === 'object' && term.image.src
      ? term.image.src
      : '';
  const overrideSrc =
    row.imageId && media?.source_url ? media.source_url : '';
  const src = overrideSrc || wcSrc;

  return (
    <li className="sobe-product-categories-grid-editor__card">
      <div className="sobe-product-categories-grid-editor__card-media">
        {src ? (
          <img src={src} alt="" />
        ) : (
          <div
            style={{
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              height: '100%',
              fontSize: 12,
              color: 'var(--c-text-subtle)',
            }}
          >
            {__('No image', 'sobe')}
          </div>
        )}
      </div>
      <div className="sobe-product-categories-grid-editor__card-body">
        <p className="sobe-product-categories-grid-editor__card-title">{name}</p>
        <p style={{ margin: 0, fontSize: 12, color: 'var(--c-text-muted)' }}>
          {sprintf(_n('%d product', '%d products', count, 'sobe'), count)}
        </p>
        <div className="sobe-product-categories-grid-editor__card-actions">
          <MediaUploadCheck>
            <MediaUpload
              value={row.imageId || undefined}
              onSelect={onImage}
              allowedTypes={['image']}
              render={({ open }) => (
                <Button variant="secondary" onClick={open} size="small">
                  {row.imageId
                    ? __('Replace image', 'sobe')
                    : __('Override image', 'sobe')}
                </Button>
              )}
            />
          </MediaUploadCheck>
          {row.imageId > 0 && (
            <Button variant="link" isDestructive onClick={onClearOverride} size="small">
              {__('Use category image', 'sobe')}
            </Button>
          )}
          <Button variant="link" isDestructive onClick={onRemove} size="small">
            {__('Remove', 'sobe')}
          </Button>
        </div>
      </div>
    </li>
  );
}

function CategoryPickerBody({ loadState, terms, termsError, items, toggleTerm, onRetry }) {
  if (loadState === 'loading') {
    return (
      <p className="components-base-control__help sobe-product-categories-grid-editor__picker-status">
        {__('Loading categories…', 'sobe')}
      </p>
    );
  }

  if (loadState === 'error') {
    return (
      <div className="sobe-product-categories-grid-editor__picker-error">
        <p className="components-base-control__help" style={{ margin: '0 0 8px' }}>
          {termsError
            || __('Could not load categories. Check your connection and try again.', 'sobe')}
        </p>
        <Button variant="secondary" onClick={onRetry} size="small">
          {__('Retry', 'sobe')}
        </Button>
      </div>
    );
  }

  if (!Array.isArray(terms) || terms.length === 0) {
    return (
      <p className="components-base-control__help sobe-product-categories-grid-editor__picker-status">
        {__('No product categories found.', 'sobe')}
      </p>
    );
  }

  return terms.map((t) => (
    <CheckboxControl
      key={t.id}
      label={t.name}
      checked={items.some((i) => i.termId === t.id)}
      onChange={(checked) => toggleTerm(t.id, checked)}
      __nextHasNoMarginBottom
    />
  ));
}

export default function Edit({ attributes, setAttributes }) {
  const { layout, enableHoverEffects, items: rawItems, heading, paragraph } = attributes;
  const items = normalizeItems(rawItems);

  const [terms, setTerms] = useState([]);
  const [loadState, setLoadState] = useState('loading');
  const [termsError, setTermsError] = useState(null);
  const [fetchKey, setFetchKey] = useState(0);

  useEffect(() => {
    let cancelled = false;

    setLoadState('loading');
    setTermsError(null);

    fetchAllProductCategories()
      .then((data) => {
        if (cancelled) {
          return;
        }
        setTerms(Array.isArray(data) ? data : []);
        setLoadState('ready');
      })
      .catch((err) => {
        if (cancelled) {
          return;
        }
        setTerms([]);
        setTermsError(
          err && typeof err.message === 'string' && err.message
            ? err.message
            : null,
        );
        setLoadState('error');
      });

    return () => {
      cancelled = true;
    };
  }, [fetchKey]);

  useEffect(() => {
    if (attributes.layout === 'stack') {
      setAttributes({ layout: 'uniform-2' });
    }
  }, [attributes.layout]);

  const blockProps = useBlockProps({
    className: 'sobe-product-categories-grid-editor',
  });

  const layoutOptions = [
    { value: 'bento-alternating', label: __('Bento — alternating heights', 'sobe') },
    { value: 'uniform-2', label: __('Two equal columns', 'sobe') },
    { value: 'columns-4', label: __('Four columns (wide screens)', 'sobe') },
    { value: 'hero-follow', label: __('Hero row + grid', 'sobe') },
    {
      value: 'split-tall-left',
      label: __('Tall left column + right pairs', 'sobe'),
    },
  ];

  const termsForCards = loadState === 'ready' ? terms : [];

  function toggleTerm(termId, checked) {
    if (checked) {
      if (items.some((i) => i.termId === termId)) {
        return;
      }
      setAttributes({
        items: [...items, { termId, imageId: 0 }],
      });
      return;
    }
    setAttributes({
      items: items.filter((i) => i.termId !== termId),
    });
  }

  function removeAt(index) {
    setAttributes({
      items: items.filter((_, i) => i !== index),
    });
  }

  function setItemImage(index, media) {
    const next = items.map((row, i) =>
      i === index
        ? { ...row, imageId: media && media.id ? media.id : 0 }
        : row,
    );
    setAttributes({ items: next });
  }

  function clearOverride(index) {
    setItemImage(index, null);
  }

  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Content', 'sobe')} initialOpen={false}>
          <TextControl
            label={__('Heading', 'sobe')}
            value={heading ?? ''}
            onChange={(val) => setAttributes({ heading: val })}
            placeholder={__('Section title…', 'sobe')}
            __nextHasNoMarginBottom
            __next40pxDefaultSize
          />
          <div style={{ marginTop: 12 }}>
            <TextControl
              label={__('Description', 'sobe')}
              value={paragraph ?? ''}
              onChange={(val) => setAttributes({ paragraph: val })}
              placeholder={__('Supporting text…', 'sobe')}
              __nextHasNoMarginBottom
              __next40pxDefaultSize
            />
          </div>
        </PanelBody>
        <PanelBody title={__('Layout', 'sobe')} initialOpen={true}>
          <PanelRow>
            <SelectControl
              label={__('Grid layout', 'sobe')}
              value={layout}
              options={layoutOptions}
              onChange={(val) => setAttributes({ layout: val })}
              __next40pxDefaultSize
              __nextHasNoMarginBottom
            />
          </PanelRow>
          <PanelRow>
            <ToggleControl
              label={__('Hover zoom & darken', 'sobe')}
              checked={enableHoverEffects}
              onChange={(val) => setAttributes({ enableHoverEffects: val })}
              __nextHasNoMarginBottom
            />
          </PanelRow>
        </PanelBody>
        <PanelBody title={__('Categories', 'sobe')} initialOpen={true}>
          <PanelRow>
            <Dropdown
              popoverProps={{ placement: 'bottom-start' }}
              contentClassName="sobe-product-categories-grid-editor__dropdown"
              renderToggle={({ isOpen, onToggle }) => (
                <Button
                  variant="secondary"
                  onClick={onToggle}
                  aria-expanded={isOpen}
                  __next40pxDefaultSize
                >
                  {sprintf(__('Categories (%d)', 'sobe'), items.length)}
                </Button>
              )}
              renderContent={() => (
                <div
                  className="sobe-product-categories-grid-editor__picker"
                  role="group"
                  aria-label={__('Product categories', 'sobe')}
                >
                  <CategoryPickerBody
                    loadState={loadState}
                    terms={terms}
                    termsError={termsError}
                    items={items}
                    toggleTerm={toggleTerm}
                    onRetry={() => {
                      setFetchKey((k) => k + 1);
                    }}
                  />
                </div>
              )}
            />
          </PanelRow>
          <p className="components-base-control__help" style={{ marginTop: 4 }}>
            {__(
              'Open the list and tick categories to include. Each new selection is added to the end of the grid.',
              'sobe',
            )}
          </p>
        </PanelBody>
      </InspectorControls>

      <div {...blockProps}>
        {(heading || paragraph) && (
          <div className="sobe-product-categories-grid-editor__section-header">
            {heading ? (
              <p className="sobe-product-categories-grid-editor__section-heading">{heading}</p>
            ) : null}
            {paragraph ? (
              <p className="sobe-product-categories-grid-editor__section-desc">{paragraph}</p>
            ) : null}
          </div>
        )}
        {items.length === 0 ? (
          <p style={{ margin: 0, color: 'var(--c-text-muted)' }}>
            {__('Open “Categories” in the sidebar and tick one or more categories.', 'sobe')}
          </p>
        ) : (
          <ul className="sobe-product-categories-grid-editor__cards">
            {items.map((row, index) => (
              <CategoryCardEditor
                key={`${row.termId}-${index}`}
                row={row}
                terms={termsForCards}
                onRemove={() => removeAt(index)}
                onImage={(media) => setItemImage(index, media)}
                onClearOverride={() => clearOverride(index)}
              />
            ))}
          </ul>
        )}
      </div>
    </>
  );
}
