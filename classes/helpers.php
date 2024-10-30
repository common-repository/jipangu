<?php
class Meow_JPNG_Helpers
{
    static function is_rest()
    {
        // WP_REST_Request init.
        $is_rest_request = defined('REST_REQUEST') && REST_REQUEST;
        if ($is_rest_request) {
            return self::verify_rest_nonce();
        }

        // Plain permalinks.
        $prefix = rest_get_url_prefix();
        $request_contains_rest = isset($_GET['rest_route']) && strpos(trim($_GET['rest_route'], '\\/'), $prefix, 0) === 0;
        if ($request_contains_rest) {
            return self::verify_rest_nonce();
        }

        // It can happen that WP_Rewrite is not yet initialized, so better to do it.
        global $wp_rewrite;
        if ($wp_rewrite === null) {
            $wp_rewrite = new WP_Rewrite();
        }
        $rest_url = wp_parse_url(trailingslashit(get_rest_url()));
        $current_url = wp_parse_url(add_query_arg(array()));
        if (!$rest_url || !$current_url)
            return false;

        // URL Path begins with wp-json.
        if (!empty($current_url['path']) && !empty($rest_url['path'])) {
            $request_contains_rest = strpos($current_url['path'], $rest_url['path'], 0) === 0;
            if ($request_contains_rest) {
                return true;
            }
        }

        return false;
    }

    private static function verify_rest_nonce()
    {
        if (!isset($_SERVER['X-WP-Nonce'])) {
            return false;
        }

        $nonce = sanitize_text_field(wp_unslash($_SERVER['X-WP-Nonce']));
        return wp_verify_nonce($nonce, 'wp_rest');
    }
}