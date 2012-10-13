jQuery(document).ready(function() {
	jQuery('[role="ajax-loader"]').each(function() {
		var el = jQuery(this);
		setTimeout(function() {
			el.load(el.attr('data-url'));
		}, el.attr('data-delay') ? el.attr('data-delay') : 0);
	});
});
