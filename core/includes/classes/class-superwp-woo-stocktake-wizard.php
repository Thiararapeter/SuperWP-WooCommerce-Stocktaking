<?php

// Exit if accessed directly.
if (!defined('ABSPATH')) exit;

if (!class_exists('Superwp_Woo_Stocktake_Wizard')) :

    /**
     * Superwp_Woo_Stocktake_Wizard Class.
     */
    class Superwp_Woo_Stocktake_Wizard {

        /**
         * Constructor.
         */
        public function __construct() {
            add_action('admin_menu', array($this, 'add_wizard_menu'));
            add_action('admin_init', array($this, 'process_wizard'));
        }

        /**
         * Add wizard menu item.
         */
        public function add_wizard_menu() {
            add_submenu_page(
                'wc-stocktaking',
                'New Stocktake',
                'New Stocktake',
                'manage_options',
                'wc-stocktaking-wizard',
                array($this, 'wizard_page')
            );
        }

        /**
         * Wizard page content.
         */
        public function wizard_page() {
            $active_stocktake = $this->get_active_stocktake();
            if ($active_stocktake) {
                ?>
                <div class="wrap">
                    <h1>New Stocktake</h1>
                    <div class="notice notice-error">
                        <p>There is already an active stocktake. Please close the current stocktake before starting a new one.</p>
                    </div>
                    <p><a href="<?php echo admin_url('admin.php?page=wc-stocktaking&stocktake_id=' . $active_stocktake->ID); ?>" class="button">View Active Stocktake</a></p>
                </div>
                <?php
                return;
            }

            ?>
            <div class="wrap">
                <h1>Start New Stocktake</h1>
                <form id="stocktaking-wizard-form" method="post" action="">
                    <?php wp_nonce_field('wc_stocktaking_wizard', 'wc_stocktaking_wizard_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="stocktake-name">Stocktake Name</label></th>
                            <td><input type="text" id="stocktake-name" name="stocktake_name" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="stocktake-date">Stocktake Date</label></th>
                            <td><input type="date" id="stocktake-date" name="stocktake_date" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="include-out-of-stock">Include Out of Stock Products</label></th>
                            <td><input type="checkbox" id="include-out-of-stock" name="include_out_of_stock" value="1"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="product-categories">Product Categories</label></th>
                            <td>
                                <label><input type="checkbox" id="select-all-categories"> Select All</label><br>
                                <?php
                                $categories = get_terms('product_cat', array('hide_empty' => false));
                                foreach ($categories as $category) {
                                    echo '<label><input type="checkbox" name="product_categories[]" value="' . esc_attr($category->term_id) . '" class="category-checkbox"> ' . esc_html($category->name) . '</label><br>';
                                }
                                ?>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" name="start_stocktake" id="start-stocktake" class="button button-primary" value="Start Stocktake">
                    </p>
                </form>
            </div>
            <?php
        }

        /**
         * Process the wizard form submission.
         */
        public function process_wizard() {
            if (isset($_POST['start_stocktake']) && check_admin_referer('wc_stocktaking_wizard', 'wc_stocktaking_wizard_nonce')) {
                $stocktake_name = sanitize_text_field($_POST['stocktake_name']);
                $stocktake_date = sanitize_text_field($_POST['stocktake_date']);
                $include_out_of_stock = isset($_POST['include_out_of_stock']) ? 1 : 0;
                
                $all_categories = get_terms('product_cat', array('fields' => 'ids', 'hide_empty' => false));
                $selected_categories = isset($_POST['product_categories']) ? array_map('intval', $_POST['product_categories']) : array();
                
                // If all categories are selected, store an empty array to represent "all categories"
                $product_categories = (count($selected_categories) === count($all_categories)) ? array() : $selected_categories;

                $stocktake_id = wp_insert_post(array(
                    'post_title'    => $stocktake_name,
                    'post_type'     => 'stocktake',
                    'post_status'   => 'publish',
                ));

                if ($stocktake_id) {
                    update_post_meta($stocktake_id, '_stocktake_date', $stocktake_date);
                    update_post_meta($stocktake_id, '_include_out_of_stock', $include_out_of_stock);
                    update_post_meta($stocktake_id, '_product_categories', $product_categories);
                    update_post_meta($stocktake_id, '_stocktake_status', 'Open');

                    wp_redirect(admin_url('admin.php?page=wc-stocktaking&stocktake_id=' . $stocktake_id));
                    exit;
                }
            }
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
    }

endif;
