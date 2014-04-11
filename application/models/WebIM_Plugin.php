<?php

/**
 * WebIM Plugin
 */
class WebIM_plugin extends CI_Model {

	/*
	 * Current user or visitor 
	 */

    protected $user = null;
    
	function __construct() {
		parent::__construct();
		$CI = &get_instance();
        $uid = $this->uid();
		if($uid) {
            $this->user = $this->user($uid);
        } else if($CI->config->item('visitor', 'webim')) {
            //visitor 
			$this->user = $this->visitor();
        } else {
            //no user or visitor
        }
	}

	/*
	 * API: uid of logined user
	 */
	protected function uid() {
        global $_SESSION;
		return isset($_SESSION['uid']) ? $_SESSION['uid'] : 'uid1';
	}

	/*
	 * API: load user
	 */
	protected function user($uid) {
		//NOTICE: demo user
		return array(
            'uid' => $uid,
            'id' => $uid,
            'nick' => preg_replace('/uid/', 'user', $uid),
            'presence' => 'online',
            'show' => "available",
            'pic_url' => $this->_image('male.png'),
            'url' => "#",
            'role' => 'user',
            'status' => "",
        );
	}

	/*
	 * API: load visitor
	 */
	protected function visitor() {
		if ( isset($_COOKIE['_webim_visitor_id']) ) {
			$id = $_COOKIE['_webim_visitor_id'];
		} else {
			$id = substr(uniqid(), 6);
			setcookie('_webim_visitor_id', $id, time() + 3600 * 24 * 30, "/", "");
		}
        $vid = $this->_vid($id);
        return array(
            'uid' => $vid,
            'id' => $vid,
            'nick' => "v".$id,
            'presence' => 'online',
            'show' => "available",
            'pic_url' => $this->_image('male.png'),
            'role' => 'visitor',
            'url' => "#",
            'status' => "",
        );
	}

    /**
     * Is visitor id?
     */
    protected function is_vid($uid) {
        return strpos($uid, 'vid:') === 0;
    }

    /**
     * Current user of the site
     *
     * @return user array 
     */
    public function current_user() {
        return $this->user;
    }

    /**
     * logined?
     */
    public function logined() {
        return ($this->user != null);
    }

	/*
	 * API: Buddies of current user.
     *
     * @param string $uid current uid
	 *
     * @return array buddies
     *
	 * Buddy:
	 *
	 * 	id:         uid
	 * 	uid:        uid
	 *	nick:       nick
	 *	pic_url:    url of photo
     *	presence:   online | offline
	 *	show:       available | unavailable | away | busy | hidden
	 *  url:        url of home page of buddy 
	 *  status:     buddy status information
	 *  group:      group of buddy
	 *
	 */
	public function buddies($uid) {
        $buddies = array();
        $ids = range(1, 10);
        foreach ($ids  as $id) {
            $buddies[] = array(
                'id' => 'uid' . $id,
                'uid' => 'uid' . $id,
                'group' => 'friend',
                'nick' => 'user'.$id,
                'presence' => 'offline',
                'show' => 'unavailable',
                'status' => '#',
                'pic_url' => $this->_image('male.png'),
            ); 
        }
        return $buddies;
	}

	/*
	 * API: buddies by ids
	 *
     * @param array $ids buddy id array
     *
     * @return array buddies
     *
	 * Buddy
	 */
	public function buddies_by_ids($ids) {
        if( empty($ids) ) return array(); foreach($ids as $id) {
            $buddies[] = array(
                'id' => $id,
                'uid' => $id,
                'group' => 'friend',
                'nick' => preg_replace('/uid/', 'user', $id),
                'presence' => 'offline',
                'show' => 'unavailable',
                'status' => '#',
                'pic_url' => $this->_image('male.png'),
            ); 
        }
		return $buddies;	
	}

	/*
	 * API：rooms of current user
     * 
     * @param string $uid 
     *
     * @return array rooms
     *
	 * Room:
	 *
	 *	id:		    Room ID,
	 *	nick:	    Room Nick
	 *	url:	    Home page of room
	 *	pic_url:    Pic of Room
	 *	status:     Room status 
	 *	count:      count of online members
	 *	all_count:  count of all members
	 *	blocked:    true | false
	 */
	public function rooms($uid) {
		$demoRoom = array(
			'id' => '1',
            'name' => 'room1',
			'nick' => 'Persist Room',
			'url' => "#",
			'pic_url' => $this->_image('room.png'),
			'status' => "Persist Room",
			'blocked' => false,
            'temporary' => false
		);
		return array( $demoRoom );	
	}

	/*
	 * API: rooms by ids
     *
     * @param array id array
     *
     * @return array rooms
	 *
	 * Room
     *
	 */
	function rooms_by_ids($ids) {
        $rooms = array();
        foreach($ids as $id) {
            $rooms[] = array(
                'id' => $id,
                'name' => $id,
                'nick' => 'room'.$id,
                'url' => "#",
                'pic_url' => $this->_image('room.png')
            );
        }
		return $rooms;
	}

    /**
     * API: members of room
     *
     * $param $room string roomid
     * 
     */
    function members($room) {
        $members = array();
        foreach (range(1, 10)  as $id) {
            $members[] = array(
                'id' => 'uid' . $id,
                'uid' => 'uid' . $id,
                'nick' => 'user'.$id
            ); 
        }
        return $members;
    }

	/*
	 * API: notifications of current user
	 *
     * @return notifications array 
     *
	 * Notification:
	 *
	 * 	text: text
	 * 	link: link
	 */	
	public function notifications($uid) {
        $demo = array('text' => 'Notification', 'link' => '#');
		return array($demo);
	}

    /**
     * Generate visitor id
     */
    private function _vid($id) { return 'vid:'.$id; }

    private function _image($src) {
		$CI = &get_instance();
        return $CI->config->base_url().'/static/images/'.$src;
    }

}


