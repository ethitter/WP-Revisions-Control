( function( $ ) {
	$( document ).ready( function() {
		var purge_button = $( '#wp_revisions_control .button.purge' ),
			post_id      = null,
			nonce        = null,
			button_text  = null;

		$( purge_button ).on( 'click', click_handler_purge );

		/**
		 *
		 */
		function click_handler_purge() {
			post_id = parseInt( $( this ).data( 'postid' ) );

			button_text = $( purge_button ).text();
			$( purge_button ).text( wp_revisions_control.processing_text );

			var confirmed = confirm( wp_revisions_control.ays );

			if ( confirmed && post_id ) {
				$.ajax({
					url: ajaxurl,
					cache: false,
					data: {
						action: wp_revisions_control.processing_text + '_purge',
						post_id: post_id,
						nonce: $( this ).data( 'nonce' )
					},
					type: 'post',
					success: ajax_request_success,
					error: ajax_request_error
				});
			} else {
				$( purge_button ).text( button_text );
			}
		}

		/**
		 *
		 */
		function ajax_request_success() {
			console.log( 'Yippee' );
			$( purge_button ).text( button_text );
		}

		/**
		 *
		 */
		function ajax_request_error() {
			console.log( 'Sad panda' );
		}
	} );
} )( jQuery );