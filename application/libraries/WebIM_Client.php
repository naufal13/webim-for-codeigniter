<?php

/**
 * PHP WebIM Library for interacting with NexTalk server.
 *
 * Copy from webim/webim-php
 *
 * @see https://github.com/webim/webim-php
 *
 */
class WebIM_Client {

    /**
    * Version
    */
    const VERSION = 'v5';

    /**
     * Server
     */
    const SERVER = 'http://t.nextalk.im:8000';

    const TIMEOUT = 15;

    const STATUS_OK = 200;
    //api error, not http status
    const ERROR_BAD_RESPONSE = -1; 
    //http error
    const ERROR_BAD_REQUEST = 400;
    const ERROR_UNAUTHORIZED = 401;
    const ERROR_FORBIDDEN = 403;
    const ERROR_NOT_FOUND = 404;
    const ERROR_NOT_ACCEPTABLE = 406;
    const ERROR_INTERNAL_SERVER_ERROR = 500;
    const ERROR_SERVICE_UNAVAILABLE = 503;

    /**
     * WebIM router endpoint
     */
    private $endpoint;

    /**
     * Domain 
     */
    private $domain;

    /**
     * APIKEY
     */
    private $apikey;

    /**
     * Ticket
     */
    private $ticket;

    /**
     * NexTalk Server
     */
    private $server;
    private $version;
    private $timeout;

    function __construct($params = array()) {
        $endpoint = $params['endpoint']; 
        if(is_array($endpoint)) 
            $endpoint = (object)$endpoint;
        $this->endpoint= $endpoint;
        $this->domain = $params['domain'];
        $this->apikey = $params['apikey']; 
        $this->ticket = $params['ticket']; 
        $this->server = $params['server'];
        $this->version = self::VERSION;
        $this->timeout = self::TIMEOUT;
    }

	/**
	 * Online
	 *
	 * @param string $buddy_ids
	 * @param string $room_ids
	 *
	 * @return object
	 * 	-success: true
	 * 	-connection:
     *  -presences: {'uid1': 'available', 'uid2': 'away', ...}
	 *
	 */
	public function online($buddy_ids, $room_ids, $show = null) {
        if(is_array($buddy_ids)) $buddy_ids = implode(',', $buddy_ids);
        if(is_array($room_ids)) $room_ids = implode(',', $room_ids);
        if( !$show ) $show = $this->endpoint->show;
        $endpoint = $this->endpoint;
        $status = isset($endpoint->status) ?  $endpoint->status : '';
		$data = array_merge($this->reqdata(), array(
			'rooms'=> $room_ids, 
			'buddies'=> $buddy_ids, 
			'name'=> $endpoint->id, 
			'nick'=> $endpoint->nick, 
			'status'=> $status, 
			'show' => $show
		));
		$response = $this->request('presences/online', $data, 'POST');
        $this->ticket = $response->ticket;
        $connection = array(
            "ticket" => $response->ticket,
            "domain" => $this->domain,
            "server" => $response->jsonpd,
            "jsonpd" => $response->jsonpd,
        );
        //if websocket 
        if(isset($response->websocket)) $connection['websocket'] = $response->websocket;
        //if mqttd
        if(isset($response->mqttd)) $connection['mqttd'] = $response->mqttd;
        return (object)array(
            "success" => true, 
            "connection" => (object)$connection,
            "presences" => $response->presences 
        );
	}
    
	/**
	 * Offline
	 *
	 * @return {'status': 'ok'}
	 */

	public function offline(){
		return $this->request('presences/offline', $this->reqdata(), 'POST');
	}

    /**
     * Get presences
     *
     * @param $ids
     *
     * @return {'uid1': 'available', 'uid2': 'away', ...}
     */
    public function presences($ids) {
        if(is_array($ids)) $ids =  implode(",", $ids);
		$data = $this->reqdata();
        $data['ids'] = $ids;
        return $this->request('presences', $data);
    }

