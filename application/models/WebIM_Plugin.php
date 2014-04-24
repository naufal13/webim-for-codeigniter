<?php

/**
 * WebIM-for-CodeIgniter
 *
 * @author      Ery Lee <ery.lee@gmail.com>
 * @copyright   2014 NexTalk.IM
 * @link        http://github.com/webim/webim-for-codeigniter
 * @license     MIT LICENSE
 * @version     5.4.1
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
 * WebIM Plugin
 */
class WebIM_plugin extends CI_Model {

	public function __construct() {
		parent::__construct();
	}

    /**
     * API: current user
     *
     * @return object current user
     */
    function user() {
        global $_SESSION;
		$uid = isset($_SESSION['uid']) ? $_SESSION['uid'] : null;
        if( !$uid ) return null;
		return (object)array(
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
	 * API: Buddies of current user.
     *
     * @param string $uid current uid
	 *
     * @return array Buddy list or current user
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
	function buddies($uid) {
        //TODO: DEMO Code
        return array_map(function($id){
            return (object)array(
                'id' => 'uid' . $id,
                'group' => 'friend',
                'nick' => 'user'.$id,
                'presence' => 'offline',
                'show' => 'unavailable',
                'status' => '#',
                'pic_url' => WEBIM_IMAGE('male.png')
            );
        
        }, range(1, 10));
	}

	/*
	 * API: buddies by ids
	 *
     * @param string $uid 
     * @param array $ids buddy id array
     *
     * @return array Buddy list
     *
	 * Buddy
	 */
	function buddies_by_ids($uid, $ids) {
        return array_map(function($id) {
            return (object)array(
                'id' => $id,
                'group' => 'friend',
                'nick' => preg_replace('/uid/', 'user', $id),
                'presence' => 'offline',
                'show' => 'unavailable',
                'status' => '#',
                'pic_url' => WEBIM_IMAGE('male.png')
            );
        }, $ids);
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
	function rooms($uid) {
        //TODO: DEMO CODE
		$room = (object)array(
			'id' => 'room1',
            'name' => 'room1',
			'nick' => 'Room',
			'url' => "#",
			'pic_url' => WEBIM_IMAGE('room.png'),
			'status' => "Room",
			'blocked' => false,
            'temporary' => false
		);
		return array( $room );	
	}

	/*
	 * API: rooms by ids
     *
     * @param string $uid 
     * @param array $ids 
     *
     * @return array rooms
	 *
	 * Room
     *
	 */
	function rooms_by_ids($uid, $ids) {
        $rooms = array();
        foreach($ids as $id) {
            if($id === 'room1') { 
                $rooms[] = (object)array(
                    'id' => $id,
                    'name' => $id,
                    'nick' => 'room'.$id,
                    'url' => "#",
                    'pic_url' => WEBIM_IMAGE('room.png')
                );
            }
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
        //TODO: DEMO CODE
        return array_map(function($id) {
            return (object)array(
                'id' => 'uid' . $id,
                'nick' => 'user'.$id
            ); 
        }, range(1, 10));
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
     * API: menu
     *
     * @return array menu list
     *
     * Menu:
     *
     * icon
     * text
     * link
     */
    function menu($uid) {
        return array();
    }

    private function _image($src) {
		$CI = &get_instance();
        return $CI->config->base_url().'/static/images/'.$src;
    }

}

