<?php
namespace scan2payme;
defined( 'ABSPATH' ) || exit;

/**
 * Add the top level menu page.
 */
function scan2payme_extension_options_page() {
    add_menu_page(
        'Scan2PayMe',
        'Scan2PayMe',
        'manage_options',
        'scan2payme',
        'scan2payme\scan2payme_extension_options_page_html'
    );
}
add_action( 'admin_menu', 'scan2payme\scan2payme_extension_options_page' );

/**
 * Add Javascript to settings page
 */
add_action( 'admin_enqueue_scripts', 'scan2payme\scan2payme_enqueue_scripts' );
function scan2payme_enqueue_scripts( $hook ) {
   if ( 'toplevel_page_scan2payme' !== $hook ) {
      return;
   }

   wp_enqueue_script(
      'scan2payme-admin-script',
      plugins_url( '/js/scan2payme-admin.js', __FILE__ ),
      array( 'jquery' ),
      '1.0.0',
      true
   );

   $account_nonce = wp_create_nonce( 'scan2payme-account-nonce' );
   wp_localize_script(
      'scan2payme-admin-script',
      'my_ajax_obj',
      array(
         'ajax_url' => admin_url( 'admin-ajax.php' ),
         'nonce'    => $account_nonce,
      )
   );
}

/**
 * Register ajax handlers
 */
add_action( 'wp_ajax_scan2payme_option_account_changed', 'scan2payme\ajax_scan2payme_option_account_changed_handler' );
function ajax_scan2payme_option_account_changed_handler() {
    check_ajax_referer( 'scan2payme-account-nonce' );
    $changed_to = wp_unslash( $_POST['changed_to'] );
    
    $return_obj = null;
    $changed_to_obj = null;
    $woocommerce_accounts  = get_option( 'woocommerce_bacs_accounts');
    if($woocommerce_accounts != false){
        for($i = 0; $i < sizeof($woocommerce_accounts); $i++ ){
            $name = $woocommerce_accounts[$i]['account_name'];
            if($name == $changed_to){
                $changed_to_obj = $woocommerce_accounts[$i];
                break;
            }
        }
    }

    if($changed_to_obj != null) {
        $return_obj = array();
        $return_obj['error'] = false;
        $return_obj['name'] = $changed_to_obj['account_name'];
        $return_obj['iban'] = $changed_to_obj['iban'];
        $return_obj['bic'] = $changed_to_obj['bic'];
    } else {
        wp_send_json(array('error' => true, 'message' => __('No such WooCommerce account found.'))); // DOES THIS WORK IN AJAX HANDLER?
        wp_die();
    }

    wp_send_json($return_obj);
    wp_die();
}

/**
 * field sanitize callbacks
 */
function scan2payme_option_sanitize_IBAN($input){
    $input = strtoupper($input);
    $old = get_option( 'scan2payme_option_IBAN' );
    $valid = true;
    if (strlen($input) > 34){
        $valid = false;
        add_settings_error('scan2payme_messages', 'scan2payme_message', __('IBAN invalid! Must be shorter than 34 symbols!', 'scan2payme'), 'error');
    }

    if(is_numeric(substr($input, 0, 1)) || is_numeric(substr($input, 1, 1))){
        $valid = false;
        add_settings_error('scan2payme_messages', 'scan2payme_message', __('IBAN invalid! First two symbol must be country identifier!', 'scan2payme'), 'error');
    }

    $bban = substr($input, 2);
    for($i = 0; $i < strlen($bban); $i++){
        if(!is_numeric(substr($bban, $i, 1))){
            $valid = false;
            add_settings_error('scan2payme_messages', 'scan2payme_message', __('IBAN invalid! Account part can only contain numbers!', 'scan2payme'), 'error');
            $i = strlen($bban); // abort, no need to check the rest
        }
    }

    if(!$valid){
        return $old;
    } else {
        return $input;
    }
}

function scan2payme_option_sanitize_BIC($input){
    $input = strtoupper($input);
    $old = get_option( 'scan2payme_option_BIC' );
    $valid = true;

    if(strlen($input) != 8 && strlen($input) != 11){
        $valid = false;
        add_settings_error('scan2payme_messages', 'scan2payme_message', __('BIC invalid! Must be 8 or 11 symbols long!', 'scan2payme'), 'error');
    }

    if(!$valid){
        return $old;
    } else {
        return $input;
    }
}

function scan2payme_option_sanitize_showwhenstatus($input){
    $validStatuses = ['on-hold' ];
    $old = get_option( 'scan2payme_option_showwhenstatus' );
    if(!in_array($input, $validStatuses)){
        add_settings_error('scan2payme_messages', 'scan2payme_message', __('Selected status invalid!', 'scan2payme'), 'error');
        return $old;
    } else {
        return $input;
    }
}

