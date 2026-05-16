const { registerBlockType } = wp.blocks;

import metadata from './block.json';
import Edit from './edit.jsx';
import save from './save.jsx';

import './style.scss';

registerBlockType(metadata, {
  edit: Edit,
  save,
});