	/**
	 * Send presence.
	 *
     * @param string 
     *      $show: 'available' | 'away' | 'chat' | 'dnd' | 'invisible' | 'unavailable'
	 * @param string $status
	 *
	 * @return ok
	 *
	 */
	function presence($show, $status = null){
        $data = $this->reqdata();
        $data['nick'] = $this->endpoint->nick;
        $data['show'] = $show;
        if($status) $data['status'] = $status;
		return $this->request('presences/show', $data, 'POST');
	}


	/**
	 * Send endpoint chat status to other.
	 *
	 * @param string $to status receiver
	 * @param string $show status
	 *
	 * @return ok
	 *
	 */
	public function status($to, $show){
		$data = array_merge($this->reqdata(), array(
			'nick' => $this->endpoint->nick,
			'to' => $to,
			'show' => $show,
		));
		return $this->request('statuses', $data, 'POST');
	}

	/**
	 * Send message
	 *
	 * @param string $from message sender, push when not null
	 * @param string $type chat or grpchat or boardcast
	 * @param string $to message receiver
	 * @param string $body message
	 * @param string $style css
	 *
	 * @return 'ok'
	 *
	 */
	public function message($from, $to, $body, $type = 'chat', $style='', $timestamp = null) {
        if(!$timestamp) $timestamp = microtime(true) * 1000;
		$data = array_merge($this->reqdata(), array(
			'nick' => $this->endpoint->nick,
			'to' => $to,
			'type' => $type,
			'body' => $body,
			'style' => $style,
			'timestamp' => $timestamp,
		));
        if($from) $data['from'] = $from;
		return $this->request('messages', $data, 'POST');
	}

    
	/**
	 * Get room members.
	 *
	 * @param string $roomId
	 *
     * @return {'uid1': 'available', 'uid2': 'away', ...}
	 *
	 */
    public function members($roomId) {
		return $this->request("rooms/{$roomId}/members", $this->reqdata());
    }

    /**
     * Join Room
     * 
	 * @param string $roomId
     *
     * @return 'ok'
     */
	public function join($roomId){
		$data = $this->reqdata();
        $data['nick'] = $this->endpoint->nick; 
		return $this->request("rooms/{$roomId}/join", $data, 'POST');
	}

    /**
     * Leave Room
     *
	 * @param string $roomId
     *
     * @return 'ok'
     */
    public function leave($roomId) {
		$data = $this->reqdata();
        $data['nick'] = $this->endpoint->nick; 
		return $this->request("rooms/{$roomId}/leave", $data,'POST');
    }

    /**
     * HTTP Request
     */
    protected function request($path, $data, $method='GET') {
        $url = "{$this->server}/{$this->version}/{$path}";
        if($method == 'GET') {
            $url .= '?'.http_build_query($data);
        }
        //curl request
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_USERPWD, "{$this->domain}:{$this->apikey}");
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        if($method == 'POST') {
            //$data = array_map(array($this, "sanitize_curl_parameter"), $data);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); //TODO: OK?
        }

        $response = curl_exec($ch);

        if (strlen($response) == 0) {
            $errno = curl_errno($ch);
            $error = curl_error($ch);
            throw new WebIMException(self::ERROR_BAD_RESPONSE, $url, "CURL error: $errno - $error");
        }

        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($code != self::STATUS_OK) {
            throw new WebIMException($code, $url, "HTTP status code: $code, response=$response");
        }

        curl_close($ch);

        $json = json_decode($response);

        if (!$response) {
            throw new WebIMException(self::ERROR_BAD_RESPONSE, $url, "Invalid JSON received: $response");
        }

        return $json;
    }

    /**
     * Request Data
     */
    protected function reqdata() {
        $data = array(
			'domain' => $this->domain, 
            //basic authentication
			//'apikey' => $this->apikey, 
			'version' => $this->version,
        );
        if($this->ticket) $data['ticket'] = $this->ticket;
        return $data;
    }

}

class WebIMException extends \Exception {

    public $code;

    function __construct($code, $url, $info) {
        $message = "Server error: code=$code, url=$url, info=$info";
        parent::__construct($message, (int)$code);
    }

}



