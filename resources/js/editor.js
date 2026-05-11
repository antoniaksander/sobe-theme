import domReady from '@wordpress/dom-ready';
import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { ToggleControl, TextControl } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { createElement } from '@wordpress/element';

// ── Page Display panel (hero background + hide title) ─────────────────────

function PageDisplaySettings() {
  const [meta, setMeta] = useEntityProp('postType', 'page', 'meta');

  return createElement(
    PluginDocumentSettingPanel,
    { name: 'sobe-page-display', title: __('Page Display', 'sobe') },
    createElement(ToggleControl, {
      label: __('Use featured image as hero background', 'sobe'),
      help: __('Displays the featured image as a full-width banner behind the page title.', 'sobe'),
      checked: !!meta?._sobe_page_hero,
      onChange: (value) => setMeta({ ...meta, _sobe_page_hero: value }),
    }),
    createElement(ToggleControl, {
      label: __('Hide page title', 'sobe'),
      help: __('Removes the page title from the header.', 'sobe'),
      checked: !!meta?._sobe_hide_title,
      onChange: (value) => setMeta({ ...meta, _sobe_hide_title: value }),
    })
  );
}

function PageDisplayPanel() {
  const postType = useSelect((select) => select('core/editor').getCurrentPostType(), []);
  if (postType !== 'page') return null;
  return createElement(PageDisplaySettings, null);
}

// ── Post Settings panel (CTA label for blog listing) ─────────────────────

function PostSettingsContent() {
  const [meta, setMeta] = useEntityProp('postType', 'post', 'meta');

  return createElement(
    PluginDocumentSettingPanel,
    { name: 'sobe-post-settings', title: __('Post Settings', 'sobe') },
    createElement(TextControl, {
      label: __('CTA label', 'sobe'),
      help: __('Custom link text shown on the blog listing. Leave blank for the default.', 'sobe'),
      value: meta?._sobe_post_cta ?? '',
      onChange: (value) => setMeta({ ...meta, _sobe_post_cta: value }),
    })
  );
}

function PostSettingsPanel() {
  const postType = useSelect((select) => select('core/editor').getCurrentPostType(), []);
  if (postType !== 'post') return null;
  return createElement(PostSettingsContent, null);
}

domReady(() => {
  registerPlugin('sobe-page-display', { render: PageDisplayPanel });
  registerPlugin('sobe-post-settings', { render: PostSettingsPanel });
});
