<?php
/**
 * Used to set up and fix common variables and include
 * the WordPress procedural and class library.
 *
 * Allows for some configuration in wp-config.php (see default-constants.php)
 *
 * @internal This file must be parsable by PHP4.
 *
 * @package WordPress
 */

/*
主要的初始过程都在这个文件内, 前后台都要执行它
*/

/**
 * Stores the location of the WordPress directory of functions, classes, and core content.
 *
 * @since 1.0.0
 */
define( 'WPINC', 'wp-includes' );

// Include files required for initialization.
require( ABSPATH . WPINC . '/load.php' );
require( ABSPATH . WPINC . '/default-constants.php' );

/*
 * These can't be directly globalized in version.php. When updating,
 * we're including version.php from another install and don't want
 * these values to be overridden if already set.
 */
global $wp_version, $wp_db_version, $tinymce_version, $required_php_version, $required_mysql_version, $wp_local_package;
require( ABSPATH . WPINC . '/version.php' );

/**
 * If not already configured, `$blog_id` will default to 1 in a single site
 * configuration. In multisite, it will be overridden by default in ms-settings.php.
 *
 * @global int $blog_id
 * @since 2.0.0
 */
global $blog_id;

// Set initial default constants including WP_MEMORY_LIMIT, WP_MAX_MEMORY_LIMIT, WP_DEBUG, SCRIPT_DEBUG, WP_CONTENT_DIR and WP_CACHE.
wp_initial_constants();

// Check for the required PHP version and for the MySQL extension or a database drop-in.
wp_check_php_mysql_versions();

// Disable magic quotes at runtime. Magic quotes are added using wpdb later in wp-settings.php.
@ini_set( 'magic_quotes_runtime', 0 );
@ini_set( 'magic_quotes_sybase',  0 );

// WordPress calculates offsets from UTC.
/* 表posts中有2个字段post_date, post_data_gmt分别存放本地时间和gmt时间 */
date_default_timezone_set( 'UTC' );

// Turn register_globals off.
wp_unregister_GLOBALS();

// Standardize $_SERVER variables across setups.
wp_fix_server_vars();

// Check if we have received a request due to missing favicon.ico
wp_favicon_request();

// Check if we're in maintenance mode.
/*  检查是否是维护状态*/
wp_maintenance();

// Start loading timer.
timer_start();

// Check if we're in WP_DEBUG mode.
wp_debug_mode();

// For an advanced caching plugin to use. Uses a static drop-in because you would only want one.
/* 
WP_CACHE已失去了字面上的意思, 而是表示是否使用advanced-cache.php
当使用者定义WP_CACHE 为true时, 同时在wp-content目录下就应当提供一个advanced-cache.php文件
这是最早用户可以干预系统的地方?
*/
if ( WP_CACHE )
	/** WP_DEBUG时报错否则不报错继续执行, include时文件不存在时可以继续, 而require则会致命错退出 */
	WP_DEBUG ? include( WP_CONTENT_DIR . '/advanced-cache.php' ) : @include( WP_CONTENT_DIR . '/advanced-cache.php' );

// Define WP_LANG_DIR if not set.
wp_set_lang_dir();

// Load early WordPress files.
require( ABSPATH . WPINC . '/compat.php' );
require( ABSPATH . WPINC . '/functions.php' );
require( ABSPATH . WPINC . '/class-wp.php' );
require( ABSPATH . WPINC . '/class-wp-error.php' );

/*** 引用hook机制, 从此就可以使用add_action,... */
require( ABSPATH . WPINC . '/plugin.php' );


// 我放在这里的测试代码BEGIN 
// 打印所有数据库操作
add_action('shutdown', function($arg) {
	global $wpdb;
	//error_log(print_r( $wpdb->queries, true ) ); 
});	

// 到底当前请求最后使用的是哪个模板文件
add_filter( 'template_include', function($template) {
	error_log( 'my template=' . $template );
	return $template;
}, 9999);

add_action('update_option', function($option, $old_value, $value) {
	//if ( is_array( $value ) || is_object( $value ) ) 
	if (in_array( $option, ['sidebars_widgets'] ) )
	{	
		//error_log(print_r( [$option, $old_value, $value], true ) ); 
	}
}, 10, 3);	
// END

