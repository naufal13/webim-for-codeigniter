<?php

/**
 * Webim集成好友关系、群组关系模型。不对应任何库表。
 */
class Webim_model extends CI_Model {

	/*
	 * 当前用户或者访客
	 */
	private $user = NULL;

	/*
	 * 角色: 管理员、用户、访客
	 */
	public $role = 'user';

	/*
	 * 是否登录
	 */
	private $is_login = false;

	function __construct() {
		parent::__construct();
	}

	/*
	 * 初始化当前用户信息
	 */
	function initialize($cfg) {
		// get CodeIgniter instance
		$CI = &get_instance();
		if($this->current_uid()) {
			$this->_init_user();			
			$this->is_login = true;
		} else if($CI->config->item('visitor', 'webim')) {
			$this->_init_visitor();
			$this->is_login = true;
		}
	}

	/*
	 * 当前用户是否登录
	 */
	function logined() {
		return $this->is_login;	
	}

	function current_uid() {
		return $_SESSION['mid'];
	}

	function current_user() {
		return $this->user;	
	}

	function is_admin() {
		return $this->role == 'admin';
	}

	function is_visitor() {
		return $this->role == 'visitor';
	}

	/*
	 * 接口函数: 读取当前用户的好友在线好友列表
	 *
	 * Buddy对象属性:
	 *
	 * 	uid: 好友uid
	 * 	id:  同uid
	 *	nick: 好友昵称
	 *	pic_url: 头像图片
	 *	show: available | unavailable
	 *  url: 好友主页URL
	 *  status: 状态信息 
	 *  group: 所属组
	 */
	function buddies() {
		//根据当前用户id获取好友列表
		return array(clone $this->user);
	}

	/*
	 * 接口函数: 根据好友id列表、陌生人id列表读取用户, id列表为逗号分隔字符串
	 *
	 * 用户属性同上
	 */
	function buddies_by_ids($friend_uids = "", $stranger_uids = "") {
		return array();
	}

	/*
	 * 接口函数：读取当前用户的Room列表
	 *
	 * Room对象属性:
	 *
	 *	id:		Room ID,
	 *	nick:	显示名称
	 *	url:	Room主页地址
	 *	pic_url: Room图片
	 *	status: Room状态信息
	 *	count:  0
	 *	all_count: 成员总计
	 *	blocked: true | false 是否block
	 */
	function rooms() {
		//根据当前用户id获取群组列表
		$demoRoom = array(
			"id" => '1',
			"nick" => 'demoroom',
			"url" => "#",
			"pic_url" => "/static/images/chat.png",
			"status" => "demo room",
			"count" => 0,
			"all_count" => 1,
			"blocked" => false,
		);
		return array( $demoRoom );	
	}
	
	/*
	 * 接口函数: 根据id列表读取rooms, id列表为逗号分隔字符串
	 *
	 * Room对象属性同上
	 */
	function rooms_by_ids($ids) {
		return array();	
	}

	/*
	 * 接口函数: 当前用户通知列表
	 *
	 * Notification对象属性:
	 *
	 * 	text: 文本
	 * 	link: 链接
	 */	
	function notifications() {
		return array();	
	}

	/*
	 * 接口函数: 初始化当前用户对象，与站点用户集成.
	 */
	private function _init_user() {
		$CI = &get_instance();
		$uid = $_SESSION['uid'];
		$this->user = (object)array(
			'uid' => $uid,
			'id' => $uid,
			'nick' => "nick".$id,//TODO: 
			'pic_url' => $CI->config->base_url() . "/static/images/chat.png", //TODO:
			'show' => "available",
			'url' => "#",
			'status' => "",
		);
	}

	/*
	 * 接口函数: 创建访客对象，可根据实际需求修改.
	 */
	private function _init_visitor() {
		$CI = &get_instance();
		if ( isset($_COOKIE['_webim_visitor_id']) ) {
			$id = $_COOKIE['_webim_visitor_id'];
		} else {
			$id = substr(uniqid(), 6);
			setcookie('_webim_visitor_id', $id, time() + 3600 * 24 * 30, "/", "");
		}
		$this->role = 'visitor';
		$this->user = (object)array(
			'uid' => $id,
			'id' => $id,
			'nick' => "v".$id,
			'pic_url' => $CI->config->base_url() . "/static/images/chat.png",
			'show' => "available",
			'url' => "#",
		);
	}

}
