<?php
/*
Plugin Name: VarkTech Min and Max Purchase for WooCommerce
Plugin URI: http://varktech.com
Description: An e-commerce add-on for WooCommerce, supplying minimum and maximum purchase functionality.
Version: 1.08.2.5
Author: Vark
Author URI: http://varktech.com
WC requires at least: 2.0.0
WC tested up to: 4.0
*/



/*
** define Globals 
*/
   $vtmam_info;  //initialized in VTMAM_Parent_Definitions
   $vtmam_rules_set;
   $vtmam_rule;
   $vtmam_cart;
   $vtmam_cart_item;
   $vtmam_setup_options;


   //initial setup only, overriden later in function vtprd_debug_options
   error_reporting(E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR); //v1.07.4
    
class VTMAM_Controller{
	
	public function __construct(){    
   
		define('VTMAM_VERSION',                               '1.08.2.5');
    define('VTMAM_MINIMUM_PRO_VERSION',                   '1.08.2'); 
    define('VTMAM_LAST_UPDATE_DATE',                      '2019-04-20');
    define('VTMAM_DIRNAME',                               ( dirname( __FILE__ ) ));
    define('VTMAM_URL',                                   plugins_url( '', __FILE__ ) );
    define('VTMAM_EARLIEST_ALLOWED_WP_VERSION',           '3.3');   //To pick up wp_get_object_terms fix, which is required for vtmam-parent-functions.php
    define('VTMAM_EARLIEST_ALLOWED_PHP_VERSION',          '5');
    define('VTMAM_PLUGIN_SLUG',                           plugin_basename(__FILE__));
    define('VTMAM_PLUGIN_PATH',                            WP_PLUGIN_DIR . '/min-and-max-purchase-for-woocommerce/vt-minandmax-purchase.php/');
    define('VTMAM_PRO_PLUGIN_NAME',                      'VarkTech Min and Max Purchase Pro for WooCommerce');    //V1.07.3
    
    require ( VTMAM_DIRNAME . '/woo-integration/vtmam-parent-definitions.php');
   
    /*  =============+++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */
    //  these control the rules ui, add/save/trash/modify/delete
    /*  =============+++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */
    add_action('init',          array( &$this, 'vtmam_controller_init' )); 
    add_action('admin_init',    array( &$this, 'vtmam_admin_init' ));
    
    //v1.07 begin
    add_action( 'draft_to_publish',       array( &$this, 'vtmam_admin_update_rule' )); 
    add_action( 'auto-draft_to_publish',  array( &$this, 'vtmam_admin_update_rule' ));
    add_action( 'new_to_publish',         array( &$this, 'vtmam_admin_update_rule' )); 			
    add_action( 'pending_to_publish',     array( &$this, 'vtmam_admin_update_rule' ));    
    //v1.07 end
        
    add_action('save_post',     array( &$this, 'vtmam_admin_update_rule' ));
    add_action('delete_post',   array( &$this, 'vtmam_admin_delete_rule' ));    
    add_action('trash_post',    array( &$this, 'vtmam_admin_trash_rule' ));
    add_action('untrash_post',  array( &$this, 'vtmam_admin_untrash_rule' ));
    /*  =============+++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */
    
    //get rid of bulk actions on the edit list screen, which aren't compatible with this plugin's actions...
    add_action('bulk_actions-edit-vtmam-rule', array($this, 'vtmam_custom_bulk_actions') ); 

	}   //end constructor

  	                                                             
 /* ************************************************
 **   Overhead and Init
 *************************************************** */
	public function vtmam_controller_init(){
    global $vtmam_setup_options;
   
    load_plugin_textdomain( 'vtmam', null, dirname( plugin_basename( __FILE__ ) ) . '/languages' ); 
                                                                               
    require ( VTMAM_DIRNAME . '/core/vtmam-backbone.php' );    
    require ( VTMAM_DIRNAME . '/core/vtmam-rules-classes.php');
    require ( VTMAM_DIRNAME . '/woo-integration/vtmam-parent-functions.php');
    // require ( VTMAM_DIRNAME . '/woo-integration/vtmam-parent-cart-validation.php'); //1.08.2.5 shifted below

    //moved here v1.07
    if (get_option( 'vtmam_setup_options' ) ) {
      $vtmam_setup_options = get_option( 'vtmam_setup_options' );  //put the setup_options into the global namespace
    }
    vtmam_debug_options();  //v1.07
    
    if (is_admin()){
        require ( VTMAM_DIRNAME . '/admin/vtmam-setup-options.php');
        //fix 02-03-2013 - register_activation_hook now at bottom of file, after class instantiates
        
        if(defined('VTMAM_PRO_DIRNAME')) {
          require ( VTMAM_PRO_DIRNAME . '/admin/vtmam-rules-ui.php' );
          require ( VTMAM_PRO_DIRNAME . '/admin/vtmam-rules-update.php');
        } else {
          require ( VTMAM_DIRNAME .     '/admin/vtmam-rules-ui.php' );
          require ( VTMAM_DIRNAME .     '/admin/vtmam-rules-update.php');
        }
        
        require ( VTMAM_DIRNAME . '/admin/vtmam-checkbox-classes.php');
        require ( VTMAM_DIRNAME . '/admin/vtmam-rules-delete.php');
        
        //V1.07.3begin
        if ( (defined('VTMAM_PRO_DIRNAME')) &&
             (version_compare(VTMAM_PRO_VERSION, VTMAM_MINIMUM_PRO_VERSION) < 0) ) {    //'<0' = 1st value is lower  
          add_action( 'admin_notices',array(&$this, 'vtmam_admin_notice_version_mismatch') );            
        }
        //V1.07.3begin 
           
    } else {  //v1.08.2.5 begin - added 'else' branch to Prevent some resources from loading in wp-admin which were causing woo api issues.
      //is_admin test doesn't alway work - test this way!!!!!!
       if ( (strpos($_SERVER["REQUEST_URI"],'wp-admin') !== false) ||
            (defined( 'DOING_CRON' )) ) {          
            //if is_admin, DO NOTHING
          $do_nothing = true;          
       } else {
         require ( VTMAM_DIRNAME . '/woo-integration/vtmam-parent-cart-validation.php');
      } 
    } //v1.08.2.5 end
    
    //unconditional branch for these resources needed for WOOCommerce, at "place order" button time
    require ( VTMAM_DIRNAME . '/core/vtmam-cart-classes.php');
    
    if(defined('VTMAM_PRO_DIRNAME')) {
      require ( VTMAM_PRO_DIRNAME . '/core/vtmam-apply-rules.php' );
    } else {
      require ( VTMAM_DIRNAME .     '/core/vtmam-apply-rules.php' );
    }
    
    wp_enqueue_script('jquery'); 

    
    //*****************
    //v1.08 begin
    //*****************
     /*
     wc_clear_notices
     doesn't clear old notices in all cases
     this takes care of it.
     
     CAN'T use is_cart() as it's not yet available.
     */
    
    //*******************************
    if (is_admin()) {
      return;
    }
    //*******************************
 
    global $vtmam_info;
    $vtmam_info['woo_cart_url']      =  vtmam_woo_get_url('cart'); 
    $vtmam_info['woo_checkout_url']  =  vtmam_woo_get_url('checkout');
    $vtmam_info['currPageURL']       =  vtmam_currPageURL(); 
    
  
     //v1.08.1 you can use this to clear each message on click
     //  the issue is that when an ajax add-to-cart is done, there's no msg
    //ONLY USED on checkout only
    $use_clear_cart_msgs = apply_filters('vtmam_use_clear_cart_msgs',TRUE );
    if ($use_clear_cart_msgs) {
      //v1.08.1 begin
  
      //enqueue not doing it's thing on this one...  - NEED wp_head to allow for is_product, is_shop etc TEST in function...   
      //add_action( "wp_enqueue_scripts", array(&$this, 'vtmam_enqueue_page_reload_on_ajax') );
      add_action( "wp_head", array(&$this, 'vtmam_enqueue_page_reload_on_ajax') );
      
      
      add_action( "wp_enqueue_scripts", array(&$this, 'vtmam_enqueue_cart_resources') );
      
      //v1.08.1 end
                  
    }
    
    return;
    //*****************
    //v1.08 end
    //*****************
  
  }
  
