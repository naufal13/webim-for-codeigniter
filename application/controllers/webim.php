<?php

class Webim extends CI_Controller {

	public function __construct() {

		parent::__construct();

		//load config
		$this->config->load('webim');

		//load model
		$this->load->model('Webim_model');
		$this->load->model('Setting_model');
		$this->load->model('History_model');
		$cfg = $this->config->item('webim');
		$this->Webim_model->initialize($cfg);

		//ticket
		$ticket = $this->input->get_post('ticket');
		if($ticket) {
			$ticket = stripslashes($ticket);
		}

		//client
		$this->load->library('Httpc', array(
			'host' => $this->config->item('host', 'webim'),
			'port' => $this->config->item('port', 'webim'),
		));
		$this->load->library('Webimc', array(
			'user'		=> $this->Webim_model->current_user(), 
			'domain'	=> $this->config->item('domain', 'webim'), 
			'apikey'	=> $this->config->item('apikey', 'webim'), 
			'ticket'	=> $ticket ? $ticket : '', 
			'httpc'		=> $this->httpc
		));

	}

	/**
	 * Webim介绍页面，正式版本可删除本页面
	 */
	public function index() {
		$this->load->view('webim/index');	
	}

	/**
	 * Webim嵌入Javascript
	 */
	public function run() {
		$imc = $this->config->item('webim');
		//插件关闭或用户未登录, 退出
		if(! ($imc['isopen'] and $this->Webim_model->logined()) ) exit();

		$fields = array(
			'version',
			'theme', 
			'local', 
			'emot',
			'opacity',
			'enable_room', 
			'enable_chatlink', 
			'enable_shortcut',
			'enable_noti',
			'enable_menu',
			'show_unavailable',
			'upload');

		$scriptVar = array(
			'production_name' => 'ci',
			'path' => $this->config->base_url(),
			'is_login' => '1',
			'login_options' => '',
			'user' => $this->Webim_model->current_user(),
			//load setting
			'setting' => '', 
			'min' => $imc['debug'] ? '' : '.min'
		);

		foreach($fields as $f) {
			$scriptVar[$f] = $imc[$f];	
		}

		header("Content-type: application/javascript");
		header("Cache-Control: no-cache");

		echo "var _IMC = " . json_encode($scriptVar) . ";" . PHP_EOL;

		$script = <<<EOF
_IMC.script = window.webim ? '' : ('<link href="' + _IMC.path + 'static/webim.' + _IMC.production_name + _IMC.min + '.css?' + _IMC.version + '" media="all" type="text/css" rel="stylesheet"/><link href="' + _IMC.path + 'static/themes/' + _IMC.theme + '/jquery.ui.theme.css?' + _IMC.version + '" media="all" type="text/css" rel="stylesheet"/><script src="' + _IMC.path + 'static/webim.' + _IMC.production_name + _IMC.min + '.js?' + _IMC.version + '" type="text/javascript"></script><script src="' + _IMC.path + 'static/i18n/webim-' + _IMC.local + '.js?' + _IMC.version + '" type="text/javascript"></script>');
_IMC.script += '<script src="' + _IMC.path + 'static/webim.js?' + _IMC.version + '" type="text/javascript"></script>';
document.write( _IMC.script );

EOF;
		exit($script);

	}

