// resize 
jQuery(function($){
	$('body').addClass('jwp_a11y_js');
	if( ! $('#a11yc_header')[0]) return;
	
	var timer = false;
	$(window).on('resize', function(){
		if( timer )
		{
			clearTimeout(timer);
		}
		timer = setTimeout(function(){
			adjust_fixed_header();
		}, 300);
	});
	$(window).on('load', function(){
		adjust_fixed_header();
	});
	
	var $header = $('#a11yc_header'),
		$header_inner = $('#a11yc_header_inner');
		$content_wrapper = $('#a11yc_checklist_wrap');
		$content = $('#wpcontent'),
		$content_inner = $('#wpcontent .postbox').first();
		header_padding = current_padding = 0;

	function adjust_fixed_header(){
		// set width
		$header_inner.css('width', $content_inner.width());
		
		// set padding
		current_padding = parseFloat($content_wrapper.css('margin-left')) + parseFloat($content.css('padding-left')) + parseFloat($content.css('margin-left'));
		if( header_padding === current_padding ) return;
		$header.css('padding-left', current_padding+'px' );
	}
	// collapse-menu trigger
	$('#collapse-button').on('click', function(){
		setTimeout(function(){
			$(window).trigger('resize');
		}, 100);
	});

	// wp-admin-bar-menu-toggle
	$('#wp-admin-bar-menu-toggle').on('click',function(){
		setTimeout(function(){
			console.log($('#wpbody').css('right'));
			$header.css('left', (parseFloat( $('#wpbody').css('right')) * -1) +'px');
		},0);
	});
	
});