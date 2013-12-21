jQuery(document).ready(function () {

	var dbsTable = jQuery('#debug-bar-shortcodes');

	/* Make sure the spinner also works in the front-end */
	dbsTable.find('span.spinner').css({ 'background-image': 'url("' + i18n_db_shortcodes.spinner + '")' });




	/* Show/hide action links */
	dbsTable.on('mouseenter.dbs-action-links', 'td.column-title', function() {
		jQuery(this).find('div.row-actions').css({ 'visibility': 'visible' });
		})
		.on('mouseleave', 'td.column-title', function() {
			jQuery(this).find('div.row-actions').css({ 'visibility': 'hidden' });
		});


	/* Show/hide existing details */
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


	/* Show/hide existing group of found uses */
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
//		jQuery(this).siblings('.spinner').show();
		var spinner = eventTarget.parent().find('span.spinner');
		spinner.show();

		var targetShortcode = this.hash.substring(1);

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
				if( response === '-1' ) {
				// Remove all retrieve details links to prevent user trying it again.
					jQuery('a.debug-bar-shortcodes-get-details').remove();
					alert( i18n_db_shortcodes.illegal );
				}
				else {
					var resData = wpAjax.parseAjaxResponse(response, 'ajax-response');
//					var resId = resData.id;
//console.log( 'id: ' + resData.responses[0].id );
//console.log( 'data: ' + resData.responses[0].data );

					if( !resData.responses || resData.responses.length < 1 ) {
						// Didn't receive a proper response
						alert( i18n_db_shortcodes.php_error );
					}
//					else if( resId != '1' || resData.responses[0].data === '' ) {
					else if( resData.responses[0].id != '1' || !resData.responses[0].data || resData.responses[0].data.length === 0 ) {
						// No info found
						eventTarget.replaceWith(i18n_db_shortcodes.no_details);
					}
					else {
						// Found some ;-)
						// @todo May be add 'view online' link if we have a url
						var nrOfColumns = ( eventTarget.closest('tr').find('td').length - 1 );
						resData = resData.responses[0];
						resData = resData.data;
						resData = resData.replace( /\{colspan\}/g, nrOfColumns );

						if( eventTarget.closest('tr').hasClass('even') ) {
							resData = jQuery( resData ).addClass('even');
						}

						eventTarget.closest('tr').after( resData );
						eventTarget.text(i18n_db_shortcodes.view_details)
							.removeClass('debug-bar-shortcodes-get-details')
							.addClass('debug-bar-shortcodes-view-details').click();
					}
				}
				spinner.hide();
			},
			error: function() {
				spinner.hide();
				alert( i18n_db_shortcodes.failed );
			}
		});
	});


	/* Find all uses of the shortcodes */
	// @todo: make sure row-actions stay visible during ajax call & make them responsive again after
	// @todo: highlight on no uses response
	dbsTable.on('click', 'a.debug-bar-shortcodes-find', function( event ) {
		event.preventDefault();
		
		var eventTarget = jQuery(this);

		var spinner = eventTarget.parent().find('span.spinner');
		spinner.show();
//		eventTarget.closest('div.row-actions').css({ 'visibility': 'visible !important' });

		var targetShortcode = this.hash.substring(1);

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
				if( response === '-1' ) {
					// Remove all find links to prevent user trying it again.
					jQuery('a.debug-bar-shortcodes-find').remove();
					alert( i18n_db_shortcodes.illegal );
				}
				else {
					var resData = wpAjax.parseAjaxResponse(response, 'ajax-response');

					if( !resData.responses || resData.responses.length < 1 ) {
						// Didn't receive a proper response
						alert( i18n_db_shortcodes.php_error );
					}
//					else if( resId != '1' || resData.responses[0].data === '' ) {
					else if( resData.responses[0].id != '1' || !resData.responses[0].data || resData.responses[0].data.length === 0 ) {
//console.log( resData.responses[0].data );
						// No uses found
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
//				eventTarget.closest('div.row-actions').css({ 'visibility': 'visible' });
			},
			error: function() {
				spinner.hide();
				alert( i18n_db_shortcodes.failed );
//				eventTarget.closest('div.row-actions').css({ 'visibility': 'visible' });
			}
		});
		
	});


/*	jQuery('form#find_form').submit(function () {
		 var found = jQuery(this);
		found.find('input[type="submit"]').val('Processing...').attr('disabled', 'disabled');
		jQuery('#result_shortcodes').html('Please waiting...');
		jQuery.ajax({
			url: (ajaxurl) ? ajaxurl : i18n_db_shortcodes.ajaxurl,
			type: 'post',
			data: found.serialize(),
			success: function (rs) {
				found.find('input[type="submit"]').val('find').removeAttr('disabled');
				jQuery('#result_shortcodes').html(rs);
			}
		});
		return false;
	});

		jQuery.ajax({
			type : 'POST',
			url : (ajaxurl) ? ajaxurl : i18n_plugin_notes.ajaxurl,
			data : {
				'action': i18n_plugin_notes.prefix + 'save_note',
				'wp-pn_nonce': jQuery('input[name=' + i18n_plugin_notes.prefix + 'nonce]').val(),
//				'wp-pn_nonce': i18n_plugin_notes.prefix + 'nonce',
				'form':	  postElms
//				'form2':	  postElms2
			}
//			success : function(xml) { plugin_note_saved(xml, note_elms); },
//			error : function(xml) { plugin_note_error(xml, note_elms); }
		});
	
		return false;


			function( response ) {
				var res = wpAjax.parseAjaxResponse(response, 'ajax-response');
				jQuery.each( res.responses, function() {
					parent.find('.dqpw-quote-wrapper').replaceWith(this.supplemental.quote);
					i18n_demo_quotes.currentQuote[parentId] = this.supplemental.quoteid;
				});
			}
			
	// Parse the response
	response = wpAjax.parseAjaxResponse(xml);
	

	response = response.responses[0];
	
	// Add/Delete new content
	note_elements.form.find('.waiting').hide();
	note_elements.box.parent().after(response.data);
	note_elements.box.parent().remove();
	note_elements.form.hide('normal');

	*/






/*
a.debug-bar-shortcodes-view-details'
a.debug-bar-shortcodes-get-details'
a.debug-bar-shortcodes-find'


tr.debug-bar-shortcodes-details

*/

/*
i18n_db_shortcodes

			$strings = array(
				'ajaxurl'			=> admin_url( 'admin-ajax.php' ),
				'hide_details'		=> __( 'Hide details', self::DBS_NAME ),
				'view_details'		=> __( 'View details', self::DBS_NAME ),
				'hide_use'			=> __( 'Hide Uses', self::DBS_NAME ),
				'view_use'			=> __( 'View Uses', self::DBS_NAME ),
				'nonce'				=> wp_create_nonce( self::DBS_NAME ),
			);
*/


});