function scan2payme_option_sanitize_showwhenmethod($input){
    $validHooks = ['bacs'];
    $old = get_option( 'scan2payme_option_showwhenmethod' );
    if(!in_array($input, $validHooks)){
        add_settings_error('scan2payme_messages', 'scan2payme_message', __('Selected payment method invalid!', 'scan2payme'), 'error');
        return $old;
    } else {
        return $input;
    } 
}

function scan2payme_option_sanitize_showhook($input){
    $validHooks = ['woocommerce_order_details_after_customer_details', 'woocommerce_order_details_after_order_table', 'woocommerce_order_details_after_order_table_items', 'woocommerce_order_details_before_order_table', 'woocommerce_order_details_before_order_table_items', 'woocommerce_after_order_details'];
    $old = get_option( 'scan2payme_option_showhook' );
    if(!in_array($input, $validHooks)){
        add_settings_error('scan2payme_messages', 'scan2payme_message', __('Selected hook invalid!', 'scan2payme'), 'error');
        return $old;
    } else {
        return $input;
    }
}

function scan2payme_option_sanitize_textabove($input){
    return strip_tags($input);
}

function scan2payme_option_sanitize_textunder($input){
    return strip_tags($input);
}

/**
 * custom option and settings
 */
function scan2payme_extension_settings_init() {
    // Register a new setting for page.
    $bic_args = array ('type' => 'string', 'sanitize_callback' => 'scan2payme\scan2payme_option_sanitize_BIC');
    register_setting( 'scan2payme', 'scan2payme_option_BIC', $bic_args );
    register_setting( 'scan2payme', 'scan2payme_option_Name' );
    $iban_args = array ('type' => 'string', 'sanitize_callback' => 'scan2payme\scan2payme_option_sanitize_IBAN');
    register_setting( 'scan2payme', 'scan2payme_option_IBAN', $iban_args );

    $showhook_args = array( 'type' => 'string', 'sanitize_callback' => 'scan2payme\scan2payme_option_sanitize_showhook', 'default' => 'woocommerce_order_details_after_order_table' );
    register_setting( 'scan2payme', 'scan2payme_option_showhook', $showhook_args ); // default: woocommerce_order_details_after_order_table

    $showwhenstatus_args = array( 'type' => 'string', 'sanitize_callback' => 'scan2payme\scan2payme_option_sanitize_showwhenstatus', 'default' => 'on-hold' );
    register_setting( 'scan2payme', 'scan2payme_option_showwhenstatus', $showwhenstatus_args ); // default: on-hold

    $showwhenmethod_args = array( 'type' => 'string', 'sanitize_callback' => 'scan2payme\scan2payme_option_sanitize_showwhenmethod', 'default' => 'bacs' );
    register_setting( 'scan2payme', 'scan2payme_option_showwhenmethod', $showwhenmethod_args ); // default: bacs

    $textabove_args = array( 'type' => 'string', 'sanitize_callback' => 'scan2payme\scan2payme_option_sanitize_textabove', 'default' => 'Scan2Pay Me' );
    register_setting( 'scan2payme', 'scan2payme_option_textabove', $textabove_args);
    $textunder_args = array( 'type' => 'string', 'sanitize_callback' => 'scan2payme\scan2payme_option_sanitize_textunder', 'default' => 'Scan this QR code with your banking app to initiate a bank transfer.' );
    register_setting( 'scan2payme', 'scan2payme_option_textunder', $textunder_args);
    register_setting( 'scan2payme', 'scan2payme_option_logo');

    // preview
    add_settings_section(
        'scan2payme_section_preview',
        __( 'Scan2Pay Me preview', 'scan2payme' ), 'scan2payme\scan2payme_section_preview_callback',
        'scan2payme'
    );

    // WooCommerce BACS account, or BIC, Name and IBAN
    add_settings_section(
        'scan2payme_section_bankingdetailsfields',
        __( 'Scan2Pay Me banking details fields', 'scan2payme' ), 'scan2payme\scan2payme_section_bankingdetailsfields_callback',
        'scan2payme'
    );

    add_settings_field(
        'scan2payme_option_account',
            __( 'WooCommerce account', 'scan2payme' ),
        'scan2payme\scan2payme_option_account_cb',
        'scan2payme',
        'scan2payme_section_bankingdetailsfields',
        array(
            'label_for'         => 'scan2payme_option_account',
            'class'             => 'scan2payme_row',
            'scan2payme_custom_data' => 'custom',
        )
    );

    add_settings_field(
        'scan2payme_option_BIC',
            __( 'BIC', 'scan2payme' ),
        'scan2payme\scan2payme_option_BIC_cb',
        'scan2payme',
        'scan2payme_section_bankingdetailsfields',
        array(
            'label_for'         => 'scan2payme_option_BIC',
            'class'             => 'scan2payme_row',
            'scan2payme_custom_data' => 'custom',
        )
    );

    add_settings_field(
        'scan2payme_option_Name',
            __( 'Name', 'scan2payme' ),
        'scan2payme\scan2payme_option_Name_cb',
        'scan2payme',
        'scan2payme_section_bankingdetailsfields',
        array(
            'label_for'         => 'scan2payme_option_Name',
            'class'             => 'scan2payme_row',
            'scan2payme_custom_data' => 'custom',
        )
    );

    add_settings_field(
        'scan2payme_option_IBAN',
            __( 'IBAN', 'scan2payme' ),
        'scan2payme\scan2payme_option_IBAN_cb',
        'scan2payme',
        'scan2payme_section_bankingdetailsfields',
        array(
            'label_for'         => 'scan2payme_option_IBAN',
            'class'             => 'scan2payme_row',
            'scan2payme_custom_data' => 'custom',
        )
    );

    // Required technical fields
    add_settings_section(
        'scan2payme_section_requiredfields',
        __( 'Scan2Pay Me required fields', 'scan2payme' ), 'scan2payme\scan2payme_section_requiredfields_callback',
        'scan2payme'
    );

    add_settings_field(
        'scan2payme_option_showwhenstatus',
            __( 'Show when order is in status', 'scan2payme' ),
        'scan2payme\scan2payme_option_showwhenstatus_cb',
        'scan2payme',
        'scan2payme_section_requiredfields',
        array(
            'label_for'         => 'scan2payme_option_showwhenstatus',
            'class'             => 'scan2payme_row',
            'scan2payme_custom_data' => 'custom',
        )
    );

    add_settings_field(
        'scan2payme_option_showwhenmethod',
            __( 'Show when method is', 'scan2payme' ),
        'scan2payme\scan2payme_option_showwhenmethod_cb',
        'scan2payme',
        'scan2payme_section_requiredfields',
        array(
            'label_for'         => 'scan2payme_option_showwhenmethod',
            'class'             => 'scan2payme_row',
            'scan2payme_custom_data' => 'custom',
        )
    );

    add_settings_field(
        'scan2payme_option_showhook',
            __( 'Show at this position in the order overview page', 'scan2payme' ),
        'scan2payme\scan2payme_option_showhook_cb',
        'scan2payme',
        'scan2payme_section_requiredfields',
        array(
            'label_for'         => 'scan2payme_option_showhook',
            'class'             => 'scan2payme_row',
            'scan2payme_custom_data' => 'custom',
        )
    );

    // Optional fields
    add_settings_section(
        'scan2payme_section_optionalfields',
        __( 'Scan2Pay Me optional fields', 'scan2payme' ), 'scan2payme\scan2payme_section_optionalfields_callback',
        'scan2payme'
    );

    add_settings_field(
        'scan2payme_option_textabove',
            __( 'textabove', 'scan2payme' ),
        'scan2payme\scan2payme_option_textabove_cb',
        'scan2payme',
        'scan2payme_section_optionalfields',
        array(
            'label_for'         => 'scan2payme_option_textabove',
            'class'             => 'scan2payme_row',
            'scan2payme_custom_data' => 'custom',
        )
    );

    add_settings_field(
        'scan2payme_option_textunder',
            __( 'textunder', 'scan2payme' ),
        'scan2payme\scan2payme_option_textunder_cb',
        'scan2payme',
        'scan2payme_section_optionalfields',
        array(
            'label_for'         => 'scan2payme_option_textunder',
            'class'             => 'scan2payme_row',
            'scan2payme_custom_data' => 'custom',
        )
    );

    add_settings_field(
        'scan2payme_option_logo',
            __( 'logo', 'scan2payme' ),
        'scan2payme\scan2payme_option_logo_cb',
        'scan2payme',
        'scan2payme_section_optionalfields',
        array(
            'label_for'         => 'scan2payme_option_logo',
            'class'             => 'scan2payme_row',
            'scan2payme_custom_data' => 'custom',
        )
    );
}
/**
 * Register our scan2payme_extension_settings_init callback to the admin_init action hook.
 */
