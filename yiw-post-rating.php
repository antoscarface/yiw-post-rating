<?php
/*
Plugin Name: YIW Post Rating
Plugin URI: http://www.yourinspirationweb.com/
Description: Aggiunge possibilità di poter valutare un articolo tramite un sistema di votazione da 1 a 5
Version: 1.0
Author: Antonino Scarfì & YIW
Author URI: http://www.yourinspirationweb.com/
License: GPLv2 or later
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

// Make sure we don't expose any info if called directly
if ( !defined( 'ABSPATH' ) ) wp_die( __( 'This file cannot be called directly!', 'ypr' ) );

/**
 * Dentro questo gancio definisco l'HTML da inserire dopo il contenuto dell'articolo.
 * 
 * Controllo se effettivamente mi trovo dentro un articolo, tramite il controllo a prima riga
 * successivamente stampo l'HTML se tutti i controlli sono stati passati correttamente.   
 */ 
function ypr_add_stars( $content ) {
    global $post;
    
    // controllo per il post type
    if ( ! isset( $post->post_type ) || $post->post_type != 'post' )  return $content;
    
    // controllo anche se siamo all'interno di un post valido, controllando l'esistenza di un ID valido
    if ( ! isset( $post->ID ) ) return $content;
    
    // recupero l'attuale votazione memorizzata all'interno del post
    $post_rate    = floatval( get_post_meta( $post->ID, '_post_rate', true ) );
    $rating_count = intval( get_post_meta( $post->ID, '_post_rate_count', true ) );
    
    ob_start();
    ?>
    
    <div class="post-rate">
    
        <?php if ( ! empty( $post_rate ) ) : ?>
        <p class="rate-info">
            <?php _e( 'Votazione', 'ypr' ) ?>: <b class="rate"><?php echo number_format( $post_rate, 1, ',', '.' ) ?></b> 
            <?php _e( 'su', 'ypr' ) ?> <b class="rate-count"><?php echo $rating_count ?></b> <?php echo _n( 'voto', 'voti', $rating_count, 'ypr' ) ?>.
        </p>
        <?php endif; ?>
        
        <div class="rating">    
            <p class="form">
                <small><?php _e( "Vota l' articolo", 'ypr' ) ?></small>:  
            </p>
                
            <form method="post" id="post-rate">
                <label><input type="radio" name="rate" value="5" /> <small><?php _e( 'Spettacolare!', 'ypr' ) ?></small></label><br />
                <label><input type="radio" name="rate" value="4" /> <small><?php _e( "Bell'articolo.", 'ypr' ) ?></small></label><br />
                <label><input type="radio" name="rate" value="3" /> <small><?php _e( 'Così così..', 'ypr' ) ?></small></label> <br />
                <label><input type="radio" name="rate" value="2" /> <small><?php _e( 'Mah..', 'ypr' ) ?></small></label><br />
                <label><input type="radio" name="rate" value="1" /> <small><?php _e( 'Non ci ho capito niente!', 'ypr' ) ?></small></label><br />
                
                <br />
                           
                <input type="hidden" name="post_id" value="<?php echo $post->ID ?>" />                                                    
                <input type="submit" value="<?php _e( 'Vota!', 'ypr' ) ?>" />
                <img src="<?php echo plugins_url( 'loading.gif', __FILE__ ) ?>" style="display:none;" class="loading" />
            </form>
        </div>
        
    </div>
    
    <?php
    
    return $content . ob_get_clean();
}
add_filter( 'the_content', 'ypr_add_stars' );

/**
 * Metto in coda i file Javascript da aggiungere per eseguire la chiamata AJAX.
 * 
 * - wp_enqueue_script si limita ad aggiungere nell'head il file javascript con dentro
 * definito il codice per eseguire la chiamata AJAX:
 * 
 * - wp_localizate_script viene utilizzato per definire una variabile javascript globale
 * all'interno della pagina, utile poi all'inteno del file custom.js per richiamare
 * determinati valori di cui altrimenti non avremmo possibilità di sapere, come
 * l'URL di admin-ajax.php e il nonce.        
 */
function ypr_enqueue_assets() {
    wp_enqueue_script( 'ypr-custom', plugins_url( 'custom.js' , __FILE__ ), array( 'jquery' ) ); 
    wp_localize_script( 'ypr-custom', 'ypr_vars', array(
        'ajaxurl'   => admin_url( 'admin-ajax.php' ),
        'nonce'     => wp_create_nonce( 'ypr-rate' ))
    );                               
}
add_action( 'wp_enqueue_scripts', 'ypr_enqueue_assets' );

