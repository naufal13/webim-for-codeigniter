<?php


class Webim extends CI_Controller {

	public function __construct() {

		parent::__construct();

		//load config
		$this->config->load('webim');
		$IMC = $this->config->item('webim');

		//load model
		$this->load->model('WebIM_Model', '', TRUE);

        //load plugin
		$this->load->model('WebIM_Plugin');

		//ticket
		$ticket = $this->input->get_post('ticket');
		if($ticket) $ticket = stripslashes($ticket);

		//client
		$this->load->library('WebIM_Client', array(
			'endpoint'	=> $this->WebIM_Plugin->current_user(), 
			'domain'	=> $IMC['domain'],
			'apikey'	=> $IMC['apikey'],
			'server'	=> $IMC['server'],
			'ticket'	=> $ticket ? $ticket : ''
		));
	}

    private function current_uid() {
        $user  = $this->WebIM_Plugin->current_user();
        return $user['uid'];
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
	public function boot() {
		$IMC = $this->config->item('webim');
		//插件关闭或用户未登录, 退出
		if(! ($IMC['isopen'] and $this->WebIM_Plugin->logined()) ) exit();

        //FIX offline Bug
        $user = $this->WebIM_Plugin->current_user();
        $user['show'] = "unavailable";

        //Setting
        $setting = $this->WebIM_Model->setting($user['id']);

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
            'version' => $IMC['version'],
			'production_name' => 'ci',
			'path' => $this->config->base_url(),
			'is_login' => '1',
            'is_visitor' => false,
			'login_options' => '',
			'user' => $user,
			//load setting
			'setting' => $setting, 
			'min' => $IMC['debug'] ? '' : '.min'
		);

		foreach($fields as $f) {
			$scriptVar[$f] = $IMC[$f];	
		}

		header("Content-type: application/javascript");
		header("Cache-Control: no-cache");

		echo "var _IMC = " . json_encode($scriptVar) . ";" . PHP_EOL;

		$script = <<<EOF
_IMC.script = window.webim ? '' : ('<link href="' + _IMC.path + 'static/webim' + _IMC.min + '.css?' + _IMC.version + '" media="all" type="text/css" rel="stylesheet"/><link href="' + _IMC.path + 'static/themes/' + _IMC.theme + '/jquery.ui.theme.css?' + _IMC.version + '" media="all" type="text/css" rel="stylesheet"/><script src="' + _IMC.path + 'static/webim' + _IMC.min + '.js?' + _IMC.version + '" type="text/javascript"></script><script src="' + _IMC.path + 'static/i18n/webim-' + _IMC.local + '.js?' + _IMC.version + '" type="text/javascript"></script>');
_IMC.script += '<script src="' + _IMC.path + 'static/webim.' + _IMC.production_name + '.js?' + _IMC.version + '" type="text/javascript"></script>';
document.write( _IMC.script );

EOF;
		exit($script);

	}

	/**
	 * Webim上线接口
	 */
	public function online() {
		$IMC  = $this->config->item('webim');
		$uid = $this->current_uid();
        $show = $this->input->post('show');

        //buddy, room, chatlink ids
		$chatlinkIds= $this->_ids_array( $this->input->post('chatlink_ids') );
		$activeRoomIds = $this->_ids_array( $this->input->post('room_ids') );
		$activeBuddyIds = $this->_ids_array( $this->input->post('buddy_ids') );
		//active buddy who send a offline message.
		$offlineMessages = $this->WebIM_Model->offline_histories($uid);
		foreach($offlineMessages as $msg) {
			if(!in_array($msg['from'], $activeBuddyIds)) {
				$activeBuddyIds[] = $msg['from'];
			}
		}
        //buddies of uid
		$buddies = $this->WebIM_Plugin->buddies($uid);
        $buddyIds = array_map(function($buddy) { return $buddy['id']; }, $buddies);
        $buddyIdsWithoutInfo = array_filter( array_merge($chatlinkIds, $activeBuddyIds), function($id) use($buddyIds){ return !in_array($id, $buddyIds); } );
        //buddies by ids
		$buddiesByIds = $this->WebIM_Plugin->buddies_by_ids($buddyIdsWithoutInfo);
        //all buddies
        $buddies = array_merge($buddies, $buddiesByIds);

        $rooms = array(); $roomIds = array();
		if( $IMC['enable_room'] ) {
            //persistent rooms
			$persistRooms = $this->WebIM_Plugin->rooms($uid);
            //temporary rooms
			$temporaryRooms = $this->WebIM_Model->rooms($uid);
            $rooms = array_merge($persistRooms, $temporaryRooms);
            $roomIds = array_map(function($room) { return $room['id']; }, $rooms);
		}

		//===============Online===============
		$data = $this->webim_client->online($buddyIds, $roomIds, $show);
		if( $data->success ) {
            $rtBuddies = array();
            $presences = $data->presences;
            foreach($buddies as $buddy) {
                $id = $buddy['id'];
                if( isset($presences->$id) ) {
                    $buddy['presence'] = 'online';
                    $buddy['show'] = $presences->$id;
                } else {
                    $buddy['presence'] = 'offline';
                    $buddy['show'] = 'unavailable';
                }
                $rtBuddies[$id] = $buddy;
            }
			//histories for active buddies and rooms
			foreach($activeBuddyIds as $id) {
                if( isset($rtBuddies[$id]) ) {
                    $rtBuddies[$id]['history'] = $this->WebIM_Model->histories($uid, $id, "chat" );
                }
			}
            if( !$IMC['show_unavailable'] ) {
                $rtBuddies = array_filter($rtBuddies, 
                    function($buddy) { return $buddy['presence'] === 'online'; });        
            }
            $rtRooms = array();
            if( $IMC['enable_room'] ) {
                foreach($rooms as $room) {
                    $rtRooms[$room['id']] = $room;
                }
                foreach($activeRoomIds as $id){
                    if( isset($rtRooms[$id]) ) {
                        $rtRooms[$id]['history'] = $this->WebIM_Model->histories($uid, $id, "grpchat" );
                    }
                }
            }

			$this->WebIM_Model->offline_readed($uid);

            $user = $this->WebIM_Plugin->current_user();
            if($show) $user['show'] = $show;

            $this->_json_return(array(
                'success' => true,
                'connection' => $data->connection,
                'user' => $user,
                'buddies' => array_values($rtBuddies),
                'rooms' => array_values($rtRooms),
                'new_messages' => $offlineMessages,
                'server_time' => microtime(true) * 1000
            ));
		} else {
			$this->jsonReply(array ( 
				'success' => false,
                'error' => $data
            )); 
        }
	}

