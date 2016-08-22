<?php

/*
  Plugin Name: EDD Service Extended
  Plugin URI: http://www.wbcomdesigns.com
  Description: Easy Digital Download Message adds message section in the user dashboard for conversation.
  Version: 1.0.1
  Author: WBCOM DESIGNS
  Author URI: http://www.wbcomdesigns.com
  License: GPL2
  http://www.gnu.org/licenses/gpl-2.0.html
 */
defined('ABSPATH') or die('Plugin file cannot be accessed directly.');

if (!defined('')) {

    define('WBCOM_EDD_DASH_MSG', '1.0');
}

if (!defined('WBCOM_EDD_DASH_MSG_PATH')) {

    define('WBCOM_EDD_DASH_MSG_PATH', plugin_dir_path(__FILE__));
}

if (!defined('WBCOM_EDD_DASH_MSG_URL')) {

    define('WBCOM_EDD_DASH_MSG_URL', plugin_dir_url(__FILE__));
}

if (!defined('WBCOM_EDD_DASH_MSG_DB_VERSION')) {

    define('WBCOM_EDD_DASH_MSG_DB_VERSION', '1');
}

if (!defined('WBCOM_EDD_DASH_MSG_TEXT_DOMIAN')) {

    define('WBCOM_EDD_DASH_MSG_TEXT_DOMIAN', 'wb-edd-order-thread');
}

class Edd_Init_Vars {

    protected $wpdb = '';
    public $edd_receipt_args = '';
    public $edd_login_redirect = '';

    public static function init_edd_class() {
        $class = __CLASS__;
        new $class;
    }

    public function __construct() {
        global $wpdb, $edd_receipt_args, $edd_login_redirect;
        $this->wpdb = &$wpdb;
        $this->edd_receipt_args = &$edd_receipt_args;
        $this->edd_login_redirect = &$edd_login_redirect;
        $this->init();
    }

    public function init() {
        if (!function_exists('wbcom_edd_require_check')) {
            add_action('admin_init', array($this, 'wbcom_edd_require_check'));
        }
        /* File have code to add and display initial message in the admin for single service
          This initial will be used as first message by the service provider on service purchase */

        require_once WBCOM_EDD_DASH_MSG_PATH . 'includes/admin/add-custom-info-field.php';
        /* Checks the Front end posting plugin is active
          File have code to add and display initial message in the front end submition form for single service
          This initial will be used as first message by the service provider on service purchase */

        if (class_exists('EDD_Front_End_Submissions'))
            require_once WBCOM_EDD_DASH_MSG_PATH . 'includes/add-custom-info-field-front.php';

        if (!function_exists('wbcom_edd_install')) {
            register_activation_hook(__FILE__, array($this, 'wbcom_edd_install'));
        }
        if (!function_exists('add_edd_message_style_script')) {
            add_action('wp_enqueue_scripts', array($this, 'add_edd_message_style_script'));
        }

        if (!function_exists('add_user_comment_edd')) {
            add_shortcode('add_user_comment_edd', array($this, 'add_user_comment_edd'));
        }
        if (!function_exists('edd_add_user_message')) {
            add_action('init', array($this, 'edd_add_user_message'));
        }
        if (!function_exists('wbcom_edd_download_history')) {
            add_shortcode('wbcom_download_history', array($this, 'wbcom_edd_download_history'));
        }
        if (!function_exists('wbcom_page_setup')) {
            add_action('init', array($this, 'wbcom_page_setup'));
        }
        if (!function_exists('wbcom_edd_receipt_shortcode')) {
            add_shortcode('wbcom_edd_receipt', array($this, 'wbcom_edd_receipt_shortcode'));
        }
    }

    /* Function to check Main Easy digtal Download plugin is active or not
      If not active it will not get active */

    public function wbcom_edd_require_check() {
        if (!class_exists('Easy_Digital_Downloads')) {
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            if (!function_exists('wbcom_edd_require_notice')) {
                add_action('admin_notices', array($this, 'wbcom_edd_require_notice'));
            }
            deactivate_plugins(plugin_basename(__FILE__));
        }
    }

