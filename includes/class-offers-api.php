<?php
class Offers_API {

    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes() {
        register_rest_route('offers/v1', '/active', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_active_offers'],
            'permission_callback' => [$this, 'validate_api_key'],
        ]);
    }

    public function validate_api_key(WP_REST_Request $request) {
        $provided_key = $request->get_header('X-API-Key');
        $saved_key    = get_option('wp_offers_api_key');
        return ($provided_key && $provided_key === $saved_key);
    }

    public function get_active_offers() {
        $offers = Offers_DB::get_active_offers();
        return rest_ensure_response([
            'success' => true,
            'count'   => count($offers),
            'data'    => $offers,
        ]);
    }
}
new Offers_API();