    /**
     * Offline API
     */
	public function offline() {
		$this->webim_client->offline();
		return $this->_ok_return();
	}

    /**
     * Browser Refresh, may be called
     */
	public function refresh() {
		$this->webim_client->offline();
		$this->_ok_return();
	}

	/**
	 * Webim读取好友列表接口
	 */
	public function buddies() {
		$ids = $this->input->get('ids');
		$buddies = $this->WebIM_Plugin->buddies_by_ids($ids);
		$this->_json_return($buddies);
	}

	public function message() {
        $user = $this->WebIM_Plugin->current_user();
		$type = $this->input->post("type");
		$offline = $this->input->post("offline");
		$to = $this->input->post("to");
		$body = $this->input->post("body");
		$style = $this->input->post("style");
		$send = $offline == "true" || $offline == "1" ? 0 : 1;
		$timestamp = microtime(true) * 1000;
		if( strpos($body, "webim-event:") !== 0 ) {
			$this->WebIM_Model->insert_history(array(
				"send" => $send,
				"type" => $type,
				"to" => $to,
                'from' => $user['id'],
                'nick' => $user['nick'],
				"body" => $body,
				"style" => $style,
				"timestamp" => $timestamp,
			));
		}
		if($send == 1){
			$this->webim_client->message(null, $to, $body, $type, $style, $timestamp);
		}
		$this->_ok_return();
	}

	public function presence() {
		$show = $this->input->post('show');
		$status = $this->input->post('status');
		$this->webim_client->presence($show, $status);
		$this->_ok_return();
	}

	public function status() {
		$to = $this->input->post("to");
		$show = $this->input->post("show");
		$this->webim_client->status($to, $show);
		$this->_ok_return();
	}

	public function history() {
		$uid = $this->current_uid();
		$with = $this->input->get('id');
		$type = $this->input->get('type');
		$histories = $this->WebIM_Model->histories($uid, $with, $type);
		$this->_json_return($histories);
	}
    
	/**
	 * 下载历史记录
	 */
	public function download_history() {
		$uid = $this->current_uid();
		$id = $this->input->get('id');
		$type = $this->input->get('type');
		$histories = $this->WebIM_Model->histories($uid, $id, $type, 1000 );
		$date = date( 'Y-m-d' );
		if($this->input->get('date')) {
			$date = $this->input->get('date');
		}
		header('Content-Type',	'text/html; charset=utf-8');
		header('Content-Disposition: attachment; filename="histories-'.$date.'.html"');
		echo "<html><head>";
		echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />";
		echo "</head><body>";
		echo "<h1>Histories($date)</h1>".PHP_EOL;
		echo "<table><thead><tr><td>用户</td><td>消息</td><td>时间</td></tr></thead><tbody>";
		foreach($histories as $history) {
			$nick = $history['nick'];
			$body = $history['body'];
			$style = $history['style'];
			$time = date( 'm-d H:i', (float)$history['timestamp']/1000 ); 
			echo "<tr><td>{$nick}</td><td style=\"{$style}\">{$body}</td><td>{$time}</td></tr>";
		}
		echo "</tbody></table>";
		echo "</body></html>";
	}

