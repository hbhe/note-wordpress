<?php
/**
 * Front to the WordPress application. This file doesn't do anything, but loads
 * wp-blog-header.php which does and tells WordPress to load the theme.
 *
 * @package WordPress
 */

/**
 * Tells WordPress to load the WordPress theme and output it.
 *
 * @var bool
 */

/*** 源码之前, 了无秘密*/

/***
wp的扩展性很强
通过meta, post可以随便扩充字段，只需要在后台编辑界面中勾选自定义栏目(每条记录都可以有自己不同的字段!)，在使用时模板中用get_post_custom()或get_post_meta()可取出来显示

metabox是如何显示和处理的?
显示meta_box时用add_meta_boxes, 处理时用save_post 勾子(处理文件是post.php)  , 如下
add_action( 'add_meta_boxes', 'halloween_store_register_meta_box' );  // halloween_store_register_meta_box()函数显示html页面
add_action( 'save_post','halloween_store_save_meta_box' ); // 提交metabox时会触发'save_post', 进行执行halloween_store_save_meta_box()

register_post_type()和register_taxonomy()都会对后台菜单有影响

taxnomy是相同post_type的记录(post)的集合, 集合本身也有属性, 如集合名(放在wp_term表中)
post_type=nav_menu_item对应的taxnomy是nav_menu
*/ 

/* 如果为false, 表示不加载主题, index.php会显示空白*/ 
define('WP_USE_THEMES', true);

/** Loads the WordPress Environment and Template */
require( dirname( __FILE__ ) . '/wp-blog-header.php' );
