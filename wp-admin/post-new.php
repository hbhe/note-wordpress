<?php
/**
 * New Post Administration Screen.
 *
 * @package WordPress
 * @subpackage Administration
 */

/***
新增或编辑post时, 默认是不出现摘要输入框的, 在screen option中勾选一下就出来了 

post.php, post-new.php, edit.php这几个文件的区别?
post.php 修改页
post-new.php 新增, 后面带参数表示对不同类型的新增, post-new.php?post_type=page, post-new.php?post_type=product, post-new.php?post_type=order
edit.php 列表页, 后面带post_type参数表示对不同类型对象的列表页，(edit.php?post_type=page, edit.php?post_type=product, edit.php?post_type=order, ...), 不同类型怎么显示不同字段?

*/

/** Load WordPress Administration Bootstrap */
require_once( dirname( __FILE__ ) . '/admin.php' );

/**
 * @global string  $post_type
 * @global object  $post_type_object
 * @global WP_Post $post
 */
global $post_type, $post_type_object, $post;

/** 先取post_type参数，看要新增的是什么类型的 */
if ( ! isset( $_GET['post_type'] ) ) {
	$post_type = 'post';
} elseif ( in_array( $_GET['post_type'], get_post_types( array('show_ui' => true ) ) ) ) {
	$post_type = $_GET['post_type'];
} else {
	wp_die( __('Invalid post type') );
}
$post_type_object = get_post_type_object( $post_type );

if ( 'post' == $post_type ) {
	$parent_file = 'edit.php';
	$submenu_file = 'post-new.php';
} elseif ( 'attachment' == $post_type ) {
	if ( wp_redirect( admin_url( 'media-new.php' ) ) )
		exit;
} else {
	$submenu_file = "post-new.php?post_type=$post_type";
	if ( isset( $post_type_object ) && $post_type_object->show_in_menu && $post_type_object->show_in_menu !== true ) {
		$parent_file = $post_type_object->show_in_menu;
		// What if there isn't a post-new.php item for this post type?
		if ( ! isset( $_registered_pages[ get_plugin_page_hookname( "post-new.php?post_type=$post_type", $post_type_object->show_in_menu ) ] ) ) {
			if (	isset( $_registered_pages[ get_plugin_page_hookname( "edit.php?post_type=$post_type", $post_type_object->show_in_menu ) ] ) ) {
				// Fall back to edit.php for that post type, if it exists
				$submenu_file = "edit.php?post_type=$post_type";
			} else {
				// Otherwise, give up and highlight the parent
				$submenu_file = $parent_file;
			}
		}
	} else {
		$parent_file = "edit.php?post_type=$post_type";
	}
}

$title = $post_type_object->labels->add_new_item;

$editing = true;

if ( ! current_user_can( $post_type_object->cap->edit_posts ) || ! current_user_can( $post_type_object->cap->create_posts ) ) {
	wp_die(
		'<h1>' . __( 'Cheatin&#8217; uh?' ) . '</h1>' .
		'<p>' . __( 'You are not allowed to create posts as this user.' ) . '</p>',
		403
	);
}

// Schedule auto-draft cleanup
if ( ! wp_next_scheduled( 'wp_scheduled_auto_draft_delete' ) )
	wp_schedule_event( time(), 'daily', 'wp_scheduled_auto_draft_delete' );

wp_enqueue_script( 'autosave' );

if ( is_multisite() ) {
	add_action( 'admin_footer', '_admin_notice_post_locked' );
} else {
	$check_users = get_users( array( 'fields' => 'ID', 'number' => 2 ) );

	if ( count( $check_users ) > 1 )
		add_action( 'admin_footer', '_admin_notice_post_locked' );

	unset( $check_users );
}

// Show post form.
/** new一个新的post对象, true表示要在db中有记录 */
/** 参数true, 每次打开新增页面时，哪怕一个字都没输入，也会插入一条draft记录? 
所谓新增，就是先插入一条记录，再编辑它, 以下edit-form-advanced.php是新增和编辑共有的部分
*/
$post = get_default_post_to_edit( $post_type, true );
$post_ID = $post->ID;

include( ABSPATH . 'wp-admin/edit-form-advanced.php' );
include( ABSPATH . 'wp-admin/admin-footer.php' );
