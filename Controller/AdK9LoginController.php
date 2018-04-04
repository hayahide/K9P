<?php

App::uses('SimplePasswordHasher', 'Controller/Component/Auth');

class AdK9LoginController extends AppController {

	var $name = 'K9Login';
	var $uses = [ "K9MasterEmployee" ];

	public function beforeFilter() {

		$post=$this->data;
		$model_name="K9MasterEmployeeAccount";

		if(isset($post["username"]) && isset($post["password"])) {

			//App::uses('SimplePasswordHasher', 'Controller/Component/Auth');
			//$passwordHasher = new SimplePasswordHasher(array('hashType' => 'sha256'));
			//$this->request->data[$model_name]["password"]=$passwordHasher->hash($post['password']);
			//v($this->request->data[$model_name]["password"]);
			$this->request->data[$model_name]["username"]  =$post["username"];
			$this->request->data[$model_name]["password"]=$post["password"];
		}

		$this->Auth->allow(['index']);
	}

	/**
	 * Determines if authorized.
	 *
	 * @param      <type>   $user   The user
	 *
	 * @return     boolean  True if authorized, False otherwise.
	 */
	public function isAuthorized($user) {

		// All registered users can logout
		if ($this->action === 'logout') {
			return true;
		}

		return parent::isAuthorized('$user');
	}

	public function index(){

		//if already logged-in, redirect
		if ($this->Session->check('Auth.User')) {

			$url=ROOT_DOMAIN.DS."K9Site".DS."index";
			$this->redirect($url);
		}

		$this->layout='login';
		if(!$this->request->is("post")) return;

		$log_path=LOGS."login";
		$relpath =$log_path.DS."normal".DS."login.txt";
		$relpath=$log_path.DS."login.txt";;
		if (!file_exists($relpath)) $file = new File($relpath, true, 0777);

		$this->Session->destroy();
		if($this->Auth->login()){

			$information=$this->Auth->user()["K9MasterEmployee"];
			$accounts=$this->Auth->user();
			unset($accounts["K9MasterEmployee"]);

			$data=array();
			$data["id"]=$information["id"];
			$data["first_name"] =$information["first_name"];
			$data["last_name"]  =$information["last_name"];
			$data["middle_name"]=$information["middle_name"];
			$data["email"]      =$information["email"];
			$data["login_id"]   =$accounts["username"];
			$data["edit_time"]=date("Y/m/d h:i:sa");
			@file_put_contents($relpath,serialize($data),FILE_APPEND);

			$url=ROOT_DOMAIN.DS."K9Site".DS."index";
			$this->redirect($url);
			return;
		}

		$this->Session->setFlash(__('パスワードが違います。'));
	}

	public function logout() {

		if(!$this->Auth->logout()) return;
		$url=ROOT_DOMAIN.DS;
		$this->redirect($url);
	}

	function beforeRender(){
	}
}
