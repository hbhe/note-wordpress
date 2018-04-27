<?php
/**
 * Post advanced form for inclusion in the administration panels.
 *
 * @package WordPress
 * @subpackage Administration
 */
/***
新增(post-new.php)或编辑(post.php)时都要调用的输入界面

此页面由很多版块组成, 大多数版块由metabox组成, 如

if ( post_type_supports($post_type, 'title') ) { // 如果support title
    <input type="text" name="post_title" size="30" value="<?php echo esc_attr( $post->post_title );    
}

if ( post_type_supports($post_type, 'editor') ) { // 如需显示post内容
    ...
}

if ( post_type_supports($post_type, 'excerpt') ) { // 如excerpt控制开关为true
	add_meta_box('postexcerpt', __('Excerpt'), 'post_excerpt_meta_box', null, 'normal', 'core');  
}

所有的版块都可以通过remove_meta_box()强行拿掉
*/
// don't load directly
if ( !defined('ABSPATH') )
	die('-1');

/**
 * @global string  $post_type
 * @global object  $post_type_object
 * @global WP_Post $post
 */
global $post_type, $post_type_object, $post;

wp_enqueue_script('post');
$_wp_editor_expand = $_content_editor_dfw = false;

/**
 * Filter whether to enable the 'expand' functionality in the post editor.
 *
 * @since 4.0.0
 * @since 4.1.0 Added the `$post_type` parameter.
 *
 * @param bool   $expand    Whether to enable the 'expand' functionality. Default true.
 * @param string $post_type Post type.
 */
 /*** 如果当前post_type支持editor, 就加载相应的js, 以支持富文本编辑框,
 那它如何与post_content绑定的? 在下面执行wp_editor()时绑定的
 */
if ( post_type_supports( $post_type, 'editor' ) && ! wp_is_mobile() &&
	 ! ( $is_IE && preg_match( '/MSIE [5678]/', $_SERVER['HTTP_USER_AGENT'] ) ) &&
	 apply_filters( 'wp_editor_expand', true, $post_type ) ) {

	wp_enqueue_script('editor-expand');
	$_content_editor_dfw = true;
	$_wp_editor_expand = ( get_user_setting( 'editor_expand', 'on' ) === 'on' );
}

if ( wp_is_mobile() )
	wp_enqueue_script( 'jquery-touch-punch' );

/**
 * Post ID global
 * @name $post_ID
 * @var int
 */
$post_ID = isset($post_ID) ? (int) $post_ID : 0;
$user_ID = isset($user_ID) ? (int) $user_ID : 0;
$action = isset($action) ? $action : '';

if ( $post_ID == get_option( 'page_for_posts' ) && empty( $post->post_content ) ) {
	add_action( 'edit_form_after_title', '_wp_posts_page_notice' );
	/*** 不想编辑正文了? why */
	remove_post_type_support( $post_type, 'editor' );
}

/*** 如果theme支持缩略图, 并且当前post_type也支持缩略图, 才认为是可以使用缩略图? */
$thumbnail_support = current_theme_supports( 'post-thumbnails', $post_type ) && post_type_supports( $post_type, 'thumbnail' );
if ( ! $thumbnail_support && 'attachment' === $post_type && $post->post_mime_type ) {
	if ( wp_attachment_is( 'audio', $post ) ) {
		$thumbnail_support = post_type_supports( 'attachment:audio', 'thumbnail' ) || current_theme_supports( 'post-thumbnails', 'attachment:audio' );
	} elseif ( wp_attachment_is( 'video', $post ) ) {
		$thumbnail_support = post_type_supports( 'attachment:video', 'thumbnail' ) || current_theme_supports( 'post-thumbnails', 'attachment:video' );
	}
}

if ( $thumbnail_support ) {
	/* 当前主题如果支持贴子缩略图, 就意味着贴子可插件多媒体, 所以加上多媒体相关的js,css,..., plupload文件上传插件 */
	add_thickbox();
	wp_enqueue_media( array( 'post' => $post_ID ) );
}

// Add the local autosave notice HTML
add_action( 'admin_footer', '_local_storage_notice' );

/*
 * @todo Document the $messages array(s).
 */
$permalink = get_permalink( $post_ID );
if ( ! $permalink ) {
	$permalink = '';
}

$messages = array();

$preview_post_link_html = $scheduled_post_link_html = $view_post_link_html = '';
$preview_page_link_html = $scheduled_page_link_html = $view_page_link_html = '';

$preview_url = get_preview_post_link( $post );

/*** 检查此post_type的public属性 */
$viewable = is_post_type_viewable( $post_type_object );