    /* Function to display the notice for the admin to activate EDD plugin */

    public function wbcom_edd_require_notice() {

        echo '<div id="message" class="updated fade"><p style="line-height: 150%">';

        echo __('<strong>Easy Digital Download</strong> plugin is not activated please activate it first.', WBCOM_EDD_DASH_MSG_TEXT_DOMIAN);

        echo '</p></div>';
    }

    /* Function to create table for the conversation message to store and add db version in the option table */

    public function wbcom_edd_install() {

        $installed_ver = get_option("edd_message_db_version");
        if ($installed_ver != WBCOM_EDD_DASH_MSG_DB_VERSION) {

            $table_name = $this->wpdb->prefix . 'edd_dashboard_message';
            $charset_collate = $this->wpdb->get_charset_collate();

            $sql = "CREATE TABLE $table_name (
					id mediumint(9) NOT NULL AUTO_INCREMENT,
					author_id mediumint(9) NOT NULL,
					user_id mediumint(9) NOT NULL,
					site_id mediumint(9) NOT NULL,
					purchase_id mediumint(9) NOT NULL,
					msg_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
					message text NOT NULL,
					attachment text NOT NULL,
					msg_read ENUM('0','1') NOT NULL,
					UNIQUE KEY id (id)
				) $charset_collate;";

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

            dbDelta($sql);

            update_option('edd_message_db_version', WBCOM_EDD_DASH_MSG_DB_VERSION);
        }
    }

    /* Function to add the style css and javascripts */

    public function add_edd_message_style_script() {
        wp_enqueue_script('jquery');

        wp_register_script('edd_message_script', WBCOM_EDD_DASH_MSG_URL . 'js/script.js', 'all');

        wp_enqueue_script('edd_message_script');

        wp_register_script('edd_rate_script', WBCOM_EDD_DASH_MSG_URL . 'js/jRate.min.js', 'all');

        wp_enqueue_script('edd_rate_script');

        wp_register_style('edd_message-css', WBCOM_EDD_DASH_MSG_URL . 'css/style.css', 'all');

        wp_enqueue_style('edd_message-css');
    }

    /* Funtion to filter the buttons from the tiny mice editor */

    public function edd_message_editor_buttons($buttons, $editor_id) {
        return array('bold', 'italic', 'underline', 'bullist', 'numlist', 'link', 'unlink', 'forecolor', 'undo', 'redo');
    }

    /* Function to add message input form with file upload 
      Display all the conversation thread with avatar and name
     */

    public function add_user_comment_edd() {

        $meta_data = edd_get_payment_meta_cart_details($this->edd_receipt_args['id'], true);
        $user = edd_get_payment_meta_user_info($this->edd_receipt_args['id']);
        $vendor = get_post_field('post_author', $meta_data[0]['id']);

        if ($user['id'] == get_current_user_id() || $vendor == get_current_user_id()) {
            $all_msg = $this->get_conversation_by_pay_id($this->edd_receipt_args['id']);
            $html = '<div id="add_user_comment" class="add_user_comment">';

            $html .= do_action('show_insert_message');
            $html .= $all_msg;
            $thread_status = get_post_meta($this->edd_receipt_args['id'], 'edd_user_order_thread', 'close');
            if ($thread_status != 'close') {
                $html .= '<h3>' . __('Massages', WBCOM_EDD_DASH_MSG_TEXT_DOMIAN) . '</h3>
					  <form method="post" action="" enctype="multipart/form-data">';
                ob_start();
                $settings = array('media_buttons' => false, 'editor_height' => '150px', 'teeny' => true, 'quicktags' => false);
                add_filter('teeny_mce_buttons', array($this, 'edd_message_editor_buttons'), 10, 2);
                wp_editor('', 'message', $settings);
                $html .= ob_get_clean();
                $html .= '<div class="my-attachments">';
                $html .= '<div class="attachment-file"><input type="file" multiple name="attach[]" id="edd_message_attachment" style="display:none;" /></div>';
                $html .= '<div class="attachment-icon"><label for="edd_message_attachment" class="edd_message_attachment">' . __('<img src="' . get_site_url() . '/wp-content/plugins/edd-service-extended/includes/images/attachment1.png" alt="Attach Files" width="20" height="20" align="center">', WBCOM_EDD_DASH_MSG_TEXT_DOMIAN) . '</label></div>';
                $html .= '</div>';
                $html .= '<div class="send-btn"><input type="submit" name="add_comment" value="Send" class="submit-msg" /><div id="edd_files_names"></div><input type="hidden" value="' . $vendor . '" name="edd_vendor"><input type="hidden" value="' . $this->edd_receipt_args['id'] . '" name="purchase_id">' . wp_nonce_field('edd_message_action', 'edd_message_nonce_field', false, false) . '</div></form>';
            }
            $html .= '</div>';
        }

        return $html;
    }

    public function edd_insert_true_msg() {
        return "Message inserted";
    }

    public function edd_insert_false_msg() {
        return "Message not inserted";
    }

    /* Function to insert the user message in the table 
      There are two condition to add message first to add normal thread message and multiple attachment
      second to add the thread close message and rating for the user for the particlar order */

    public function edd_add_user_message() {
        /* start of the condition one */

        if ('POST' == $_SERVER['REQUEST_METHOD'] && !empty($_POST['add_comment']) && isset($_POST['add_comment'])) {
            if (wp_verify_nonce($_POST['edd_message_nonce_field'], 'edd_message_action')) {
                $table_name = $this->wpdb->prefix . 'edd_dashboard_message';
                $meta_data = edd_get_payment_meta_cart_details($_POST['purchase_id'], true);
                $vendor_check = get_post_field('post_author', $meta_data[0]['id']);
                $vendor = intval($_POST['edd_vendor']);
                $user_id = get_current_user_id();
                $purchase_id = intval($_POST['purchase_id']);
                $message = wp_kses_post($_POST['message']);
                $attach = '';

                if ($vendor == $vendor_check) {
                    if ($_FILES) {
                        include_once ABSPATH . 'wp-admin/includes/media.php';
                        include_once ABSPATH . 'wp-admin/includes/file.php';
                        include_once ABSPATH . 'wp-admin/includes/image.php';
                        foreach ($_FILES['attach']['name'] as $f => $name) {
                            if ($_FILES['attach']['error'][$f] == 0) {
                                $file['name'] = $name;
                                $file['type'] = $_FILES['attach']['type'][$f];
                                $file['tmp_name'] = $_FILES['attach']['tmp_name'][$f];
                                $file['error'] = $_FILES['attach']['error'][$f];
                                $file['size'] = $_FILES['attach']['size'][$f];

                                $upload = wp_handle_upload($file, array('test_form' => false));
                                if (!isset($upload['error']) && isset($upload['file'])) {
                                    $filetype = wp_check_filetype(basename($upload['file']), null);
                                    $title = $file['name'];
                                    $ext = strrchr($title, '.');
                                    $title = ( $ext !== false ) ? substr($title, 0, -strlen($ext)) : $title;
                                    $attachment = array(
                                        'guid' => $upload['url'],
                                        'post_mime_type' => $filetype['type'],
                                        'post_title' => addslashes($title),
                                        'post_content' => '',
                                        'post_status' => 'inherit'
                                    );

                                    $attach[] = wp_insert_attachment($attachment, $upload['file']);
                                }
                            }
                        }
                    }
                    //insert the single message with attachment*/
                    $this->wpdb->insert(
                            $table_name, array(
                        'author_id' => $vendor,
                        'user_id' => $user_id,
                        'purchase_id' => $purchase_id,
                        'message' => $message,
                        'msg_time' => date("Y-m-d H:i:s"),
                        'attachment' => serialize($attach),
                            ), array(
                        '%d',
                        '%d',
                        '%d',
                        '%s',
                        '%s',
                        '%s',
                            )
                    );
                    add_action('show_insert_message', array($this, 'edd_insert_true_msg'));
                } else {
                    add_action('show_insert_message', array($this, 'edd_insert_false_msg'));
                }
            }
        }
        /* End of condition one */
        /* Second condition start to add close message and rating */
        if ('POST' == $_SERVER['REQUEST_METHOD'] && !empty($_POST['add_close']) && isset($_POST['add_close'])) {
            if (wp_verify_nonce($_POST['edd_close_thread_nonce_field'], 'edd_close_thread')) {
                $table_name = $this->wpdb->prefix . 'edd_dashboard_message';
                $meta_data = edd_get_payment_meta_cart_details($_POST['purchase_id'], true);
                $vendor_check = get_post_field('post_author', $meta_data[0]['id']);
                $vendor = intval($_POST['edd_vendor']);
                $user_id = get_current_user_id();
                $purchase_id = intval($_POST['purchase_id']);
                $rating = floatval($_POST['rating']);
                $message = wp_kses_post($_POST['rate_message']);
                $this->wpdb->insert(
                        $table_name, array(
                    'author_id' => $vendor,
                    'user_id' => $user_id,
                    'purchase_id' => $purchase_id,
                    'message' => $message,
                    'msg_time' => date("Y-m-d H:i:s"),
                    'attachment' => serialize($attach),
                        ), array(
                    '%d',
                    '%d',
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                        )
                );
                $rating_arr = get_user_meta($vendor, 'edd_user_order_rating', true);
                $rating_arr[$purchase_id] = $rating;
                update_user_meta($vendor, 'edd_user_order_rating', $rating_arr);
                update_post_meta($purchase_id, 'edd_user_order_thread', 'close');
            }
        }
        /* End of second condition */
    }

    /* Function to get all the conversation related to a single service id */

    public function get_conversation_by_pay_id($id) {

        $cart = edd_get_payment_meta_cart_details($id, true);
        $message = get_post_meta($cart[0]['id'], '_fes_edd_initial_message', true);
        $table_name = $this->wpdb->prefix . 'edd_dashboard_message';
        $msg = $this->wpdb->get_results("SELECT * FROM $table_name WHERE purchase_id = $id ORDER BY msg_time ASC");
        $html = '<div class="edd-messages-list">';
        /* Display initial message */
        if (isset($message) && $message != "" && empty($msg)) {
            $post = get_post($cart[0]['id']);
            $user_data = get_userdata($post->post_author);
            $html .= '<div class="each-edd-message">';
            $html .= '<div class="each-edd-message-avatar">';
            $html .= get_avatar($post->post_author, '50') . '<br><label class="name">' . $user_data->data->user_login . '</label>';
            $html .= '</div>';
            $html .= '<div class="each-edd-message-right-cont">';
            $html .= '<div class="each-edd-message-msg">';
            $html .= $message;
            $html .= '</div>';
            $html .= '</div>';
            $html .= '<div style="clear:both;"></div>';
            $html .= '</div>';
        }
        if (isset($msg)) {
            if (isset($message) && $message != "") {
                $post = get_post($cart[0]['id']);
                $user_data = get_userdata($post->post_author);
                $html .= '<div class="each-edd-message">';
                $html .= '<div class="each-edd-message-avatar">';
                $html .= get_avatar($post->post_author, '50') . '<br><label class="name">' . $user_data->data->user_login . '</label>';
                $html .= '</div>';
                $html .= '<div class="each-edd-message-right-cont">';
                $html .= '<div class="each-edd-message-msg">';
                $html .= $message;
                $html .= '</div>';
                $html .= '</div>';
                $html .= '<div style="clear:both;"></div>';
                $html .= '</div>';
            }
            foreach ($msg as $each) {
                if ($each->attachment != "") {
                    $filehtml = "";
                    $attach = unserialize($each->attachment);
                    if (!empty($attach)) {
                        foreach ($attach as $file) {
                            $filehtml .= '<a href="' . wp_get_attachment_url($file) . '" target="_blank">' . get_the_title($file) . '</a>';
                        }
                    }
                }
                $user_data = get_userdata($each->user_id);
                $html .= '<div class="each-edd-message">';
                $html .= '<div class="each-edd-message-avatar">';
                $html .= get_avatar($each->user_id, '50') . '<br><label class="name">' . $user_data->data->user_login . '</label>';
                $html .= '</div>';
                $html .= '<div class="each-edd-message-right-cont">';
                $html .= '<div class="each-edd-message-msg">';
                $html .= $each->message;
                $html .= '</div>';
                $html .= '<div class="each-edd-message-attach"><div class="attach-head">' . __('Attachments', WBCOM_EDD_DASH_MSG_TEXT_DOMIAN) . '</div>';
                $html .= $filehtml;
                $html .= '</div>';
                $html .= '</div>';
                $html .= '<div style="clear:both;"></div>';
                $html .= '</div>';
            }
            $meta_data = edd_get_payment_meta_cart_details($id, true);
            $vendor = get_post_field('post_author', $meta_data[0]['id']);
            $user_id = get_current_user_id();
            $thread_status = get_post_meta($id, 'edd_user_order_thread', true);
            if (($vendor != $user_id ) && ( $thread_status != 'close' )) {
                $html .= '<div class="edd-close-thread-cont"> <a href="javascript:void();" class="edd-close-thread">Close Thread</a></div>';
                $html .= '<div style="clear:both;"></div>';
                $html .= '<div id="dialog-form">
								<p class="validateTips">' . __('All form fields are required.', WBCOM_EDD_DASH_MSG_TEXT_DOMIAN) . '</p>
								  <form method="post" action="">
									<fieldset>
									  <label for="name">' . __('Message', WBCOM_EDD_DASH_MSG_TEXT_DOMIAN) . '</label>';
                ob_start();
                $settings = array('media_buttons' => false, 'editor_height' => '150px', 'teeny' => true, 'quicktags' => false);
                add_filter('teeny_mce_buttons', array($this, 'edd_message_editor_buttons'), 10, 2);
                wp_editor('', 'rate_message', $settings);
                $html .= ob_get_clean();
                $html .= '<label for="rating">' . __('Rating', WBCOM_EDD_DASH_MSG_TEXT_DOMIAN) . '</label>
												  <div id="jRate" style="height:30px;"></div>
												  <input type="hidden" value="1" name="rating" id="rating">' . wp_nonce_field('edd_close_thread', 'edd_close_thread_nonce_field', false, false) . '<input type="hidden" value="' . $vendor . '" name="edd_vendor"><input type="hidden" value="' . $id . '" name="purchase_id"><input type="submit" class="edd-close-thread" name="add_close" value="Close">
									</fieldset>
								  </form>
								</div>';
            }
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Download History Shortcode
     *
     * Displays a user's download history.
     *
     * @since 1.0
     * @return string
     */
    public function wbcom_edd_download_history() {
        if (is_user_logged_in()) {
            ob_start();

            if (!edd_user_pending_verification()) {

                include( plugin_dir_path(__FILE__) . 'includes/edd-history_downloads.php');
            } else {

                edd_get_template_part('account', 'pending');
            }

            return ob_get_clean();
        }
    }

    public function wbcom_plugin_activate() {
        if (!get_option('edd_required_pages')) {
            //Create landing page correspondingly
            $pg_args = array(
                'post_type' => 'page',
                'post_title' => 'Manage Orders',
                'post_content' => '[wbcom_download_history]',
                'post_status' => 'publish'
            );
            wp_insert_post($pg_args);
            $pg_args1 = array(
                'post_type' => 'page',
                'post_title' => 'Single Order',
                'post_content' => '[wbcom_edd_receipt]',
                'post_status' => 'publish'
            );
            wp_insert_post($pg_args1);
            update_option('edd_required_pages', 1);
        }
    }

    /**
     * Receipt Shortcode
     *
     * Shows an order receipt.
     *
     * @since 1.4
     * @param array $atts Shortcode attributes
     * @param string $content
     * @return string
     */
    public function wbcom_edd_receipt_shortcode($atts, $content = null) {
        $this->edd_receipt_args = shortcode_atts(array(
            'error' => __('Sorry, trouble retrieving payment receipt.', 'easy-digital-downloads'),
            'price' => true,
            'discount' => true,
            'products' => true,
            'date' => true,
            'notes' => true,
            'payment_key' => false,
            'payment_method' => true,
            'payment_id' => true
                ), $atts, 'edd_receipt');

        $session = edd_get_purchase_session();
        if (isset($_GET['payment_key'])) {
            $payment_key = urldecode($_GET['payment_key']);
        } else if ($session) {
            $payment_key = $session['purchase_key'];
        } elseif ($this->edd_receipt_args['payment_key']) {
            $payment_key = $this->edd_receipt_args['payment_key'];
        }

        // No key found
        if (!isset($payment_key)) {
            return '<p class="edd-alert edd-alert-error">' . $this->edd_receipt_args['error'] . '</p>';
        }

        $payment_id = edd_get_purchase_id_by_key($payment_key);
        $user_can_view = edd_can_view_receipt($payment_key);

        // Key was provided, but user is logged out. Offer them the ability to login and view the receipt
        if (!$user_can_view && !empty($payment_key) && !is_user_logged_in() && !edd_is_guest_payment($payment_id)) {
            $this->edd_login_redirect = edd_get_current_page_url();
            ob_start();

            echo '<p class="edd-alert edd-alert-warn">' . __('You must be logged in to view this payment receipt.', 'easy-digital-downloads') . '</p>';
            edd_get_template_part('shortcode', 'login');

            $login_form = ob_get_clean();

            return $login_form;
        }

        /*
         * Check if the user has permission to view the receipt
         *
         * If user is logged in, user ID is compared to user ID of ID stored in payment meta
         *
         * Or if user is logged out and purchase was made as a guest, the purchase session is checked for
         *
         * Or if user is logged in and the user can view sensitive shop data
         *
         */


        if (!apply_filters('edd_user_can_view_receipt', array($this, $user_can_view, $this->edd_receipt_args))) {
            return '<p class="edd-alert edd-alert-error">' . $this->edd_receipt_args['error'] . '</p>';
        }

        ob_start();

        include( WBCOM_EDD_DASH_MSG_PATH . 'includes/edd-shortcode_receipt.php');
        $display = ob_get_clean();

        return $display;
    }

    /**
     * Get an array of all the log IDs using the EDD Logging Class
     * 
     * @return array if logs, null otherwise
     * @param $download_id Download's ID
     */
    public function get_log_ids($download_id = '') {
        // Instantiate a new instance of the class
        $edd_logging = new EDD_Logging;
        // get logs for this download with type of 'sale'
        $logs = $edd_logging->get_logs($download_id, 'sale');
        // if logs exist
        if ($logs) {
            // create array to store our log IDs into
            $log_ids = array();
            // add each log ID to the array
            foreach ($logs as $log) {
                $log_ids[] = $log->ID;
            }
            // return our array
            return $log_ids;
        }

        return null;
    }

    /**
     * Get array of payment IDs
     * 
     * @param int $download_id Download ID
     * @return array $payment_ids
     */
    public function get_payment_ids($download_id = '') {
        // these functions are used within a class, so you may need to update the function call
        $log_ids = $this->get_log_ids($download_id);
        if ($log_ids) {
            // create $payment_ids array
            $payment_ids = array();
            foreach ($log_ids as $id) {
                // get the payment ID for each corresponding log ID
                $payment_ids[] = get_post_meta($id, '_edd_log_payment_id', true);
            }

            // return our payment IDs
            return $payment_ids;
        }

        return null;
    }

}

add_action('plugins_loaded', array('Edd_Init_Vars', 'init_edd_class'));
?>
