<?php
/**
 * Bootstrap file for setting the ABSPATH constant
 * and loading the wp-config.php file. The wp-config.php
 * file will then load the wp-settings.php file, which
 * will then set up the WordPress environment.
 *
 * If the wp-config.php file is not found then an error
 * will be displayed asking the visitor to set up the
 * wp-config.php file.
 *
 * Will also search for wp-config.php in WordPress' parent
 * directory to allow the WordPress directory to remain
 * untouched.
 *
 * @internal This file must be parsable by PHP4.
 *
 * @package WordPress
 */
/* 
找wp-config.php文件, 找不到就开始安装 
正常情况下wp-config.php文件放在d:/htdocs/wordpress/wp-config.php, 但是也可以放在d:/htdocs/wp-config.php(即放在项目之外),
这样别的wordpress安装也可以分享这个配置文件,好处?
*/

/** Define ABSPATH as this file's directory */
define( 'ABSPATH', dirname(__FILE__) . '/' );

error_reporting( E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_ERROR | E_WARNING | E_PARSE | E_USER_ERROR | E_USER_WARNING | E_RECOVERABLE_ERROR );

/*
// 如果要支持composer的autoload, 加上此名
if (file_exists(ABSPATH . 'vendor/autoload.php')) {
	require_once(ABSPATH . 'vendor/autoload.php');
}

$wx_options = [
    'debug'  => true,
    'app_id'  => 'wxd1806e66fe96a00c',
    'secret'  => '17ac86b02a204254b4a563cd6a3c05af',
    'token'   => 'vDHg6heBH3m6OM6F7D3638EObEZEDm3b',
    'aes_key'   => 'v6tMUhKs59m936g8G8M3869hIC1SSC8C8QI6IgTMZim',            
    'log' => [
        'level' => 'debug', // level: debug/info/notice/warning/error/critical/alert/emergency
        'file'  => 'easywechat.log',
    ],
    'oauth' => [
        'scopes' => ['snsapi_base'], // scopes: snsapi_userinfo /snsapi_base/snsapi_login
        'callback' => '/examples/oauth_callback.php',
    ],
    'payment' => [
        'merchant_id' => '11111',
        'key' => '222222',
        'cert_path' => 'path/to/your/cert.pem',
        'key_path' => 'path/to/your/key', // XXX: absolute path！！！！
    ],
];

$app = new \EasyWeChat\Foundation\Application($wx_options);
$menu = $app->menu;
$menus = $menu->all();
$menus = $menus['menu']['button'];
var_dump($menus);

*/


/*
 * If wp-config.php exists in the WordPress root, or if it exists in the root and wp-settings.php
 * doesn't, load wp-config.php. The secondary check for wp-settings.php has the added benefit
 * of avoiding cases where the current directory is a nested installation, e.g. / is WordPress(a)
 * and /blog/ is WordPress(b).
 *
 * If neither set of conditions is true, initiate loading the setup process.
 */
if ( file_exists( ABSPATH . 'wp-config.php') ) {
	// 正常流程进入这里
	/** The config file resides in ABSPATH */
	require_once( ABSPATH . 'wp-config.php' );

} elseif ( @file_exists( dirname( ABSPATH ) . '/wp-config.php' ) && ! @file_exists( dirname( ABSPATH ) . '/wp-settings.php' ) ) {
	/* wp-config.php文件也可以放在上一级的目录 , 防止嵌套安装? */
	/** The config file resides one level above ABSPATH but is not part of another install */
	require_once( dirname( ABSPATH ) . '/wp-config.php' );

} else {

	/* 没有配置文件,进入安装步骤 */
	// A config file doesn't exist

	define( 'WPINC', 'wp-includes' );
	require_once( ABSPATH . WPINC . '/load.php' );

	// Standardize $_SERVER variables across setups.
	wp_fix_server_vars();

	require_once( ABSPATH . WPINC . '/functions.php' );

	$path = wp_guess_url() . '/wp-admin/setup-config.php';

	/*
	 * We're going to redirect to setup-config.php. While this shouldn't result
	 * in an infinite loop, that's a silly thing to assume, don't you think? If
	 * we're traveling in circles, our last-ditch effort is "Need more help?"
	 */
	if ( false === strpos( $_SERVER['REQUEST_URI'], 'setup-config' ) ) {
		header( 'Location: ' . $path );
		exit;
	}

	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
	require_once( ABSPATH . WPINC . '/version.php' );

	wp_check_php_mysql_versions();
	wp_load_translations_early();

	// Die with an error message
	$die  = sprintf(
		/* translators: %s: wp-config.php */
		__( "There doesn't seem to be a %s file. I need this before we can get started." ),
		'<code>wp-config.php</code>'
	) . '</p>';
	$die .= '<p>' . sprintf(
		/* translators: %s: Codex URL */
		__( "Need more help? <a href='%s'>We got it</a>." ),
		__( 'https://codex.wordpress.org/Editing_wp-config.php' )
	) . '</p>';
	$die .= '<p>' . sprintf(
		/* translators: %s: wp-config.php */
		__( "You can create a %s file through a web interface, but this doesn't work for all server setups. The safest way is to manually create the file." ),
		'<code>wp-config.php</code>'
	) . '</p>';
	$die .= '<p><a href="' . $path . '" class="button button-large">' . __( "Create a Configuration File" ) . '</a>';

	wp_die( $die, __( 'WordPress &rsaquo; Error' ) );
}