require( ABSPATH . WPINC . '/pomo/mo.php' );

// Include the wpdb class and, if present, a db.php database drop-in.
// 初始化$wpdb = new wpdb(...);
require_wp_db();

// Set the database table prefix and the format specifiers for database table columns.
$GLOBALS['table_prefix'] = $table_prefix;
wp_set_wpdb_vars();

// Start the WordPress object cache, or an external object cache if the drop-in is present.
/* 
加载wordpress自已实现的cache文件; 或者wp-content/object-cache.php这个外部cache文件,如果存在的话 
*/
wp_start_object_cache();

// Attach the default filters.
/*** 加载default的filter, 到这里才首次使用了add_action */
require( ABSPATH . WPINC . '/default-filters.php' );

// Initialize multisite if enabled.
if ( is_multisite() ) {
	require( ABSPATH . WPINC . '/ms-blogs.php' );
	require( ABSPATH . WPINC . '/ms-settings.php' );
} elseif ( ! defined( 'MULTISITE' ) ) {
	define( 'MULTISITE', false );
}

register_shutdown_function( 'shutdown_action_hook' ); /*** 程序退出时(包括exit,die)执行挂在'shutdown'上的函数 */

// Stop most of WordPress from being loaded if we just want the basics.
if ( SHORTINIT )
	return false;

// Load the L10n library.
require_once( ABSPATH . WPINC . '/l10n.php' );

// Run the installer if WordPress is not installed.
wp_not_installed();

// Load most of WordPress.
// 没搞什么autoload机制，全include...
require( ABSPATH . WPINC . '/class-wp-walker.php' );
require( ABSPATH . WPINC . '/class-wp-ajax-response.php' );
require( ABSPATH . WPINC . '/formatting.php' );
require( ABSPATH . WPINC . '/capabilities.php' );
require( ABSPATH . WPINC . '/class-wp-roles.php' );
require( ABSPATH . WPINC . '/class-wp-role.php' );
require( ABSPATH . WPINC . '/class-wp-user.php' );
require( ABSPATH . WPINC . '/query.php' );
require( ABSPATH . WPINC . '/date.php' );
require( ABSPATH . WPINC . '/theme.php' );
require( ABSPATH . WPINC . '/class-wp-theme.php' );
require( ABSPATH . WPINC . '/template.php' );
require( ABSPATH . WPINC . '/user.php' );
require( ABSPATH . WPINC . '/class-wp-user-query.php' );
require( ABSPATH . WPINC . '/session.php' );
require( ABSPATH . WPINC . '/meta.php' );
require( ABSPATH . WPINC . '/class-wp-meta-query.php' );
require( ABSPATH . WPINC . '/class-wp-metadata-lazyloader.php' );
require( ABSPATH . WPINC . '/general-template.php' );
require( ABSPATH . WPINC . '/link-template.php' );
require( ABSPATH . WPINC . '/author-template.php' );
require( ABSPATH . WPINC . '/post.php' );
require( ABSPATH . WPINC . '/class-walker-page.php' );
require( ABSPATH . WPINC . '/class-walker-page-dropdown.php' );
require( ABSPATH . WPINC . '/class-wp-post.php' );
require( ABSPATH . WPINC . '/post-template.php' );
require( ABSPATH . WPINC . '/revision.php' );
require( ABSPATH . WPINC . '/post-formats.php' );
require( ABSPATH . WPINC . '/post-thumbnail-template.php' );
require( ABSPATH . WPINC . '/category.php' );
require( ABSPATH . WPINC . '/class-walker-category.php' );
require( ABSPATH . WPINC . '/class-walker-category-dropdown.php' );
require( ABSPATH . WPINC . '/category-template.php' );
require( ABSPATH . WPINC . '/comment.php' );
require( ABSPATH . WPINC . '/class-wp-comment.php' );
require( ABSPATH . WPINC . '/class-wp-comment-query.php' );
require( ABSPATH . WPINC . '/class-walker-comment.php' );
require( ABSPATH . WPINC . '/comment-template.php' );
require( ABSPATH . WPINC . '/rewrite.php' );
require( ABSPATH . WPINC . '/class-wp-rewrite.php' );
require( ABSPATH . WPINC . '/feed.php' );
require( ABSPATH . WPINC . '/bookmark.php' );
require( ABSPATH . WPINC . '/bookmark-template.php' );
require( ABSPATH . WPINC . '/kses.php' );
require( ABSPATH . WPINC . '/cron.php' );
require( ABSPATH . WPINC . '/deprecated.php' );
require( ABSPATH . WPINC . '/script-loader.php' );
require( ABSPATH . WPINC . '/taxonomy.php' );
require( ABSPATH . WPINC . '/class-wp-term.php' );
require( ABSPATH . WPINC . '/class-wp-tax-query.php' );

