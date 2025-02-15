<?php
/*
Plugin Name: WooCommerce Email Translator
Description: A plugin to send order email confirmation link and track email statistics.
Version: 1.0
Author: Faraz Khan
Text Domain: wc-email-translator
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class WC_Email_Translator {

    public function __construct() {
        add_action('init', array($this, 'load_textdomain'));
        add_action('admin_menu', array($this, 'register_admin_pages'));
        add_action('woocommerce_thankyou', array($this, 'send_confirmation_email'), 10, 1);
        add_action('wp', array($this, 'handle_confirmation_link'));
    }

    public function load_textdomain() {
        load_plugin_textdomain('wc-email-translator', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function register_admin_pages() {
        add_menu_page(
            __('Email Translator Settings', 'wc-email-translator'),
            __('Email Translator', 'wc-email-translator'),
            'manage_options',
            'wc-email-translator-settings',
            array($this, 'settings_page_callback'),
            'dashicons-email-alt',
            56
        );

        add_submenu_page(
            'wc-email-translator-settings',
            __('Statistics', 'wc-email-translator'),
            __('Statistics', 'wc-email-translator'),
            'manage_options',
            'wc-email-translator-statistics',
            array($this, 'statistics_page_callback')
        );
    }

    public function settings_page_callback() {
        if (!current_user_can('manage_options')) {
            return;
        }
        if (isset($_POST['submit'])) {
            update_option('wc_email_translator_content', sanitize_text_field($_POST['wc_email_translator_content']));
        }
        $email_content = get_option('wc_email_translator_content', __('Default email content', 'wc-email-translator'));
        ?>
        <div class="wrap">
            <h1><?php _e('Email Translator Settings', 'wc-email-translator'); ?></h1>
            <form method="post">
                <textarea name="wc_email_translator_content" rows="10" cols="50"><?php echo esc_textarea($email_content); ?></textarea>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function statistics_page_callback() {
        // Dummy data for the example
        $emails_sent = get_option('wc_email_translator_emails_sent', 0);
        $emails_confirmed = get_option('wc_email_translator_emails_confirmed', 0);
        ?>
        <div class="wrap">
            <h1><?php _e('Email Statistics', 'wc-email-translator'); ?></h1>
            <canvas id="emailStatisticsChart" width="400" height="200"></canvas>
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
                var ctx = document.getElementById('emailStatisticsChart').getContext('2d');
                var chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['Emails Sent', 'Emails Confirmed'],
                        datasets: [{
                            label: '<?php _e('Email Statistics', 'wc-email-translator'); ?>',
                            data: [<?php echo $emails_sent; ?>, <?php echo $emails_confirmed; ?>],
                            backgroundColor: ['#0073aa', '#00a32a']
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            </script>
        </div>
        <?php
    }

    public function send_confirmation_email($order_id) {
        $order = wc_get_order($order_id);
        $email_content = get_option('wc_email_translator_content', __('Default email content', 'wc-email-translator'));
        $confirmation_link = add_query_arg(array('wc_confirm' => 'yes', 'order_id' => $order_id), home_url());

        $message = $email_content . "\n\n" . __('Please confirm your order by clicking the link below:', 'wc-email-translator') . "\n" . $confirmation_link;

        wp_mail($order->get_billing_email(), __('Order Confirmation', 'wc-email-translator'), $message);

        $emails_sent = get_option('wc_email_translator_emails_sent', 0);
        update_option('wc_email_translator_emails_sent', ++$emails_sent);
    }

    public function handle_confirmation_link() {
        if (isset($_GET['wc_confirm']) && $_GET['wc_confirm'] == 'yes' && isset($_GET['order_id'])) {
            $order_id = intval($_GET['order_id']);
            // Mark the order as confirmed (you can handle it as per your need)
            update_post_meta($order_id, '_wc_email_translator_confirmed', 'yes');

            $emails_confirmed = get_option('wc_email_translator_emails_confirmed', 0);
            update_option('wc_email_translator_emails_confirmed', ++$emails_confirmed);

            wp_redirect(home_url()); // Redirect to the homepage or any other page
            exit;
        }
    }
}

new WC_Email_Translator();

