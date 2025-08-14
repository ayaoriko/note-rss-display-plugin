import './style.scss';
import './editor.scss'; 
import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import save from './save';
import metadata from '../block.json';

registerBlockType( metadata.name, {
	edit: Edit,
	save,
} );