if ( $viewable ) {

	// Preview post link.
	$preview_post_link_html = sprintf( ' <a target="_blank" href="%1$s">%2$s</a>',
		esc_url( $preview_url ),
		__( 'Preview post' )
	);

	// Scheduled post preview link.
	$scheduled_post_link_html = sprintf( ' <a target="_blank" href="%1$s">%2$s</a>',
		esc_url( $permalink ),
		__( 'Preview post' )
	);

	// View post link.
	$view_post_link_html = sprintf( ' <a href="%1$s">%2$s</a>',
		esc_url( $permalink ),
		__( 'View post' )
	);

	// Preview page link.
	$preview_page_link_html = sprintf( ' <a target="_blank" href="%1$s">%2$s</a>',
		esc_url( $preview_url ),
		__( 'Preview page' )
	);

	// Scheduled page preview link.
	$scheduled_page_link_html = sprintf( ' <a target="_blank" href="%1$s">%2$s</a>',
		esc_url( $permalink ),
		__( 'Preview page' )
	);

	// View page link.
	$view_page_link_html = sprintf( ' <a href="%1$s">%2$s</a>',
		esc_url( $permalink ),
		__( 'View page' )
	);

}

/* translators: Publish box date format, see http://php.net/date */
$scheduled_date = date_i18n( __( 'M j, Y @ H:i' ), strtotime( $post->post_date ) );