add_action( 'admin_init', 'scan2payme\scan2payme_extension_settings_init' );

function scan2payme_section_preview_callback($args){
        ?>
        <p><?php esc_html_e( 'Show preview QR Code with order id 919 and total 49.99 euro.', 'scan2payme' ); ?></p>
        <?php
        $option_textabove = get_option('scan2payme_option_textabove');
        $option_textunder = get_option('scan2payme_option_textunder');
        $epc_version = "001";
        $epc_encoding = "1";
        $epc_identity = "SCT";
        $epc_bic = get_option( 'scan2payme_option_BIC' );
        $epc_name = get_option( 'scan2payme_option_Name' );
        $epc_iban = get_option( 'scan2payme_option_IBAN' );
        $epc_total = "EUR49.99";
        $epc_use = "";
        $epc_ref = "919";
        $epc_textref = "";
        $epc_hint = "";
        generate_and_output_qr_code($option_textabove, $option_textunder, $epc_version, $epc_encoding, $epc_identity, $epc_bic, $epc_name, $epc_iban, $epc_total, $epc_use, $epc_ref, $epc_textref, $epc_hint);
}

/**
 * section callbacks
 */
function scan2payme_section_bankingdetailsfields_callback( $args ) {
    ?>
    <p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Your banking details.', 'scan2payme' ); ?></p>
    <?php
}

