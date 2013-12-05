( function( $ ) {
	$( document ).ready( function() {
		var purge_button = $( '#' + wp_revisions_control.namespace + ' .button.purge' ),
			post_id      = null,
			nonce        = null,
			button_text  = null;

		button_text = $( purge_button ).text();

		$( purge_button ).on( 'click', click_handler_purge );

		/**
		 * Click handler for purging a post's revisions
		 */
		function click_handler_purge() {
			post_id = parseInt( $( this ).data( 'postid' ) );

			$( purge_button ).text( wp_revisions_control.processing_text );

			var confirmed = confirm( wp_revisions_control.ays );

			if ( confirmed && post_id ) {
				$.ajax({
					url: ajaxurl,
					cache: false,
					data: {
						action: wp_revisions_control.action_base + '_purge',
						post_id: post_id,
						nonce: $( this ).data( 'nonce' )
					},
					type: 'post',
					dataType: 'json',
					success: ajax_purge_request_success,
					error: ajax_purge_request_error
				});
			} else {
				$( purge_button ).text( button_text );
			}
		}

		/**
		 * User feedback when Ajax request succeeds
		 * Does not indicate that purge request succeeded
		 */
		function ajax_purge_request_success( response ) {
			if ( response.error ) {
				alert( response.error );

				$( purge_button ).text( button_text );
			} else if ( response.success ) {
				var list_table = $( 'ul.post-revisions > li' );

				$( list_table ).each( function() {
					var autosave = $( this ).text().match( wp_revisions_control.autosave );

					if ( ! autosave )
						$( this ).slideUp( 'slow' ).remove();
				} );

				$( purge_button ).fadeOut( 'slow' ).after( wp_revisions_control.nothing_text );
			}
		}

		/**
		 * Return a generic error when the Ajax request fails
		 */
		function ajax_purge_request_error() {
			alert( wp_revisions_control.error );

			$( purge_button ).text( button_text );
		}
	} );
} )( jQuery );