$messages['post'] = array(
	 0 => '', // Unused. Messages start at index 1.
	 1 => __( 'Post updated.' ) . $view_post_link_html,
	 2 => __( 'Custom field updated.' ),
	 3 => __( 'Custom field deleted.' ),
	 4 => __( 'Post updated.' ),
	/* translators: %s: date and time of the revision */
	 5 => isset($_GET['revision']) ? sprintf( __( 'Post restored to revision from %s.' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
	 6 => __( 'Post published.' ) . $view_post_link_html,
	 7 => __( 'Post saved.' ),
	 8 => __( 'Post submitted.' ) . $preview_post_link_html,
	 9 => sprintf( __( 'Post scheduled for: %s.' ), '<strong>' . $scheduled_date . '</strong>' ) . $scheduled_post_link_html,
	10 => __( 'Post draft updated.' ) . $preview_post_link_html,
);
$messages['page'] = array(
	 0 => '', // Unused. Messages start at index 1.
	 1 => __( 'Page updated.' ) . $view_page_link_html,
	 2 => __( 'Custom field updated.' ),
	 3 => __( 'Custom field deleted.' ),
	 4 => __( 'Page updated.' ),
	/* translators: %s: date and time of the revision */
	 5 => isset($_GET['revision']) ? sprintf( __( 'Page restored to revision from %s.' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
	 6 => __( 'Page published.' ) . $view_page_link_html,
	 7 => __( 'Page saved.' ),
	 8 => __( 'Page submitted.' ) . $preview_page_link_html,
	 9 => sprintf( __( 'Page scheduled for: %s.' ), '<strong>' . $scheduled_date . '</strong>' ) . $scheduled_page_link_html,
	10 => __( 'Page draft updated.' ) . $preview_page_link_html,
);
$messages['attachment'] = array_fill( 1, 10, __( 'Media file updated.' ) ); // Hack, for now.

/**
 * Filter the post updated messages.
 *
 * @since 3.0.0
 *
 * @param array $messages Post updated messages. For defaults @see $messages declarations above.
 */
$messages = apply_filters( 'post_updated_messages', $messages );

$message = false;
if ( isset($_GET['message']) ) {
	$_GET['message'] = absint( $_GET['message'] );
	if ( isset($messages[$post_type][$_GET['message']]) )
		$message = $messages[$post_type][$_GET['message']];
	elseif ( !isset($messages[$post_type]) && isset($messages['post'][$_GET['message']]) )
		$message = $messages['post'][$_GET['message']];
}

$notice = false;
$form_extra = '';
if ( 'auto-draft' == $post->post_status ) {
	if ( 'edit' == $action )
		$post->post_title = '';
	$autosave = false;
	$form_extra .= "<input type='hidden' id='auto_draft' name='auto_draft' value='1' />";
} else {
	$autosave = wp_get_post_autosave( $post_ID );
}

/** 提交form时, 字段action=editpost, 表单提交到post.php */
$form_action = 'editpost';
$nonce_action = 'update-post_' . $post_ID;
$form_extra .= "<input type='hidden' id='post_ID' name='post_ID' value='" . esc_attr($post_ID) . "' />";

// Detect if there exists an autosave newer than the post and if that autosave is different than the post
if ( $autosave && mysql2date( 'U', $autosave->post_modified_gmt, false ) > mysql2date( 'U', $post->post_modified_gmt, false ) ) {
	foreach ( _wp_post_revision_fields( $post ) as $autosave_field => $_autosave_field ) {
		if ( normalize_whitespace( $autosave->$autosave_field ) != normalize_whitespace( $post->$autosave_field ) ) {
			$notice = sprintf( __( 'There is an autosave of this post that is more recent than the version below. <a href="%s">View the autosave</a>' ), get_edit_post_link( $autosave->ID ) );
			break;
		}
	}
	// If this autosave isn't different from the current post, begone.
	if ( ! $notice )
		wp_delete_post_revision( $autosave->ID );
	unset($autosave_field, $_autosave_field);
}

$post_type_object = get_post_type_object($post_type);

// All meta boxes should be defined and added before the first do_meta_boxes() call (or potentially during the do_meta_boxes action).
/*** meta-boxes.php文件有一些系统内置的输入版块(metabox) */
require_once( ABSPATH . 'wp-admin/includes/meta-boxes.php' );

/* 以下根据post_type的类型, 开始使用前面提供的函数, 挂各种版块 */

$publish_callback_args = null;

/*** 如果支持resisions版本控制, 才显示输入框 */
if ( post_type_supports($post_type, 'revisions') && 'auto-draft' != $post->post_status ) {
	$revisions = wp_get_post_revisions( $post_ID );

	// We should aim to show the revisions metabox only when there are revisions.
	if ( count( $revisions ) > 1 ) {
		reset( $revisions ); // Reset pointer for key()
		$publish_callback_args = array( 'revisions_count' => count( $revisions ), 'revision_id' => key( $revisions ) );
		/*** 调用post_revisions_meta_box()生成,normal表示在中间正常位置, side表示侧栏, advanced表示什么位置? 
              注意, 输入界面几乎全部是以metabox版块的形式拼成的! 
		*/
		add_meta_box('revisionsdiv', __('Revisions'), 'post_revisions_meta_box', null, 'normal', 'core');
	}
}

// 编辑媒体时
if ( 'attachment' == $post_type ) {
	wp_enqueue_script( 'image-edit' );
	wp_enqueue_style( 'imgareaselect' );
	// 右侧图片的提交版块
	add_meta_box( 'submitdiv', __('Save'), 'attachment_submit_meta_box', null, 'side', 'core' );
	add_action( 'edit_form_after_title', 'edit_form_image_editor' );

	if ( wp_attachment_is( 'audio', $post ) ) {
		add_meta_box( 'attachment-id3', __( 'Metadata' ), 'attachment_id3_data_meta_box', null, 'normal', 'core' );
	}
} else {
        /** 正常的发布(保存)版块 , 这个不受support控制, 但是还是可以通过remove_meta_box() remove掉的
	除post_type类型为'attachment' 之外的所有其它post, 都要有'发布'输入框
	如果想改变下发布metabox,如将发布改为'保存'，可以在模板代码中
	 先remove_meta_box('submitdiv', $item, 'core'); // $item represents post_type
        再add_meta_box('submitdiv', sprintf( __('Save/Update %s'), $value ), ... )
	*/
	add_meta_box( 'submitdiv', __( 'Publish' ), 'post_submit_meta_box', null, 'side', 'core', $publish_callback_args );
}

/*** 如果theme和post_type都支持'post-formats', 才调用post_format_meta_box() */
if ( current_theme_supports( 'post-formats' ) && post_type_supports( $post_type, 'post-formats' ) )
	/* 如果post_type支持formats, 编辑页面中还要加上形式(或称格式)输入框 */
	add_meta_box( 'formatdiv', _x( 'Format', 'post format' ), 'post_format_meta_box', null, 'side', 'core' );

// all taxonomies
/** 对应的分类显示在右边 , add_meta_box()并不是真正显示，只是收集数据*/
foreach ( get_object_taxonomies( $post ) as $tax_name ) {
	$taxonomy = get_taxonomy( $tax_name );
	if ( ! $taxonomy->show_ui || false === $taxonomy->meta_box_cb )
		continue;

	$label = $taxonomy->labels->name;

	if ( ! is_taxonomy_hierarchical( $tax_name ) )
		$tax_meta_box_id = 'tagsdiv-' . $tax_name;
	else
		$tax_meta_box_id = $tax_name . 'div';
        /*** 此post有多少个taxonomy显示多少个输入版块,  meta_box_cb()显示输入界面, 
        它是在register_taxonomy()时指定的 , 默认category的metabox是post_categories_meta_box(), tag的是post_tags_meta_box        
        */
	add_meta_box( $tax_meta_box_id, $label, $taxonomy->meta_box_cb, null, 'side', 'core', array( 'taxonomy' => $tax_name ) );
}

if ( post_type_supports($post_type, 'page-attributes') )
	/* page_attributes_meta_box() callback函数用来显示页面属性中的3个输入项 : 父页面, 页面模板(如果有的话), 显示序号 */
	add_meta_box('pageparentdiv', 'page' == $post_type ? __('Page Attributes') : __('Attributes'), 'page_attributes_meta_box', null, 'side', 'core');

if ( $thumbnail_support && current_user_can( 'upload_files' ) )
	/* 对于支持upload的post_type, 在编辑时要加上缩略图(或称特色图片)输入框 */
	add_meta_box('postimagediv', esc_html( $post_type_object->labels->featured_image ), 'post_thumbnail_meta_box', null, 'side', 'low');

if ( post_type_supports($post_type, 'excerpt') )
	/* 加上摘要metabox */
	add_meta_box('postexcerpt', __('Excerpt'), 'post_excerpt_meta_box', null, 'normal', 'core');

if ( post_type_supports($post_type, 'trackbacks') )
	add_meta_box('trackbacksdiv', __('Send Trackbacks'), 'post_trackback_meta_box', null, 'normal', 'core');

/*** 自定义版块 */
if ( post_type_supports($post_type, 'custom-fields') )
	add_meta_box('postcustom', __('Custom Fields'), 'post_custom_meta_box', null, 'normal', 'core');

/**
 * Fires in the middle of built-in meta box registration.
 *
 * @since 2.1.0
 * @deprecated 3.7.0 Use 'add_meta_boxes' instead.
 *
 * @param WP_Post $post Post object.
 */
do_action( 'dbx_post_advanced', $post );

// Allow the Discussion meta box to show up if the post type supports comments,
// or if comments or pings are open.
if ( comments_open( $post ) || pings_open( $post ) || post_type_supports( $post_type, 'comments' ) ) {
        /** 讨论版块, 即设置此post是否允许评论 */
	add_meta_box( 'commentstatusdiv', __( 'Discussion' ), 'post_comment_status_meta_box', null, 'normal', 'core' );
}

$stati = get_post_stati( array( 'public' => true ) );
if ( empty( $stati ) ) {
	$stati = array( 'publish' );
}
$stati[] = 'private';

if ( in_array( get_post_status( $post ), $stati ) ) {
	// If the post type support comments, or the post has comments, allow the
	// Comments meta box.
	/** 评论列表版块 */
	if ( comments_open( $post ) || pings_open( $post ) || $post->comment_count > 0 || post_type_supports( $post_type, 'comments' ) ) {
		add_meta_box( 'commentsdiv', __( 'Comments' ), 'post_comment_meta_box', null, 'normal', 'core' );
	}
}

/*** 别名 */
if ( ! ( 'pending' == get_post_status( $post ) && ! current_user_can( $post_type_object->cap->publish_posts ) ) )
	add_meta_box('slugdiv', __('Slug'), 'post_slug_meta_box', null, 'normal', 'core');

/*** 作者版块, add_meta_box 登记产生作者输入框的函数 */
if ( post_type_supports($post_type, 'author') ) {
	if ( is_super_admin() || current_user_can( $post_type_object->cap->edit_others_posts ) )
		add_meta_box('authordiv', __('Author'), 'post_author_meta_box', null, 'normal', 'core');
}

/**
 * Fires after all built-in meta boxes have been added.
 *
 * @since 3.0.0
 *
 * @param string  $post_type Post type.
 * @param WP_Post $post      Post object.
 */
 /*** 
 让用户也有机会加自已的输入表单(metabox) 
// 在编辑订单时, 加个订单数据metabox
add_meta_box( 'woocommerce-order-data', sprintf( __( '%s Data', 'woocommerce' ), 'shop_order' ), 'WC_Meta_Box_Order_Data::output', $type, 'normal', 'high' );
 
 */
do_action( 'add_meta_boxes', $post_type, $post );

/**
 * Fires after all built-in meta boxes have been added, contextually for the given post type.
 *
 * The dynamic portion of the hook, `$post_type`, refers to the post type of the post.
 *
 * @since 3.0.0
 *
 * @param WP_Post $post Post object.
 */
do_action( 'add_meta_boxes_' . $post_type, $post );

/**
 * Fires after meta boxes have been added.
 *
 * Fires once for each of the default meta box contexts: normal, advanced, and side.
 *
 * @since 3.0.0
 *
 * @param string  $post_type Post type of the post.
 * @param string  $context   string  Meta box context.
 * @param WP_Post $post      Post object.
 */ 
/*
normal, advanced, side 什么意思
分别表示metabox在页面的什么位置
*/

/*** 这个hook什么用?  给别人一个机会，调整normal位置的metabox ? 
现在就显示metabox html?
*/
do_action( 'do_meta_boxes', $post_type, 'normal', $post );

/** This action is documented in wp-admin/edit-form-advanced.php */
do_action( 'do_meta_boxes', $post_type, 'advanced', $post );

/** This action is documented in wp-admin/edit-form-advanced.php */
do_action( 'do_meta_boxes', $post_type, 'side', $post );

/*** 显示选项怎么加上去的? 就这一句? */
add_screen_option('layout_columns', array('max' => 2, 'default' => 2) );

if ( 'post' == $post_type ) {
	$customize_display = '<p>' . __('The title field and the big Post Editing Area are fixed in place, but you can reposition all the other boxes using drag and drop. You can also minimize or expand them by clicking the title bar of each box. Use the Screen Options tab to unhide more boxes (Excerpt, Send Trackbacks, Custom Fields, Discussion, Slug, Author) or to choose a 1- or 2-column layout for this screen.') . '</p>';

	get_current_screen()->add_help_tab( array(
		'id'      => 'customize-display',
		'title'   => __('Customizing This Display'),
		'content' => $customize_display,
	) );

	$title_and_editor  = '<p>' . __('<strong>Title</strong> &mdash; Enter a title for your post. After you enter a title, you&#8217;ll see the permalink below, which you can edit.') . '</p>';
	$title_and_editor .= '<p>' . __( '<strong>Post editor</strong> &mdash; Enter the text for your post. There are two modes of editing: Visual and Text. Choose the mode by clicking on the appropriate tab.' ) . '</p>';
	$title_and_editor .= '<p>' . __( 'Visual mode gives you an editor that is similar to a word processor. Click the Toolbar Toggle button to get a second row of controls.' ) . '</p>';
	$title_and_editor .= '<p>' . __( 'The Text mode allows you to enter HTML along with your post text. Note that &lt;p&gt; and &lt;br&gt; tags are converted to line breaks when switching to the Text editor to make it less cluttered. When you type, a single line break can be used instead of typing &lt;br&gt;, and two line breaks instead of paragraph tags. The line breaks are converted back to tags automatically.' ) . '</p>';
	$title_and_editor .= '<p>' . __( 'You can insert media files by clicking the icons above the post editor and following the directions. You can align or edit images using the inline formatting toolbar available in Visual mode.' ) . '</p>';
	$title_and_editor .= '<p>' . __( 'You can enable distraction-free writing mode using the icon to the right. This feature is not available for old browsers or devices with small screens, and requires that the full-height editor be enabled in Screen Options.' ) . '</p>';
	$title_and_editor .= '<p>' . __( 'Keyboard users: When you&#8217;re working in the visual editor, you can use <kbd>Alt + F10</kbd> to access the toolbar.' ) . '</p>';

	get_current_screen()->add_help_tab( array(
		'id'      => 'title-post-editor',
		'title'   => __('Title and Post Editor'),
		'content' => $title_and_editor,
	) );

	get_current_screen()->set_help_sidebar(
			'<p>' . sprintf(__('You can also create posts with the <a href="%s">Press This bookmarklet</a>.'), 'tools.php') . '</p>' .
			'<p><strong>' . __('For more information:') . '</strong></p>' .
			'<p>' . __('<a href="https://codex.wordpress.org/Posts_Add_New_Screen" target="_blank">Documentation on Writing and Editing Posts</a>') . '</p>' .
			'<p>' . __('<a href="https://wordpress.org/support/" target="_blank">Support Forums</a>') . '</p>'
	);
} elseif ( 'page' == $post_type ) {
	$about_pages = '<p>' . __('Pages are similar to posts in that they have a title, body text, and associated metadata, but they are different in that they are not part of the chronological blog stream, kind of like permanent posts. Pages are not categorized or tagged, but can have a hierarchy. You can nest pages under other pages by making one the &#8220;Parent&#8221; of the other, creating a group of pages.') . '</p>' .
		'<p>' . __('Creating a Page is very similar to creating a Post, and the screens can be customized in the same way using drag and drop, the Screen Options tab, and expanding/collapsing boxes as you choose. This screen also has the distraction-free writing space, available in both the Visual and Text modes via the Fullscreen buttons. The Page editor mostly works the same as the Post editor, but there are some Page-specific features in the Page Attributes box.') . '</p>';

	get_current_screen()->add_help_tab( array(
		'id'      => 'about-pages',
		'title'   => __('About Pages'),
		'content' => $about_pages,
	) );

	get_current_screen()->set_help_sidebar(
			'<p><strong>' . __('For more information:') . '</strong></p>' .
			'<p>' . __('<a href="https://codex.wordpress.org/Pages_Add_New_Screen" target="_blank">Documentation on Adding New Pages</a>') . '</p>' .
			'<p>' . __('<a href="https://codex.wordpress.org/Pages_Screen#Editing_Individual_Pages" target="_blank">Documentation on Editing Pages</a>') . '</p>' .
			'<p>' . __('<a href="https://wordpress.org/support/" target="_blank">Support Forums</a>') . '</p>'
	);
} elseif ( 'attachment' == $post_type ) {
	get_current_screen()->add_help_tab( array(
		'id'      => 'overview',
		'title'   => __('Overview'),
		'content' =>
			'<p>' . __('This screen allows you to edit four fields for metadata in a file within the media library.') . '</p>' .
			'<p>' . __('For images only, you can click on Edit Image under the thumbnail to expand out an inline image editor with icons for cropping, rotating, or flipping the image as well as for undoing and redoing. The boxes on the right give you more options for scaling the image, for cropping it, and for cropping the thumbnail in a different way than you crop the original image. You can click on Help in those boxes to get more information.') . '</p>' .
			'<p>' . __('Note that you crop the image by clicking on it (the Crop icon is already selected) and dragging the cropping frame to select the desired part. Then click Save to retain the cropping.') . '</p>' .
			'<p>' . __('Remember to click Update Media to save metadata entered or changed.') . '</p>'
	) );

	get_current_screen()->set_help_sidebar(
	'<p><strong>' . __('For more information:') . '</strong></p>' .
	'<p>' . __('<a href="https://codex.wordpress.org/Media_Add_New_Screen#Edit_Media" target="_blank">Documentation on Edit Media</a>') . '</p>' .
	'<p>' . __('<a href="https://wordpress.org/support/" target="_blank">Support Forums</a>') . '</p>'
	);
}

if ( 'post' == $post_type || 'page' == $post_type ) {
	$inserting_media = '<p>' . __( 'You can upload and insert media (images, audio, documents, etc.) by clicking the Add Media button. You can select from the images and files already uploaded to the Media Library, or upload new media to add to your page or post. To create an image gallery, select the images to add and click the &#8220;Create a new gallery&#8221; button.' ) . '</p>';
	$inserting_media .= '<p>' . __( 'You can also embed media from many popular websites including Twitter, YouTube, Flickr and others by pasting the media URL on its own line into the content of your post/page. Please refer to the Codex to <a href="https://codex.wordpress.org/Embeds">learn more about embeds</a>.' ) . '</p>';

	get_current_screen()->add_help_tab( array(
		'id'		=> 'inserting-media',
		'title'		=> __( 'Inserting Media' ),
		'content' 	=> $inserting_media,
	) );
}

if ( 'post' == $post_type ) {
	$publish_box = '<p>' . __('Several boxes on this screen contain settings for how your content will be published, including:') . '</p>';
	$publish_box .= '<ul><li>' .
		__( '<strong>Publish</strong> &mdash; You can set the terms of publishing your post in the Publish box. For Status, Visibility, and Publish (immediately), click on the Edit link to reveal more options. Visibility includes options for password-protecting a post or making it stay at the top of your blog indefinitely (sticky). The Password protected option allows you to set an arbitrary password for each post. The Private option hides the post from everyone except editors and administrators. Publish (immediately) allows you to set a future or past date and time, so you can schedule a post to be published in the future or backdate a post.' ) .
	'</li>';

	if ( current_theme_supports( 'post-formats' ) && post_type_supports( 'post', 'post-formats' ) ) {
		$publish_box .= '<li>' . __( '<strong>Format</strong> &mdash; Post Formats designate how your theme will display a specific post. For example, you could have a <em>standard</em> blog post with a title and paragraphs, or a short <em>aside</em> that omits the title and contains a short text blurb. Please refer to the Codex for <a href="https://codex.wordpress.org/Post_Formats#Supported_Formats">descriptions of each post format</a>. Your theme could enable all or some of 10 possible formats.' ) . '</li>';
	}

	if ( current_theme_supports( 'post-thumbnails' ) && post_type_supports( 'post', 'thumbnail' ) ) {
		/* translators: %s: Featured Image */
		$publish_box .= '<li>' . sprintf( __( '<strong>%s</strong> &mdash; This allows you to associate an image with your post without inserting it. This is usually useful only if your theme makes use of the image as a post thumbnail on the home page, a custom header, etc.' ), esc_html( $post_type_object->labels->featured_image ) ) . '</li>';
	}

	$publish_box .= '</ul>';

	get_current_screen()->add_help_tab( array(
		'id'      => 'publish-box',
		'title'   => __('Publish Settings'),
		'content' => $publish_box,
	) );

	$discussion_settings  = '<p>' . __('<strong>Send Trackbacks</strong> &mdash; Trackbacks are a way to notify legacy blog systems that you&#8217;ve linked to them. Enter the URL(s) you want to send trackbacks. If you link to other WordPress sites they&#8217;ll be notified automatically using pingbacks, and this field is unnecessary.') . '</p>';
	$discussion_settings .= '<p>' . __('<strong>Discussion</strong> &mdash; You can turn comments and pings on or off, and if there are comments on the post, you can see them here and moderate them.') . '</p>';

	get_current_screen()->add_help_tab( array(
		'id'      => 'discussion-settings',
		'title'   => __('Discussion Settings'),
		'content' => $discussion_settings,
	) );
} elseif ( 'page' == $post_type ) {
	$page_attributes = '<p>' . __('<strong>Parent</strong> &mdash; You can arrange your pages in hierarchies. For example, you could have an &#8220;About&#8221; page that has &#8220;Life Story&#8221; and &#8220;My Dog&#8221; pages under it. There are no limits to how many levels you can nest pages.') . '</p>' .
		'<p>' . __('<strong>Template</strong> &mdash; Some themes have custom templates you can use for certain pages that might have additional features or custom layouts. If so, you&#8217;ll see them in this dropdown menu.') . '</p>' .
		'<p>' . __('<strong>Order</strong> &mdash; Pages are usually ordered alphabetically, but you can choose your own order by entering a number (1 for first, etc.) in this field.') . '</p>';

	get_current_screen()->add_help_tab( array(
		'id' => 'page-attributes',
		'title' => __('Page Attributes'),
		'content' => $page_attributes,
	) );
}
/* 开始输出 */
require_once( ABSPATH . 'wp-admin/admin-header.php' );
?>

<div class="wrap">
<h1><?php
echo esc_html( $title ); /* 显示'编辑文章'这几个字 */
if ( isset( $post_new_file ) && current_user_can( $post_type_object->cap->create_posts ) )
	echo ' <a href="' . esc_url( admin_url( $post_new_file ) ) . '" class="page-title-action">' . esc_html( $post_type_object->labels->add_new ) . '</a>'; /* 显示'写文章' 链接*/
?></h1>
<?php if ( $notice ) : ?>
<div id="notice" class="notice notice-warning"><p id="has-newer-autosave"><?php echo $notice ?></p></div>
<?php endif; ?>
<?php if ( $message ) : ?>
<div id="message" class="updated notice notice-success is-dismissible"><p><?php echo $message; ?></p></div>
<?php endif; ?>
<div id="lost-connection-notice" class="error hidden">
	<p><span class="spinner"></span> <?php _e( '<strong>Connection lost.</strong> Saving has been disabled until you&#8217;re reconnected.' ); ?>
	<span class="hide-if-no-sessionstorage"><?php _e( 'We&#8217;re backing up this post in your browser, just in case.' ); ?></span>
	</p>
</div>
<form name="post" action="post.php" method="post" id="post"<?php
/* 处理form中输入title, content, metadata的是post.php,  整个页面只有一个form  */ 
/**
 * Fires inside the post editor form tag.
 *
 * @since 3.0.0
 *
 * @param WP_Post $post Post object.
 */
do_action( 'post_edit_form_tag', $post );

$referer = wp_get_referer();
?>>
<?php wp_nonce_field($nonce_action); ?>
<input type="hidden" id="user-id" name="user_ID" value="<?php echo (int) $user_ID ?>" />
<input type="hidden" id="hiddenaction" name="action" value="<?php echo esc_attr( $form_action ) ?>" />
<input type="hidden" id="originalaction" name="originalaction" value="<?php echo esc_attr( $form_action ) ?>" />
<input type="hidden" id="post_author" name="post_author" value="<?php echo esc_attr( $post->post_author ); ?>" />
<input type="hidden" id="post_type" name="post_type" value="<?php echo esc_attr( $post_type ) ?>" />
<input type="hidden" id="original_post_status" name="original_post_status" value="<?php echo esc_attr( $post->post_status) ?>" />
<input type="hidden" id="referredby" name="referredby" value="<?php echo $referer ? esc_url( $referer ) : ''; ?>" />
<?php if ( ! empty( $active_post_lock ) ) { ?>
<input type="hidden" id="active_post_lock" value="<?php echo esc_attr( implode( ':', $active_post_lock ) ); ?>" />
<?php
}
if ( 'draft' != get_post_status( $post ) )
	wp_original_referer_field(true, 'previous');

echo $form_extra;

wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
?>

<?php
/**
 * Fires at the beginning of the edit form.
 *
 * At this point, the required hidden fields and nonces have already been output.
 *
 * @since 3.7.0
 *
 * @param WP_Post $post Post object.
 */
do_action( 'edit_form_top', $post ); ?>

<div id="poststuff">
<div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">
<div id="post-body-content">

<?php if ( post_type_supports($post_type, 'title') ) { /*** 如果支持编辑'标题', 这个不是metabox */  ?>
<div id="titlediv">
<div id="titlewrap">
	<?php
	/**
	 * Filter the title field placeholder text.
	 *
	 * @since 3.1.0
	 *
	 * @param string  $text Placeholder text. Default 'Enter title here'.
	 * @param WP_Post $post Post object.
	 */
	$title_placeholder = apply_filters( 'enter_title_here', __( 'Enter title here' ), $post );
	?>
	<label class="screen-reader-text" id="title-prompt-text" for="title"><?php echo $title_placeholder; ?></label>
	<input type="text" name="post_title" size="30" value="<?php echo esc_attr( $post->post_title ); /* post的标题 */ ?>" id="title" spellcheck="true" autocomplete="off" />
</div>
<?php
/**
 * Fires before the permalink field in the edit form.
 *
 * @since 4.1.0
 *
 * @param WP_Post $post Post object.
 */
do_action( 'edit_form_before_permalink', $post );
?>
<div class="inside">
<?php
if ( $viewable ) :
$sample_permalink_html = $post_type_object->public ? get_sample_permalink_html($post->ID) : '';

// As of 4.4, the Get Shortlink button is hidden by default.
if ( has_filter( 'pre_get_shortlink' ) || has_filter( 'get_shortlink' ) ) {
	$shortlink = wp_get_shortlink($post->ID, 'post');

	if ( !empty( $shortlink ) && $shortlink !== $permalink && $permalink !== home_url('?page_id=' . $post->ID) ) {
    	$sample_permalink_html .= '<input id="shortlink" type="hidden" value="' . esc_attr($shortlink) . '" /><a href="#" class="button button-small" onclick="prompt(&#39;URL:&#39;, jQuery(\'#shortlink\').val()); return false;">' . __('Get Shortlink') . '</a>';
	}
}

if ( $post_type_object->public && ! ( 'pending' == get_post_status( $post ) && !current_user_can( $post_type_object->cap->publish_posts ) ) ) {
	$has_sample_permalink = $sample_permalink_html && 'auto-draft' != $post->post_status;
?>
	<div id="edit-slug-box" class="hide-if-no-js">
	<?php
		if ( $has_sample_permalink )
			echo $sample_permalink_html;	/* 显示'固定链接, 更改固定链接' */
	?>
	</div>
<?php
}
endif;
?>
</div>
<?php
wp_nonce_field( 'samplepermalink', 'samplepermalinknonce', false );
?>
</div><!-- /titlediv -->
<?php
}
/**
 * Fires after the title field.
 *
 * @since 3.5.0
 *
 * @param WP_Post $post Post object.
 */
 /*** 让别人有机会在title后面加点html */
do_action( 'edit_form_after_title', $post );

if ( post_type_supports($post_type, 'editor') ) {
?>
<div id="postdivrich" class="postarea<?php if ( $_wp_editor_expand ) { echo ' wp-editor-expand'; } ?>">

<?php wp_editor( $post->post_content, 'content', array(
	'_content_editor_dfw' => $_content_editor_dfw,
	'drag_drop_upload' => true,
	'tabfocus_elements' => 'content-html,save-post',
	'editor_height' => 300,
	'tinymce' => array(
		'resize' => false,
		'wp_autoresize_on' => $_wp_editor_expand,
		'add_unload_trigger' => false,
	),
) ); /* 富文本编辑, 包括上面的'添加媒体', '可视化', '文本'三个按钮 */ ?>

<table id="post-status-info"><tbody><tr>
	<td id="wp-word-count" class="hide-if-no-js"><?php printf( __( 'Word count: %s' ), '<span class="word-count">0</span>' ); /* 输入字数统计 */ ?></td>
	<td class="autosave-info">
	<span class="autosave-message">&nbsp;</span>
<?php
	if ( 'auto-draft' != $post->post_status ) {
		echo '<span id="last-edit">';
		/* 显示最后编辑时间 */
		if ( $last_user = get_userdata( get_post_meta( $post_ID, '_edit_last', true ) ) ) {
			printf( __( 'Last edited by %1$s on %2$s at %3$s' ), esc_html( $last_user->display_name ), mysql2date( __( 'F j, Y' ), $post->post_modified ), mysql2date( __( 'g:i a' ), $post->post_modified ) );
		} else {
			printf( __( 'Last edited on %1$s at %2$s' ), mysql2date( __( 'F j, Y' ), $post->post_modified ), mysql2date( __( 'g:i a' ), $post->post_modified ) );
		}
		echo '</span>';
	} ?>
	</td>
	<td id="content-resize-handle" class="hide-if-no-js"><br /></td>
</tr></tbody></table>

</div>
<?php }
/**
 * Fires after the content editor.
 *
 * @since 3.5.0
 *
 * @param WP_Post $post Post object.
 */
 /*** 让别人有机会在context后面加点html */ 
do_action( 'edit_form_after_editor', $post );
?>
</div><!-- /post-body-content -->

<div id="postbox-container-1" class="postbox-container">
<?php

if ( 'page' == $post_type ) {
	/**
	 * Fires before meta boxes with 'side' context are output for the 'page' post type.
	 *
	 * The submitpage box is a meta box with 'side' context, so this hook fires just before it is output.
	 *
	 * @since 2.5.0
	 *
	 * @param WP_Post $post Post object.
	 */
	do_action( 'submitpage_box', $post );
}
else {
	/**
	 * Fires before meta boxes with 'side' context are output for all post types other than 'page'.
	 *
	 * The submitpost box is a meta box with 'side' context, so this hook fires just before it is output.
	 *
	 * @since 2.5.0
	 *
	 * @param WP_Post $post Post object.
	 */
	do_action( 'submitpost_box', $post );
}

/*
前面用add_meta_box() 登记了很多callback, 现在要执行callback输出具体的metabox框了

add_meta_box()只是准备数据, do_meta_boxes() 才是输出meta-box
真正在右侧显示输出之前注册的的一些meta boxes输入框 

先显示side(即右侧的), 再normal, advanced?
*/
do_meta_boxes($post_type, 'side', $post);

?>
</div>


<div id="postbox-container-2" class="postbox-container">
<?php
/* 这是输出什么? normal, advance, side 区别? 
输出那些登记时放在normal位置的metabox
*/
do_meta_boxes(null, 'normal', $post);

if ( 'page' == $post_type ) {
	/**
	 * Fires after 'normal' context meta boxes have been output for the 'page' post type.
	 *
	 * @since 1.5.0
	 *
	 * @param WP_Post $post Post object.
	 */
	do_action( 'edit_page_form', $post );
}
else {
	/**
	 * Fires after 'normal' context meta boxes have been output for all post types other than 'page'.
	 *
	 * @since 1.5.0
	 *
	 * @param WP_Post $post Post object.
	 */
	do_action( 'edit_form_advanced', $post );
}

/*** advanced与normal没什么区别,只是一个在前, 一个在后? */
do_meta_boxes(null, 'advanced', $post);

?>
</div>
<?php
/**
 * Fires after all meta box sections have been output, before the closing #post-body div.
 *
 * @since 2.1.0
 *
 * @param WP_Post $post Post object.
 */
do_action( 'dbx_post_sidebar', $post );

?>
</div><!-- /post-body -->
<br class="clear" />
</div><!-- /poststuff -->
</form>
</div>

<?php
/* form结束, 整个页面只有一个form */
if ( post_type_supports( $post_type, 'comments' ) )
	wp_comment_reply();
?>

<?php if ( ! wp_is_mobile() && post_type_supports( $post_type, 'title' ) && '' === $post->post_title ) : ?>
<script type="text/javascript">
try{document.post.title.focus();}catch(e){}
</script>
<?php endif; ?>
