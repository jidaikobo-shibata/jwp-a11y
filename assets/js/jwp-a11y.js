// resize 
jQuery(function($){
	$('body').addClass('jwp_a11y_js');
	if( ! $('#a11yc_header')[0]) return;

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