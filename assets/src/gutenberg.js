const apiFetch = require( '@wordpress/api-fetch' );
const { Button, Modal, TextControl } = require( '@wordpress/components' );
const { compose } = require( '@wordpress/compose' );
const { withSelect, withDispatch } = require( '@wordpress/data' );
const { PluginDocumentSettingPanel } = require( '@wordpress/edit-post' );
const { useState } = require( '@wordpress/element' );
const { __, _n, sprintf } = require( '@wordpress/i18n' );
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

		{ showPurgeButton && PurgeModal( limit, manualPurge ) }
	</PluginDocumentSettingPanel>
);

const PurgeModal = ( limit, manualPurge ) => {
	const [ isOpen, setOpen ] = useState( false );
	const openModal = () => setOpen( true );
	const closeModal = () => setOpen( false );
	const closeModalAndPurge = () => {
		closeModal();
		manualPurge();
	};
	const parsedLimit = parseInt( limit, 10 );

	const modalText = 0 === parsedLimit
		? __( 'This will remove all revisions.', 'wp_revisions_control' )
		: sprintf(
			/* translators: 1. Number of revisions to keep. */
			_n(
				'This will remove all but the most-recent revision.',
				'This will remove all but the %1$d most-recent revisions.',
				parsedLimit,
				'wp_revisions_control'
			),
			limit
		);

	return (
		<>
			<Button isSecondary onClick={ openModal }>
				{ __( 'Purge excess revisions', 'wp_revisions_control' ) }
			</Button>

			{ isOpen && (
				<Modal
					title={ __( 'Purge excess revisions', 'wp_revisions_control' ) }
					contentLabel={ modalText }
					onRequestClose={ closeModal }
				>
					<p>
						{ modalText }
					</p>

					<Button isPrimary onClick={ closeModalAndPurge }>
						{ __( 'Purge', 'wp_revisions_control' ) }
					</Button>

					&nbsp;

					<Button isSecondary onClick={ closeModal }>
						{ __( 'Cancel', 'wp_revisions_control' ) }
					</Button>
				</Modal>
			) }
		</>
	);
};

// TODO: switch to `useSelect` and `useDispatch`.
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
		withDispatch( ( dispatch, { limit }, { select } ) => {
			const manualPurge = () => {
				const postId = select( 'core/editor' ).getCurrentPostId();

				apiFetch( {
					path: `/wp-revisions-control/v1/schedule/${postId}`,
					method: 'PUT',
				} )
					.then( ( result ) => {
						let noticeType;
						let noticeText;

						if ( result ) {
							noticeType = 'success';
							noticeText = __( 'Excess revisions scheduled for removal.', 'wp_revisions_control' );
						} else {
							noticeType = 'error';
							noticeText = __( 'Failed to schedule excess revisions for removal.', 'wp_revisions_control' );
						}

						dispatch( 'core/notices' ).createNotice(
							noticeType,
							noticeText,
							{
								id: 'wp-revisions-control-scheduled-purge',
								isDismissible: true,
								type: 'snackbar',
							}
						);
					} );
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
	slug,
	{
		render: RevisionsControl,
		icon: 'backup',
	}
);