	/**
	 * Webim上线接口
	 */
	public function online() {
		$uid = $this->Webim_model->current_uid();
		$domain = $this->input->post('domain');
		if ( !$this->Webim_model->logined() ) {
			return _json_return(
				array("success" => false, 
					  "error_msg" => "Forbidden" ));
		}
		$im_buddies = array(); //For online.
		$im_rooms = array(); //For online.
		$strangers = $this->_ids_array( $this->input->post('stranger_ids') );
		$cache_buddies = array();//For find.
		$cache_rooms = array();//For find.

		$active_buddies = $this->_ids_array( $this->input->post('buddy_ids') );
		$active_rooms = $this->_ids_array( $this->input->post('room_ids') );

		$new_messages = $this->History_model->getOffline($uid);
		$online_buddies = $this->Webim_model->buddies();
		
		$buddies_with_info = array();
		//Active buddy who send a new message.
		foreach($new_messages as $msg) {
			if(!in_array($msg['from'], $active_buddies)) {
				$active_buddies[] = $msg['from'];
			}
		}

		//Find im_buddies
		$all_buddies = array();
		foreach($online_buddies as $k => $v){
			$id = $v->id;
			$im_buddies[] = $id;
			$buddies_with_info[] = $id;
			$v->presence = "offline";
			$v->show = "unavailable";
			$cache_buddies[$id] = $v;
			$all_buddies[] = $id;
		}

		//Get active buddies info.
		$buddies_without_info = array();
		foreach($active_buddies as $k => $v){
			if(!in_array($v, $buddies_with_info)){
				$buddies_without_info[] = $v;
			}
		}
		if(!empty($buddies_without_info) || !empty($strangers)){
			//FIXME
			$bb = $this->Webim_model->buddies_by_ids(implode(",", $buddies_without_info), implode(",", $strangers));
			foreach( $bb as $k => $v){
				$id = $v->id;
				$im_buddies[] = $id;
				$v->presence = "offline";
				$v->show = "unavailable";
				$cache_buddies[$id] = $v;
			}
		}
		if(! $this->config->item('enable_room', 'webim') ){
			$rooms = $this->Webim_model->rooms();
			$setting = $this->Setting_model->get($uid);
			$blocked_rooms = $setting && is_array($setting->blocked_rooms) ? $setting->blocked_rooms : array();
			//Find im_rooms 
			//Except blocked.
			foreach($rooms as $k => $v){
				$id = $v->id;
				if(in_array($id, $blocked_rooms)){
					$v->blocked = true;
				}else{
					$v->blocked = false;
					$im_rooms[] = $id;
				}
				$cache_rooms[$id] = $v;
			}
			//Add temporary rooms 
			$temp_rooms = $setting && is_array($setting->temporary_rooms) ? $setting->temporary_rooms : array();
			for ($i = 0; $i < count($temp_rooms); $i++) {
				$rr = $temp_rooms[$i];
				$rr->temporary = true;
				$rr->pic_url = ($this->config->base_url() . "static/images/chat.png");
				$rooms[] = $rr;
				$im_rooms[] = $rr->id;
				$cache_rooms[$rr->id] = $rr;
			}
		}else{
			$rooms = array();
		}

		//===============Online===============
		//

		$data = $this->webimc->online( implode(",", array_unique( $im_buddies ) ), implode(",", array_unique( $im_rooms ) ) );

		if( $data->success ){
			$data->new_messages = $new_messages;

			if(!$this->config->item('enable_room', 'webim')) {
				//Add room online member count.
				foreach ($data->rooms as $k => $v) {
					$id = $v->id;
					$cache_rooms[$id]->count = $v->count;
				}
				//Show all rooms.
			}
			$data->rooms = $rooms;

			$show_buddies = array();//For output.
			foreach($data->buddies as $k => $v){
				$id = $v->id;
				if(!isset($cache_buddies[$id])){
					$cache_buddies[$id] = (object)array(
						"id" => $id,
						"nick" => $id,
						"incomplete" => true,
					);
				}
				$b = $cache_buddies[$id];
				$b->presence = $v->presence;
				$b->show = $v->show;
				if( !empty($v->nick) )
					$b->nick = $v->nick;
				if( !empty($v->status) )
					$b->status = $v->status;
				#show online buddy
				$show_buddies[] = $id;
			}
			#show active buddy
			$show_buddies = array_unique(array_merge($show_buddies, $active_buddies, $all_buddies));
			$o = array();
			foreach($show_buddies as $id){
				//Some user maybe not exist.
				if(isset($cache_buddies[$id])){
					$o[] = $cache_buddies[$id];
				}
			}

			//Provide history for active buddies and rooms
			foreach($active_buddies as $id){
				if(isset($cache_buddies[$id])){
					$cache_buddies[$id]->history = $this->History_model->get($uid, $id, "chat" );
				}
			}
			foreach($active_rooms as $id){
				if(isset($cache_rooms[$id])){
					$cache_rooms[$id]->history = $this->History_model->get($uid, $id, "grpchat" );
				}
			}

			$show_buddies = $o;
			$data->buddies = $show_buddies;
			$this->History_model->offlineReaded($uid);
			$this->_json_return($data);
		} else {
			$this->_json_return(array( 
				"success" => false, 
				"error_msg" => empty( $data->error_msg ) ? "IM Server Not Found" : "IM Server Not Authorized", 
				"im_error_msg" => $data->error_msg)); 
		}
	}

	public function offline() {
		$this->webimc->offline();
		return "ok";
	}

	public function message() {
		$type = $this->input->post("type");
		$offline = $this->input->post("offline");
		$to = $this->input->post("to");
		$body = $this->input->post("body");
		$style = $this->input->post("style");
		$send = $offline == "true" || $offline == "1" ? 0 : 1;
		$timestamp = $this->_microtime_float() * 1000;
		if( strpos($body, "webim-event:") !== 0 ) {
			$this->History_model->insert($this->current_user, array(
				"send" => $send,
				"type" => $type,
				"to" => $to,
				"body" => $body,
				"style" => $style,
				"timestamp" => $timestamp,
			));
		}
		if($send == 1){
			$this->webimc->message($type, $to, $body, $style, $timestamp);
		}
		$this->_ok_return();
	}