/* 
打开前后台页面时, 都要检查更新plugin? 
打开前台页面时, 不需要做update吧?
*/
require( ABSPATH . WPINC . '/update.php' );

require( ABSPATH . WPINC . '/canonical.php' );
require( ABSPATH . WPINC . '/shortcodes.php' );
require( ABSPATH . WPINC . '/embed.php' );
require( ABSPATH . WPINC . '/class-wp-embed.php' );
require( ABSPATH . WPINC . '/class-wp-oembed-controller.php' );
require( ABSPATH . WPINC . '/media.php' );
require( ABSPATH . WPINC . '/http.php' );
require( ABSPATH . WPINC . '/class-http.php' );
require( ABSPATH . WPINC . '/class-wp-http-streams.php' );
require( ABSPATH . WPINC . '/class-wp-http-curl.php' );
require( ABSPATH . WPINC . '/class-wp-http-proxy.php' );
require( ABSPATH . WPINC . '/class-wp-http-cookie.php' );
require( ABSPATH . WPINC . '/class-wp-http-encoding.php' );
require( ABSPATH . WPINC . '/class-wp-http-response.php' );
require( ABSPATH . WPINC . '/widgets.php' );
require( ABSPATH . WPINC . '/class-wp-widget.php' );
require( ABSPATH . WPINC . '/class-wp-widget-factory.php' );
require( ABSPATH . WPINC . '/nav-menu.php' );
require( ABSPATH . WPINC . '/nav-menu-template.php' );
require( ABSPATH . WPINC . '/admin-bar.php' );
require( ABSPATH . WPINC . '/rest-api.php' );
require( ABSPATH . WPINC . '/rest-api/class-wp-rest-server.php' );
require( ABSPATH . WPINC . '/rest-api/class-wp-rest-response.php' );
require( ABSPATH . WPINC . '/rest-api/class-wp-rest-request.php' );

// Load multisite-specific files.
if ( is_multisite() ) {
	require( ABSPATH . WPINC . '/ms-functions.php' );
	require( ABSPATH . WPINC . '/ms-default-filters.php' );
	require( ABSPATH . WPINC . '/ms-deprecated.php' );
}

// Define constants that rely on the API to obtain the default value.
// Define must-use plugin directory constants, which may be overridden in the sunrise.php drop-in.
/*** 定义插件常量, 下面准备依次加载: 必用plugin, 全网plugin, 单站plugin */
wp_plugin_directory_constants();

$GLOBALS['wp_plugin_paths'] = array();

// Load must-use plugins.
 /***  
 放在wp-content/mu-plugins 目录下的插件, 称为必用插件, 也可以认为是不能被disable的插件?
 而dropins 插件可以认为是有固定文件名的必用插件?
 */
foreach ( wp_get_mu_plugins() as $mu_plugin ) {
	include_once( $mu_plugin );
}
unset( $mu_plugin );

// Load network activated plugins.
if ( is_multisite() ) {
	/*** 加载适用于全网范围内的已激活的插件 */
	foreach ( wp_get_active_network_plugins() as $network_plugin ) {
		wp_register_plugin_realpath( $network_plugin );
		include_once( $network_plugin );
	}
	unset( $network_plugin );
}

/**
 * Fires once all must-use and network-activated plugins have loaded.
 *
 * @since 2.8.0
 */
do_action( 'muplugins_loaded' );

