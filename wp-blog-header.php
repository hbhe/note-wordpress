<?php
/**
 * Loads the WordPress environment and template.
 *
 * @package WordPress
 */
 
/*
wordpress的执行步骤
1. 分析请求url中的参数(wordpress所支持的参数是固定的), 置标志
2. 根据参数执行db查询, 修正标志
3. 根据标志显示不同的模板文件(wordpress中主要的模板文件名(一级,theme目录下)是固定的,如home.php, single.php, 
但content.php, content-single.php这种不是wordpress规定的,它是写模板的人规定的二级模板文件名)
*/

if ( !isset($wp_did_header) ) {

	$wp_did_header = true;

	// Load the WordPress library.
	/*
	加载插件, new并初始化一个全局对象$wp, 
	wp-load.php这个文件是前后台都要include的,
	执行后, 各种库函数,各种default filter, 已经就绪, 只差分析当前url
	*/
	require_once( dirname(__FILE__) . '/wp-load.php' );

	// Set up the WordPress query.
	/*** 
	wp()实际就是执行$wp->main() ($wp对象在前面wp-load.php中已经实例化了).
	剖析url, 分析请求是is_home, is_page, 还是其它, 然后从db中取出(get_posts())相应的贴子(post)数据, 供将来在模板中使用. 
	但是它没想过, 如果将来模板中要的不是贴子,而是评论数据(或者其它自定义表中的数据), 那岂不是白取了?	
	确实, 因为在wp()执行时, 并不知道将要加载哪个模板, 更不知道模板文件中要什么数据(这话也不尽然, 
	访问首页时, wp()时确实不知道首页要什么内容, 但是访问别的页面时, url中的参数应该就表明了要加载哪个模板, 这
	个模板中要什么数据, 所以wp()执行时已经明确知道了)

	因为wp()只从wp_posts表(及关联表)中取数据, 如果你的模板文件中用不到这些数据, 那在wp()中取的数据确实白取了, 
	这对性能有点影响,但目前没有办法,也许将来wordpress考虑可以加入hook让用户跳过不必要的查询...
	
	如果你的模板中用到post中的数据, 但不是默认的最近贴子,这种情况可以通过hook 改变url中的参数来做到, wp()中的查询就不是白做了
	当然你也可以不管它, wp()中查询的结果你不用它就是了, 在模板文件中重新查询数据库中的数据

	好在大多数网站首页都要取一点post中的内容, 如贴子、静态页面...
	*/
	wp();

	// Load the theme template.
	/*** 
	载入相应的模板文件
	根据前面对url参数的剖析结果, 载入相应的模板(theme)文件, 在模板文件中可以直接取用前面从
	db中取出的数据, 也可以根据需要重新从db中拉取自己想要显示的数据 
	*/
	require_once( ABSPATH . WPINC . '/template-loader.php' );

}
