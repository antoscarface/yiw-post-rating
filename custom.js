jQuery(document).ready(function($){
    
    // eseguo la chiamata AJAX al submit del form
    $('#post-rate').on( 'submit', function(e){
        e.preventDefault();
    
        var form = $(this),
            loading = form.find('img.loading'),
            rate = form.find('input[name=rate]:checked').val();
            
        // messaggio di errore se la votazione non è stata espressa
        if ( typeof rate == 'undefined' ) {
            if ( form.parent().find('p.feedback').length == 0 ) { 
                $('<p />')
                    .addClass('feedback')
                    .css('font-weight','bold')
                    .css('color','#aa0000')
                    .text('Esprimi un giudizio!')
                    .insertBefore( form.parent() );
            }
            return;
        }
            
        // visualizza l'iconcina di caricamento
        loading.show();
    
        // esegue la chiamata AJAX
        $.post(
            
            // l'indirizzo a cui fare la richiesta, wp-admin/admin-ajax.php
			ypr_vars.ajaxurl,
            
			{
				// il nome dell'action che utilizzeremo con il gancio wp_ajax_
				action : 'add_post_rating',
				
				// il voto che ha scelto l'utente
				rate : form.find('input[name=rate]:checked').val(),
                
                // il post ID per memorizzare il voto nel post corrente
                post_id : form.find('input[name=post_id]').val(),
			 
				// controllo di sicurezza tramite il nonce
				ypr_nonce : ypr_vars.nonce
			},
            
			function( response ) {
                loading.hide();              
                    
                $('.post-rate').find('p.feedback, p.form, form#post-rate').remove();
                
                // mostro il messaggio di avvenuto salvataggio della votazione
                $('<p />')
                    .addClass('feedback')
                    .css('font-weight','bold')
                    .css('color','#00aa00')
                    .text( response.feedback )
                    .insertAfter( $('.post-rate .rate-info') );     
                    
                // aggiorno i valori rispettivamente per la votazione complessiva e il numero di voti
                $('.rate-info').find('b.rate').text( response.rate );
                $('.rate-info').find('b.rate-count').text( response.count );
			},
            
            // definisco che il formato dell'output è JSON
            'json'
		);
    
    });
    
});