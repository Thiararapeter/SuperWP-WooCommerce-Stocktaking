<?php

// Exit if accessed directly.
if (!defined('ABSPATH')) exit;

if (!class_exists('Superwp_Woo_Stocktake_Audit')) :

class Superwp_Woo_Stocktake_Audit {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_audit_menu'));
        add_action('admin_init', array($this, 'maybe_handle_audit_submission'));
    }

    public function add_audit_menu() {
        add_submenu_page(
            'wc-stocktaking',
            'Stocktake Audit',
            'Audit',
            'manage_options',
            'wc-stocktaking-audit',
            array($this, 'render_audit_page')
        );
    }

    public function render_audit_page() {
        // Add a body class for our custom styles
        add_filter('admin_body_class', function($classes) {
            return $classes . ' stocktake-audit-page';
        });

        $this->enqueue_styles();

        $stocktake_id = isset($_GET['stocktake_id']) ? intval($_GET['stocktake_id']) : 0;

        if ($stocktake_id) {
            $this->render_single_audit($stocktake_id);
        } else {
            $this->render_audit_list();
        }
    }

    private function render_single_audit($stocktake_id) {
        $stocktake = get_post($stocktake_id);
        if (!$stocktake || $stocktake->post_type !== 'stocktake') {
            wp_die('Invalid stocktake.');
        }

        $discrepancies = get_post_meta($stocktake_id, '_stock_discrepancies', true);
        $stocktake_date = get_post_meta($stocktake_id, '_stocktake_date', true);
        $status = get_post_meta($stocktake_id, '_stocktake_status', true);

        ?>
        <div class="wrap stocktake-audit-wrap">
            <div class="stocktake-audit-header">
                <h1 class="stocktake-audit-title">Audit for Stocktake: <?php echo esc_html($stocktake->post_title); ?></h1>
                <div class="stocktake-audit-meta">
                    <span>Date: <?php echo esc_html($stocktake_date); ?></span> | 
                    <span>Status: <?php echo esc_html($status); ?></span>
                </div>
            </div>
            <div class="stocktake-audit-content">
                <form method="post" action="">
                    <?php wp_nonce_field('stocktake_audit_action', 'stocktake_audit_nonce'); ?>
                    <input type="hidden" name="stocktake_id" value="<?php echo esc_attr($stocktake_id); ?>">
                    <table class="stocktake-audit-table">
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
                                    <td><?php echo esc_html($data['discrepancy']); ?></td>
                                    <td class="variance-type <?php echo esc_attr(strtolower(str_replace(' ', '-', $variance_type))); ?>">
                                        <?php echo esc_html($variance_type); ?>
                                    </td>
                                    <td><?php echo esc_html($data['reason'] ?? 'Unknown'); ?></td>
                                    <td>
                                        <select name="audit[<?php echo esc_attr($product_id); ?>][action]" class="audit-action-select" onchange="toggleCustomActionInput(this)">
                                            <option value="">Select an action</option>
                                            <option value="Investigate" <?php selected($audit_data['action'] ?? '', 'Investigate'); ?>>Investigate</option>
                                            <option value="Adjust Stock" <?php selected($audit_data['action'] ?? '', 'Adjust Stock'); ?>>Adjust Stock</option>
                                            <option value="No Action" <?php selected($audit_data['action'] ?? '', 'No Action'); ?>>No Action</option>
                                            <option value="Custom" <?php selected($audit_data['action'] ?? '', 'Custom'); ?>>Custom</option>
                                        </select>
                                        <input type="text" 
                                               name="audit[<?php echo esc_attr($product_id); ?>][custom_action]" 
                                               value="<?php echo esc_attr($audit_data['custom_action'] ?? ''); ?>" 
                                               placeholder="Enter custom action" 
                                               class="custom-action-input" 
                                               style="display: <?php echo ($audit_data['action'] ?? '') === 'Custom' ? 'block' : 'none'; ?>; width: 100%; padding: 8px; margin-top: 5px;">
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
                    <div class="stocktake-audit-actions">
                        <input type="submit" name="update_audit" class="button button-primary" value="Update Audit">
                    </div>
                </form>
            </div>
        </div>

        <script>
            function toggleCustomActionInput(selectElement) {
                const customActionInput = selectElement.closest('tr').querySelector('.custom-action-input');
                if (selectElement.value === 'Custom') {
                    customActionInput.style.display = 'block';
                    customActionInput.focus(); // Focus on the input for easy typing
                } else {
                    customActionInput.style.display = 'none';
                    customActionInput.value = ''; // Clear the input if not custom
                }
            }
        </script>
        <?php
    }

    private function get_recent_stocktakes() {
        $args = array(
            'post_type' => 'stocktake',
            'posts_per_page' => 10,
            'orderby' => 'date',
            'order' => 'DESC',
        );
        $stocktakes = get_posts($args);
        
        $formatted_stocktakes = array();
        foreach ($stocktakes as $stocktake) {
            $total_counted = get_post_meta($stocktake->ID, '_total_counted', true);
            $total_value = get_post_meta($stocktake->ID, '_total_value', true);
            
            $formatted_stocktakes[] = array(
                'id' => $stocktake->ID,
                'name' => $stocktake->post_title,
                'date' => get_post_meta($stocktake->ID, '_stocktake_date', true),
                'total_counted' => $total_counted !== '' ? $total_counted : 'N/A',
                'total_value' => $total_value !== '' ? wc_price($total_value) : 'N/A',
                'status' => get_post_meta($stocktake->ID, '_stocktake_status', true),
            );
        }
        
        return $formatted_stocktakes;
    }

    public function render_audit_list() {
        $stocktakes = $this->get_recent_stocktakes();
        ?>
        <div class="wrap">
            <h1>Stocktaking Audit</h1>
            
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
                    <?php foreach ($stocktakes as $stocktake) : ?>
                        <tr>
                            <td><?php echo esc_html($stocktake['name']); ?></td>
                            <td><?php echo esc_html($stocktake['date']); ?></td>
                            <td><?php echo esc_html($stocktake['total_counted']); ?></td>
                            <td><?php echo $stocktake['total_value']; ?></td>
                            <td><?php echo esc_html($stocktake['status']); ?></td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=wc-stocktaking-audit&stocktake_id=' . $stocktake['id'])); ?>" class="button">View Audit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function maybe_handle_audit_submission() {
        if (isset($_POST['update_audit']) && check_admin_referer('stocktake_audit_action', 'stocktake_audit_nonce')) {
            $stocktake_id = intval($_POST['stocktake_id']);
            $audit_data = isset($_POST['audit']) ? $_POST['audit'] : array();

            foreach ($audit_data as $product_id => $data) {
                $product_id = intval($product_id);
                $action = sanitize_text_field($data['action']);
                $updated_reason = sanitize_text_field($data['updated_reason']);

                update_post_meta($stocktake_id, "_audit_data_{$product_id}", array(
                    'action' => $action,
                    'updated_reason' => $updated_reason,
                ));
            }

            wp_redirect(add_query_arg('audit_updated', '1', wp_get_referer()));
            exit;
        }
    }

    public function enqueue_styles() {
        wp_enqueue_style(
            'stocktake-audit-styles',
            plugin_dir_url(__FILE__) . '../assets/css/stocktake-audit.css',
            array(),
            SUPERWPSTOCKTAKE_VERSION
        );
    }
}

endif; // End if class_exists check.
