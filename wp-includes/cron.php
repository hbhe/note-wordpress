<?php
/**
 * WordPress CRON API
 *
 * @package WordPress
 */

 /* 
 实现定时任务的2种办法:
 1. 利用linux中的crond周期性执行php-cli wp-cron.php (先要在wp-config.php中定义DISABLE_WP_CRON为true关掉方法2即页面触发式)
 2. 利用页面触发执行(wordpress默认选择此方法)
 
 wordpress中的定时任务有: 定时发贴, 周期性检查插件主题更新, 发送邮件, 发送pingback等
 增加一个计划:当时间到$timestamp时, 执行挂在$hook锚点上的函数 


如何在应用中使用cron?
1. 注册一种周期id,当然如果系统自带的3种周期(hourly, twicedaily, daily)能满足你的需求, 就不用注册
add_filter( 'cron_schedules', 'example_add_cron_interval' ); 
function example_add_cron_interval( $schedules ) {
    $schedules['5seconds'] = array(
        'interval' => 5,
        'display'  => esc_html__( 'Every Five Seconds' ),
    ); 
    return $schedules;
}

2. 在一个勾子上挂一个函数
add_action( 'my_hook', 'my_func' );

3. 将首次执行时间(stamp), 周期id, 勾子进行登记,
if( !wp_next_scheduled( 'my_hook' ) ) {		// 一定要检查, 如果未登记过才登记, 以免费重复
    wp_schedule_event( time(), '5seconds', 'my_hook' );
    //wp_schedule_event( time()+10, 'hourly', 'my_hook' );
}
    
 OK, 3步做完后, 系统就会自动实现: 在首次执行时间到后执行一次勾子, 以后每 隔一段时间再执行一次

数据结构如下:
00:00:01 --->hook1  --> hook2 --> ... 
                        |
                        func1
                        |
                        func2

00:00:59 --> hook3 --> ... 
                        |
                        func1
                        
 */
 

/**
 * Schedules a hook to run only once.
 *
 * Schedules a hook which will be executed once by the WordPress actions core at
 * a time which you specify. The action will fire off when someone visits your
 * WordPress site, if the schedule time has passed.
 *
 * @since 2.1.0
 * @link https://codex.wordpress.org/Function_Reference/wp_schedule_single_event
 *
 * @param int $timestamp Timestamp for when to run the event.
 * @param string $hook Action hook to execute when cron is run.
 * @param array $args Optional. Arguments to pass to the hook's callback function.
 * @return false|void False when an event is not scheduled.
 */
 /***
 10秒钟后执行一次挂在勾子'my_hook'上的所有函数 
 wp_schedule_single_event( time()+10, 'my_hook' );
 */
function wp_schedule_single_event( $timestamp, $hook, $args = array()) {
	// Make sure timestamp is a positive integer
	if ( ! is_numeric( $timestamp ) || $timestamp <= 0 ) {
		return false;
	}

	// Don't schedule a duplicate if there's already an identical event due within 10 minutes of it
	$next = wp_next_scheduled($hook, $args);
	if ( $next && abs( $next - $timestamp ) <= 10 * MINUTE_IN_SECONDS ) {
		return false;
	}

	$crons = _get_cron_array();
	$event = (object) array( 'hook' => $hook, 'timestamp' => $timestamp, 'schedule' => false, 'args' => $args );
	/**
	 * Filter a single event before it is scheduled.
	 *
	 * @since 3.1.0
	 *
	 * @param object $event An object containing an event's data.
	 */
	$event = apply_filters( 'schedule_event', $event );

	// A plugin disallowed this event
	if ( ! $event )
		return false;

	$key = md5(serialize($event->args));

	$crons[$event->timestamp][$event->hook][$key] = array( 'schedule' => $event->schedule, 'args' => $event->args );
	uksort( $crons, "strnatcasecmp" );
	_set_cron_array( $crons );
}

