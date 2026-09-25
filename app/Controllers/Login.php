<?php

namespace App\Controllers;

class Login extends BaseController {


	public function index() 
    {
		return view('login.php');
	}
	
	public function signin()
	{
		helper(['form', 'url']);

		$request = $this->request;
		$username = $request->getVar('username');
		$plainPassword = $request->getVar('password');

		$db = \Config\Database::connect();
		$query = $db->query("SELECT user_id, password FROM users WHERE user = " . $db->escape($username));
		$result = $query->getRow();

		$valid = false;
		if ($result !== NULL) {
			if (password_get_info($result->password)['algo'] !== null) {
				$valid = password_verify($plainPassword, $result->password);
			} else {
				// legacy MD5 — verify and upgrade to bcrypt
				$valid = hash_equals($result->password, md5($plainPassword));
				if ($valid) {
					$newHash = password_hash($plainPassword, PASSWORD_BCRYPT);
					$db->query("UPDATE users SET password = " . $db->escape($newHash) . " WHERE user_id = " . (int)$result->user_id);
				}
			}
		}

		if (!$valid)
		{
			log_message('info', 'Login failed ip:' . $request->getIPAddress() . ' user:' . $username);
			session()->setFlashdata('error', 'Utilizatorul sau parola sunt gresite.');
			return view('login.php');
		}

		$session = \Config\Services::session();
		$session->set('user', $result->user_id);

		log_message('info', 'Login succesfull ip:' . $request->getIPAddress() . ' user:' . $username);
		return redirect()->to( base_url('Home'));
	}

}
?>
