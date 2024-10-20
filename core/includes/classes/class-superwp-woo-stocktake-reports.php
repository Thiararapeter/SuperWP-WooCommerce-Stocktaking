<?php

// Exit if accessed directly.
if (!defined('ABSPATH')) exit;

if (!class_exists('Superwp_Woo_Stocktake_Reports')) :

    /**
     * Superwp_Woo_Stocktake_Reports Class.
     */
    class Superwp_Woo_Stocktake_Reports {

        /**
         * Constructor.
         */
        public function __construct() {
            add_action('admin_menu', array($this, 'add_reports_menu'));
            add_action('admin_init', array($this, 'handle_audit_submission'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
        }

        /**
         * Add reports menu item.
         */
        public function add_reports_menu() {
            add_submenu_page(
                'wc-stocktaking',
                'Stocktaking Reports',
                'Reports',
                'manage_options',
                'wc-stocktaking-reports',
                array($this, 'reports_page')
            );
        }

        /**
         * Reports page content.
         */
        public function reports_page() {
            // Add a body class for our custom styles
            add_filter('admin_body_class', function($classes) {
                return $classes . ' stocktake-report-page';
            });

            if (isset($_GET['audit_updated'])) {
                ?>
                <div class="notice notice-success is-dismissible">
                    <p>Audit data updated successfully.</p>
                </div>
                <?php
            }

            $stocktake_id = isset($_GET['stocktake_id']) ? intval($_GET['stocktake_id']) : 0;

            if ($stocktake_id) {
                $this->single_report($stocktake_id);
            } else {
                $this->reports_overview();
            }
        }

        /**
         * Reports overview.
         */
        private function reports_overview() {
            $active_stocktake = $this->get_active_stocktake();
            ?>
            <div class="wrap">
                <h1>Stocktaking Reports</h1>
                
                <?php if ($active_stocktake) : ?>
                    <div class="notice notice-info" style="font-weight: bold; background-color: #0073aa; padding: 10px; border-radius: 5px;">
                        <p>There is an active stocktake: <a href="<?php echo admin_url('admin.php?page=wc-stocktaking&stocktake_id=' . $active_stocktake->ID); ?>" class="active-stocktake-link"><?php echo esc_html($active_stocktake->post_title); ?></a></p>
                    </div>
                <?php endif; ?>

                <h2>Recent Stocktakes</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Stocktake Name</th>
                            <th>Date</th>
                            <th>Total Counted</th>
                            <th>Total Value</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $args = array(
                            'post_type' => 'stocktake',
                            'posts_per_page' => 10,
                            'orderby' => 'date',
                            'order' => 'DESC',
                        );
                        $stocktakes = get_posts($args);
                        foreach ($stocktakes as $stocktake) :
                            $stocktake_id = $stocktake->ID;
                            $stocktake_date = get_post_meta($stocktake_id, '_stocktake_date', true);
                            $total_counted = get_post_meta($stocktake_id, '_total_counted', true);
                            $total_value = get_post_meta($stocktake_id, '_total_value', true);
                            $status = get_post_meta($stocktake_id, '_stocktake_status', true);
                            ?>
                            <tr>
                                <td><?php echo esc_html($stocktake->post_title); ?></td>
                                <td><?php echo esc_html($stocktake_date); ?></td>
                                <td><?php echo esc_html($total_counted); ?></td>
                                <td><?php echo wc_price($total_value); ?></td>
                                <td><?php echo esc_html($status); ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=wc-stocktaking-reports&stocktake_id=' . $stocktake_id); ?>">View Report</a>
                                    <?php if ($status === 'Open') : ?>
                                        | <a href="<?php echo admin_url('admin.php?page=wc-stocktaking-close&stocktake_id=' . $stocktake_id); ?>">Close Stocktake</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php
        }

        /**
         * Single stocktake report.
         */
        private function single_report($stocktake_id) {
            $stocktake = get_post($stocktake_id);
            if (!$stocktake || $stocktake->post_type !== 'stocktake') {
                ?>
                <div style="color: blue; text-transform: uppercase; text-decoration: underline; font-weight: bold; background-color: #ff0000; padding: 10px; border-radius: 5px;">
                    Invalid stocktake.
                </div>
                <?php
                return; // Exit the function after displaying the error
            }
            $stocktake_date = get_post_meta($stocktake_id, '_stocktake_date', true);
            $total_value = get_post_meta($stocktake_id, '_total_value', true);
            $status = get_post_meta($stocktake_id, '_stocktake_status', true);
            $discrepancies = get_post_meta($stocktake_id, '_stock_discrepancies', true) ?: array();
            $total_counted = get_post_meta($stocktake_id, '_total_counted', true);
            $total_expected = get_post_meta($stocktake_id, '_total_expected', true);
            $audit_status = get_post_meta($stocktake_id, '_audit_status', true) ?: 'Not Started';

            // Calculate new metrics
            $total_expected_value = 0;
            $total_counted_value = 0;
            $total_products = count($discrepancies);
            $products_with_discrepancies = 0;

            foreach ($discrepancies as $product_id => $data) {
                $product = wc_get_product($product_id);
                if ($product) {
                    $price = $product->get_price();
                    $total_expected_value += $data['expected'] * $price;
                    $total_counted_value += $data['counted'] * $price;
                    if ($data['discrepancy'] != 0) {
                        $products_with_discrepancies++;
                    }
                }
            }

            ?>
            <div class="wrap stocktake-report-wrap">
                <div class="stocktake-report-header">
                    <h1 class="stocktake-report-title"><?php echo esc_html($stocktake->post_title); ?></h1>
                    <div class="stocktake-report-meta">
                        <span>StockTake Date: <?php echo esc_html($stocktake_date); ?></span>
                        <span>Stocktake Status: <?php echo esc_html($status); ?></span>
                        <span>Audit Status: <?php echo esc_html($audit_status); ?></span>
                    </div>
                </div>

                <div class="stocktake-report-content">
                    <div class="stocktake-report-section">
                        <h2>Stocktake Overview</h2>
                        <table class="widefat striped">
                            <tr>
                                <th>Total Expected Value</th>
                                <td><?php echo wc_price($total_expected_value); ?></td>
                            </tr>
                            <tr>
                                <th>Total Counted Value</th>
                                <td><?php echo wc_price($total_counted_value); ?></td>
                            </tr>
                            <tr>
                                <th>Value Discrepancy</th>
                                <td><?php echo wc_price($total_expected_value - $total_counted_value); ?></td>
                            </tr>
                            <tr>
                                <th>Discrepancy Percentage</th>
                                <td><?php echo number_format(($total_expected_value - $total_counted_value) / $total_expected_value * 100, 2); ?>%</td>
                            </tr>
                            <tr>
                                <th>Total Products</th>
                                <td><?php echo esc_html($total_products); ?></td>
                            </tr>
                            <tr>
                                <th>Products with Discrepancies</th>
                                <td><?php echo esc_html($products_with_discrepancies); ?></td>
                            </tr>
                            <tr>
                                <th>Accuracy Rate</th>
                                <td><?php echo number_format(($total_products - $products_with_discrepancies) / $total_products * 100, 2); ?>%</td>
                            </tr>
                            <tr>
                                <th>Average Discrepancy per Product</th>
                                <td><?php echo wc_price(($total_expected_value - $total_counted_value) / $total_products); ?></td>
                            </tr>
                        </table>
                    </div>

                    <div class="stocktake-report-section">
                        <h2>Discrepancy Summary</h2>
                        <?php $this->display_discrepancy_summary($discrepancies); ?>
                    </div>

                    <div class="stocktake-report-section">
                        <h2>Products with Discrepancies</h2>
                        <?php $this->display_products_with_discrepancies($discrepancies); ?>
                    </div>
                </div>

                <div class="stocktake-report-actions">
                    <a href="<?php echo admin_url('admin.php?page=wc-stocktaking-reports'); ?>" class="button">Back to Reports</a>
                    <a href="<?php echo admin_url('admin.php?page=wc-stocktaking-audit&stocktake_id=' . $stocktake_id); ?>" class="button button-primary">Open Audit</a>
                    <?php if ($status === 'Open') : ?>
                        <a href="<?php echo admin_url('admin.php?page=wc-stocktaking-close&stocktake_id=' . $stocktake_id); ?>" class="button button-secondary">Close Stocktake</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php
        }

        /**
         * Get active stocktake.
         */
        private function get_active_stocktake() {
            $args = array(
                'post_type' => 'stocktake',
                'posts_per_page' => 1,
                'meta_query' => array(
                    array(
                        'key' => '_stocktake_status',
                        'value' => 'Open',
                    ),
                ),
            );
            $stocktakes = get_posts($args);
            return !empty($stocktakes) ? $stocktakes[0] : null;
        }

        private function display_discrepancy_summary($discrepancies) {
            $total_expected = 0;
            $total_counted = 0;
            $total_discrepancy = 0;
            $total_products = count($discrepancies);
            $products_with_discrepancies = 0;

            foreach ($discrepancies as $data) {
                $total_expected += $data['expected'];
                $total_counted += $data['counted'];
                $total_discrepancy += $data['discrepancy'];
                if ($data['discrepancy'] != 0) {
                    $products_with_discrepancies++;
                }
            }

            ?>
            <table class="widefat striped">
                <tr>
                    <th>Total Products</th>
                    <td><?php echo esc_html($total_products); ?></td>
                </tr>
                <tr>
                    <th>Products with Discrepancies</th>
                    <td><?php echo esc_html($products_with_discrepancies); ?></td>
                </tr>
                <tr>
                    <th>Total Expected</th>
                    <td><?php echo esc_html($total_expected); ?></td>
                </tr>
                <tr>
                    <th>Total Counted</th>
                    <td><?php echo esc_html($total_counted); ?></td>
                </tr>
                <tr>
                    <th>Total Discrepancy</th>
                    <td><?php echo esc_html($total_discrepancy); ?></td>
                </tr>
            </table>
            <?php
        }

        private function display_products_with_discrepancies($discrepancies) {
            if (empty($discrepancies)) {
                echo '<p>No products with discrepancies.</p>';
                return;
            }
            ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Expected</th>
                        <th>Counted</th>
                        <th>Discrepancy</th>
                        <th>Reason</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($discrepancies as $product_id => $data) :
                        $product = wc_get_product($product_id);
                        if (!$product) continue;
                        ?>
                        <tr>
                            <td><?php echo esc_html($product->get_name()); ?></td>
                            <td><?php echo esc_html($product->get_sku()); ?></td>
                            <td><?php echo esc_html($data['expected']); ?></td>
                            <td><?php echo esc_html($data['counted']); ?></td>
                            <td class="<?php echo $data['discrepancy'] > 0 ? 'positive' : ($data['discrepancy'] < 0 ? 'negative' : ''); ?>">
                                <?php echo esc_html($data['discrepancy']); ?>
                            </td>
                            <td><?php echo esc_html($data['reason'] ?? 'To Be Determined'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php
        }

        public function handle_audit_submission() {
            if (!isset($_POST['update_audit']) || !isset($_POST['stocktake_audit_nonce'])) {
                return;
            }

            if (!wp_verify_nonce($_POST['stocktake_audit_nonce'], 'stocktake_audit_action')) {
                wp_die('Security check failed');
            }

            $stocktake_id = isset($_POST['stocktake_id']) ? intval($_POST['stocktake_id']) : 0;
            if (!$stocktake_id) {
                wp_die('Invalid stocktake ID');
            }

            $audit_data = isset($_POST['audit']) ? $_POST['audit'] : array();
            foreach ($audit_data as $product_id => $data) {
                $product_id = intval($product_id);
                $action = sanitize_text_field($data['action']);
                $custom_action = sanitize_text_field($data['custom_action']);
                $updated_reason = sanitize_text_field($data['updated_reason']);

                // Use custom action if 'custom' is selected
                if ($action === 'custom' && !empty($custom_action)) {
                    $action = $custom_action;
                }

                update_post_meta($stocktake_id, "_audit_data_{$product_id}", array(
                    'action' => $action,
                    'updated_reason' => $updated_reason,
                ));
            }

            wp_redirect(add_query_arg('audit_updated', '1', wp_get_referer()));
            exit;
        }

        public function enqueue_styles() {
            wp_enqueue_style(
                'stocktake-reports-styles',
                plugin_dir_url(__FILE__) . '../assets/css/stocktake-reports.css',
                array(),
                SUPERWPSTOCKTAKE_VERSION
            );
        }
    }

endif;

function enqueue_stocktake_report_scripts() {
    wp_enqueue_script(
        'stocktake-report-scripts',
        plugin_dir_url(__FILE__) . 'includes/assets/js/stocktake-audit-report.js',
        array('jquery'),
        SUPERWPSTOCKTAKE_VERSION,
        true
    );
}
add_action('admin_enqueue_scripts', 'enqueue_stocktake_report_scripts');






