<?php
class Offers_DB {

    public static function create_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'offers';
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            discount VARCHAR(50),
            discount_code VARCHAR(100) DEFAULT '',
            status ENUM('active','inactive') DEFAULT 'active',
            expiry_date DATE NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public static function get_active_offers() {
        global $wpdb;
        $table = $wpdb->prefix . 'offers';
        $today = current_time('Y-m-d');

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table
                 WHERE status = 'active'
                 AND expiry_date >= %s
                 ORDER BY created_at DESC",
                $today
            )
        );
    }

    public static function insert_offer($data) {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'offers', [
            'title'         => sanitize_text_field($data['title']),
            'description'   => sanitize_textarea_field($data['description']),
            'discount'      => sanitize_text_field($data['discount']),
            'discount_code' => sanitize_text_field($data['discount_code']),
            'status'        => sanitize_text_field($data['status']),
            'expiry_date'   => sanitize_text_field($data['expiry_date']),
        ]);
    }

    public static function delete_offer($id) {
        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'offers', ['id' => intval($id)]);
    }
}