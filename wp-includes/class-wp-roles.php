<?php
/**
 * User API: WP_Roles class
 *
 * @package WordPress
 * @subpackage Users
 * @since 4.4.0
 */
 
/***
用户, 角色, 权限的关系
一个用户可有多种角色, 保存在wp_capabilities meta字段中(wp_usermeta表). 此字段除了存放roles外, 也存放个人caps
一个角色可有多种权限,这种关系存放在wp_option表的'wp_user_roles'中
*/

/**
 * Core class used to implement a user roles API.
 *
 * The role option is simple, the structure is organized by role name that store
 * the name in value of the 'name' key. The capabilities are stored as an array
 * in the value of the 'capability' key.
 *
 *     array (
 *    		'rolename' => array (
 *    			'name' => 'rolename',
 *    			'capabilities' => array()
 *    		)
 *     )
 *
 * @since 2.0.0
 */
class WP_Roles {
	/**
	 * List of roles and capabilities.
	 *
	 * @since 2.0.0
	 * @access public
	 * @var array
	 */
	public $roles;

	/**
	 * List of the role objects.
	 *
	 * @since 2.0.0
	 * @access public
	 * @var array
	 */
	public $role_objects = array();

	/**
	 * List of role names.
	 *
	 * @since 2.0.0
	 * @access public
	 * @var array
	 */
	public $role_names = array();

	/**
	 * Option name for storing role list.
	 *
	 * @since 2.0.0
	 * @access public
	 * @var string
	 */
	 /*** 
	 一个user的所有权限在wp_usermeta表中wp_capabilities中, 其中包含有他个人的roles id,
	 而全站支持的roles信息实际存放在wp_options表中的wp_user_roles中 

	 $role_key = 'wp_user_roles'
	 */
	public $role_key;

	/**
	 * Whether to use the database for retrieval and storage.
	 *
	 * @since 2.1.0
	 * @access public
	 * @var bool
	 */
	public $use_db = true;