  //*****************
  //v1.08 New Function
  //*****************
  function vtmam_enqueue_cart_resources() {
    wp_register_script('vtmam-clear-cart-msgs', VTMAM_URL.'/woo-integration/js/vtmam-clear-cart-msgs.js' ); 
    wp_enqueue_script ('vtmam-clear-cart-msgs', array('jquery'), false, true);
    //error_log( print_r(  'vtmam-clear-cart-msgs', true ) ); 
    wp_register_style( 'vtmam-error-style', VTMAM_URL.'/core/css/vtmam-error-style.css' );  
    wp_enqueue_style('vtmam-error-style');
  } 
 
   
  //*****************
  //v1.08.1 New Function
  //*****************
  function vtmam_enqueue_page_reload_on_ajax() {
 //error_log( print_r(  'BEGIN vtmam_enqueue_page_reload_on_ajax', true ) ); 
  
    global $vtmam_setup_options;

    /*
    if ( ($vtmam_setup_options['show_errors_on_more_pages'] == 'all') &&    
         (is_shop() || is_product_category() ) ) { //earliest this works is at add_action( 'wp', 'init' );
 //error_log( print_r(  'LOAD JS', true ) ); 
    reload js was here!
      
      //wp_register_script('vtmam-page-reload-on-ajax', VTMAM_URL.'/woo-integration/js/vtmam-page-reload-on-ajax.js' ); 
      //wp_enqueue_script ('vtmam-page-reload-on-ajax', array('jquery'), false, true);
    
    } 
    */

    if (isset( $vtmam_setup_options['show_errors_on_more_pages'] )) {
       switch( $vtmam_setup_options['show_errors_on_more_pages'] ) {
        /*
        case 'productPages':
            if (is_product() || is_cart() || is_checkout() )  {
              $do_nothing = true;
            } else {
                //js remove error...
            }  
          break;
         */ 
        case 'all':  
            //reload on ajax completion needed to run this plugin, possibly creating another error warning for min/max issue
            
            //if (is_cart() || is_checkout() )  { //earliest this works is at add_action( 'wp', 'init' ); <<== can't use this, causes a loop of some kind on the single product page.
            
            if (is_shop() || is_product_category() )  { //earliest this works is at add_action( 'wp', 'init' );
               //error_log( print_r(  'LOAD JS', true ) ); 
                  ?>
                    <script type="text/javascript">
                        jQuery(document).ready(function($) {
                           $( document ).ajaxComplete(function() { 
                              //alert ('about to reload');
                              location.reload(true);
                            });
                        });   
                    </script>
                  <?php
                  
            }
      
          break;
          
       }  //end switch 
     }  
    
  } 
  
   
         
