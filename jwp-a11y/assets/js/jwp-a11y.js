// resize
jQuery(function($){
	if( ! $('#wpcontent')[0]) return;
	$(document).resize(function(){
		console.log($('#wpcontent').css('padding-left'));
	});
)};