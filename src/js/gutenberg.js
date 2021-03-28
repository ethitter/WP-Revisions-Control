const { Button, TextControl } = require( '@wordpress/components' );
const { compose } = require( '@wordpress/compose' );
const { withSelect, withDispatch } = require( '@wordpress/data' );
const { PluginDocumentSettingPanel } = require( '@wordpress/edit-post' );
const { __ } = require( '@wordpress/i18n' );
const { registerPlugin } = require( '@wordpress/plugins' );

const metaKey = '_wp_rev_ctl_limit';
const slug = 'wp-revisions-control';

const Render = ( { limit, manualPurge, showPurgeButton, updateMeta } ) => (
	<PluginDocumentSettingPanel
		name={ slug }
		title={ __( 'WP Revisions Control', 'wp_revisions_control' ) }
		className={ slug }
	>
		<TextControl
			label={ __( 'Number of revisions to retain:', 'wp_revisions_control' ) }
			help={ __( 'Leave blank to keep all revisions.', 'wp_revisions_control' ) }
			value={ limit }
			onChange={ updateMeta }
		/>

		{ showPurgeButton && (
			<Button onClick={ manualPurge }>
				{ __( 'Purge excess revisions', 'wp_revisions_control' ) }
			</Button>
		) }

	</PluginDocumentSettingPanel>
);

const RevisionsControl = compose(
	[
		withSelect( ( select ) => {
			const {
				getCurrentPostRevisionsCount,
				getEditedPostAttribute,
			} = select( 'core/editor' );

			const count = getCurrentPostRevisionsCount();
			const limit = getEditedPostAttribute(
				'meta'
			)[ metaKey ];

			const showPurgeButton = Boolean( limit )
				&& count > parseInt( limit );

			return {
				limit,
				showPurgeButton,
			};
		} ),
		withDispatch( ( dispatch, { limit } ) => {
			const manualPurge = () => {
				// TODO: reuse the existing Ajax endpoint?
				console.log( 'Purging!', limit );
			};

			const updateMeta = ( value ) => {
				dispatch( 'core/editor' ).editPost( {
					meta: {
						[ metaKey ]: value,
					},
				} );
			};

			return {
				manualPurge,
				updateMeta,
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