  /* ************************************************
  **   Admin - Remove bulk actions on edit list screen, actions don't work the same way as onesies...
  ***************************************************/ 
  function vtmam_custom_bulk_actions($actions){
    
    ?> 
    <style type="text/css"> #delete_all {display:none;} /*kill the 'empty trash' buttons, for the same reason*/ </style>
    <?php
    
    unset( $actions['edit'] );
    unset( $actions['trash'] );
    unset( $actions['untrash'] );
    unset( $actions['delete'] );
    return $actions;
  }
    
  /* ************************************************
  **   Admin - Show Rule UI Screen
  *************************************************** 
  *  This function is executed whenever the add/modify screen is presented
  *  WP also executes it ++right after the update function, prior to the screen being sent back to the user.   
  */  
	public function vtmam_admin_init(){
     if ( !current_user_can( 'edit_posts', 'vtmam-rule' ) )
          return;

     $vtmam_rules_ui = new VTMAM_Rules_UI; 
  }
  
 
  /* ************************************************
  **   Admin - Update Rule 
  *************************************************** */
	public function vtmam_admin_update_rule(){
    /* *****************************************************************
         The delete/trash/untrash actions *will sometimes fire save_post*
         and there is a case structure in the save_post function to handle this.
    
          the delete/trash actions are sometimes fired twice, 
               so this can be handled by checking 'did_action'
     ***************************************************************** */
      
      global $post, $vtmam_rules_set;
      
      // v1.07.4 begin
      if( !isset( $post ) ) {    
        return;
      }  
      // v1.07.4  end  
          
      if ( !( 'vtmam-rule' == $post->post_type )) {
        return;
      }  
      if (( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
            return; 
      }
     if (isset($_REQUEST['vtmam_nonce']) ) {     //nonce created in vtmam-rules-ui.php  
          $nonce = $_REQUEST['vtmam_nonce'];
          if(!wp_verify_nonce($nonce, 'vtmam-rule-nonce')) { 
            return;
          }
      } 
      if ( !current_user_can( 'edit_posts', 'vtmam-rule' ) ) {
          return;
      }

      
      /* ******************************************
       The 'SAVE_POST' action is fired at odd times during updating.
       When it's fired early, there's no post data available.
       So checking for a blank post id is an effective solution.
      *************************************************** */      
      if ( !( $post->ID > ' ' ) ) { //a blank post id means no data to proces....
        return;
      } 
      //AND if we're here via an action other than a true save, do the action and exit stage left
      $action_type = $_REQUEST['action'];
      if ( in_array($action_type, array('trash', 'untrash', 'delete') ) ) {
        switch( $action_type ) {
            case 'trash':
                $this->vtmam_admin_trash_rule();  
              break;
            case 'untrash':
                $this->vtmam_admin_untrash_rule();
              break;
            case 'delete':
                $this->vtmam_admin_delete_rule();  
              break;
        }
        return;
      }
                 
      $vtmam_rule_update = new VTMAM_Rule_update;
  }
   
  
 /* ************************************************
 **   Admin - Delete Rule
 *************************************************** */
	public function vtmam_admin_delete_rule(){
     global $post, $vtmam_rules_set; 
     if ( !( 'vtmam-rule' == $post->post_type ) ) {
      return;
     }        

     if ( !current_user_can( 'delete_posts', 'vtmam-rule' ) )  {
          return;
     }
    
    $vtmam_rule_delete = new VTMAM_Rule_delete;            
    $vtmam_rule_delete->vtmam_delete_rule();
        
    if(defined('VTMAM_PRO_DIRNAME')) {
      require ( VTMAM_PRO_DIRNAME . '/core/vtmam-delete-purchaser-info.php' );   
    }
  }
  
  
  /* ************************************************
  **   Admin - Trash Rule
  *************************************************** */   
	public function vtmam_admin_trash_rule(){
     global $post, $vtmam_rules_set; 
     if ( !( 'vtmam-rule' == $post->post_type ) ) {
      return;
     }        
  
     if ( !current_user_can( 'delete_posts', 'vtmam-rule' ) )  {
          return;
     }  
     
     if(did_action('trash_post')) {    
         return;
    }
    
    $vtmam_rule_delete = new VTMAM_Rule_delete;            
    $vtmam_rule_delete->vtmam_trash_rule();

  }
  
  
 /* ************************************************
 **   Admin - Untrash Rule
 *************************************************** */   
	public function vtmam_admin_untrash_rule(){
     global $post, $vtmam_rules_set; 
     if ( !( 'vtmam-rule' == $post->post_type ) ) {
      return;
     }        

     if ( !current_user_can( 'delete_posts', 'vtmam-rule' ) )  {
          return;
     }       
    $vtmam_rule_delete = new VTMAM_Rule_delete;            
    $vtmam_rule_delete->vtmam_untrash_rule();
  }


  /* ************************************************
  **   Admin - Activation Hook
  *************************************************** */  
   function vtmam_activation_hook() {
     //the options are added at admin_init time by the setup_options.php as soon as plugin is activated!!!
    //verify the requirements for Vtmam.
    global $wp_version;
		if((float)$wp_version < 3.3){
			// delete_option('vtmam_setup_options');
			 wp_die( __('<strong>Looks like you\'re running an older version of WordPress, you need to be running at least WordPress 3.3 to use the Varktech Minimum Purchase plugin.</strong>', 'vtmam'), __('VT Minimum Purchase not compatible - WP', 'vtmam'), array('back_link' => true));
			return;
		}
           
    //fix 02-13-2013 - changed php version_compare, altered error msg   
   if (version_compare(PHP_VERSION, VTMAM_EARLIEST_ALLOWED_PHP_VERSION) < 0) {    //'<0' = 1st value is lower 
			wp_die( __('<strong><em>PLUGIN CANNOT ACTIVATE &nbsp;&nbsp;-&nbsp;&nbsp;     Varktech Min and Max Purchase </em>
      <br><br>&nbsp;&nbsp;&nbsp;&nbsp;   Your installation is running on an older version of PHP 
      <br><br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;   - your PHP version = ', 'vtmam') .PHP_VERSION. __(' . 
      <br><br>&nbsp;&nbsp;&nbsp;&nbsp;   You need to be running **at least PHP version 5** to use this plugin.  
      <br><br>&nbsp;&nbsp;&nbsp;&nbsp;   Please contact your host and request an upgrade to PHP 5+ . 
      <br><br>&nbsp;&nbsp;&nbsp;&nbsp;   Then activate this plugin following the upgrade.</strong>', 'vtmam'), __('VT Min and Max Purchase not compatible - PHP', 'vtmam'), array('back_link' => true));
			return; 
		}

    if(defined('WOOCOMMERCE_VERSION') && (VTMAM_PARENT_PLUGIN_NAME == 'WooCommerce')) { 
      $new_version =      VTMAM_EARLIEST_ALLOWED_PARENT_VERSION;
      $current_version =  WOOCOMMERCE_VERSION;
      if( (version_compare(strval($new_version), strval($current_version), '>') == 1) ) {   //'==1' = 2nd value is lower 
  			// delete_option('vtmam_setup_options');
  			 wp_die( __('<strong>Looks like you\'re running an older version of WooCommerce. <br>You need to be running at least ** WooCommerce 1.0 **, to use the Varktech Minimum Purchase plugin.</strong>', 'vtmam'), __('VT Minimum Purchase not compatible - WooCommerce', 'vtmam'), array('back_link' => true));
  			return;
  		}
    }   else 
    if (VTMAM_PARENT_PLUGIN_NAME == 'WooCommerce') {
        wp_die( __('<strong>Varktech Minimum Purchase for WooCommerce requires that WooCommerce be installed and activated.</strong>', 'vtmam'), __('WooCommerce not installed or activated', 'vtmam'), array('back_link' => true));
  			return;
    }
     
  }
 

   //V1.07.3 begin                          
   function vtmam_admin_notice_version_mismatch() {
      $message  =  '<strong>' . __('Please also update plugin: ' , 'vtmam') . ' &nbsp;&nbsp;'  .VTMAM_PRO_PLUGIN_NAME . '</strong>' ;
      $message .=  '<br>&nbsp;&nbsp;&bull;&nbsp;&nbsp;' . __('Your Pro Version = ' , 'vtmam') .VTMAM_PRO_VERSION. ' &nbsp;&nbsp;' . __(' The Minimum Required Pro Version = ' , 'vtmam') .VTMAM_MINIMUM_PRO_VERSION ;      
      $message .=  '<br>&nbsp;&nbsp;&bull;&nbsp;&nbsp;' . __('Please delete the old Pro plugin from your installation via ftp.'  , 'vtmam');
      $message .=  '<br>&nbsp;&nbsp;&bull;&nbsp;&nbsp;' . __('Go to ', 'vtmam');
      $message .=  '<a target="_blank" href="http://www.varktech.com/download-pro-plugins/">Varktech Downloads</a>';
      $message .=   __(', download and install the newest <strong>'  , 'vtmam') .VTMAM_PRO_PLUGIN_NAME. '</strong>' ;
      
      $admin_notices = '<div id="message" class="error fade" style="background-color: #FFEBE8 !important;"><p>' . $message . ' </p></div>';
      echo $admin_notices;
      
      //v1.07.7 added
      $plugin = VTMAM_PRO_PLUGIN_SLUG;
			if( is_plugin_active($plugin) ) {
			   deactivate_plugins( $plugin );
      }      
                
      return;    
  }   
   //V1.07.3 end    

   
  /* ************************************************
  **   Admin - **Uninstall** Hook and cleanup
  *************************************************** */ 
  function vtmam_uninstall_hook() {
      
      if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
      	return;
        //exit ();
      }
  
      delete_option('vtmam_setup_options');
      $vtmam_nuke = new VTMAM_Rule_delete;            
      $vtmam_nuke->vtmam_nuke_all_rules();
      $vtmam_nuke->vtmam_nuke_all_rule_cats();
      
  }


} //end class
$vtmam_controller = new VTMAM_Controller; 

  //***************************************************************************************
  //fix 02-13-2013  -  problems with activation hook and class, solved herewith....
  //   FROM http://website-in-a-weekend.net/tag/register_activation_hook/
  //***************************************************************************************
  if (is_admin()){ 
        register_activation_hook(__FILE__, array($vtmam_controller, 'vtmam_activation_hook'));
        register_activation_hook(__FILE__, array($vtmam_controller, 'vtmam_uninstall_hook'));                                   
  }
