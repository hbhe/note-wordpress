<?php
/**
 * Loads the correct template based on the visitor's url
 * @package WordPress
 */
if ( defined('WP_USE_THEMES') && WP_USE_THEMES )
	/**
	 * Fires before determining which template to load.
	 *
	 * @since 1.5.0
	 */
	 /***  在测定模板之前, 可以做点事, 比如直接跳走(wp_redirect) */
	do_action( 'template_redirect' );

/**
 * Filter whether to allow 'HEAD' requests to generate content.
 *
 * Provides a significant performance bump by exiting before the page
 * content loads for 'HEAD' requests. See #14348.
 *
 * @since 3.5.0
 *
 * @param bool $exit Whether to exit without generating any content for 'HEAD' requests. Default true.
 */
 /* 如果是HTTP HEAD请求,就不发内容了 */
if ( 'HEAD' === $_SERVER['REQUEST_METHOD'] && apply_filters( 'exit_on_http_head', true ) )
	exit();

// Process feeds and trackbacks even if not using themes.
if ( is_robots() ) :
	/**
	 * Fired when the template loader determines a robots.txt request.
	 *
	 * @since 2.1.0
	 */
	do_action( 'do_robots' );
	return;
elseif ( is_feed() ) :
	/* 
	http://127.0.0.1/note-wordpress/index.php?feed=rss2 表示订阅该网站的贴子,
	http://127.0.0.1/note-wordpress/?feed=comments-rss2 表示订阅该网站的评论
	
	使用rss技术, 你可以不用进入某网站, 就得到它的最新文章列表
	比如用firefox(或其rss阅读工具)访问此链接, 就会得到一个xml响应, firefox会打开一个页面, 把它加入书签,
	以后在访问此书签时, 会自动得到一个关注网站的最新文章列表,点击列表上的
	链接就会进入被关注的网站了.
			
	返加贴子或评论的xml响应,包括作者和更新时间 
	*/
	do_feed();
	return;
elseif ( is_trackback() ) :
	include( ABSPATH . 'wp-trackback.php' );
	return;
endif;

 /*
 根据请求执行相应的php模板文件, 如
 搜索-> search.php
 error -> 404.php
 文章归档-> archive.php
index.php是找不到相应的模板文件之后, 不得已最后的选择, 并不是代表首页, home.php才是首页模板文件
front-page.php与home.php的区别?
 */
if ( defined('WP_USE_THEMES') && WP_USE_THEMES ) :
	$template = false;
	if     ( is_embed()          && $template = get_embed_template()          ) :
	elseif ( is_404()            && $template = get_404_template()            ) :
	elseif ( is_search()         && $template = get_search_template()         ) :
	elseif ( is_front_page()     && $template = get_front_page_template()     ) :		/*** 取front-page.php */
	/*
	前面执行parse_query()时根据请求时$_GET中的所带参数, 判断这个请求是home页还是别的页面
	如果是首页, 到相应theme目录下取home.php(或index.php)这个view模板文件,得到文件名
	如果$_GET中有'cat' , is_category()为真, 取theme目录下的category.php这个view文件
	*/
	elseif ( is_home()           && $template = get_home_template()           ) :   /*** 取home.php */
	elseif ( is_post_type_archive() && $template = get_post_type_archive_template() ) :
	elseif ( is_tax()            && $template = get_taxonomy_template()       ) :
	elseif ( is_attachment()     && $template = get_attachment_template()     ) :
		remove_filter('the_content', 'prepend_attachment');
	elseif ( is_single()         && $template = get_single_template()         /* 显示某个贴子 */ ) :
	elseif ( is_page()           && $template = get_page_template()           /* 显示某个page */) :
	elseif ( is_singular()       && $template = get_singular_template()       ) :
	elseif ( is_category()       && $template = get_category_template()       ) :
	elseif ( is_tag()            && $template = get_tag_template()            ) :
	elseif ( is_author()         && $template = get_author_template()         ) :
	elseif ( is_date()           && $template = get_date_template()           ) :
	elseif ( is_archive()        && $template = get_archive_template()        ) :
	elseif ( is_paged()          && $template = get_paged_template()          ) :
	else :
		$template = get_index_template();			/* 其它情况显示index页 */
	endif;
	
	/*
	至此, 已挑出了一个模板文件, 比如:
	$template = D:\htdocs\note-wordpress/wp-content/themes/twentysixteen/index.php
	$template = D:\htdocs\note-wordpress/wp-content/themes/twentysixteen/single.php	
	$template = D:\htdocs\note-wordpress/wp-content/themes/twentysixteen/page.php
	archive.php, ...
	*/	
	// debug 显示到底是哪个文件
	error_log($template);
	
	/**
	 * Filter the path of the current template before including it.
	 *
	 * @since 3.0.0
	 *
	 * @param string $template The path of the template to include.
	 */
	 /*** 再给别人一个机会去改变模板文件名 */
	if ( $template = apply_filters( 'template_include', $template ) ) {
		/*
		执行挂在'template_include'的所有钩子函数,每个钩子函数的输出是下个钩子函数的输入?
		有时想改变default模板,用法如下
		add_filter( 'template_include', 'portfolio_page_template', 99 );
		function portfolio_page_template( $template ) {
			if ( is_page( 'portfolio' )  ) {
				$new_template = locate_template( array( 'portfolio-page-template.php' ) );
				if ( '' != $new_template ) {
					return $new_template ;
				}
			}
			if ( is_singular('post') ) {
				return 'my_post_template.php';
			}	
			return $template;
		}				
		*/
		include( $template );
	} elseif ( current_user_can( 'switch_themes' ) ) {		/* 否则$template未找到, 换个主题试试?  在哪里include模板内? */
		/*
		如果有'switch_themes' 权限...
		*/
		$theme = wp_get_theme();
		if ( $theme->errors() ) {
			wp_die( $theme->errors() );
		}
	}
	return;
endif;
/* 如果WP_USE_THEMES=false则不显示*/