/**
 * Schedule a periodic event.
 *
 * Schedules a hook which will be executed by the WordPress actions core on a
 * specific interval, specified by you. The action will trigger when someone
 * visits your WordPress site, if the scheduled time has passed.
 *
 * Valid values for the recurrence are hourly, daily and twicedaily. These can
 * be extended using the cron_schedules filter in wp_get_schedules().
 *
 * Use wp_next_scheduled() to prevent duplicates
 *
 * @since 2.1.0
 *
 * @param int $timestamp Timestamp for when to run the event.
 * @param string $recurrence How often the event should recur.
 * @param string $hook Action hook to execute when cron is run.
 * @param array $args Optional. Arguments to pass to the hook's callback function.
 * @return false|void False when an event is not scheduled.
 */
 /***
每半天一次, 执行挂在'woocommerce_cleanup_sessions' 锚点上的所有函数 
wp_schedule_event( time(), 'twicedaily', 'woocommerce_cleanup_sessions' );

$timestamp: 从什么时候起可以执行
$recurrence: 隔多久一次, 默认支持hourly, twicedaily,daily, 如果想自己加周期可用add_filter( 'cron_schedules', 'example_add_cron_interval' ); 见wp_get_schedules()
*/
function wp_schedule_event( $timestamp, $recurrence, $hook, $args = array()) {
	// Make sure timestamp is a positive integer
	if ( ! is_numeric( $timestamp ) || $timestamp <= 0 ) {
		return false;
	}

	$crons = _get_cron_array();
	$schedules = wp_get_schedules();
	/*** 如果此前未先定义$recurrence这个周期(或称频率), 退出 */
	if ( !isset( $schedules[$recurrence] ) )
		return false;

	$event = (object) array( 'hook' => $hook, 'timestamp' => $timestamp, 'schedule' => $recurrence, 'args' => $args, 'interval' => $schedules[$recurrence]['interval'] );
	/** This filter is documented in wp-includes/cron.php */
	$event = apply_filters( 'schedule_event', $event );

	// A plugin disallowed this event
	if ( ! $event )
		return false;

	$key = md5(serialize($event->args));

	$crons[$event->timestamp][$event->hook][$key] = array( 'schedule' => $event->schedule, 'args' => $event->args, 'interval' => $event->interval );
	uksort( $crons, "strnatcasecmp" );
	_set_cron_array( $crons );
}

/**
 * Reschedule a recurring event.
 *
 * @since 2.1.0
 *
 * @param int $timestamp Timestamp for when to run the event.
 * @param string $recurrence How often the event should recur.
 * @param string $hook Action hook to execute when cron is run.
 * @param array $args Optional. Arguments to pass to the hook's callback function.
 * @return false|void False when an event is not scheduled.
 */
 /***
根据一条记录, 插入一条下个时间点要执行的记录
 */
function wp_reschedule_event( $timestamp, $recurrence, $hook, $args = array() ) {
	// Make sure timestamp is a positive integer
	if ( ! is_numeric( $timestamp ) || $timestamp <= 0 ) {
		return false;
	}

	/*** 从db中取 */
	$crons = _get_cron_array();

	/*** 从内存中取 */
	$schedules = wp_get_schedules();
	
	$key = md5( serialize( $args ) );
	$interval = 0;

	// First we try to get it from the schedule
	/*** 
	$recurrence是频率id, 如果频率表中有这个id, 就取表中的保存的interval, 否则就从实际参数中取interval 
	interval保存在2个地方, 一个是在schedules频率表(内存中), 一个是在数据库中, 即使整张schedules表都不存在了,还是可以从数据库取得interval的
	知道interval, 就可以计算出下个执行时间点了
	*/
	if ( isset( $schedules[ $recurrence ] ) ) {
		$interval = $schedules[ $recurrence ]['interval'];
	}
	// Now we try to get it from the saved interval in case the schedule disappears
	if ( 0 == $interval ) {
		$interval = $crons[ $timestamp ][ $hook ][ $key ]['interval'];
	}
	// Now we assume something is wrong and fail to schedule
	if ( 0 == $interval ) {
		return false;
	}

	$now = time();

	if ( $timestamp >= $now ) {
		$timestamp = $now + $interval;
	} else {
		$timestamp = $now + ( $interval - ( ( $now - $timestamp ) % $interval ) );
	}

	/* 插入下一次要执行的任务及时间点*/
	wp_schedule_event( $timestamp, $recurrence, $hook, $args );
}

/**
 * Unschedule a previously scheduled cron job.
 *
 * The $timestamp and $hook parameters are required, so that the event can be
 * identified.
 *
 * @since 2.1.0
 *
 * @param int $timestamp Timestamp for when to run the event.
 * @param string $hook Action hook, the execution of which will be unscheduled.
 * @param array $args Arguments to pass to the hook's callback function.
 * Although not passed to a callback function, these arguments are used
 * to uniquely identify the scheduled event, so they should be the same
 * as those used when originally scheduling the event.
 * @return false|void False when an event is not unscheduled.
 */