/**
 * Il gancio che si occuperà di elaborare la richiesta AJAX
 * 
 * Questo gancio verrà automaticamente eseguito da Wordpress per noi tramite il
 * il gancio che abbiamo definito da codice Javascript (nel nostro caso "add_post_rating"),
 * che servirà a wordpress per identificare il gancio da eseguire non appena la richiesta
 * AJAX deve essere elaborata.
 * 
 * Wordpress altro non fa che richiamare quindi un action che abbia come nome: 
 * "wp_ajax_add_post_rating", dove per "add_post_rating" si intende il campo 'action'
 * che abbiamo passato da javascript.
 * 
 * Il tutto verrà eseguito soltanto se verrà passato il controllo di sicurezza
 * tramite nonce, per verificare correttamente se siamo autorizzati ad eseguire
 * questo codice via AJAX.         
 */
function ypr_add_rate_ajax() {
    // controlla se siamo autorizzati ad eseguire questa chiamata AJAX
    // come primo paramentro bisogna passare il valore nonce creato durante l'invio della richiesta AJAX
    // il secondo parametro indica il nome dell'azione che ha creato il valore nonce (creato con wp_create_nonce)
	if ( ! wp_verify_nonce( $_REQUEST['ypr_nonce'], 'ypr-rate' ) )
		die ( 'Non autorizzato!');
        
    /**
     * Da qui in poi possiamo scrivere il nostro codice che ci permetterà di memorizzare
     * la votazione dell'articolo come post meta dell'articolo
     */
     
    if ( ! isset( $_REQUEST['rate'] ) ) die();
    
    $user_rate = intval( $_REQUEST['rate'] );
    $post_id   = intval( $_REQUEST['post_id'] );  
    
    // recupero l'attuale votazione memorizzata all'interno del post
    $post_rate    = floatval( get_post_meta( $post_id, '_post_rate', true ) );
    $rating_count = intval( get_post_meta( $post_id, '_post_rate_count', true ) );
    
    // se c'è già un valore memorizzato, calcola il voto insieme a quello dato dall'utente
    if ( $post_rate )  {
        $post_rate = ( $post_rate + $user_rate ) / 2;
        $rating_count++;
    }
    
    // altrimenti aggiungi semplicemente il voto dell'utente nel database      
    else {
        $post_rate = $user_rate;
        $rating_count = 1;
    }     
    
    // aggiorniamo il valore nel database
    update_post_meta( $post_id, '_post_rate', $post_rate ); 
    update_post_meta( $post_id, '_post_rate_count', $rating_count ); 
    
    // ritorno un JSON per gestire meglio le info necessarie lato javascript
    echo json_encode( array(
        'rate' => number_format( $post_rate, 1, ',', '.' ),
        'count' => $rating_count,
        'feedback' => __( 'Grazie per aver espresso un tuo giudizio!', 'ypr' ),
    ));
    
    // chiudiamo l'esecuzione della chiamata AJAX, evitando di far ritornare errori da parte di Wordpress
    die();

}
add_action( 'wp_ajax_add_post_rating', 'ypr_add_rate_ajax' );
add_action( 'wp_ajax_nopriv_add_post_rating', 'ypr_add_rate_ajax' );




function my_add_rate_ajax() {
    $user_rate = intval( $_REQUEST['rate'] );
    $post_id   = intval( $_REQUEST['post_id'] );  
    
    // recupero l'attuale votazione memorizzata all'interno del post
    $post_rate    = floatval( get_post_meta( $post_id, '_post_rate', true ) );
    $rating_count = intval( get_post_meta( $post_id, '_post_rate_count', true ) );
    
    // se c'è già un valore memorizzato, calcola il voto insieme a quello dato dall'utente
    if ( $post_rate )  {
        $post_rate = ( $post_rate + $user_rate ) / 2;
        $rating_count++;
    }
    
    // altrimenti aggiungi semplicemente il voto dell'utente nel database      
    else {
        $post_rate = $user_rate;
        $rating_count = 1;
    }     
    
    // aggiorniamo il valore nel database
    update_post_meta( $post_id, '_post_rate', $post_rate ); 
    update_post_meta( $post_id, '_post_rate_count', $rating_count ); 
    
    // do una risposta al client
    echo "Grazie per aver votato!";
    
    // chiudiamo l'esecuzione della chiamata AJAX, evitando di far ritornare errori da parte di Wordpress
    die();

}
add_action( 'wp_ajax_add_post_rating', 'my_add_rate_ajax' );