function scan2payme_section_requiredfields_callback( $args ) {
    ?>
    <p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Required fields.', 'scan2payme' ); ?></p>
    <?php
}

function scan2payme_section_optionalfields_callback( $args ) {
    ?>
    <p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Optional fields. Provide your users with information about how you want them to use the code.', 'scan2payme' ); ?></p>
    <?php
}

/**
 * option callbacks
 */
function scan2payme_option_account_cb( $args ) {
    // Get the value of the setting we've registered with register_setting()
    $selected_option = get_option( 'scan2payme_option_account' );

    $woocommerce_accounts  = get_option( 'woocommerce_bacs_accounts');

    $options = array();
    if($woocommerce_accounts != false){
        // provide all accounts in the woocommerce setting as options
        for($i = 0; $i < sizeof($woocommerce_accounts); $i++ ){
            $name = $woocommerce_accounts[$i]['account_name'];
            $options[] = $name;
        }
    }

    ?>
    <select name="scan2payme_option_account" id="scan2payme_option_account">
        <?php
        foreach($options as $opt){
            $val = $opt;
        ?>
            <option value="<?php echo isset( $val ) ? esc_attr( $val ) : ''; ?>"><?php echo isset( $opt ) ? esc_attr( $opt ) : ''; ?></option>
        <?php
        }
        ?>

        <option value="OPTION_ACCOUNT_NONE"><? esc_html_e( '(none)', 'scan2payme' ); ?></option>
    </select>
    
    <?php
}

function scan2payme_option_BIC_cb( $args ) {
    // Get the value of the setting we've registered with register_setting()
    $options = get_option( 'scan2payme_option_BIC' );
    ?>
    <input type="text" class="scan2payme_account_setting" name="scan2payme_option_BIC" id="scan2payme_option_BIC" value="<?php echo isset( $options ) ? esc_attr( $options ) : ''; ?>">
    <?php
}

function scan2payme_option_Name_cb( $args ) {
    // Get the value of the setting we've registered with register_setting()
    $options = get_option( 'scan2payme_option_Name' );
    ?>
    <input type="text" class="scan2payme_account_setting" name="scan2payme_option_Name" id="scan2payme_option_Name" value="<?php echo isset( $options ) ? esc_attr( $options ) : ''; ?>">
    <?php
}

function scan2payme_option_IBAN_cb( $args ) {
    // Get the value of the setting we've registered with register_setting()
    $options = get_option( 'scan2payme_option_IBAN' );
    ?>
    <input type="text" class="scan2payme_account_setting" name="scan2payme_option_IBAN" id="scan2payme_option_IBAN" value="<?php echo isset( $options ) ? esc_attr( $options ) : ''; ?>">
    <?php
}

function scan2payme_option_showwhenstatus_cb( $args ) {
    // Get the value of the setting we've registered with register_setting()
    $options = get_option( 'scan2payme_option_showwhenstatus' );
    ?>
    <select name="scan2payme_option_showwhenstatus">
        <option value="<?php echo isset( $options ) ? esc_attr( $options ) : ''; ?>"><?php echo isset( $options ) ? esc_attr( $options ) : ''; ?></option>
    </select>
    
    <?php
}