	/**
	 * 清空历史记录
	 */
	public function clear_history() {
		$id = $this->input->post('id');
		$this->WebIM_Model->clear_histories($this->current_uid(), $id);
		$this->_ok_return();
	}

	/**
	 * Webim读取群组接口
	 */
	public function rooms() {
		$ids = $this->input->get("ids");
		$rooms = $this->WebIM_Plugin->rooms_by_ids($ids);
		$this->_json_return($rooms);	
	}

    /**
     * Invite Room
     */
    public function invite() {
        $uid = $this->current_uid();
        $user = $this->WebIM_Plugin->current_user();
        $roomId = $this->input->post('id');
        $nick = $this->input->post('nick');
        if(strlen($nick) === 0) {
			header("HTTP/1.0 400 Bad Request");
			exit("Nick is Null");
        }
        //find persist room 
        $room = $this->_find_room($this->WebIM_Plugin, $roomId);
        if(!$room) {
            $room = $this->_find_room($this->WebIM_Model, $data);
        }
        //join the room
        $this->WebIM_Model->join_room($roomId, $uid, $user['nick']);
        //invite members
        $members = explode(",", $this->input->post('members'));
        $members = $this->WebIM_Plugin->buddies_by_ids($members);
        $this->WebIM_Model->invite_room($roomId, $members);
        //send invite message to members
        foreach($members as $m) {
            $body = "webim-event:invite|,|{$roomId}|,|{$nick}";
            $this->webim_client->message(null, $m['id'], $body); 
        }
        //tell server that I joined
        $this->webim_client->join($roomId);
        $this->_json_return(array(
            'id' => $room['name'],
            'nick' => $room['nick'],
            'temporary' => true,
            'pic_url' => $this->_webim_image('room.png')
        ));
    }

	public function join() {
        $uid = $this->current_uid();
        $user  = $this->WebIM_Plugin->current_user();
        $roomId = $this->input->post('id');
        $nick = $this->input->post('nick');
        $room = $this->_find_room($this->WebIM_Plugin, $roomId);
        if(!$room) {
            $room = $this->_find_room($this->WebIM_Model, $roomId);
        }
        if(!$room) {
			header("HTTP/1.0 404 Not Found");
			exit("Can't found room: {$roomId}");
        }
        $this->webim_client->join($roomId);
        $this->_json_return(array(
            'id' => $roomId,
            'nick' => $room['nick'],
            'temporary' => false,
            'pic_url' => $this->_webim_image('room.png')
        ));
	}

	public function leave() {
        $uid = $this->current_uid();
		$room = $this->input->post('id');
		$this->webim_client->leave( $room );
		$this->_ok_return();
	}

	public function members() {
        $user = $this->WebIM_Plugin->current_user();
        $id = $this->input->get('id');
        $room = $this->_find_room($this->WebIM_Plugin, $id);
        if($room) {
            $members = $this->WebIM_Plugin->members($id);
        } else {
            $room = $this->_find_room($this->WebIM_Model, $roomId);
            if($room) {
                $members = $this->WebIM_Model->members($roomId);
            }
        }
        if(!$room) {
			header("HTTP/1.0 404 Not Found");
			exit("Can't found room: {$id}");
            return;
        }
        $presences = $this->webim_client->members($id);
        $rtMembers = array();
        foreach($members as $m) {
            $id = $m['id'];
            if(isset($presences->$id)) {
                $m['presence'] = 'online';
                $m['show'] = $presences->$id;
            } else {
                $m['presence'] = 'offline';
                $m['show'] = 'unavailable';
            }
            $rtMembers[] = $m;
        }
        usort($rtMembers, function($m1, $m2) {
            if($m1['presence'] === $m2['presence']) return 0;
            if($m1['presence'] === 'online') return 1;
            return -1;
        });
        $this->_json_return($rtMembers);
	}
	

    /**
     * Block room
     */
    public function block() {
        $uid = $this->current_uid();
        $room = $this->input->post('id');
        $this->WebIM_Model->block($room, $uid);
        $this->_ok_return();
    }

    /**
     * Unblock Room
     */
    public function unblock() {
        $uid = $this->current_uid();
        $room = $this->input->post('id');
        $this->WebIM_Model->unblock($room, $uid);
        $this->_ok_return();
    }

    /**
     * Read Notifications
     */
	public function notifications() {
        $uid = $this->current_uid();
		$this->_json_return(
			$this->WebIM_Plugin->notifications($uid));
	}

    /**
     * Setting
     */
	public function setting() {
		$uid = $this->current_uid();
		$data = $this->input->post('data');
		$this->WebIM_Model->setting($uid, $data);
		$this->_ok_return();
	}

    private function _find_room($obj, $id) {
        $rooms = $obj->rooms_by_ids(array($id));
        if($rooms && isset($rooms[0])) return $rooms[0];
        return null;
    }

    private function _webim_image($src) {
        return $this->config->base_url() . '/static/images/' . $src;
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

}

