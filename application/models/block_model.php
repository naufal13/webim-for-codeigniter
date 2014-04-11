<?php

class Block_model extends CI_Model {

	protected $fields = array(
			0 => 'id',
			1 => 'uid',
			2 => 'room',
			4 => 'blocked'
	);

	function __construct() {
		parent::__construct();
	}

    public function block($room) {
        /*
        $block = T('blocked')->select('id')
            ->where('room', $room)
            ->where('uid', $uid)->findOne();
        if($block == null) {
            T('blocked')->create()
                ->set('room', $room)
                ->set('uid', $uid)
                ->setExpr('blocked', 'NOW()')
                ->save();
        }
         */
    }

    public function unblock($room) {
        /*
        T('blocked')->where('uid', $uid)->where('room', $room)->deleteMany();
         */
    }

}
