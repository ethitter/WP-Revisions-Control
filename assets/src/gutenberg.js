const apiFetch = require( '@wordpress/api-fetch' );
const { Button, Modal, TextControl } = require( '@wordpress/components' );
const { compose } = require( '@wordpress/compose' );
const { withSelect, withDispatch } = require( '@wordpress/data' );
const { PluginDocumentSettingPanel } = require( '@wordpress/edit-post' );
const { useState } = require( '@wordpress/element' );
const { __, _n, sprintf } = require( '@wordpress/i18n' );
const { registerPlugin } = require( '@wordpress/plugins' );

const metaKey = window.wpRevisionsControlBlockEditorSettings.metaKey;
const slug = 'wp-revisions-control';

/**
 * The settings panel for the plugin.
 *
 * @param {Object}   props                 Component props.
 * @param {number}   props.limit           Number of revisions to keep.
 * @param {Function} props.manualPurge     Callback to manually purge revisions.
 * @param {boolean}  props.showPurgeButton Whether there are enough revisions to
 *                                         show the purge button.
 * @param {Function} props.updateMeta      Callback to update the revisions-limit for
 *                                         this post.
 * @return {JSX.Element} Sidebar panel.
 */
const Render = ( { limit, manualPurge, showPurgeButton, updateMeta } ) => (
	<PluginDocumentSettingPanel
		name={ slug }
		title={ __( 'WP Revisions Control', 'wp_revisions_control' ) }
		className={ slug }
	>
		<TextControl
			label={ __(
				'Number of revisions to retain:',
				'wp_revisions_control'
			) }
			help={ __(
				'Leave blank to keep all revisions.',
				'wp_revisions_control'
			) }
			value={ limit }
			onChange={ updateMeta }
		/>

		{ showPurgeButton && PurgeModal( limit, manualPurge ) }
	</PluginDocumentSettingPanel>
);

/**
 * Modal to confirm and trigger manual purge of excess revisions.
 *
 * @param {number}   limit       Number of revisions to keep.
 * @param {Function} manualPurge Callback to manually purge revisions.
 * @return {JSX.Element} Modal to confirm and trigger manual purge of excess
 *                       revisions.
 */
const PurgeModal = ( limit, manualPurge ) => {
	const [ isOpen, setOpen ] = useState( false );
	const openModal = () => setOpen( true );
	const closeModal = () => setOpen( false );
	const closeModalAndPurge = () => {
		closeModal();
		manualPurge();
	};
	const parsedLimit = parseInt( limit, 10 );

	const modalText =
		0 === parsedLimit
			? __( 'This will remove all revisions.', 'wp_revisions_control' )
			: // eslint-disable-next-line @wordpress/valid-sprintf
			  sprintf(
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
					title={ __(
						'Purge excess revisions',
						'wp_revisions_control'
					) }
					contentLabel={ modalText }
					onRequestClose={ closeModal }
				>
					<p>{ modalText }</p>
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

/**
 * Higher order component to render plugin's sidebar panel.
 */
const RevisionsControl = compose( [
	withSelect( ( select ) => {
		const { getCurrentPostRevisionsCount, getEditedPostAttribute } =
			select( 'core/editor' );

		const count = getCurrentPostRevisionsCount();
		const limit = getEditedPostAttribute( 'meta' )[ metaKey ];

		const showPurgeButton = Boolean( limit ) && count > parseInt( limit );

		return {
			limit,
			showPurgeButton,
		};
	} ),
	withDispatch( ( dispatch, { limit }, { select } ) => {
		const manualPurge = () => {
			const postId = select( 'core/editor' ).getCurrentPostId();

			apiFetch( {
				path: `/wp-revisions-control/v1/schedule/${ postId }/${ parseInt(
					limit,
					10
				) }`,
				method: 'PUT',
			} ).then( ( result ) => {
				let noticeType;
				let noticeText;

				if ( result ) {
					noticeType = 'success';
					noticeText = __(
						'Excess revisions scheduled for removal.',
						'wp_revisions_control'
					);
				} else {
					noticeType = 'error';
					noticeText = __(
						'Failed to schedule excess revisions for removal.',
						'wp_revisions_control'
					);
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
] )( Render );

registerPlugin( slug, {
	render: RevisionsControl,
	icon: 'backup',
} );
