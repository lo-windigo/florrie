<?php
/*
	Login and Logout Controller
	Copyright © 2015 Jacob Hume

	This file is part of Florrie.

	Florrie is free software: you can redistribute it and/or modify it
	under the terms of the GNU Affero General Public License as published
	by the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	Florrie is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU Affero General Public License for more details.

	You should have received a copy of the GNU Affero General Public License
	along with Florrie.  If not, see <http://www.gnu.org/licenses/>.
*/

session_start();


require_once __DIR__.'/../lib/controller.php';
require_once __DIR__.'/../lib/forms.php';



class Login extends Controller {

	public function __construct() {

		parent::__construct();

		$this->model = Florrie::loadModel('User');
	}


	//========================================
	// Views
	//========================================


	// Allow a user to log into the system
	public function index() {

		$error = null;

		// Process form data if it has been submitted
		if(Submitted()) {

			$values = array(
				'username' => null, 
				'password' => null
			);

			try {

				ProcessFormInput($values);

				// TODO: If an invalid username is entered, no object is 
				// returned
				$user = $this->model->authenticateUser($values['username'], $values['password']);

				$_SESSION['user'] = $user;

				// Generate CSRF token: https://www.owasp.org/index.php/PHP_CSRF_Guard
				$_SESSION['csrf'] = hash('ripemd320', mt_rand(0,mt_getrandmax()));

				// Installation complete; redirect to the attempted page
				if(empty($_SESSION['page-attempted'])) {
					$page = 'admin';
				}
				else {
					$page = $_SESSION['page-attempted'];
				}

				header('Location: '.$this->config['florrie']['url']);
				return;
			}
			catch (FormException $e) {

				// TODO: Type the right values, damnit!
				die('Form Error Handling? Maybe later. Error: '.$e->getMessage());

			}
			catch (AuthException $e) {

				$error = $e->getMessage();
			}
		}

		$this->render('login', array('error' => $error));
	}


	// Gets rid of a user's session variables
	public function logout() {

		unset($_SESSION['user']);

		$this->render('logout', array());
	}
}
?>
