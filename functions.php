<?php
/**
 * Anglia Tackle & Gun Bespoke Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Anglia Tackle & Gun Bespoke
 * @since 1.0.0
 */

/**
 * Define Constants
 */
define( 'CHILD_THEME_ANGLIA_TACKLE_GUN_BESPOKE_VERSION', '1.0.0' );

/**   
 * Enqueue styles
 */
function child_enqueue_styles() {

	wp_enqueue_style( 'anglia-tackle-gun-bespoke-theme-css', get_stylesheet_directory_uri() . '/style.css', array('astra-theme-css'), CHILD_THEME_ANGLIA_TACKLE_GUN_BESPOKE_VERSION, 'all' );

}

add_action( 'wp_enqueue_scripts', 'child_enqueue_styles', 15 );

add_filter('woocommerce_product_add_to_cart_text', function ($text) {
     global $product;
     if ($product instanceof WC_Product && $product->is_type('variable')) {
         $text = $product->is_purchasable() ? _('View Options', 'woocommerce')
		 : _('Read more', 'woocommerce');
     }
     return $text;
 }, 10);

function custom_openpos_pos_header_js($handles){
    $handles[] = 'openpos.websql_handle';
    return $handles;
}

add_filter( 'openpos_pos_header_js', 'custom_openpos_pos_header_js' ,10 ,1);
add_action( 'init', 'custom_registerScripts' ,10 );
function custom_registerScripts(){
    wp_register_script( 'openpos.websql_handle', '' );
    wp_enqueue_script('openpos.websql_handle');
    wp_add_inline_script('openpos.websql_handle',"
        if(typeof global == 'undefined')
        {
             var global = global || window;
        }
        global.allow_websql = 'yes';
    ");
}


/**
 * change the “Out Of Stock” text on the Product Catalog (Shop page)
 */
add_filter( 'astra_woo_shop_out_of_stock_string', 'ced_out_of_stock_callback' );
function ced_out_of_stock_callback( $title ) {
    global $product;
    $product = wc_get_product($product->get_id());
    // Get children product variation IDs in an array
    $children_ids = $product->get_children();
    if(!empty($children_ids)){
        foreach($children_ids as $children_id){
            $product = wc_get_product($children_id);
            if($product->get_stock_quantity() > 0 ){
                $title = '';
                break;
            }else{
                $title = 'Call us for lead time';
            }
        }
    }else{
        $title = 'Call us for lead time';
    }
    return $title;
}




add_filter( 'woocommerce_available_variation', 'form_to_out_of_stock_product_variations', 10, 3 );
function form_to_out_of_stock_product_variations( $data, $product, $variation ) {
    if( ! $data['is_in_stock'] )
        $data['availability_html'] .= do_shortcode('[contact-form-7 id="19692" title="product-out-of-stock-form"]').'<button id="ced_notify_user"><span itemprop="telephone"><a href="tel:+01603 870 353">'.__('Call us for lead time', 'woocommerce').'</a></span></button>';

    return $data;
}


// add_action('woocommerce_after_variations_form', 'outofstock_product_variation_js');
function outofstock_product_variation_js() {
    ?>
    <script type="text/javascript">
    jQuery(function($) {
        var contactFormObject  = $('.outofstock-form'),
            addToCartButtonObj = $('.woocommerce-variation-add-to-cart');

        $('form.variations_form').on('show_variation', function(event, data) { // No selected variation
            if ( ! data.is_in_stock  ) {
                addToCartButtonObj.hide('fast');
                contactFormObject.show('fast');
            } else {
                addToCartButtonObj.show('fast');
                contactFormObject.hide('fast');
            }
        }).on('hide_variation', function() { // Not on selected variation
            addToCartButtonObj.show('fast');
            contactFormObject.hide('fast');
        });
    });
    </script>
    <?php
}

function action_woocommerce_before_add_to_cart_form() {
    global $product;
    $product_id = $product->get_id();
    if( $product->is_type('simple') ){
        if($product->get_stock_quantity()<1){
            echo do_shortcode('[contact-form-7 id="19692" title="product-out-of-stock-form"]');
            echo '<button id="ced_notify_user"><span itemprop="telephone"><a href="tel:+01603 870 353">'.__('Call us for lead time', 'woocommerce').'</a></span></button>';
        }
    }
    
    
}
add_action( 'woocommerce_product_meta_start', 'action_woocommerce_before_add_to_cart_form', 10, 0 );




// add_action('woocommerce_before_add_to_cart_quantity','ced_Add_text');
// add_action( 'woocommerce_get_price_html', 'ced_Add_text' ,20);
// add_action( 'woocommerce_cart_item_price', 'ced_Add_text',20 );
function ced_Add_text(){
    if(is_product()){
        ob_start();
            echo do_shortcode('[contact-form-7 id="19692" title="product-out-of-stock-form"]');
        return ob_get_clean();
        
    }
}

   
add_action('wp_footer', 'ced_notify_popup');
function ced_notify_popup(){
    // popup
    global $product;
    // $id = $product->get_id();
    if(is_product()){
        $product_name = $product->get_name();
        // $formval = do_shortcode('[contact-form-7 id="19692" title="product-out-of-stock-form"]');
        // echo do_shortcode('[contact-form-7 id="19692" title="product-out-of-stock-form"]');
    ?>
    
   
    <script>
        jQuery(document).on('click','.elementor-widget-container form',function(){
            
            if(jQuery('.elementor-widget-container .elementor-add-to-cart').hasClass('elementor-product-variable')){
                var product_id = jQuery("input[name=variation_id]").val();
                jQuery('#product-stock-id').val(product_id);
                jQuery('#product-stock-title').val('<?php echo $product_name; ?>');
            }else{
                var product_id = jQuery("input[name=queried_id]").val();
                jQuery('#product-stock-id').val(product_id);
                jQuery('#product-stock-title').val('<?php echo $product_name; ?>');
            }
        });
      
    </script>
    <?php
    }
}

add_action('woocommerce_update_product', 'ced_product_update_stock_mail', 10, 2);
/**
 * mail will send after getting in stock product
 * @para    $product_id     update product id
 * @para    $product        update product details
 */
function ced_product_update_stock_mail($product_id, $product) {
    global $wpdb;
    $current_user = wp_get_current_user();
    $results = $wpdb->get_results( "SELECT * FROM tks_db7_forms", ARRAY_A );
    $product = wc_get_product($product_id);
    $product_url = get_permalink( $product_id );
    
    if($product->is_type( 'simple' )){
         // iterate over results
        foreach ($results as $result){
            if($result['form_value']){
                $data = array();
                $data = unserialize($result['form_value']);
                if($product_id == $data['product-stock-id']){
                    if($product->get_stock_quantity() > 0 ){
                        $check_mail =   get_post_meta($result['form_post_id'], '_instock_'.$result['form_id'], true);
                        if( empty($check_mail) || $check_mail==0 ){
  
                            $html = '<h1>Product has come in stock</h1>'.'<b>Product Url : </b>'.$product_url.'<br/><b>Product Name :</b>'.$data['product-stock-title'];
                            $prepared_html = $html;
                            $email      = $data['user-email'];
                            $to      = $email;
                            $subject ="Product In Stock now";
                            $message = $prepared_html;
                            $headers = "From:Topknotch-Solutions/ <$current_user->user_email>\r\n";
                            $headers.= "Content-Type: text/html; charset=utf-8\r\n";
                            $headers .= "CC:".$data['user-email']."\r\n";
                            wp_mail($to, $subject, $message, $headers);         
                            add_post_meta( $result['form_post_id'], '_instock_'.$result['form_id'], 1 );
                        }
                    }
                }
            }
        }
        
    }elseif($product->is_type( 'variable' )){
        // Get children product variation IDs in an array
        $children_ids = $product->get_children();
        foreach ($results as $result) {
            if($result['form_value']){
                $data = array();
                $data = unserialize($result['form_value']);
                // echo "<pre>";var_dump($data);die;
                foreach($children_ids as $children_id){
                    $product_var = wc_get_product($children_id);
                    if($children_id == $data['product-stock-id'] && $product_var->get_stock_quantity() > 0 ){
                        $check_mail =   get_post_meta($result['form_post_id'], '_instock_'.$result['form_id'], true);
                        if( empty($check_mail) || $check_mail==0 ){
                            $html = '<h1>Product has come in stock</h1>'.'<b>Product Url : </b>'.$product_url.'<br/><b>Product Name :</b>'.$data['product-stock-title'];
                            $prepared_html = $html;
                            $email      = $data['user-email'];
                            $to      = $email;
                            $subject ="Product In Stock now";
                            $message = $prepared_html;
                            $headers = "From:Topknotch-Solutions/ <$current_user->user_email>\r\n";
                            $headers.= "Content-Type: text/html; charset=utf-8\r\n";
                            $headers .= "CC:".$data['user-email']."\r\n";
                            wp_mail($to, $subject, $message, $headers);
                            add_post_meta( $result['form_post_id'], '_instock_'.$result['form_id'], 1 );
                        }
                    }
                }
            }
        }
    }

}


