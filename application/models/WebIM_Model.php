<?php

/**
 * WebIM Model
 */
class WebIM_Model extends CI_Model {

	function __construct() {
		parent::__construct();
	}

    /**
     * table prefix
     */
    private function _table($name) {
        return $this->db->dbprefix($name);
    }

    /**
     * Read histories
     */
    public function histories($uid, $with, $type = 'chat',  $limit = 30) {
        if( $type === 'chat' ) {
            $sql = "SELECT * FROM {$this->_table('histories')} where `type` = 'chat' AND (`to`= ? AND `from`= ? AND `fromdel` != 1) OR (`send` = 1 AND `from`= ? AND `to`= ? AND `todel` != 1)  ORDER BY timestamp DESC LIMIT $limit";
            $vars = array($with, $uid, $with, $uid);
        } else {
            $sql = "SELECT * FROM {$this->_table('histories')} where `type` = 'grpchat' and `to` = ? and send = 1 ORDER BY timestamp DESC limit $limit";
            $vars = array($with);
        }
        $query = $this->db->query($sql, $vars);
        return array_reverse($query->result_array());
    }

    
    /**
     * Read offline histories
     */
	public function offline_histories($uid, $limit = 100) {
        $sql = "SELECT * from {$this->_table('histories')} where `to` = '$uid' and send = 0 ORDER BY timestamp DESC limit $limit";
        $query = $this->db->query($sql);
        return array_reverse( $query->result_array() );
	}

    /**
     * Save history
     */
    public function insert_history($message) {
        $sql = "INSERT into {$this->_table('histories')}(`send`, `type`, `to`, `from`, `nick`, `body`, `style`, `timestamp`) values(?, ?, ?, ?, ?, ?, ?, ?)";
        $vars = array_values($message);
        $this->db->query($sql, $vars);
    }

    /**
     * Clear histories
     */
    public function clear_histories($uid, $with) {
        $this->db->query("UPDATE {$this->_table('histories')} SET fromdel = 1 where `from` = ? and `to` = ?", array($uid, $with));
        $this->db->query("UPDATE {$this->_table('histories')} SET todel = 1 where `to` = ? and `from` = ?", array($uid, $with));
        $this->db->query("DELETE from {$this->_table('histories')} where todel = 1 and fromdel = 1");
    }

    /**
     * Offline histories readed
     */
	public function offline_readed($uid) {
        $this->db->query("UPDATE {$this->_table('histories')} SET send = 1 where `to` = ? and send = 0", array($uid));
	}

    /**
     * User setting
     */
    public function setting($uid, $data = null) {
        $query = $this->db->query("SELECT data from {$this->_table('settings')} WHERE uid = ?", array($uid));
        if($query->num_rows() > 0) { $setting = $query->row(); }
        if (func_num_args() === 1) { //get setting
            if($setting) return json_decode($setting->data); 
           return null;
        } 
        //save setting
        if($setting) {
            if(!is_string($data)) { $data = json_decode($data); }
            $this->db->query("UPDATE {$this->_table('settings')} set data = ? where uid = ?", array($data, $uid));
        } else {
            $this->db->query("INSERT INTO {$this->_table('settings')}(uid, data, created) VALUES(?, ?, ?)", array($uid, $data, date( 'Y-m-d H:i:s' )));
        }
    }

    /**
     * User rooms
     */
    public function rooms($uid) {
        $sql = "SELECT t1.room as name, t2.nick as nick from {$this->_table('members')} t1 left join {$this->_table('rooms')} t2 on t1.room = t2.name where t1.uid = ?";
        $query = $this->db->query($sql, array($uid));
        $rooms = array();
        foreach($query->result() as $row) {
            $rooms[] = array(
                'id' => $row->name,
                'nick' => $row->nick,
                "url" => "#", //TODO
                "pic_url" => $this->_image('room.png'),//TODO
                "status" => '',
                "temporary" => true,
                "blocked" => $this->is_room_blocked($row->name, $uid)
            );
        }
        return $rooms;
    }

    /**
     * Rooms by ids
     */
    public function rooms_by_ids($ids) {
        if($ids === '' || empty($ids)) return array();
        $ids = implode(',', array_map(function($id) {return "'$id'";}, $ids));
        $sql = "SELECT * from {$this->_table('rooms')} where name in ({$ids})";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    /**
     * Room members
     */
    public function members($room) {
        $query = $this->db->query("SELECT uid as id, nick FROM {$this->_table('members')} WHERE room = ?", array($room));
        return $query->result_array();
    }

    /**
     * Create room
     */
    public function create_room($data) {
        $name = $data['name'];
        $query = $this->db->query("SELECT * from {$this->_table('rooms')} WHERE name = ?", array($name));
        if( $query->num_rows() > 0 ) {
            return (array)$query->row();
        }
        $this->db->query("INSERT INTO {$this->_table('rooms')}(owner, name, nick, created) VALUES(?, ?, ?, ?)", 
            array($data['owner'], $data['name'], $data['nick'], date( 'Y-m-d H:i:s' )));
        //TODO return $data;
    }

    /**
      * Invite members into room
     */
    public function invite_room($room, $members) {
        foreach($members as $member) {
            $this->join_room($room, $member['uid'], $member['nick']);
        }
    }

    /**
     * Join Room
     */
    public function join_room($room, $uid, $nick) {
        $query = $this->db->query("SELECT * FROM {$this->_table('members')} WHERE uid = ? and room = ?", array($uid, $room));
        if($query->num_rows() == 0) {
            $this->db->query("INSERT INTO {$this->_table('members')}(uid, room, nick, joined) VALUES(?, ?, ?, ?)",
                array($uid, $room, $nick, date('Y-m-d H:i:s')));
        }
    }

    /**
     * Leave room
     */
    public function leave_room($room, $uid) {
        $this->db->query("DELETE FROM {$this->_table('members')} WHERE room = ? and uid = ?", array($room, $uid));
        $query = $this->db->query("SELECT count(id) as total from {$this->_table('members')} where room = ?", array($room));
        //if no members, room deleted...
        if($query->num_rows() > 0 && $query->row()->total === 0) {
           $this->db->query("DELETE FROM {$this->_table('rooms')} WHERE name = ?", array($room)); 
        }
    }

    /**
     * Block room
     */
    public function block_room($room, $uid) {
        $query = $this->db->query("SELECT id FROM {$this->_table('blocked')} WHERE room = ? and uid = ?", array($room, $uid));
        if($query->num_rows() == 0) {
            $this->db->query("INSERT INTO {$this->_table('blocked')}(room, uid, blocked) VALUES(?, ?, ?)", array($room, $uid, date('Y-m-d H:i:s')));
        }
    }

    /**
     * Is room blocked
     */
    public function is_room_blocked($room, $uid) {
        $query = $this->db->query("SELECT id FROM {$this->_table('blocked')} WHERE room = ? and uid = ?", array($room, $uid));
        return ($query->num_rows() > 0); 
    }


    /**
     * Unblock room
     */
    public function unblock_room($room, $uid) {
        $this->db->query("DELETE FROM {$this->_table('blocked')} WHERE room = ? and uid = ?", array($room, $uid));
    }

    private function _image($src) {
		$CI = &get_instance();
        return $CI->config->base_url().'/static/images/'.$src;
    }
}
