const { useBlockProps, RichText } = wp.blockEditor;
const { __ } = wp.i18n;

export default function Edit({ attributes, setAttributes }) {
  const blockProps = useBlockProps();

  return (
    <section {...blockProps}>
      <RichText
        tagName="p"
        value={attributes.content}
        placeholder={__('Add example content...', 'sobe')}
        onChange={(content) => setAttributes({ content })}
      />
    </section>
  );
}
