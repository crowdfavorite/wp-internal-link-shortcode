cfplsc_show_shortcode = function($elem) {
	if ($elem.find('input').size() == 0) {
		$elem.append('<input type="text" value="' + $elem.attr('title') + '" />').find('input').keydown(function(e) {
			switch (e.which) {
				case 13: // enter
					return false;
					break;
				case 27: // esc
					jQuery('#cfplsc_post_title').focus();
					break;
			}
		}).focus().select();
	}
};
jQuery(function($) {
	$('#cfplsc_meta_box a.cfplsc_help').click(function() {
		$('#cfplsc_meta_box div.cfplsc_readme').slideToggle(function() {
			$('#cfplsc_post_title').css('background', '#fff');
		});
		return false;
	});
	$('#cfplsc_search_box').click(function() {
		$('#cfplsc_post_title').focus().css('background', '#ffc');
		return false;
	});
	$('#cfplsc_post_title').keyup(function(e) {
		form = $('#cfplsc_meta_box');
		term = $(this).val();
		// catch everything except up/down arrow
		switch (e.which) {
			case 27: // esc
				form.find('.live_search_results ul').remove();
				break;
			case 13: // enter
			case 38: // up
			case 40: // down
				break;
			default:
				if (term == '') {
					form.find('.live_search_results ul').remove();
				}
				if (term.length > 2) {
					$.get(
						Admin.index_url,
						{
							cf_action: 'cfplsc_id_lookup',
							post_title: term
						},
						function(response) {
							$('#cfplsc_meta_box div.live_search_results').html(response).find('li').click(function() {
								$('#cfplsc_meta_box .active').removeClass('active');
								$(this).addClass('active');
								cfplsc_show_shortcode($(this));
								return false;
							});
						},
						'html'
					);
				}
				break;
		}
	}).keydown(function(e) {
	// catch arrow up/down here
		form = $('#cfplsc_meta_box');
		if (form.find('.live_search_results ul li').size()) {
			switch (e.which) {
				case 13: // enter
					active = form.find('.live_search_results ul li.active');
					if (active.size()) {
						cfplsc_show_shortcode(active);
					}
					return false;
					break;
				case 40: // down
					if (!form.find('.live_search_results ul li.active').size()) {
						form.find('.live_search_results ul li:first-child').addClass('active');
					}
					else {
						form.find('.live_search_results ul li.active').next('li').addClass('active').prev('li').removeClass('active');
					}
					return false;
					break;
				case 38: // up
					if (!form.find('.live_search_results ul li.active').size()) {
						form.find('.live_search_results ul li:last-child').addClass('active');
					}
					else {
						form.find('.live_search_results ul li.active').prev('li').addClass('active').next('li').removeClass('active');
					}
					return false;
					break;
			}
		}
	});
});
