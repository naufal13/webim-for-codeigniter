<?php

class Member_model extends CI_Model {

	protected $fields = array(
			0 => 'id',
			1 => 'room',
			2 => 'nick',
			4 => 'uid',
			5 => 'updated'
	);

    public function inRoom($room) {
        return array();
        /*T('members')
            ->select('uid', 'id')
            ->select('nick')
            ->where('room', $room)->findArray();
       */
    } 

    public function joinRoom($room, $uid, $nick) {
        /*
        $member = T('members')
            ->where('room', $room)
            ->where('uid', $uid)
            ->findOne();
        if($member == null) {
            $member = T('members')->create();
            $member->set(array(
                'uid' => $uid,
                'nick' => $nick,
                'room' => $room
            ))->set_expr('joined', 'NOW()');
            $member->save();
        }
         */
    }

    public function leaveRoom($room, $uid) {
        /**
        T('members')->where('room', $room)->where('uid', $uid)->deleteMany();
        //if no members, room deleted...
        $data = T("members")->selectExpr('count(id)', 'total')->where('room', $room)->findOne();
        if($data && $data->total === 0) {
            T('rooms')->where('name', $room)->deleteMany();
        }
        **/
    }

}

