/*------------------------- 
Frontend related javascript
-------------------------*/

(function( $ ) {

	"use strict";

    $(document).ready( function() {
        $.ajax({
            type : "post",
            dataType : "json",
            url : superwpstocktake.ajaxurl,
            data : {
                action: "my_demo_ajax_call", 
                demo_data : 'test_data', 
                ajax_nonce_parameter: superwpstocktake.security_nonce
            },
            success: function(response) {
                console.log( response );
            }
        });
    });

	$( document ).on( 'heartbeat-send', function( event, data ){
		// Add additional data to Heartbeat data.
		data.myplugin_customfield = 'some_data';
	});

	$( document ).on( 'heartbeat-tick', function( event, data ){
		// Check for our data, and use it.
		if( ! data.myplugin_customfield_hashed ){
			return;
		}
	
		alert( 'The hash is ' + data.myplugin_customfield_hashed );
	});

})( jQuery );
