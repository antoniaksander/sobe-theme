const { useBlockProps, InspectorControls, RichText } = wp.blockEditor;
const { PanelBody, Button } = wp.components;
const { __ } = wp.i18n;
const { useState } = wp.element;

import './editor.scss';

export default function Edit({ attributes, setAttributes }) {
  const { faqs } = attributes;
  const [openIndex, setOpenIndex] = useState(null);

  const blockProps = useBlockProps({ className: 'sobe-faq' });

  function updateFaq(index, patch) {
    setAttributes({ faqs: faqs.map((f, i) => (i === index ? { ...f, ...patch } : f)) });
  }

  function addFaq() {
    setAttributes({ faqs: [...faqs, { question: '', answer: '' }] });
    setOpenIndex(faqs.length);
  }

  function removeFaq(index) {
    setAttributes({ faqs: faqs.filter((_, i) => i !== index) });
    setOpenIndex((prev) => {
      if (prev === index) return null;
      if (prev > index) return prev - 1;
      return prev;
    });
  }

  function toggle(index) {
    setOpenIndex((prev) => (prev === index ? null : index));
  }

  return (
    <>
      <InspectorControls>
        <PanelBody title={__('FAQ Items', 'sobe')} initialOpen={true}>
          <p style={{ fontSize: '12px', color: '#757575', margin: '0 0 12px' }}>
            {__('Click the chevron in the block to expand an item and edit inline.', 'sobe')}
          </p>
          <Button variant="secondary" onClick={addFaq} style={{ width: '100%' }}>
            {__('+ Add FAQ Item', 'sobe')}
          </Button>
        </PanelBody>
      </InspectorControls>

      <div {...blockProps}>
        <div className="faq__header">
          <h2 className="faq__title">{__('Frequently Asked Questions', 'sobe')}</h2>
        </div>

        <div className="faq__items">
          {faqs.length === 0 ? (
            <p className="faq__empty">
              {__('No FAQ items yet. Add your first item using the sidebar.', 'sobe')}
            </p>
          ) : (
            faqs.map((faq, index) => {
              const isOpen = openIndex === index;
              return (
                <div key={index} className={`sobe-faq__item faq__item${isOpen ? ' is-open' : ''}`}>

                  {/* ── Question row ── */}
                  <div className="faq-editor__question-row">
                    <RichText
                      tagName="span"
                      className="faq__question-text"
                      value={faq.question}
                      onChange={(val) => updateFaq(index, { question: val })}
                      placeholder={__('Enter question…', 'sobe')}
                      allowedFormats={[]}
                    />
                    <button
                      type="button"
                      className="faq-editor__toggle"
                      onClick={() => toggle(index)}
                      aria-label={isOpen ? __('Collapse', 'sobe') : __('Expand', 'sobe')}
                    >
                      <svg viewBox="0 0 24 24" fill="none" className={`faq__chevron${isOpen ? ' faq__chevron--open' : ''}`}>
                        <path d="M6 9L12 15L18 9" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                      </svg>
                    </button>
                    <button
                      type="button"
                      className="faq-editor__remove"
                      onClick={() => removeFaq(index)}
                      aria-label={__('Remove item', 'sobe')}
                    >
                      <svg viewBox="0 0 24 24" fill="none">
                        <path d="M6 18L18 6M6 6l12 12" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                      </svg>
                    </button>
                  </div>

                  {/* ── Answer (shown when open) ── */}
                  {isOpen && (
                    <div className="sobe-faq__answer-wrapper faq__answer-wrapper faq-editor__answer">
                      <div className="sobe-faq__answer-inner faq__answer-inner">
                        <RichText
                          tagName="p"
                          className="faq__answer-text"
                          value={faq.answer}
                          onChange={(val) => updateFaq(index, { answer: val })}
                          placeholder={__('Enter answer…', 'sobe')}
                          allowedFormats={['core/bold', 'core/italic', 'core/link']}
                        />
                      </div>
                    </div>
                  )}

                </div>
              );
            })
          )}

          <Button variant="secondary" onClick={addFaq} style={{ width: '100%', marginTop: '12px' }}>
            {__('+ Add FAQ Item', 'sobe')}
          </Button>
        </div>
      </div>
    </>
  );
}
