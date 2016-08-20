<?php
/***
WordPress基础配置文件, 参考: https://codex.wordpress.org/Editing_wp-config.php
*/

 //Added by WP-Cache Manager
define( 'WPCACHEHOME', 'D:\htdocs\note-wordpress\wp-content\plugins\wp-super-cache/' ); //Added by WP-Cache Manager

define('DB_NAME', 'note-wordpress');

/** MySQL数据库用户名 */
define('DB_USER', 'root');

/** MySQL数据库密码 */
define('DB_PASSWORD', '');

/** MySQL主机 */
define('DB_HOST', 'localhost');

/** 创建数据表时默认的文字编码 */
/*** 
utf8mb4可以储存四字节的emoji表情符, 而utf8最多3字节, 所以优先考虑utfmb4, 但mysql要5.5版本的才支持utf8mb4 ?
wordpress安装时会自动检查能否使用'utf8mb4'
*/
define('DB_CHARSET', 'utf8mb4');

/** 数据库整理类型。如不确定请勿更改 */
define('DB_COLLATE', '');

/**#@+
 * 身份认证密钥与盐。
 *
 * 修改为任意独一无二的字串！
 * 或者直接访问{@link https://api.wordpress.org/secret-key/1.1/salt/
 * WordPress.org密钥生成服务}
 * 任何修改都会导致所有cookies失效，所有用户将必须重新登录。
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'U[Qoz2#+M}@X8C7?OBE&/|!{U7[iP`8i.9Ed2]|n:v3v*m+Y%W`Pa6qAG{GuvGoq');
define('SECURE_AUTH_KEY',  'VCCp=;9= }Loj_S4!>3(?OixKU:C+=-Zn<7TJP^/L,o}~kjmY%-ROq8/uBE1i.Ji');
define('LOGGED_IN_KEY',    '=#83:}64^FdRY)&[LWp;6Fu)XH2yOJa)&AQdv3;/da?|]$?zG+G(%Y[$6Oz9<L%+');
define('NONCE_KEY',        's)Y|Cu=LlyEbhYC)*6%5W-[]+6RmuCM-qD(!(KLhOP<)<;:}ZL*{_HQzOxnla/ij');
define('AUTH_SALT',        'QVuir_9D=GH`4|T|]H5BrEOJTd-|<_&0%I :C{C*i$QSh#aW$q=]F6T;9$^zE5[ ');
define('SECURE_AUTH_SALT', 'o>4g6tAL ,ZR/2q:o~wVbX2d~]GdKsgn3jH>W#qx2w2g3=$un(g& BTLQ04q~s+f');
define('LOGGED_IN_SALT',   '7NIO%F9h]WK3 Z,4OnbQ7i?3 !>kt6M-zm#Ak-G|tYcY`cx^30gN-LN~}D`ha#Nf');
define('NONCE_SALT',       '%8yRHIg_kRC,Q8#^=jz$4ru{)9z0KiB=ctyKP?akbc. <~h1Ic`G}rz#/cA#t|Yj');

/**#@-*/

/**
 * WordPress数据表前缀。
 *
 * 如果您有在同一数据库内安装多个WordPress的需求，请为每个WordPress设置
 * 不同的数据表前缀。前缀名只能为数字、字母加下划线。
 */
$table_prefix  = 'wp_';

/**
 * 开发者专用：WordPress调试模式。
 *
 * 将这个值改为true，WordPress将显示所有用于开发的提示。
 * 强烈建议插件开发者在开发环境中启用WP_DEBUG。
 *
 * 要获取其他能用于调试的信息，请访问Codex。
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

// Enable Debug logging to the /wp-content/debug.log file
//定义之后, 就可以使用error_log("this is a test");进行记录了
define( 'WP_DEBUG_LOG', true );

// Disable display of errors and warnings , default is true
define( 'WP_DEBUG_DISPLAY', true );
@ini_set( 'display_errors', 1 );

// Use dev versions of core JS and CSS files (only needed if you are modifying these core files)
define( 'SCRIPT_DEBUG', true );
//define('CONCATENATE_SCRIPTS', false); disables compression and concatenation of scripts and CSS,
//define('COMPRESS_SCRIPTS', false); disables compression of scripts,
//define('COMPRESS_CSS', false); disables compression of CSS,
//define('ENFORCE_GZIP', true); forces gzip for compression (default is deflate).

/*
要调试sql语句可设SAVEQUERIES为true
在theme的view文件如index.php中,打印所有query语句
if ( current_user_can( 'manage_options' ) ) {
	global $wpdb;
	print_r( $wpdb->queries );
	var_dump($wpdb->queries);
}
*/
define('SAVEQUERIES', true);	