if ( is_multisite() )
	ms_cookie_constants(  );

// Define constants after multisite is loaded.
wp_cookie_constants();

// Define and enforce our SSL constants
wp_ssl_constants();

// Create common globals.
require( ABSPATH . WPINC . '/vars.php' );

// Make taxonomies and posts available to plugins and themes.
// @plugin authors: warning: these get registered again on the init hook.
/*** 先注册category,post_tag, 表明这些taxnomy适应于什么post_type, 虽然此时post_type尚未定义? */
create_initial_taxonomies();

/*** 注册系统内建的几种post type */
create_initial_post_types();

// Register the default theme directory root
// get_theme_root() = D:\htdocs\note-wordpress/wp-content/themes
register_theme_directory( get_theme_root() );

// Load active plugins.
/*
加载适用于本站的激活的插件, 这里是进入插件的唯一入口点?
$plugin值形如D:\htdocs\note-wordpress/wp-content/plugins/hello.php 或D:\htdocs\note-wordpress/wp-content/plugins/akismet/akismet.php
前后台都会运行到这里
一个插件一般分为前台展示，后台展示, 这里只有一个入口, 怎么分前后台呢? 答案是在脚本里面自己去定义for frontend或者for background 的hook去

在插件的直接执行代码(非回调函数中的代码)中, 尚不能使用当前插件的翻译词条, 因 为一般
插件的翻译文件是在后面触发'plugins_loaded' 时才加载?
*/
foreach ( wp_get_active_and_valid_plugins() as $plugin ) {
	wp_register_plugin_realpath( $plugin );
	include_once( $plugin );
}
unset( $plugin );

//show_admin_bar(false);

// Load pluggable functions.
/*** pluggable.php放在插件之后加载, 意味着在插件中可以先定义一些pluggable.php中的函数,以改变worpress的行为 */
require( ABSPATH . WPINC . '/pluggable.php' );
require( ABSPATH . WPINC . '/pluggable-deprecated.php' );

// Set internal encoding.
wp_set_internal_encoding();

// Run wp_cache_postload() if object cache is enabled and the function exists.
if ( WP_CACHE && function_exists( 'wp_cache_postload' ) )
	/* 从cache中取数据输出?  wp-cache-phase2.php */
	wp_cache_postload();

/**
 * Fires once activated plugins have loaded.
 *
 * Pluggable functions are also available at this point in the loading order.
 *
 * @since 1.5.0
 */
do_action( 'plugins_loaded' );

// Define constants which affect functionality if not already defined.
// 有多个定义常量的函数如wp_functionality_constants(), wp_plugin_directory_constants(), wp_initial_constants() ..., 为什么不把这些常量定义合并在一起放在一个函数内?
wp_functionality_constants();

// Add magic quotes and set up $_REQUEST ( $_GET + $_POST )
wp_magic_quotes();

/**
 * Fires when comment cookies are sanitized.
 *
 * @since 2.0.11
 */
do_action( 'sanitize_comment_cookies' );

/*
开始初始化WP系列对象如wp, wp_the_query, wp_roles
*/

/**
 * WordPress Query object
 * @global WP_Query $wp_the_query
 * @since 2.0.0
 */
 /**
$GLOBALS['wp_the_query']与$GLOBALS['wp_query']区别?
一般用$wp_query, $wp_the_query用于备份与恢复?
以后还可以$q = new WP_Query(); 但不会再有$v = new WP();了?
 */
$GLOBALS['wp_the_query'] = new WP_Query();

/**
 * Holds the reference to @see $wp_the_query
 * Use this global for WordPress queries
 * @global WP_Query $wp_query
 * @since 1.5.0
 */
$GLOBALS['wp_query'] = $GLOBALS['wp_the_query'];

/**
 * Holds the WordPress Rewrite object for creating pretty URLs
 * @global WP_Rewrite $wp_rewrite
 * @since 1.5.0
 */
$GLOBALS['wp_rewrite'] = new WP_Rewrite();

/**
 * WordPress Object
 * @global WP $wp
 * @since 2.0.0
 */
 /** 初始化wp对象*/
$GLOBALS['wp'] = new WP();

