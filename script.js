(function($) {
	var this2 = this;
	$( ".search" ).autocomplete({
		source: function( request, response ) {
			$.ajax({
				dataType: "json",
				type : "GET",
				url: ac.ajax_url,
				data: {
					term : request.term,
					action : 'location_search'
				},
				success: function(data) {
					response(data);
				},
				error: function(data) {
					alert('Oops!')
				}
			});
		},
		select: function(event, ui) {
			window.location.href = ui['item']['slug'];
		},
		search: function( event, ui ) {
			$('.searching').show();
		},
		open: function( event, ui ) {
			$('.searching').hide();	
		},
		close: function( event, ui ) {
			$('.searching').hide();
		},
	    minLength: 3
	});
})(jQuery);