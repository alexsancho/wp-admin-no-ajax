<?php
/**
 * Plugin Name: admin-no-ajax
 * Plugin URI: https://github.com/alexsancho/wp-admin-no-ajax
 * Description: A plugin that lightens the WP AJAX routine and directs the requests to front-end rather than admin back-end.
 * Author: Alex Sancho
 * Author URI: http://alexsancho.name
 * License: MIT
 * License URI: https://github.com/alexsancho/wp-admin-no-ajax/blob/master/LICENSE
 * Version: 1.0.2
 */

namespace Asancho\Helper;

final class Admin_No_Ajax {
	public $keyword;

	private static $instance;

	public static function getInstane() {
		if ( null === self::$instance) {
			self::$instance = new self;
		}
	}

	private function __construct() {
		add_action( 'after_setup_theme', [ $this, 'init' ] );
	}

	public function init() {
		// Rewrite only public side admin urls
		if ( ! is_admin() ) {
			add_filter( 'admin_url', [ $this, 'redirect_ajax_url' ], 11, 3 );

			add_action( 'template_redirect', [ $this, 'run_ajax' ] );
		}

		// Register activation hook to flush the rewrites
		register_activation_hook( __FILE__, [ $this, 'activate' ] );
		add_action( 'init', [ $this, 'rewrite' ] );

		// Url keyword to use for the ajax calls. Is modifiable with filter "admin-no-ajax/keyword"
		if ( defined( 'WP_ADMIN_NO_AJAX_URL' ) ) {
			// keyword doesn't need to contain slashes because they are set in redirect_ajax_url()
			// trim slashes to avoid confusion
			$default_keyword = trim( WP_ADMIN_NO_AJAX_URL, '/' );
		} else {
			$default_keyword = 'admin-no-ajax';
		}

		$this->keyword = apply_filters( 'admin-no-ajax/keyword', $default_keyword );
	}

	// Function that handles rewriting the admin-ajax url to the one we want
	public function redirect_ajax_url( $url, $path, $blog_id ) {
		if ( strpos( $url, 'admin-ajax' ) ) {
			return home_url( '/' . $this->keyword . '/' );
		}

		return $url;
	}

	// Creates the rewrite
	public function rewrite() {
		global $wp_rewrite;

		add_rewrite_tag( '%admin-no-ajax%', '([0-9]+)' );

		// The whole ajax url matching pattern can be altered with filter "admin-no-ajax/rule"
		$default_rule = '^' . $this->keyword . '/?$';

		$rule = apply_filters( 'admin-no-ajax/rule', $default_rule );

		add_rewrite_rule(
			$rule,
			'index.php?admin-no-ajax=true',
			'top'
		);
	}

	// Runs the ajax calls. Equivalent to the real admin-ajax.php
	public function run_ajax() {
		global $wp_query;

		if ( $wp_query->get( 'admin-no-ajax' ) ) {
			// Constant for plugins to know that we are on an AJAX request
			define( 'DOING_AJAX', true );

			// If we don't have an action, do nothing
			if ( ! isset( $_REQUEST['action'] ) ) {
				die( 0 );
			}

			// Escape the parameter to prevent disastrous things
			$action = esc_attr( $_REQUEST['action'] );

			// Run customized admin-no-ajax methods with action "admin-no-ajax/before"
			do_action( 'admin-no-ajax/before' );

			// Run customized admin-no-ajax methods for specific ajax actions with "admin-no-ajax/before/{action}"
			do_action( 'admin-no-ajax/before/' . $action );

			// Same headers as WordPress normal AJAX routine sends
			$default_headers = [
				'Content-Type: text/html; charset=' . get_option( 'blog_charset' ),
				'X-Robots-Tag: noindex',
			];

			// Filter to customize the headers sent by ajax calls
			$headers = apply_filters( 'admin-no-ajax/headers', $default_headers );

			// Send the headers to the user
			if ( is_array( $headers ) && count( $headers ) > 0 ) {
				foreach ( $headers as $header ) {
					@header( $header );
				}
			}

			send_nosniff_header();
			nocache_headers();

			// Run the actions
			if ( is_user_logged_in() ) {
				do_action( 'wp_ajax_' . $action );
			} else {
				do_action( 'wp_ajax_nopriv_' . $action );
			}

			die( 0 );
		}
	}

	// Run activate during plugin activation
	public function activate() {
		global $wp_rewrite;
		$this->rewrite();
		$wp_rewrite->flush_rules();
	}
}

Admin_No_Ajax::getInstane();
