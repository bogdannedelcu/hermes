<?php

function checkAuth()
{
	if (\Config\Services::session()->get('user') === NULL) {
		log_message('info','User not logged. IP:'.\Config\Services::request()->getIPAddress());
		//redirect()->to( base_url('Login'));
		header('Location:' . '.');
		exit;
		}
		else
		{
			log_message('info','User Id:'.\Config\Services::session()->get('user').' URI:'.(string)\Config\Services::request()->uri);
			\Config\Services::session()->set(['sessionExpiration' => 108000]);
		}
}			
			
?>