function scan2payme_option_showwhenmethod_cb( $args ) {
    // Get the value of the setting we've registered with register_setting()
    $options = get_option( 'scan2payme_option_showwhenmethod' );
    ?>
    <select name="scan2payme_option_showwhenmethod">
        <option value="<?php echo isset( $options ) ? esc_attr( $options ) : ''; ?>"><?php echo isset( $options ) ? esc_attr( $options ) : ''; ?></option>
    </select>
    
    <?php
}

function scan2payme_option_showhook_cb( $args ) {
    // Get the value of the setting we've registered with register_setting()
    $options = get_option( 'scan2payme_option_showhook' );
    ?>
    <select name="scan2payme_option_showhook">
        <option value="<?php echo isset( $options ) ? esc_attr( $options ) : ''; ?>"><?php echo isset( $options ) ? esc_attr( $options ) : ''; ?></option>
        <option value="woocommerce_order_details_after_customer_details">woocommerce_order_details_after_customer_details</option>
        <option value="woocommerce_order_details_after_order_table">woocommerce_order_details_after_order_table</option>
        <option value="woocommerce_order_details_after_order_table_items">woocommerce_order_details_after_order_table_items</option>
        <option value="woocommerce_order_details_before_order_table">woocommerce_order_details_before_order_table</option>
        <option value="woocommerce_order_details_before_order_table_items">woocommerce_order_details_before_order_table_items</option>
        <option value="woocommerce_after_order_details">woocommerce_after_order_details</option>
    </select>
    <?php
}

function scan2payme_option_textunder_cb( $args ) {
    // Get the value of the setting we've registered with register_setting()
    $options = get_option( 'scan2payme_option_textunder' );
    ?>
    <input type="text" name="scan2payme_option_textunder" value="<?php echo isset( $options ) ? esc_attr( $options ) : ''; ?>">
    <?php
}

function scan2payme_option_textabove_cb( $args ) {
    // Get the value of the setting we've registered with register_setting()
    $options = get_option( 'scan2payme_option_textabove' );
    ?>
    <input type="text" name="scan2payme_option_textabove" value="<?php echo isset( $options ) ? esc_attr( $options ) : ''; ?>">
    <?php
}

function scan2payme_option_logo_cb( $args ) {
    // Get the value of the setting we've registered with register_setting()
    $logos = scan2payme_get_available_logos();
    $option_logo_id = get_option( 'scan2payme_option_logo' );
    $selectedLogo = null;
    if(isset($option_logo_id)) {
        foreach ( $logos as $logo ) {
            if ( $option_logo_id == $logo->post_id ) {
                $selectedLogo = $logo;
            }
        }
    }

    // delete the option if it is not available anymore.
    if($selectedLogo == null){
        $updated = update_option('scan2payme_option_logo', null);
    }
    ?>
    <select name="scan2payme_option_logo">
        <?php if($selectedLogo !== null){ ?>
        <option value="<?php echo esc_attr( $selectedLogo->post_id ); ?>"><?php echo esc_attr( $selectedLogo->name ); ?></option>
        <?php } ?>

        <option value=""><?php echo esc_attr(__('(no logo)', 'scan2payme')); ?></option>

        <?php foreach($logos as $logo){ ?>
        <option value="<?php echo esc_attr( $logo->post_id ); ?>"><?php echo esc_attr( $logo->name ); ?></option>
        <?php } ?>
    </select>
    <?php
}

function scan2payme_extension_options_page_html() {
    // check user capabilities
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // check if the user have submitted the settings
    // WordPress will add the "settings-updated" $_GET parameter to the url
    if ( isset( $_GET['settings-updated'] ) ) {
        // add settings saved message with the class of "updated"
        add_settings_error( 'scan2payme_messages', 'scan2payme_message', __( 'Settings Saved', 'scan2payme' ), 'updated' );
    }

    // show error/update messages
    settings_errors( 'scan2payme_messages' );
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form action="options.php" method="post">
            <?php
            // output security fields for the registered setting 
            settings_fields( 'scan2payme' );
            // output setting sections and their fields
            do_settings_sections( 'scan2payme' );
            // output save settings button
            submit_button( 'Save Settings' );
            ?>
        </form>
    </div>
    <?php
}

function scan2payme_get_available_logos(){
    $logo_query_args = array(
        'post_type'      => 'attachment',
        'post_mime_type' => 'image/png',
        'post_status'    => 'inherit',
        'orderby'        => 'post_date',
        'posts_per_page' =>  30,

    );
    $logo_query = new \WP_Query( $logo_query_args );
    $logos = array();
    if ( $logo_query->have_posts() ) {
        for($i = 0; $i < count($logo_query->posts); $i++){
            $logos[] = new Logo($logo_query->posts[$i]->ID, $logo_query->posts[$i]->post_name);
        }
    }
    return $logos;
}