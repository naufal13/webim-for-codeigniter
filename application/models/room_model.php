<?php 

class Room_model extends CI_model {
    
	protected $fields = array(
			0 => 'id',
			1 => 'owner',
			2 => 'name',
			3 => 'nick',
			4 => 'topic',
			5 => 'created',
			6 => 'updated'
	);

	function __construct() {
		parent::__construct();
	}

    public function create($data) {
        /*
        $name = $data['name'];
        $room = T('rooms')->where('name', $name)->findOne();
        if($room) return $room;
        $room = T('rooms')->create();
        $room->set($data)->set_expr('created', 'NOW()')->set_expr('updated', 'NOW()');
        $room->save();
        */
        return $data;//$room->asArray();
    }

    public function findOne($name) {
        $room = null;//T('rooms')->where('name', $id)->findOne();
        if($room) {
            return array(
                'id' => $room->name,
                'name' => $room->name,
                'nick' => $room->nick,
                "url" => "#",
                "pic_url" => WEBIM_IMAGE("room.png"),
                "status" => "",
                "temporary" => true,
                "blocked" => false
            );
        }
        return null;
    }

    public function roomsByUid($uid) {
        $rooms = null; 
        /*T('members')
            ->tableAlias('t1')
            ->select('t1.room', 'name')
            ->select('t2.nick', 'nick')
            ->join(TName('rooms'), array('t1.room', '=', 't2.name'), 't2')
            ->where('t1.uid', $uid)->findMany();
        */
        $rtRooms = array();
        foreach($rooms as $room) {
            $rtRooms[] = array(
                'id' => $room->name,
                'nick' => $room->nick,
                "url" => "#",
                "pic_url" => "#",
                "status" => "",
                "temporary" => true,
                "blocked" => $this->isBlocked($room->name, $uid)
            );
        }
        return $rtRooms;
    }

    public function invite($room, $members) {
        /*
        foreach($members as $member) {
            $this->joinRoom($room, $member['uid'], $member['nick']);
        }
        */
    }

    public function isBlocked($name, $uid) {
        return false;
    }

}
