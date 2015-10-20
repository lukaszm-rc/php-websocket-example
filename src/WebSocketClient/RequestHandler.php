<?php

/**
 * Description of functions
 *
 * @author Lukasz Mazurek <lukasz.mazurek@redcart.pl>
 */

namespace WebSocketClient;

class RequestHandler {

	public $db = array ();

	public function time() {
		return microtime(true);
	}

	public function ping() {
		return "pong";
	}

	public function pong() {
		return "ping";
	}

	public function getList() {
		return $this->db;
	}

	public function add($value) {
		$name = array_push($this->db, $value);
		return ($name - 1);
	}

	public function delete($name) {
		if (isset($this->db[$name])) {
			unset($this->db[$name]);
			return true;
		}
		return false;
	}

	public function get($name) {
		if (isset($this->db[$name])) {
			return $this->db[$name];
		}
		else {
			return false;
		}
	}

	public function set($name, $value) {
		$this->db[$name] = $value;
		return true;
	}

}
