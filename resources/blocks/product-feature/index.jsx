import './style.scss';
import './editor.scss';
import metadata from './block.json';
import Edit from './edit.jsx';
import save from './save.jsx';

const { registerBlockType } = wp.blocks;

registerBlockType(metadata.name, {
  ...metadata,
  edit: Edit,
  save,
});
