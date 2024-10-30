<?php
/*
Plugin Name: C4D Social Login
Plugin URI: http://coffee4dev.com/
Description: Social Login 
Author: Coffee4dev.com
Author URI: http://coffee4dev.com/
Text Domain: c4d-social-login
Version: 2.0.0
*/

define('C4DSOCIALLOGIN_PLUGIN_URI', plugins_url('', __FILE__));
define('C4DSOCIALLOGIN', 'c4d_social_login');
add_action('wp_enqueue_scripts', 'c4d_social_login_safely_add_stylesheet_to_frontsite');
add_filter('login_form_bottom', 'c4d_social_login_form_bottom', 10);
add_action('wp_ajax_c4d_social_login_validate', 'c4d_social_login_validate');
add_action('wp_ajax_nopriv_c4d_social_login_validate', 'c4d_social_login_validate');
add_shortcode('c4d-social-login-form-login', 'c4d_social_login_form_login');
add_filter( 'plugin_row_meta', 'c4d_social_login_plugin_row_meta', 10, 2 );

function c4d_social_login_plugin_row_meta( $links, $file ) {
    if ( strpos( $file, basename(__FILE__) ) !== false ) {
        $new_links = array(
            'visit' => '<a href="http://coffee4dev.com">Visit Plugin Site</<a>',
            'forum' => '<a href="http://coffee4dev.com/forums/">Forum</<a>',
            'premium' => '<a href="http://coffee4dev.com">Premium Support</<a>'
        );
        
        $links = array_merge( $links, $new_links );
    }
    
    return $links;
}

function c4d_social_login_safely_add_stylesheet_to_frontsite() {
	if(!defined('C4DPLUGINMANAGER')) {
		wp_enqueue_style( 'c4d-social-login-frontsite-style', C4DSOCIALLOGIN_PLUGIN_URI.'/assets/default.css' );
		wp_enqueue_script( 'c4d-social-login-frontsite-plugin-js', C4DSOCIALLOGIN_PLUGIN_URI.'/assets/default.js', array( 'jquery' ), false, true ); 
	}
	wp_localize_script( 'jquery', C4DSOCIALLOGIN,
	        array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
}
function c4d_social_login_form_bottom($content) {
	return $content.'<div class="c4d-social-login-separate">'.esc_html__('Or', 'c4d-social-login').'</div>'.c4d_social_login_button_facebook().c4d_social_login_button_google();
}
function c4d_social_login_button_facebook() {
	return apply_filters('c4d_social_login_button_facebook', '<div class="c4d-social-login facebook"><a data-nonce="'.wp_create_nonce(C4DSOCIALLOGIN).'" onclick="c4d_social_login.checkLogin(this, \'fb\'); return false;" href="#">'.esc_html__('Login With Facebook', 'c4d-social-login').'</a></div>');
}

function c4d_social_login_button_google() {
	return apply_filters('c4d_social_login_button_google', '<div class="c4d-social-login google"><a data-nonce="'.wp_create_nonce(C4DSOCIALLOGIN).'" onclick="c4d_social_login.checkLogin(this, \'google\'); return false;" href="#">'.esc_html__('Login With Google', 'c4d-social-login').'</a></div>');
}

function c4d_social_login($user, $pass, $remember = true) {
	if (!is_user_logged_in()) {
		$secure_cookie = is_ssl();
		$userId = username_exists($user);
		if ($userId) {
			wp_set_auth_cookie($userId, true, $secure_cookie);
			return $userId;
		}
	}
	return false;
}

function c4d_social_login_validate() {
	// check user exist and login
	// if user does not exist, create new user and auto login
	// email emtpy, if id@facebook.com 
	if (!isset($_REQUEST['security'])) return false;
	if (!wp_verify_nonce( $_REQUEST['security'], C4DSOCIALLOGIN )) return false;
	if (isset($_REQUEST['data'])) {
		if ($_REQUEST['model'] == 'fb') {
			$access_token = isset( $_REQUEST['data']['authResponse']['accessToken'] ) ? $_REQUEST['data']['authResponse']['accessToken'] : '';
			// Get user from Facebook with given access token
			$fb_url = add_query_arg(
				array(
					'fields'            =>  'id,email',
					'access_token'      =>  $access_token,
				),
				'https://graph.facebook.com/v2.4/'.$_REQUEST['data']['authResponse']['userID']
			);

			$response = wp_remote_get( esc_url_raw( $fb_url ), array( 'timeout' => 30 ) );

			if ($response) {
				$user = json_decode( wp_remote_retrieve_body( $response ), true );	
				
				if (isset($user['email']) && $user['email'] == '') {
					$user['email'] = $user['id']. '@facebook.com';
				}
				echo json_encode(c4d_social_validate_email($user['email'])); die();
			}
		}

		if ($_REQUEST['model'] == 'google') {
			$tokenId = $_REQUEST['data']['id_token'];
			$validateUrl =  'https://www.googleapis.com/oauth2/v3/tokeninfo?id_token='.$tokenId;

			$response = wp_remote_get( esc_url_raw( $validateUrl ), array( 'timeout' => 30 ) );
			if ($response) {
				$user = json_decode( wp_remote_retrieve_body( $response ), true );	
				// var_dump($user); die();
				if (isset($user['email'])) {
					// check user exist 
					echo json_encode(c4d_social_validate_email($user['email'])); die();
				}
			}
		}
	}
	
	return false; die();
}
function c4d_social_validate_email($email) {
	$userId = username_exists($email);
		
	if ($user_id) {
		// login code ...
		return c4d_social_login($email);
	} else {
		// create new user and login ...
		$random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
		$user_id = wp_create_user( esc_sql($email), $random_password, $email);
		if ($user_id) {
			return c4d_social_login(esc_sql($email), $random_password);
		}
	}
	return false;
}

function c4d_social_login_form_login($params) {
	$html = '';
	ob_start();
	wp_login_form();
	$html = ob_get_contents();
	ob_end_clean();
	return $html;
}