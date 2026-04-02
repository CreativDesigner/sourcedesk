<?php

// Class for session purposes

class Session {

	// Method to get a value for a key from session
	// If the key is not set within session it returns false
	public function get($k) {
		global $_SESSION;
		return isset($_SESSION[$k]) ? $_SESSION[$k] : false;
	}

	// Method to assign a value to a key of session

	public function remove($k) {
		global $_SESSION;
		if (isset($_SESSION[$k])) {
			unset($_SESSION[$k]);
		}

		return $this;
	}

	// Method to unset a key of session

	public function destroy($all = 0) {
		switch ($all) {
		case 0:
			$this->set('mail', '');
			$this->set('pwd', '');
			$this->set('tfa', '');
			$this->set('admin_login', '');
			break;

		case 1:
			session_destroy();
			break;
		}

		return $this;
	}

	// Method to destroy session
	// Parameter can be passed to destroy only user session or complete session

	public function set($k, $v) {
		global $_SESSION;
		$_SESSION[$k] = $v;
		return $this;
	}

}

?>