	public function presence() {
		$show = $this->input->post('show');
		$status = $this->input->post('status');
		$this->webimc->presence($show, $status);
		$this->_ok_return();
	}

	public function history() {
		$uid = $this->Webim_model->current_uid();
		$with = $this->input->get('id');
		$type = $this->input->get('type');
		$histories = $this->History_model->get($uid, $with, $type);
		$this->_json_return($histories);
	}

	public function status() {
		$to = $this->input->post("to");
		$show = $this->input->post("show");
		$this->webimc->status($to, $show);
		$this->_ok_return();
	}

	public function members() {
		$id = $this->input->get('id');
		$re = $this->webimc->members( $id );
		if($re) {
			$this->_json_return($re);
		} else {
			$this->_json_return("Not Found");
		}
	}
	
	public function join() {
		$id = $this->input->post('id');
		$room = $this->Webim_model->rooms_by_ids( $id );
		if( $room && count($room) ) {
			$room = $room[0];
		} else {
			$room = (object)array(
				"id" => $id,
				"nick" => $this->input->post('nick'),
				"temporary" => true,
				"pic_url" => ($this->config->base_url() . "static/images/chat.png"),
			);
		}
		if(!$room){
			header("HTTP/1.0 404 Not Found");
			exit("Can't found this room");
		}
		$re = $this->webimc->join($id);
		if(!$re){
			header("HTTP/1.0 404 Not Found");
			exit("Can't join this room right now");
		}
		$room->count = $re->count;
		$this->_json_return($room);
	}

	public function leave() {
		$id = $this->input->post('id');
		$this->webimc->leave( $id );
		$this->_ok_return();
	}

	/**
	 * Webim读取好友列表接口
	 */
	public function buddies() {
		$ids = $this->input->get('ids');
		$buddies = $this->Webim_model->buddies_by_ids($ids);
		$this->_json_return($buddies);
	}

	/**
	 * Webim读取群组接口
	 */
	public function rooms() {
		$ids = $this->input->get("ids");
		$rooms = $this->Webim_model->rooms_by_ids($ids);
		$this->_json_return($rooms);	
	}

	public function refresh() {
		$this->webimc->offline();
		$this->_ok_return();
	}

	/**
	 * 清空历史记录
	 */
	public function clear_history() {
		$id = $this->input->post('id');
		$this->History_model->clear($this->Webim_model->current_uid(), $id);
		$this->_ok_return();
	}

	/**
	 * 下载历史记录
	 */
	public function download_history() {
		$uid = $this->Webim_model->current_uid();
		$id = $this->input->get('id');
		$type = $this->input->get('type');
		$histories = $this->History_model->get($uid, $id, $type, 1000 );
		$date = date( 'Y-m-d' );
		if($this->input->get('date')) {
			$date = $this->input->get('date');
		}
		//FIXME Later
		//$client_time = (int)$this->input('time');
		//$server_time = webim_microtime_float() * 1000;
		//$timedelta = $client_time - $server_time;
		header('Content-Type',	'text/html; charset=utf-8');
		header('Content-Disposition: attachment; filename="histories-'.$date.'.html"');
		$this->load->view('webim/download_history');
	}

	public function setting() {
		$uid = $this->Webim_model->current_uid();
		$data = $this->input->post('data');
		$this->History_model->set($uid, $data);
		$this->_ok_return();
	}

	/**
	 * 返回通知列表
	 */
	public function notifications() {
		$this->_json_return(
			$this->Webim_model->notifications());
	}

	public function openchat() {
		$grpid = $this->input->post('group_id');
		$nick = $this->input->post('nick');
		return $this->_json_return( 
			$this->webimc->openchat($grpid, $nick) );	
	}

	public function closechat() {
		$grpid = $this->input->post('group_id');
		$buddy_id = $this->input->post('buddy_id');
		$this->_json_return( 
			$this->webimc->closechat($grpid, $buddy_id) );
	}

	private function _ok_return() {
		$this->_json_return("ok");		
	}

	public function _json_return( $data ) {
		header('Content-Type:application/json; charset=utf-8');
		exit(json_encode($data));
	}

	private function _ids_array( $ids ){
		return ($ids===NULL || $ids==="") ? array() : (is_array($ids) ? array_unique($ids) : array_unique(explode(",", $ids)));
	}

	private function _microtime_Float() {
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}

}

