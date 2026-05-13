const { registerBlockType } = wp.blocks;
import metadata from './block.json';
import Edit from './edit';

registerBlockType(metadata.name, {
  edit: Edit,
  save: () => null, // Dynamic block: rendered in Blade
});