/*** WP_CACHE为true时, 得与wp-content/advanced-cache.php配套使用 */
//define( 'WP_CACHE', true );

// define( 'WP_MEMORY_LIMIT', '64M' );
// define( 'WP_MAX_MEMORY_LIMIT', '256M' );

/*** 使用自已的用户表名,而不是使用'wp_users', 'wp_usermeta'表名, 没什么用吧? */
//define( 'CUSTOM_USER_TABLE', $table_prefix.'my_users' );
//define( 'CUSTOM_USER_META_TABLE', $table_prefix.'my_usermeta' );

/* 
新增一个贴子时, 表post中会插入2条记录, 一条是贴子本身, 另一条是贴子版本号,
关掉post的版本记录功能,或者最多支持5个版本 
*/
define( 'WP_POST_REVISIONS', false);
//define( 'WP_POST_REVISIONS', 5 );

/*
通过AUTOMATIC_UPDATER_DISABLED来禁止自动更新(内核, 插件, 主题, 翻译)
也可以通过WP_AUTO_UPDATE_CORE来控制自动升级
define( 'WP_AUTO_UPDATE_CORE', true );		// 开发版, 大版本, 小版本都自动更新
define( 'WP_AUTO_UPDATE_CORE', false );		// 开发版, 大版本, 小版本都禁止自动更新
define( 'WP_AUTO_UPDATE_CORE', 'minor' );	// 只有小版本自动更新
*/
define('AUTOMATIC_UPDATER_DISABLED', true);	/* 关掉所有自动升级*/

/* 默认是每60秒自动保存贴子,改为300秒 */
define( 'AUTOSAVE_INTERVAL', 300);

/*** 管理员默认是可以通过后台界面编辑php, css文件的, 以下可disable掉 */
//define('DISALLOW_FILE_EDIT', true);

/*** true表示关掉定时功能(页面触发的), 可利用linux的crontab -e 来实现 */
//define('DISABLE_WP_CRON', true);

/*
在安装插件或theme时，如果没有对wp-content目录的写权限，会要求管理员输入ftp用户名和密码，这个让人有点不爽
这时先 chmod -R 777 /wp-content 目录, 然后 在wp-config.php中加define('FS_METHOD', 'direct'); 就可以免ftp参数安装插件了
*/
//define('FS_METHOD', 'direct');

//define( 'FORCE_SSL_LOGIN', true );

/**
 * zh_CN本地化设置：启用ICP备案号显示
 *
 * 可在设置→常规中修改。
 * 如需禁用，请移除或注释掉本行。
 */
define('WP_ZH_CN_ICP_NUM', true);
date_default_timezone_set('Asia/Shanghai');

/* 
定义后, 可覆盖db中的siteurl, home(可在后台设置), 比如如果将远程主机上的db导到本地测试, 可将WP_SITEURL定义为本地
这2个地址的区别?
The "SiteURL" setting is the address you want people to type in their browser to reach your WordPress blog.
The "WordPress HOME URL" setting is the address where your WordPress core files reside.

*/
//define( 'WP_SITEURL', 'http://127.0.0.1/note-wordpress' );
//define( 'WP_HOME', 'http://127.0.0.1/note-wordpress' );
//define('WP_CONTENT_URL', 'http://mydomain.com/files/wp-content');
//define( 'RELOCATE', true); 

/*** 安装多站点步骤
1. 在wp-config.php中定义define( 'WP_ALLOW_MULTISITE', true );
2. 到后台->工具->网络配置
3. 将页面上的代码copy & paste到wp-config.php, .htaccess中
*/


define( 'WP_ALLOW_MULTISITE', true );
define('MULTISITE', true);
define('SUBDOMAIN_INSTALL', false);
//define('DOMAIN_CURRENT_SITE', 's1.local.com');
define('DOMAIN_CURRENT_SITE', '127.0.0.1');
define('PATH_CURRENT_SITE', '/note-wordpress/');
define('SITE_ID_CURRENT_SITE', 1);
define('BLOG_ID_CURRENT_SITE', 1);


// 作用? domain mapping时要用?
//define( 'SUNRISE', 'on' );

/* 好了！请不要再继续编辑。请保存本文件。使用愉快！ */

/** WordPress目录的绝对路径。 */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** 设置WordPress变量和包含文件。 */
require_once(ABSPATH . 'wp-settings.php');