function wp_unschedule_event( $timestamp, $hook, $args = array() ) {
	// Make sure timestamp is a positive integer
	if ( ! is_numeric( $timestamp ) || $timestamp <= 0 ) {
		return false;
	}

	$crons = _get_cron_array();
	$key = md5(serialize($args));
	unset( $crons[$timestamp][$hook][$key] );
	if ( empty($crons[$timestamp][$hook]) )
		unset( $crons[$timestamp][$hook] );
	if ( empty($crons[$timestamp]) )
		unset( $crons[$timestamp] );
	_set_cron_array( $crons );
}

/**
 * Unschedule all cron jobs attached to a specific hook.
 *
 * @since 2.1.0
 *
 * @param string $hook Action hook, the execution of which will be unscheduled.
 * @param array $args Optional. Arguments that were to be pass to the hook's callback function.
 */
function wp_clear_scheduled_hook( $hook, $args = array() ) {
	// Backward compatibility
	// Previously this function took the arguments as discrete vars rather than an array like the rest of the API
	if ( !is_array($args) ) {
		_deprecated_argument( __FUNCTION__, '3.0', __('This argument has changed to an array to match the behavior of the other cron functions.') );
		$args = array_slice( func_get_args(), 1 );
	}

	// This logic duplicates wp_next_scheduled()
	// It's required due to a scenario where wp_unschedule_event() fails due to update_option() failing,
	// and, wp_next_scheduled() returns the same schedule in an infinite loop.
	$crons = _get_cron_array();
	if ( empty( $crons ) )
		return;

	$key = md5( serialize( $args ) );
	foreach ( $crons as $timestamp => $cron ) {
		if ( isset( $cron[ $hook ][ $key ] ) ) {
			wp_unschedule_event( $timestamp, $hook, $args );
		}
	}
}

/**
 * Retrieve the next timestamp for a cron event.
 *
 * @since 2.1.0
 *
 * @param string $hook Action hook to execute when cron is run.
 * @param array $args Optional. Arguments to pass to the hook's callback function.
 * @return false|int The UNIX timestamp of the next time the scheduled event will occur.
 */
function wp_next_scheduled( $hook, $args = array() ) {
	$crons = _get_cron_array();
	$key = md5(serialize($args));
	if ( empty($crons) )
		return false;
	foreach ( $crons as $timestamp => $cron ) {
		if ( isset( $cron[$hook][$key] ) )
			return $timestamp;
	}
	return false;
}

/**
 * Sends a request to run cron through HTTP request that doesn't halt page loading.
 *
 * @since 2.1.0
 *
 * @param int $gmt_time Optional. Unix timestamp. Default 0 (current time is used).
 */
 /*
发起请求, 去做实际任务?
 */
