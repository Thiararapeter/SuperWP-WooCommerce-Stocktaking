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
                    <div class="notice notice-info">
                        <p>There is an active stocktake: <a href="<?php echo admin_url('admin.php?page=wc-stocktaking&stocktake_id=' . $active_stocktake->ID); ?>"><?php echo esc_html($active_stocktake->post_title); ?></a></p>
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
                wp_die('Invalid stocktake.');
            }

            $stocktake_date = get_post_meta($stocktake_id, '_stocktake_date', true);
            $sku_count_with_discrepancies = get_post_meta($stocktake_id, '_sku_count_with_discrepancies', true);
            $total_value = get_post_meta($stocktake_id, '_total_value', true);
            $status = get_post_meta($stocktake_id, '_stocktake_status', true);
            $discrepancies = get_post_meta($stocktake_id, '_stock_discrepancies', true);
            $total_counted = get_post_meta($stocktake_id, '_total_counted', true);
            $total_expected = get_post_meta($stocktake_id, '_total_expected', true); // Assuming this meta key exists

            $total_expected = 0;
            $total_counted = 0;
            $total_value = 0;
            $sku_count_with_discrepancies = 0;

            foreach ($discrepancies as $product_id => $data) {
                $total_expected += $data['expected'];
                $total_counted += $data['counted'];
                $product = wc_get_product($product_id);
                if ($product) {
                    $total_value += $product->get_price() * $data['counted'];
                }
                if ($data['discrepancy'] != 0) {
                    $sku_count_with_discrepancies++;
                }
            }

            update_post_meta($stocktake_id, '_total_expected', $total_expected);
            update_post_meta($stocktake_id, '_total_counted', $total_counted);
            update_post_meta($stocktake_id, '_total_value', $total_value);
            update_post_meta($stocktake_id, '_sku_count_with_discrepancies', $sku_count_with_discrepancies);

            ?>
            <div class="wrap stocktake-report-wrap">
                <div class="stocktake-report-header">
                    <h1 class="stocktake-report-title">Stocktake Report: <?php echo esc_html($stocktake->post_title); ?></h1>
                    <div class="stocktake-report-meta">
                        <span>Date: <?php echo esc_html($stocktake_date); ?></span> | 
                        <span>Status: <?php echo esc_html($status); ?></span>
                    </div>
                </div>

                <div class="stocktake-report-content">
                    <!-- Stocktake Details Section -->
                    <div class="stocktake-details">
                        <h2>Stocktake Details</h2>
                        <div class="stocktake-details-grid">
                            <div class="detail-card">
                                <h3>Total Expected</h3>
                                <p class="detail-value"><?php echo esc_html(get_post_meta($stocktake_id, '_total_expected', true)); ?></p>
                            </div>
                            <div class="detail-card">
                                <h3>Total Counted</h3>
                                <p class="detail-value"><?php echo esc_html(get_post_meta($stocktake_id, '_total_counted', true)); ?></p>
                            </div>
                            <div class="detail-card">
                                <h3>Total Value</h3>
                                <p class="detail-value"><?php echo wc_price(get_post_meta($stocktake_id, '_total_value', true)); ?></p>
                            </div>
                            <div class="detail-card">
                                <h3>SKUs with Discrepancies</h3>
                                <p class="detail-value"><?php echo esc_html(get_post_meta($stocktake_id, '_sku_count_with_discrepancies', true)); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Sections -->
                    <div class="stocktake-report-section report">
                        <?php $this->display_discrepancy_details($discrepancies); ?>
                        <?php $this->display_products_with_discrepancies($discrepancies); ?>
                    </div>

                    <div class="stocktake-report-section audit">
                        <?php $this->display_audit_section($stocktake_id); ?>
                    </div>

                    <!-- Move Discrepancy Analysis to the end -->
                    <div class="stocktake-report-section discrepancy-analysis">
                        <h2>Discrepancy Analysis</h2>
                        <div class="stocktake-report-summary">
                            <div class="summary-card">
                                <h3>Total Discrepancy</h3>
                                <p><?php echo esc_html($this->calculate_total_discrepancy($discrepancies)); ?></p>
                            </div>
                            <div class="summary-card">
                                <h3>Positive Discrepancies</h3>
                                <p><?php echo esc_html($this->count_positive_discrepancies($discrepancies)); ?></p>
                            </div>
                            <div class="summary-card">
                                <h3>Negative Discrepancies</h3>
                                <p><?php echo esc_html($this->count_negative_discrepancies($discrepancies)); ?></p>
                            </div>
                        </div>
                        <h3>Reasons for Discrepancies</h3>
                        <p>To be Explained in Audit</p>
                    </div>
                </div>

                <div class="stocktake-report-actions">
                    <a href="<?php echo admin_url('admin.php?page=wc-stocktaking-reports'); ?>" class="button">Back to Reports</a>
                    <?php if ($status === 'Open') : ?>
                        <a href="<?php echo admin_url('admin.php?page=wc-stocktaking-close&stocktake_id=' . $stocktake_id); ?>" class="button button-primary">Close Stocktake</a>
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
            <h2>Discrepancy Summary</h2>
            <table class="stocktake-report-table">
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

        private function display_discrepancy_details($discrepancies) {
            // Remove the entire table and loop structure
            // Instead, you might want to add a comment explaining why this section is empty
            // or call the method that correctly handles this functionality
            
            // For example:
            // $this->display_working_discrepancy_details($discrepancies);
            
            // Or simply leave a comment:
            // Discrepancy details are handled in another section of the report
        }

        private function display_discrepancy_analysis($discrepancies) {
            $total_discrepancy = 0;
            $positive_discrepancies = 0;
            $negative_discrepancies = 0;
            $reasons = [];

            foreach ($discrepancies as $data) {
                $total_discrepancy += $data['discrepancy'];
                if ($data['discrepancy'] > 0) {
                    $positive_discrepancies++;
                } elseif ($data['discrepancy'] < 0) {
                    $negative_discrepancies++;
                }
                if (isset($data['reason'])) {
                    $reasons[$data['reason']] = ($reasons[$data['reason']] ?? 0) + 1;
                }
            }

            ?>
            <h2>Discrepancy Analysis</h2>
            <div class="stocktake-report-summary">
                <div class="summary-card">
                    <h3>Total Discrepancy</h3>
                    <p><?php echo esc_html($total_discrepancy); ?></p>
                </div>
                <div class="summary-card">
                    <h3>Positive Discrepancies</h3>
                    <p><?php echo esc_html($positive_discrepancies); ?></p>
                </div>
                <div class="summary-card">
                    <h3>Negative Discrepancies</h3>
                    <p><?php echo esc_html($negative_discrepancies); ?></p>
                </div>
            </div>
            
            <h3>Reasons for Discrepancies</h3>
            <ul>
                <?php foreach ($reasons as $reason => $count) : ?>
                    <li><?php echo esc_html($reason); ?>: <?php echo esc_html($count); ?></li>
                <?php endforeach; ?>
            </ul>
            <?php
        }

        private function display_audit_section($stocktake_id) {
            $discrepancies = get_post_meta($stocktake_id, '_stock_discrepancies', true);
            ?>
            <div class="audit-section">
                <h2>Audit and Follow-up</h2>
                <form method="post" action="">
                    <?php wp_nonce_field('stocktake_audit_action', 'stocktake_audit_nonce'); ?>
                    <input type="hidden" name="stocktake_id" value="<?php echo esc_attr($stocktake_id); ?>">
                    <table class="audit-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Discrepancy</th>
                                <th>Variance Type</th>
                                <th>Initial Reason</th>
                                <th>Follow-up Action</th>
                                <th>Updated Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($discrepancies as $product_id => $data) : 
                                $product = wc_get_product($product_id);
                                if (!$product) continue;
                                $audit_data = get_post_meta($stocktake_id, "_audit_data_{$product_id}", true) ?: array();
                                $variance_type = $data['discrepancy'] > 0 ? 'Positive Variance' : 'Negative Variance';
                                ?>
                                <tr>
                                    <td><?php echo esc_html($product->get_name()); ?></td>
                                    <td class="<?php echo $data['discrepancy'] > 0 ? 'discrepancy-positive' : ($data['discrepancy'] < 0 ? 'discrepancy-negative' : ''); ?>">
                                        <?php echo esc_html($data['discrepancy']); ?>
                                    </td>
                                    <td><?php echo esc_html($variance_type); ?></td>
                                    <td><?php echo esc_html($data['reason'] ?? 'Unknown'); ?></td>
                                    <td>
                                        <select name="audit[<?php echo esc_attr($product_id); ?>][action]">
                                            <option value="">Select an action</option>
                                            <option value="Investigate" <?php selected($audit_data['action'] ?? '', 'Investigate'); ?>>Investigate</option>
                                            <option value="Adjust Stock" <?php selected($audit_data['action'] ?? '', 'Adjust Stock'); ?>>Adjust Stock</option>
                                            <option value="No Action" <?php selected($audit_data['action'] ?? '', 'No Action'); ?>>No Action</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" 
                                               name="audit[<?php echo esc_attr($product_id); ?>][updated_reason]" 
                                               value="<?php echo esc_attr($audit_data['updated_reason'] ?? ''); ?>" 
                                               placeholder="Enter updated reason">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <button type="submit" name="update_audit" class="update-audit-button">Update Audit</button>
                </form>
            </div>
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

        private function display_products_without_discrepancies($products) {
            if (empty($products)) {
                echo '<p>No products without discrepancies.</p>';
                return;
            }
            ?>
            <table class="stocktake-report-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Expected Stock</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product_id => $data) : 
                        $product = wc_get_product($product_id);
                        if (!$product) continue; ?>
                        <tr>
                            <td><?php echo esc_html($product->get_name()); ?></td>
                            <td><?php echo esc_html($product->get_sku()); ?></td>
                            <td><?php echo esc_html($data['expected']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php
        }

        // New method to display products with discrepancies
        private function display_products_with_discrepancies($discrepancies) {
            if (empty($discrepancies)) {
                echo '<p>No products with discrepancies.</p>';
                return;
            }
            ?>
            <table class="stocktake-report-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Expected</th>
                        <th>Counted</th>
                        <th>Discrepancy</th>
                        <th>Variance Type</th>
                        <th>Initial Reason</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    foreach ($discrepancies as $product_id => $data) : 
                        $product = wc_get_product($product_id);
                        if (!$product) continue;
                        $variance_type = $data['discrepancy'] > 0 ? 'Positive Variance' : ($data['discrepancy'] < 0 ? 'Negative Variance' : 'No Variance');
                        ?>
                        <tr>
                            <td><?php echo esc_html($product->get_name()); ?></td>
                            <td><?php echo esc_html($product->get_sku()); ?></td>
                            <td><?php echo esc_html($data['expected']); ?></td>
                            <td><?php echo esc_html($data['counted']); ?></td>
                            <td class="<?php echo $data['discrepancy'] > 0 ? 'discrepancy-positive' : ($data['discrepancy'] < 0 ? 'discrepancy-negative' : ''); ?>">
                                <?php echo esc_html($data['discrepancy']); ?>
                            </td>
                            <td><?php echo esc_html($variance_type); ?></td>
                            <td><?php echo esc_html($data['reason'] ?? 'N/A'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php
        }

        private function calculate_total_discrepancy($discrepancies) {
            $total_discrepancy = 0;

            foreach ($discrepancies as $data) {
                $total_discrepancy += $data['discrepancy']; // Assuming 'discrepancy' is a key in the $data array
            }

            return $total_discrepancy;
        }

        private function count_positive_discrepancies($discrepancies) {
            $count = 0;

            foreach ($discrepancies as $data) {
                if ($data['discrepancy'] > 0) {
                    $count++;
                }
            }

            return $count;
        }

        private function count_negative_discrepancies($discrepancies) {
            $count = 0;

            foreach ($discrepancies as $data) {
                if ($data['discrepancy'] < 0) {
                    $count++;
                }
            }

            return $count;
        }
    }

endif;