/**
 * WordPress Widget Factory Object
 * @global WP_Widget_Factory $wp_widget_factory
 * @since 2.8.0
 */
$GLOBALS['wp_widget_factory'] = new WP_Widget_Factory();

/**
 * WordPress User Roles
 * @global WP_Roles $wp_roles
 * @since 2.0.0
 */
$GLOBALS['wp_roles'] = new WP_Roles();

/**
 * Fires before the theme is loaded.
 *
 * @since 2.6.0
 */
 
/*** 
先插件, 后主题, 
这里开始做主题(或称模板)加载前的动作
*/
do_action( 'setup_theme' );

// Define the template related constants.
// 指定模板路径
wp_templating_constants(  );

// Load the default text localization domain.
// 加载默认内核词典(翻译文件), wp-content\languages\zh_CN.mo, 其它主题词典, 插件词典不在这里load
load_default_textdomain();

// $locale形如'zh_CN'
$locale = get_locale();
$locale_file = WP_LANG_DIR . "/$locale.php";
/* 加载wp-content\languages\zh_CN.php脚本, 作用? */
if ( ( 0 === validate_file( $locale ) ) && is_readable( $locale_file ) )
	require( $locale_file );
unset( $locale_file );

// Pull in locale data after loading text domain.
require_once( ABSPATH . WPINC . '/locale.php' );

/**
 * WordPress Locale object for loading locale domain date and various strings.
 * @global WP_Locale $wp_locale
 * @since 2.1.0
 */
$GLOBALS['wp_locale'] = new WP_Locale();

// Load the functions for the active theme, for both parent and child theme if applicable.
if ( ! wp_installing() || 'wp-activate.php' === $pagenow ) {
	/*** 
	TEMPLATEPATH !== STYLESHEETPATH时,表明是子主题,
	先加载子主题内的functions.php(function.php相当于这个主题自己的函数库, 也执行一些注册之类的动作)
	*/
	
	/***
	theme的模板文件是最后执行的, 为了改变模板中的变量, 就需要在include模板之前有个地方能加入theme的hook, 
	这个地方就是functions.php
	*/
	if ( TEMPLATEPATH !== STYLESHEETPATH && file_exists( STYLESHEETPATH . '/functions.php' ) )
		include( STYLESHEETPATH . '/functions.php' );

	/*** 
	再加载父主题的functions.php, 可见只支持父子关系, 不支持孙子主题,
	如果你有 孙子主题的想法, wordpress认为你的思路就有问题, 多重继承会导致复杂依赖, 性能slow down?

	在加载模板文件时, 先在子主题下找, 找到就算了, 找不到就到父主题目录下找. 这与处理functions.php是有区别的
	*/		
	if ( file_exists( TEMPLATEPATH . '/functions.php' ) )
		include( TEMPLATEPATH . '/functions.php' );
}

/**
 * Fires after the theme is loaded.
 *
 * @since 3.0.0
 */
do_action( 'after_setup_theme' );
//////////// 模板加载前的动作完毕, 真正加载模板的代码还在后面,在template-loader.php中


// Set up current user.
$GLOBALS['wp']->init();

/**
 * Fires after WordPress has finished loading but before any headers are sent.
 *
 * Most of WP is loaded at this stage, and the user is authenticated. WP continues
 * to load on the init hook that follows (e.g. widgets), and many plugins instantiate
 * themselves on it for all sorts of reasons (e.g. they need a user, a taxonomy, etc.).
 *
 * If you wish to plug an action once WP is loaded, use the wp_loaded hook below.
 *
 * @since 1.5.0
 */
do_action( 'init' );

// Check site status
if ( is_multisite() ) {
	if ( true !== ( $file = ms_site_check() ) ) {
		require( $file );
		die();
	}
	unset($file);
}

/**
 * This hook is fired once WP, all plugins, and the theme are fully loaded and instantiated.
 *
 * AJAX requests should use wp-admin/admin-ajax.php. admin-ajax.php can handle requests for
 * users not logged in.
 *
 * @link https://codex.wordpress.org/AJAX_in_Plugins
 *
 * @since 3.0.0
 */
do_action( 'wp_loaded' );
