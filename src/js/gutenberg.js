const { TextControl } = require( '@wordpress/components' );
const { compose } = require( '@wordpress/compose' );
const { withSelect, withDispatch } = require( '@wordpress/data' );
const { PluginDocumentSettingPanel } = require( '@wordpress/edit-post' );
const { __ } = require( '@wordpress/i18n' );
const { registerPlugin } = require( '@wordpress/plugins' );

const metaKey = '_wp_rev_ctl_limit';

const Render = ( { limit, update } ) => (
	<PluginDocumentSettingPanel
		name="wp-revisions-control"
		title={ __( 'WP Revisions Control', 'wp_revisions_control' ) }
		className="wp-revisions-control"
	>
		<TextControl
			label={ __( 'Number of revisions to retain.', 'wp_revisions_control' ) }
			value={ limit }
			onChange={ update }
		/>
	</PluginDocumentSettingPanel>
);

const RevisionsControl = compose(
	[
		withSelect( ( select ) => {
			const limit = select( 'core/editor' ).getEditedPostAttribute(
				'meta'
			)[ metaKey ];

			return {
				limit,
			};
		} ),
		withDispatch( ( dispatch ) => {
			const update = ( value ) => {
				dispatch( 'core/editor' ).editPost( {
					meta: {
						[ metaKey ]: value,
					},
				} );
			};

			return {
				update,
			};
		} ),
	]
)( Render );

registerPlugin(
	'plugin-document-setting-panel-demo',
	{
		render: RevisionsControl,
		icon: 'backup',
	}
);
