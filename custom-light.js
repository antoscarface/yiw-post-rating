jQuery(document).ready(function($){
    $('#post-rate').on( 'submit', function(e){
        e.preventDefault();
    
        $.post(
			ypr_vars.ajaxurl,
			{
				action : 'add_post_rating',      	
				rate : form.find('input[name=rate]:checked').val(),
                post_id : form.find('input[name=post_id]').val()
			},
            
			function( response ) {
                // qualcosa da eseguire se la richiesta è andata a buon fine
			}
		);            
    });             
});