function spawn_cron( $gmt_time = 0 ) {
	if ( ! $gmt_time )
		$gmt_time = microtime( true );

	/* DOING_CRON,  doing_wp_cron 标志作用? */
	if ( defined('DOING_CRON') || isset($_GET['doing_wp_cron']) )
		return;

	/*
	 * Get the cron lock, which is a unix timestamp of when the last cron was spawned
	 * and has not finished running.
	 *
	 * Multiple processes on multiple web servers can run this code concurrently,
	 * this lock attempts to make spawning as atomic as possible.
	 */
	$lock = get_transient('doing_cron');

	if ( $lock > $gmt_time + 10 * MINUTE_IN_SECONDS )
		$lock = 0;

	// don't run if another process is currently running it or more than once every 60 sec.
	if ( $lock + WP_CRON_LOCK_TIMEOUT > $gmt_time )
		return;

	//sanity check
	$crons = _get_cron_array();
	if ( !is_array($crons) )
		return;

	$keys = array_keys( $crons );
	if ( isset($keys[0]) && $keys[0] > $gmt_time )
		return;

	/* 如果定义ALTERNATE_WP_CRON, 表示使用另一种直接式(include)调起wp-cron.php */
	if ( defined( 'ALTERNATE_WP_CRON' ) && ALTERNATE_WP_CRON ) {
		if ( 'GET' !== $_SERVER['REQUEST_METHOD'] || defined( 'DOING_AJAX' ) ||  defined( 'XMLRPC_REQUEST' ) ) {
			return;
		}

		$doing_wp_cron = sprintf( '%.22F', $gmt_time );
		set_transient( 'doing_cron', $doing_wp_cron );

		ob_start();
		wp_redirect( add_query_arg( 'doing_wp_cron', $doing_wp_cron, wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
		echo ' ';

		// flush any buffers and send the headers
		while ( @ob_end_flush() );
		flush();

		/* 通过直接include方式执行wp-cron.php 
		本来用户访问index.php文件想看某贴子内容,结果现在要执行wp-cron代码, 所以干脆
		在wp-cron.php执行完后,redirect到原url(加上doing_wp_cron=9834555这个参数),用户就看到贴子的内容了
		ALTERNATE_WP_CRON的后果是如果有定时任务到时间了, 这时用户访问任何页面都会发生redirect ?
		*/
		WP_DEBUG ? include_once( ABSPATH . 'wp-cron.php' ) : @include_once( ABSPATH . 'wp-cron.php' );
		return;
	}

	// Set the cron lock with the current unix timestamp, when the cron is being spawned.
	$doing_wp_cron = sprintf( '%.22F', $gmt_time );
	// 置doing_cron标志
	set_transient( 'doing_cron', $doing_wp_cron );

	/**
	 * Filter the cron request arguments.
	 *
	 * @since 3.5.0
	 * @since 4.5.0 The `$doing_wp_cron` parameter was added.
	 *
	 * @param array $cron_request_array {
	 *     An array of cron request URL arguments.
	 *
	 *     @type string $url  The cron request URL.
	 *     @type int    $key  The 22 digit GMT microtime.
	 *     @type array  $args {
	 *         An array of cron request arguments.
	 *
	 *         @type int  $timeout   The request timeout in seconds. Default .01 seconds.
	 *         @type bool $blocking  Whether to set blocking for the request. Default false.
	 *         @type bool $sslverify Whether SSL should be verified for the request. Default false.
	 *     }
	 * }
	 * @param string $doing_wp_cron The unix timestamp of the cron lock.
	 */
	$cron_request = apply_filters( 'cron_request', array(
		'url'  => add_query_arg( 'doing_wp_cron', $doing_wp_cron, site_url( 'wp-cron.php' ) ),
		'key'  => $doing_wp_cron,
		'args' => array(
			'timeout'   => 0.01,
			'blocking'  => false,
			/** This filter is documented in wp-includes/class-wp-http-streams.php */
			'sslverify' => apply_filters( 'https_local_ssl_verify', false )
		)
	), $doing_wp_cron );

	/* 非阻塞发起HTTP请求方式执行wp-cron.php */
	wp_remote_post( $cron_request['url'], $cron_request['args'] );
}

/**
 * Run scheduled callbacks or spawn cron for all scheduled events.
 *
 * @since 2.1.0
 */
 /* 
 每次页面请求(wp-cron.php除外)都会执行此函数? 
 wp_cron() 会通过HTTP或直接include的方式调起wp-cron.php文件
 */
function wp_cron() {
	// Prevent infinite loops caused by lack of wp-cron.php
	/* 
	如果DISABLE_WP_CRON = true 直接退出, 
	wp-cron.php是被调用者,如果调用wp_cron()就会形成死循环
	*/
	if ( strpos($_SERVER['REQUEST_URI'], '/wp-cron.php') !== false || ( defined('DISABLE_WP_CRON') && DISABLE_WP_CRON ) )
		return;

	/* 从db中获取所有计划,逐条执行(执行的方式有2种:include直接式, socket请求式)? */
	if ( false === $crons = _get_cron_array() )
		return;

	$gmt_time = microtime( true );
	$keys = array_keys( $crons );
	if ( isset($keys[0]) && $keys[0] > $gmt_time )
		return;

	$schedules = wp_get_schedules();
	foreach ( $crons as $timestamp => $cronhooks ) {
		// $schedules是按时间从小到大排序的, 如果前面某个计划时间没到,就没必要查后面的了
		if ( $timestamp > $gmt_time ) break;
		foreach ( (array) $cronhooks as $hook => $args ) {
			/* 如果存在callback, 直接执行之 */
			if ( isset($schedules[$hook]['callback']) && !call_user_func( $schedules[$hook]['callback'] ) )
				continue;
				
			spawn_cron( $gmt_time );
			/* 激发一次就行了, 跳出2层循环*/
			break 2;
		}
	}
}

/**
 * Retrieve supported and filtered Cron recurrences.
 *
 * The supported recurrences are 'hourly' and 'daily'. A plugin may add more by
 * hooking into the 'cron_schedules' filter. The filter accepts an array of
 * arrays. The outer array has a key that is the name of the schedule or for
 * example 'weekly'. The value is an array with two keys, one is 'interval' and
 * the other is 'display'.
 *
 * The 'interval' is a number in seconds of when the cron job should run. So for
 * 'hourly', the time is 3600 or 60*60. For weekly, the value would be
 * 60*60*24*7 or 604800. The value of 'interval' would then be 604800.
 *
 * The 'display' is the description. For the 'weekly' key, the 'display' would
 * be `__( 'Once Weekly' )`.
 *
 * For your plugin, you will be passed an array. you can easily add your
 * schedule by doing the following.
 *
 *     // Filter parameter variable name is 'array'.
 *     $array['weekly'] = array(
 *         'interval' => 604800,
 *     	   'display'  => __( 'Once Weekly' )
 *     );
 *
 *
 * @since 2.1.0
 *
 * @return array
 */
 /***
默认是:
每小时一次,
每半天一次,
每天一次,

如果想增加一种周期
每5秒一次, 
add_filter( 'cron_schedules', 'example_add_cron_interval' ); 
function example_add_cron_interval( $schedules ) {
    $schedules['five_seconds'] = array(
        'interval' => 5,
        'display'  => esc_html__( 'Every Five Seconds' ),
    ); 
    return $schedules;
}
 */
function wp_get_schedules() {
	$schedules = array(
		'hourly'     => array( 'interval' => HOUR_IN_SECONDS,      'display' => __( 'Once Hourly' ) ),
		'twicedaily' => array( 'interval' => 12 * HOUR_IN_SECONDS, 'display' => __( 'Twice Daily' ) ),
		'daily'      => array( 'interval' => DAY_IN_SECONDS,       'display' => __( 'Once Daily' ) ),
	);
	/**
	 * Filter the non-default cron schedules.
	 *
	 * @since 2.1.0
	 *
	 * @param array $new_schedules An array of non-default cron schedules. Default empty.
	 */
	return array_merge( apply_filters( 'cron_schedules', array() ), $schedules );
}

/**
 * Retrieve Cron schedule for hook with arguments.
 *
 * @since 2.1.0
 *
 * @param string $hook Action hook to execute when cron is run.
 * @param array $args Optional. Arguments to pass to the hook's callback function.
 * @return string|false False, if no schedule. Schedule on success.
 */
function wp_get_schedule($hook, $args = array()) {
	$crons = _get_cron_array();
	$key = md5(serialize($args));
	if ( empty($crons) )
		return false;
	foreach ( $crons as $timestamp => $cron ) {
		if ( isset( $cron[$hook][$key] ) )
			return $cron[$hook][$key]['schedule'];
	}
	return false;
}

//
// Private functions
//

/**
 * Retrieve cron info array option.
 *
 * @since 2.1.0
 * @access private
 *
 * @return false|array CRON info array.
 */
 /* 从db中取出计划 */
function _get_cron_array()  {
	$cron = get_option('cron');
	if ( ! is_array($cron) )
		return false;

	if ( !isset($cron['version']) )
		$cron = _upgrade_cron_array($cron);

	unset($cron['version']);

	return $cron;
}

/**
 * Updates the CRON option with the new CRON array.
 *
 * @since 2.1.0
 * @access private
 *
 * @param array $cron Cron info array from {@link _get_cron_array()}.
 */
function _set_cron_array($cron) {
	$cron['version'] = 2;
	update_option( 'cron', $cron );
}

/**
 * Upgrade a Cron info array.
 *
 * This function upgrades the Cron info array to version 2.
 *
 * @since 2.1.0
 * @access private
 *
 * @param array $cron Cron info array from {@link _get_cron_array()}.
 * @return array An upgraded Cron info array.
 */
function _upgrade_cron_array($cron) {
	if ( isset($cron['version']) && 2 == $cron['version'])
		return $cron;

	$new_cron = array();

	foreach ( (array) $cron as $timestamp => $hooks) {
		foreach ( (array) $hooks as $hook => $args ) {
			$key = md5(serialize($args['args']));
			$new_cron[$timestamp][$hook][$key] = $args;
		}
	}

	$new_cron['version'] = 2;
	update_option( 'cron', $new_cron );
	return $new_cron;
}