	/**
	 * Constructor
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		/*** 从数据库中读取roles集合, 初始化本对象 */
		$this->_init();
	}

	/**
	 * Make private/protected methods readable for backwards compatibility.
	 *
	 * @since 4.0.0
	 * @access public
	 *
	 * @param callable $name      Method to call.
	 * @param array    $arguments Arguments to pass when calling.
	 * @return mixed|false Return value of the callback, false otherwise.
	 */
	public function __call( $name, $arguments ) {
		if ( '_init' === $name ) {
			return call_user_func_array( array( $this, $name ), $arguments );
		}
		return false;
	}

	/**
	 * Set up the object properties.
	 *
	 * The role key is set to the current prefix for the $wpdb object with
	 * 'user_roles' appended. If the $wp_user_roles global is set, then it will
	 * be used and the role option will not be updated or used.
	 *
	 * @since 2.1.0
	 * @access protected
	 *
	 * @global wpdb  $wpdb          WordPress database abstraction object.
	 * @global array $wp_user_roles Used to set the 'roles' property value.
	 */
	 /***
	 从表wp_options中读取key为'wp_user_roles'的值, 
	 建立一个WP_Roles对象(内含一组WP_Role对象)

	error_log(print_r($this->roles, true));
	Array
	(
	    [administrator] => Array
	        (
	            [name] => Administrator
	            [capabilities] => Array
	                (
	                    [switch_themes] => 1
	                    [edit_themes] => 1
	                    [activate_plugins] => 1
	                    [edit_plugins] => 1
	                    [edit_users] => 1
	                    [edit_files] => 1
	                    [manage_options] => 1
	                    [moderate_comments] => 1
	                    [manage_categories] => 1
	                    [manage_links] => 1
	                    [upload_files] => 1
	                    [import] => 1
	                    [unfiltered_html] => 1
	                    [edit_posts] => 1
	                    [edit_others_posts] => 1
	                    [edit_published_posts] => 1
	                    [publish_posts] => 1
	                    [edit_pages] => 1
	                    [read] => 1
	                    [level_10] => 1
	                    [level_9] => 1
	                    [level_8] => 1
	                    [level_7] => 1
	                    [level_6] => 1
	                    [level_5] => 1
	                    [level_4] => 1
	                    [level_3] => 1
	                    [level_2] => 1
	                    [level_1] => 1
	                    [level_0] => 1
	                    [edit_others_pages] => 1
	                    [edit_published_pages] => 1
	                    [publish_pages] => 1
	                    [delete_pages] => 1
	                    [delete_others_pages] => 1
	                    [delete_published_pages] => 1
	                    [delete_posts] => 1
	                    [delete_others_posts] => 1
	                    [delete_published_posts] => 1
	                    [delete_private_posts] => 1
	                    [edit_private_posts] => 1
	                    [read_private_posts] => 1
	                    [delete_private_pages] => 1
	                    [edit_private_pages] => 1
	                    [read_private_pages] => 1
	                    [delete_users] => 1
	                    [create_users] => 1
	                    [unfiltered_upload] => 1
	                    [edit_dashboard] => 1
	                    [update_plugins] => 1
	                    [delete_plugins] => 1
	                    [install_plugins] => 1
	                    [update_themes] => 1
	                    [install_themes] => 1
	                    [update_core] => 1
	                    [list_users] => 1
	                    [remove_users] => 1
	                    [promote_users] => 1
	                    [edit_theme_options] => 1
	                    [delete_themes] => 1
	                    [export] => 1
	                    [manage_woocommerce] => 1
	                    [view_woocommerce_reports] => 1
	                    [edit_product] => 1
	                    [read_product] => 1
	                    [delete_product] => 1
	                    [edit_products] => 1
	                    [edit_others_products] => 1
	                    [publish_products] => 1
	                    [read_private_products] => 1
	                    [delete_products] => 1
	                    [delete_private_products] => 1
	                    [delete_published_products] => 1
	                    [delete_others_products] => 1
	                    [edit_private_products] => 1
	                    [edit_published_products] => 1
	                    [manage_product_terms] => 1
	                    [edit_product_terms] => 1
	                    [delete_product_terms] => 1
	                    [assign_product_terms] => 1
	                    [edit_shop_order] => 1
	                    [read_shop_order] => 1
	                    [delete_shop_order] => 1
	                    [edit_shop_orders] => 1
	                    [edit_others_shop_orders] => 1
	                    [publish_shop_orders] => 1
	                    [read_private_shop_orders] => 1
	                    [delete_shop_orders] => 1
	                    [delete_private_shop_orders] => 1
	                    [delete_published_shop_orders] => 1
	                    [delete_others_shop_orders] => 1
	                    [edit_private_shop_orders] => 1
	                    [edit_published_shop_orders] => 1
	                    [manage_shop_order_terms] => 1
	                    [edit_shop_order_terms] => 1
	                    [delete_shop_order_terms] => 1
	                    [assign_shop_order_terms] => 1
	                    [edit_shop_coupon] => 1
	                    [read_shop_coupon] => 1
	                    [delete_shop_coupon] => 1
	                    [edit_shop_coupons] => 1
	                    [edit_others_shop_coupons] => 1
	                    [publish_shop_coupons] => 1
	                    [read_private_shop_coupons] => 1
	                    [delete_shop_coupons] => 1
	                    [delete_private_shop_coupons] => 1
	                    [delete_published_shop_coupons] => 1
	                    [delete_others_shop_coupons] => 1
	                    [edit_private_shop_coupons] => 1
	                    [edit_published_shop_coupons] => 1
	                    [manage_shop_coupon_terms] => 1
	                    [edit_shop_coupon_terms] => 1
	                    [delete_shop_coupon_terms] => 1
	                    [assign_shop_coupon_terms] => 1
	                    [edit_shop_webhook] => 1
	                    [read_shop_webhook] => 1
	                    [delete_shop_webhook] => 1
	                    [edit_shop_webhooks] => 1
	                    [edit_others_shop_webhooks] => 1
	                    [publish_shop_webhooks] => 1
	                    [read_private_shop_webhooks] => 1
	                    [delete_shop_webhooks] => 1
	                    [delete_private_shop_webhooks] => 1
	                    [delete_published_shop_webhooks] => 1
	                    [delete_others_shop_webhooks] => 1
	                    [edit_private_shop_webhooks] => 1
	                    [edit_published_shop_webhooks] => 1
	                    [manage_shop_webhook_terms] => 1
	                    [edit_shop_webhook_terms] => 1
	                    [delete_shop_webhook_terms] => 1
	                    [assign_shop_webhook_terms] => 1
	                )

	        )

	    [editor] => Array
	        (
	            [name] => Editor
	            [capabilities] => Array
	                (
	                    [moderate_comments] => 1
	                    [manage_categories] => 1
	                    [manage_links] => 1
	                    [upload_files] => 1
	                    [unfiltered_html] => 1
	                    [edit_posts] => 1
	                    [edit_others_posts] => 1
	                    [edit_published_posts] => 1
	                    [publish_posts] => 1
	                    [edit_pages] => 1
	                    [read] => 1
	                    [level_7] => 1
	                    [level_6] => 1
	                    [level_5] => 1
	                    [level_4] => 1
	                    [level_3] => 1
	                    [level_2] => 1
	                    [level_1] => 1
	                    [level_0] => 1
	                    [edit_others_pages] => 1
	                    [edit_published_pages] => 1
	                    [publish_pages] => 1
	                    [delete_pages] => 1
	                    [delete_others_pages] => 1
	                    [delete_published_pages] => 1
	                    [delete_posts] => 1
	                    [delete_others_posts] => 1
	                    [delete_published_posts] => 1
	                    [delete_private_posts] => 1
	                    [edit_private_posts] => 1
	                    [read_private_posts] => 1
	                    [delete_private_pages] => 1
	                    [edit_private_pages] => 1
	                    [read_private_pages] => 1
	                )

	        )

	    [author] => Array
	        (
	            [name] => Author
	            [capabilities] => Array
	                (
	                    [upload_files] => 1
	                    [edit_posts] => 1
	                    [edit_published_posts] => 1
	                    [publish_posts] => 1
	                    [read] => 1
	                    [level_2] => 1
	                    [level_1] => 1
	                    [level_0] => 1
	                    [delete_posts] => 1
	                    [delete_published_posts] => 1
	                )

	        )

	    [contributor] => Array
	        (
	            [name] => Contributor
	            [capabilities] => Array
	                (
	                    [edit_posts] => 1
	                    [read] => 1
	                    [level_1] => 1
	                    [level_0] => 1
	                    [delete_posts] => 1
	                )

	        )

	    [subscriber] => Array
	        (
	            [name] => Subscriber
	            [capabilities] => Array
	                (
	                    [read] => 1
	                    [level_0] => 1
	                )

	        )

	    [customer] => Array
	        (
	            [name] => Customer
	            [capabilities] => Array
	                (
	                    [read] => 1
	                )

	        )

	    [shop_manager] => Array
	        (
	            [name] => Shop Manager
	            [capabilities] => Array
	                (
	                    [level_9] => 1
	                    [level_8] => 1
	                    [level_7] => 1
	                    [level_6] => 1
	                    [level_5] => 1
	                    [level_4] => 1
	                    [level_3] => 1
	                    [level_2] => 1
	                    [level_1] => 1
	                    [level_0] => 1
	                    [read] => 1
	                    [read_private_pages] => 1
	                    [read_private_posts] => 1
	                    [edit_users] => 1
	                    [edit_posts] => 1
	                    [edit_pages] => 1
	                    [edit_published_posts] => 1
	                    [edit_published_pages] => 1
	                    [edit_private_pages] => 1
	                    [edit_private_posts] => 1
	                    [edit_others_posts] => 1
	                    [edit_others_pages] => 1
	                    [publish_posts] => 1
	                    [publish_pages] => 1
	                    [delete_posts] => 1
	                    [delete_pages] => 1
	                    [delete_private_pages] => 1
	                    [delete_private_posts] => 1
	                    [delete_published_pages] => 1
	                    [delete_published_posts] => 1
	                    [delete_others_posts] => 1
	                    [delete_others_pages] => 1
	                    [manage_categories] => 1
	                    [manage_links] => 1
	                    [moderate_comments] => 1
	                    [unfiltered_html] => 1
	                    [upload_files] => 1
	                    [export] => 1
	                    [import] => 1
	                    [list_users] => 1
	                    [manage_woocommerce] => 1
	                    [view_woocommerce_reports] => 1
	                    [edit_product] => 1
	                    [read_product] => 1
	                    [delete_product] => 1
	                    [edit_products] => 1
	                    [edit_others_products] => 1
	                    [publish_products] => 1
	                    [read_private_products] => 1
	                    [delete_products] => 1
	                    [delete_private_products] => 1
	                    [delete_published_products] => 1
	                    [delete_others_products] => 1
	                    [edit_private_products] => 1
	                    [edit_published_products] => 1
	                    [manage_product_terms] => 1
	                    [edit_product_terms] => 1
	                    [delete_product_terms] => 1
	                    [assign_product_terms] => 1
	                    [edit_shop_order] => 1
	                    [read_shop_order] => 1
	                    [delete_shop_order] => 1
	                    [edit_shop_orders] => 1
	                    [edit_others_shop_orders] => 1
	                    [publish_shop_orders] => 1
	                    [read_private_shop_orders] => 1
	                    [delete_shop_orders] => 1
	                    [delete_private_shop_orders] => 1
	                    [delete_published_shop_orders] => 1
	                    [delete_others_shop_orders] => 1
	                    [edit_private_shop_orders] => 1
	                    [edit_published_shop_orders] => 1
	                    [manage_shop_order_terms] => 1
	                    [edit_shop_order_terms] => 1
	                    [delete_shop_order_terms] => 1
	                    [assign_shop_order_terms] => 1
	                    [edit_shop_coupon] => 1
	                    [read_shop_coupon] => 1
	                    [delete_shop_coupon] => 1
	                    [edit_shop_coupons] => 1
	                    [edit_others_shop_coupons] => 1
	                    [publish_shop_coupons] => 1
	                    [read_private_shop_coupons] => 1
	                    [delete_shop_coupons] => 1
	                    [delete_private_shop_coupons] => 1
	                    [delete_published_shop_coupons] => 1
	                    [delete_others_shop_coupons] => 1
	                    [edit_private_shop_coupons] => 1
	                    [edit_published_shop_coupons] => 1
	                    [manage_shop_coupon_terms] => 1
	                    [edit_shop_coupon_terms] => 1
	                    [delete_shop_coupon_terms] => 1
	                    [assign_shop_coupon_terms] => 1
	                    [edit_shop_webhook] => 1
	                    [read_shop_webhook] => 1
	                    [delete_shop_webhook] => 1
	                    [edit_shop_webhooks] => 1
	                    [edit_others_shop_webhooks] => 1
	                    [publish_shop_webhooks] => 1
	                    [read_private_shop_webhooks] => 1
	                    [delete_shop_webhooks] => 1
	                    [delete_private_shop_webhooks] => 1
	                    [delete_published_shop_webhooks] => 1
	                    [delete_others_shop_webhooks] => 1
	                    [edit_private_shop_webhooks] => 1
	                    [edit_published_shop_webhooks] => 1
	                    [manage_shop_webhook_terms] => 1
	                    [edit_shop_webhook_terms] => 1
	                    [delete_shop_webhook_terms] => 1
	                    [assign_shop_webhook_terms] => 1
	                )

	        )

	)
	 */
	protected function _init() {
		global $wpdb, $wp_user_roles;
		$this->role_key = $wpdb->get_blog_prefix() . 'user_roles';
		if ( ! empty( $wp_user_roles ) ) {
			/*** 正常情况下角色信息存放在全局变量$wp_roles中,
			如果在内存中定义了全局变量$wp_user_roles就表示角色信息不从db中取, 有何作用? */
			$this->roles = $wp_user_roles;
			$this->use_db = false;
		} else {
			$this->roles = get_option( $this->role_key );
		}

		if ( empty( $this->roles ) )
			return;
			
		$this->role_objects = array();
		$this->role_names =  array();
		foreach ( array_keys( $this->roles ) as $role ) {
			$this->role_objects[$role] = new WP_Role( $role, $this->roles[$role]['capabilities'] );
			$this->role_names[$role] = $this->roles[$role]['name'];
		}
	}

	/**
	 * Reinitialize the object
	 *
	 * Recreates the role objects. This is typically called only by switch_to_blog()
	 * after switching wpdb to a new site ID.
	 *
	 * @since 3.5.0
	 * @access public
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 */
	public function reinit() {
		// There is no need to reinit if using the wp_user_roles global.
		if ( ! $this->use_db )
			return;

		global $wpdb;

		// Duplicated from _init() to avoid an extra function call.
		$this->role_key = $wpdb->get_blog_prefix() . 'user_roles';
		$this->roles = get_option( $this->role_key );
		if ( empty( $this->roles ) )
			return;

		$this->role_objects = array();
		$this->role_names =  array();
		foreach ( array_keys( $this->roles ) as $role ) {
			$this->role_objects[$role] = new WP_Role( $role, $this->roles[$role]['capabilities'] );
			$this->role_names[$role] = $this->roles[$role]['name'];
		}
	}

	/**
	 * Add role name with capabilities to list.
	 *
	 * Updates the list of roles, if the role doesn't already exist.
	 *
	 * The capabilities are defined in the following format `array( 'read' => true );`
	 * To explicitly deny a role a capability you set the value for that capability to false.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param string $role Role name.
	 * @param string $display_name Role display name.
	 * @param array $capabilities List of role capabilities in the above format.
	 * @return WP_Role|void WP_Role object, if role is added.
	 */
	public function add_role( $role, $display_name, $capabilities = array() ) {
		if ( empty( $role ) || isset( $this->roles[ $role ] ) ) {
			return;
		}

		$this->roles[$role] = array(
			'name' => $display_name,
			'capabilities' => $capabilities
			);
		if ( $this->use_db )
			update_option( $this->role_key, $this->roles );
		$this->role_objects[$role] = new WP_Role( $role, $capabilities );
		$this->role_names[$role] = $display_name;
		return $this->role_objects[$role];
	}

	/**
	 * Remove role by name.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param string $role Role name.
	 */
	public function remove_role( $role ) {
		if ( ! isset( $this->role_objects[$role] ) )
			return;

		unset( $this->role_objects[$role] );
		unset( $this->role_names[$role] );
		unset( $this->roles[$role] );

		if ( $this->use_db )
			update_option( $this->role_key, $this->roles );

		if ( get_option( 'default_role' ) == $role )
			update_option( 'default_role', 'subscriber' );
	}

	/**
	 * Add capability to role.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param string $role Role name.
	 * @param string $cap Capability name.
	 * @param bool $grant Optional, default is true. Whether role is capable of performing capability.
	 */
	public function add_cap( $role, $cap, $grant = true ) {
		if ( ! isset( $this->roles[$role] ) )
			return;

		$this->roles[$role]['capabilities'][$cap] = $grant;
		if ( $this->use_db )
			update_option( $this->role_key, $this->roles );
	}

	/**
	 * Remove capability from role.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param string $role Role name.
	 * @param string $cap Capability name.
	 */
	public function remove_cap( $role, $cap ) {
		if ( ! isset( $this->roles[$role] ) )
			return;

		unset( $this->roles[$role]['capabilities'][$cap] );
		if ( $this->use_db )
			update_option( $this->role_key, $this->roles );
	}

	/**
	 * Retrieve role object by name.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param string $role Role name.
	 * @return WP_Role|null WP_Role object if found, null if the role does not exist.
	 */
	public function get_role( $role ) {
		if ( isset( $this->role_objects[$role] ) )
			return $this->role_objects[$role];
		else
			return null;
	}

	/**
	 * Retrieve list of role names.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @return array List of role names.
	 */
	public function get_names() {
		return $this->role_names;
	}

	/**
	 * Whether role name is currently in the list of available roles.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param string $role Role name to look up.
	 * @return bool
	 */
	public function is_role( $role ) {
		return isset( $this->role_names[$role] );
	}
}
