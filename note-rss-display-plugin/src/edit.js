import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, RangeControl, Placeholder, ToggleControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { Fragment } from '@wordpress/element';
import metadata from '../block.json';
export default function Edit( { attributes, setAttributes } ) {
	const { feed_url, items, slide } = attributes;
	const blockProps = useBlockProps();

	const inspectorControls = (
		<InspectorControls>
			<PanelBody title={ __( 'フィード設定', 'note-rss-block' ) }>
				<TextControl
					label={ __( 'URL', 'note-rss-block' ) }
					value={ feed_url }
					onChange={ ( newUrl ) => setAttributes( { feed_url: newUrl } ) }
					help={ __( '例: https://note.com/your_id', 'note-rss-block' ) }
				/>
				<RangeControl
					label={ __( '表示件数', 'note-rss-block' ) }
					value={ items }
					onChange={ ( newItems ) => setAttributes( { items: newItems } ) }
					min={ 1 }
					max={ 30 }
				/>
				<ToggleControl
					label={ __( 'スライダー機能', 'note-rss-block' ) }
					checked={ slide === 'on' }
					onChange={ ( newValue ) =>
						setAttributes( { slide: newValue ? 'on' : 'off' } )
					}
					help={
						slide === 'on'
							? __( 'スライダーは有効になっています。', 'note-rss-block' )
							: __( 'スライダーは無効になっています。', 'note-rss-block' )
					}
				/>
			</PanelBody>
		</InspectorControls>
	);

	return (
		<div { ...blockProps }>
			{ inspectorControls }
			{ ! feed_url ? (
				<Placeholder
					label={ __( 'note.com RSS表示', 'note-rss-block' ) }
					instructions={ __( 'サイドバーでnote.comのURLを入力してください。', 'note-rss-block' ) }
				/>
			) : (
				<ServerSideRender
					block={ metadata.name }
					attributes={ attributes }
				/>
			) }
		</div>
	);
}