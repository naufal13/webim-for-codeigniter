<?php

/**
 * WebIM-for-CodeIgniter
 *
 * @author      Ery Lee <ery.lee@gmail.com>
 * @copyright   2014 NexTalk.IM
 * @link        http://github.com/webim/webim-for-codeigniter
 * @license     MIT LICENSE
 * @version     5.4.1
 * @package     WebIM
 *
 * MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

/**
 * WebIM Controller
 *
 * @package WebIM
 * @autho Ery Lee
 * @since 5.4.1
 */

class Webim extends CI_Controller {

    /**
     * current user
     */
    var $user = null;

	public function __construct() {

		parent::__construct();

		//load config
		$this->config->load('webim');
		$IMC = $this->config->item('webim');

		//is opened
		if(! $IMC['isopen'] ) exit();

		//load model
		$this->load->model('WebIM_Model', '', TRUE);

        //load plugin
		$this->load->model('WebIM_Plugin');

        //load user
        $user = $this->WebIM_Plugin->user();
        if($user == null &&  $IMC['visitor']) {
            $user = $this->WebIM_Model->visitor();
        }
        if(!$user) exit("Login Required");
        $this->user = $user;

		//ticket
		$ticket = $this->input->get_post('ticket');
		if($ticket) $ticket = stripslashes($ticket);

		//client
		$this->load->library('WebIM_Client', array(
			'endpoint'	=> $user,
			'domain'	=> $IMC['domain'],
			'apikey'	=> $IMC['apikey'],
			'server'	=> $IMC['server'],
			'ticket'	=> $ticket ? $ticket : ''
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
	public function boot() {
		$IMC = $this->config->item('webim');

        //FIX offline Bug
        $this->user->show = "unavailable";
        $uid = $this->user->id;

        //Setting
        $setting = $this->WebIM_Model->setting($uid);

		$fields = array(
			'version',
			'theme', 
			'local', 
			'emot',
			'opacity',
            'discussion',
			'enable_room', 
			'enable_chatlink', 
			'enable_shortcut',
			'enable_noti',
			'enable_menu',
			'show_unavailable',
			'upload');

		$scriptVar = array(
            'version' => $IMC['version'],
			'product' => 'ci',
			'path' => $this->config->base_url(),
			'is_login' => '1',
            'is_visitor' => false,
			'login_options' => '',
			'user' => $this->user,
			//load setting
            'jsonp' => false,
			'setting' => $setting, 
			'min' => $IMC['debug'] ? '' : '.min'
		);

		foreach($fields as $f) { $scriptVar[$f] = $IMC[$f];	}

		header("Content-type: application/javascript");
		header("Cache-Control: no-cache");

		echo "var _IMC = " . json_encode($scriptVar) . ";" . PHP_EOL;

		$script = <<<EOF
_IMC.script = window.webim ? '' : ('<link href="' + _IMC.path + 'static/webim' + _IMC.min + '.css?' + _IMC.version + '" media="all" type="text/css" rel="stylesheet"/><link href="' + _IMC.path + 'static/themes/' + _IMC.theme + '/jquery.ui.theme.css?' + _IMC.version + '" media="all" type="text/css" rel="stylesheet"/><script src="' + _IMC.path + 'static/webim' + _IMC.min + '.js?' + _IMC.version + '" type="text/javascript"></script><script src="' + _IMC.path + 'static/i18n/webim-' + _IMC.local + '.js?' + _IMC.version + '" type="text/javascript"></script>');
_IMC.script += '<script src="' + _IMC.path + 'static/webim.' + _IMC.product + '.js?vsn=' + _IMC.version + '" type="text/javascript"></script>';
document.write( _IMC.script );

EOF;
		exit($script);
	}

	/**
	 * Webim上线接口
	 */
	public function online() {
		$IMC  = $this->config->item('webim');
		$uid = $this->user->id;
        $show = $this->input->post('show');

        //buddy, room, chatlink ids
		$chatlinkIds= $this->_ids_array( $this->input->post('chatlink_ids') );
		$activeRoomIds = $this->_ids_array( $this->input->post('room_ids') );
		$activeBuddyIds = $this->_ids_array( $this->input->post('buddy_ids') );
		//active buddy who send a offline message.
		$offlineMessages = $this->WebIM_Model->offline_histories($uid);
		foreach($offlineMessages as $msg) {
			if(!in_array($msg->from, $active_buddy_ids)) {
				$active_buddy_ids[] = $msg->from;
			}
		}
        //buddies of uid
		$buddies = $this->WebIM_Plugin->buddies($uid);
        $buddyIds = array_map(array($this, '_buddy_id'), $buddies);
        $buddyIdsWithoutInfo = array();
        foreach(array_merge($chatlinkIds, $activeBuddyIds) as $id) {
            if( !in_array($id, $buddyIds) ) {
                $buddyIdsWithoutInfo[] = $id;
            }
        }
        //buddies by ids
		$buddiesByIds = $this->WebIM_Plugin->buddies_by_ids($uid, $buddyIdsWithoutInfo);
        //all buddies
        $buddies = array_merge($buddies, $buddiesByIds);
        $allBuddyIds = array();
        foreach($buddies as $buddy) { $allBuddyIds[] = $buddy->id; }

        $rooms = array(); $roomIds = array();
		if( $IMC['enable_room'] ) {
            //persistent rooms
			$persistRooms = $this->WebIM_Plugin->rooms($uid);
            //temporary rooms
			$temporaryRooms = $this->WebIM_Model->rooms($uid);
            $rooms = array_merge($persistRooms, $temporaryRooms);
            $roomIds = array_map(array($this, '_room_id'), $rooms);
		}

		//===============Online===============
		$data = $this->webim_client->online($allBuddyIds, $roomIds, $show);
		if( $data->success ) {
            $rtBuddies = array();
            $presences = $data->presences;
            foreach($buddies as $buddy) {
                $id = $buddy->id;
                if( isset($presences->$id) ) {
                    $buddy->presence = 'online';
                    $buddy->show = $presences->$id;
                } else {
                    $buddy->presence = 'offline';
                    $buddy->show = 'unavailable';
                }
                $rtBuddies[$id] = $buddy;
            }
			//histories for active buddies and rooms
			foreach($activeBuddyIds as $id) {
                if( isset($rtBuddies[$id]) ) {
                    $rtBuddies[$id]->history = $this->WebIM_Model->histories($uid, $id, "chat" );
                }
			}
            if( !$IMC['show_unavailable'] ) {
                $olBuddies = array();
                foreach($rtBuddies as $buddy) {
                    if($buddy->presence === 'online') $olBuddies[] = $buddy;
                }
                $rtBuddies = $olBuddies;
            }
            $rtRooms = array();
            if( $IMC['enable_room'] ) {
                foreach($rooms as $room) {
                    $rtRooms[$room->id] = $room;
                }
                foreach($activeRoomIds as $id){
                    if( isset($rtRooms[$id]) ) {
                        $rtRooms[$id]->history = $this->WebIM_Model->histories($uid, $id, "grpchat" );
                    }
                }
            }

			$this->WebIM_Model->offline_readed($uid);

            if($show) $this->user->show = $show;

            $this->_json_return(array(
                'success' => true,
                'connection' => $data->connection,
                'presences' => $data->presences,
                'user' => $this->user,
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
        $uid = $this->user->id;
		$ids = $this->input->get('ids');
		$buddies = $this->WebIM_Plugin->buddies_by_ids($uid, $ids);
		$this->_json_return($buddies);
	}

	public function message() {
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
                'from' => $this->user->id,
                'nick' => $this->user->nick,
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
		$uid = $this->user->id;
		$with = $this->input->get('id');
		$type = $this->input->get('type');
		$histories = $this->WebIM_Model->histories($uid, $with, $type);
		$this->_json_return($histories);
	}

	/**
	 * 清空历史记录
	 */
	public function clear_history() {
        $uid = $this->user->id;
		$id = $this->input->post('id');
		$this->WebIM_Model->clear_histories($uid, $id);
		$this->_ok_return();
	}
    
	/**
	 * 下载历史记录
	 */
	public function download_history() {
		$uid = $this->user->id;
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
			$nick = $history->nick;
			$body = $history->body;
			$style = $history->style;
			$time = date( 'm-d H:i', (float)$history->timestamp/1000 ); 
			echo "<tr><td>{$nick}:</td><td style=\"{$style}\">{$body}</td><td>{$time}</td></tr>";
		}
		echo "</tbody></table>";
		echo "</body></html>";
	}


	/**
	 * Webim读取群组接口
	 */
	public function rooms() {
        $uid = $this->user->id;
		$ids = $this->input->get("ids");
		$persist_rooms = $this->WebIM_Plugin->rooms_by_ids($uid, $ids);
        $temporary_rooms = $this->WebIM_Model->rooms_by_ids($uid, $ids);
        $rooms = array_merge($persist_rooms, $temporary_rooms);
		$this->_json_return($rooms);
	}

    /**
     * Invite Room
     */
    public function invite() {
        $uid = $this->user->id;
        $roomId = $this->input->post('id');
        $nick = $this->input->post('nick');
        if(strlen($nick) === 0) {
			header("HTTP/1.0 400 Bad Request");
			exit("Nick is Null");
        }
        //find persist room 
        $room = $this->_find_room($this->WebIM_Model, $roomId);
        if(!$room) {
            //create temporary room
            $room = $this->WebIM_Model->create_room(array(
                'owner' => $uid,
                'name' => $roomId, 
                'nick' => $nick
            ));
        }
        //join the room
        $this->WebIM_Model->join_room($roomId, $uid, $this->user->nick);
        //invite members
        $members = explode(",", $this->input->post('members'));
        $members = $this->WebIM_Plugin->buddies_by_ids($uid, $members);
        $this->WebIM_Model->invite_room($roomId, $members);
        //send invite message to members
        foreach($members as $m) {
            $body = "webim-event:invite|,|{$roomId}|,|{$nick}";
            $this->webim_client->message(null, $m->id, $body); 
        }
        //tell server that I joined
        $this->webim_client->join($roomId);
        $this->_json_return(array(
            'id' => $room->name,
            'nick' => $room->nick,
            'temporary' => true,
            'avatar' => $this->_webim_image('room.png')
        ));
    }

	public function join() {
        $uid = $this->user->uid;
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
        $this->WebIM_Model->join_room($roomId, $uid, $this->user->nick);
        $this->webim_client->join($roomId);
        $this->_json_return(array(
            'id' => $roomId,
            'nick' => $nick,
            'temporary' => true,
            'avatar' => $this->_webim_image('room.png')
        ));
	}

    /**
     * Leave room
     */
	public function leave() {
        $uid = $this->user->id;
		$room = $this->input->post('id');
		$this->webim_client->leave( $room, $uid);
		$this->_ok_return();
	}

	public function members() {
        $members = array();
        $id = $this->input->get('id');
        $room = $this->_find_room($this->WebIM_Plugin, $id);
        if($room) {
            $members = $this->WebIM_Plugin->members($id);
        } else {
            $room = $this->_find_room($this->WebIM_Model, $id);
            if($room) {
                $members = $this->WebIM_Model->members($id);
            }
        }
        if(!$room) {
			header("HTTP/1.0 404 Not Found");
			exit("Can't found room: {$id}");
        }
        $presences = $this->webim_client->members($id);
        $rtMembers = array();
        foreach($members as $m) {
            $id = $m->id;
            if(isset($presences->$id)) {
                $m->presence = 'online';
                $m->show = $presences->$id;
            } else {
                $m->presence = 'offline';
                $m->show = 'unavailable';
            }
            $rtMembers[] = $m;
        }
        /*
        usort($rtMembers, function($m1, $m2) {
            if($m1->presence === $m2->presence) return 0;
            if($m1->presence === 'online') return 1;
            return -1;
        });
        */
        $this->_json_return($rtMembers);
	}

    /**
     * Block room
     */
    public function block() {
        $uid = $this->user->id;
        $room = $this->input->post('id');
        $this->WebIM_Model->block($room, $uid);
        $this->_ok_return();
    }

    /**
     * Unblock Room
     */
    public function unblock() {
        $uid = $this->user->id;
        $room = $this->input->post('id');
        $this->WebIM_Model->unblock($room, $uid);
        $this->_ok_return();
    }

    /**
     * Read Notifications
     */
	public function notifications() {
        $uid = $this->user->id;
		$this->_json_return(
			$this->WebIM_Plugin->notifications($uid));
	}

    /**
     * Setting
     */
	public function setting() {
		$data = $this->input->post('data');
		$this->WebIM_Model->setting($this->user->id, $data);
		$this->_ok_return();
	}

    private function _find_room($obj, $id) {
        $rooms = $obj->rooms_by_ids($this->user->id, array($id));
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

    private function _room_id($room) {
        return $room->id;
    }

    private function _buddy_id($buddy) {
        return $buddy->id;
    }

}

