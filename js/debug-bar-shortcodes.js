jQuery(document).ready(function () {

	var dbsTable = jQuery('#debug-bar-shortcodes');

	/* Make sure the spinner also works in the front-end */
	dbsTable.find('span.spinner').css({ 'background-image': 'url("' + i18n_db_shortcodes.spinner + '")' });


	/* Show/hide action links */
	dbsTable.on('mouseenter.dbs-action-links', 'td.column-title', function() {
		jQuery(this).find('div.row-actions').css({ 'visibility': 'visible' });
		})
		.on('mouseleave.dbs-action-links', 'td.column-title', function() {
			jQuery(this).find('div.row-actions').css({ 'visibility': 'hidden' });
		});


	/* Show/hide details */
	dbsTable.on('click', 'a.debug-bar-shortcodes-view-details', function( event ) {
		event.preventDefault();
		var targetTr = jQuery(this).closest('tr').next('tr.debug-bar-shortcodes-details');

		if( jQuery(this).text() == i18n_db_shortcodes.view_details ) {
			targetTr.show();
			jQuery(this).text( i18n_db_shortcodes.hide_details );
		}
		else {
			targetTr.hide();
			jQuery(this).text( i18n_db_shortcodes.view_details );
		}
	});


	/* Show/hide group of found uses */
	dbsTable.on('click', 'a.debug-bar-shortcodes-view-use', function( event ) {
		event.preventDefault();
		var targetTr = jQuery(this).closest('tr').next('tr');
		if( targetTr.hasClass('debug-bar-shortcodes-details' ) ) {
			targetTr = targetTr.next('tr');
		}
		if( targetTr.hasClass('debug-bar-shortcodes-uses' ) ) {
			if( jQuery(this).text() == i18n_db_shortcodes.view_use ) {
				targetTr.show();
				jQuery(this).text( i18n_db_shortcodes.hide_use );
			}
			else {
				targetTr.hide();
				jQuery(this).text( i18n_db_shortcodes.view_use );
			}
		}
	});


	/* Retrieve & show details if there were none */
	dbsTable.on('click', 'a.debug-bar-shortcodes-get-details', function( event ) {
		event.preventDefault();
		var eventTarget = jQuery(this);
		var targetShortcode = this.hash.substring(1);
		var spinner = eventTarget.closest('td.column-title').find('span.spinner');
		spinner.show();

		jQuery.ajax({
			url:	(ajaxurl) ? ajaxurl : i18n_db_shortcodes.ajaxurl,
			type:	'post',
			data:	{
				'action':		'debug-bar-shortcodes-retrieve',
				'dbs-nonce':	i18n_db_shortcodes.nonce,
				'shortcode':	targetShortcode
			},
			success: function( response ) {
				// Handle errors
				// -1 is nonce error, no proper response received
				// 0 no wp ajax action hook found for this action
				if( 'string' == typeof( response ) ) {
					// Remove all retrieve details links to prevent user trying it again.
					jQuery('a.debug-bar-shortcodes-get-details').remove();
					alert( i18n_db_shortcodes.illegal );
				}
				else {
					var resData = wpAjax.parseAjaxResponse(response, 'ajax-response');

					if( !resData.responses || 1 > resData.responses.length || resData.errors ) {
						// Didn't receive a proper response or received a WP error response
						console.log( 'Received response: ' + response  );
						alert( i18n_db_shortcodes.error );
					}
					else if( '1' != resData.responses[0].id || !resData.responses[0].data || 0 === resData.responses[0].data.length ) {
						// No info found
						/* @todo Usability: row actions are hidden on mouseleave, so this feedback may not be seen
						   figure out a way to make this easier to see
						   (make row actions visible & highlight kind of thing, but is not so easy to do */
						eventTarget.replaceWith(i18n_db_shortcodes.no_details);
					}
					else {
						// Found some ;-)
						var nrOfColumns = ( eventTarget.closest('tr').find('td').length - 1 );
						resData = resData.responses[0];
						var supplemental = resData.supplemental;
						resData = resData.data;
						resData = resData.replace( /\{colspan\}/g, nrOfColumns );

						if( eventTarget.closest('tr').hasClass('even') ) {
							resData = jQuery( resData ).addClass('even');
						}

						eventTarget.closest('tr').after( resData );
						eventTarget.text(i18n_db_shortcodes.view_details)
							.removeClass('debug-bar-shortcodes-get-details')
							.addClass('debug-bar-shortcodes-view-details').click();
						if( supplemental.url_link ) {
							eventTarget.closest('div.row-actions').append(supplemental.url_link);
						}
					}
				}
				spinner.hide();
			},
			error: handleAjaxError
		});
	});


	/* Find all uses of the shortcodes */
	dbsTable.on('click', 'a.debug-bar-shortcodes-find', function( event ) {
		event.preventDefault();
		var eventTarget = jQuery(this);
		var targetShortcode = this.hash.substring(1);
		var spinner = eventTarget.closest('td.column-title').find('span.spinner');
		spinner.show();

		jQuery.ajax({
			url:	(ajaxurl) ? ajaxurl : i18n_db_shortcodes.ajaxurl,
			type:	'post',
			data:	{
				'action':		'debug-bar-shortcodes-find',
				'dbs-nonce':	i18n_db_shortcodes.nonce,
				'shortcode':	targetShortcode
			},
			success: function( response ) {
				// Handle errors
				// -1 is nonce error, no proper response received
				// 0 no wp ajax action hook found for this action
				if( 'string' == typeof( response ) ) {
					// Remove all find links to prevent user trying it again.
					jQuery('a.debug-bar-shortcodes-find').remove();
					alert( i18n_db_shortcodes.illegal );
				}
				else {
					var resData = wpAjax.parseAjaxResponse(response, 'ajax-response');

					if( !resData.responses || 1 > resData.responses.length || resData.errors ) {
						// Didn't receive a proper response or received a WP error response
						console.log( 'Received response: ' + response  );
						alert( i18n_db_shortcodes.error );
					}
					else if( '1' != resData.responses[0].id || !resData.responses[0].data || 0 === resData.responses[0].data.length ) {
						// No uses found
						/* @todo Usability: row actions are hidden on mouseleave, so this feedback may not be seen
						   figure out a way to make this easier to see
						   (make row actions visible & highlight kind of thing, but is not so easy to do */
						eventTarget.replaceWith(i18n_db_shortcodes.not_in_use);
					}
					else {
						// Found some ;-)
						var nrOfColumns = ( eventTarget.closest('tr').find('td').length - 1 );
						resData = resData.responses[0];
						resData = resData.data;
						resData = resData.replace( /\{colspan\}/g, nrOfColumns );
	
						var nextTr = eventTarget.closest('tr').next('tr');
						if( nextTr.hasClass('debug-bar-shortcodes-details') ) {
							nextTr = nextTr.next('tr');
						}
	
						if( eventTarget.closest('tr').hasClass('even') ) {
							resData = jQuery( resData ).addClass('even');
						}

						nextTr.before( resData );
						eventTarget.text(i18n_db_shortcodes.hide_use)
							.removeClass('debug-bar-shortcodes-find')
							.addClass('debug-bar-shortcodes-view-use');
					}
				}

				spinner.hide();
			},
			error: handleAjaxError
		});
	});
	
	function handleAjaxError( response ) {
		/* Triggered by http errors and by various jQuery errors such as:
		   - 'junk after document element'
		   - 'not well-formed'
		   - 'undefined entity'
		*/
		if( 'undefined' !== typeof( response ) ) { console.log( 'Received response: ' + response  ); }
		dbsTable.find('span.spinner').hide();
		alert( i18n_db_shortcodes.error );
	}
});