<?php
/*
	Login and Logout Controller
	By Jacob Hume

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


require_once $_SERVER['DOCUMENT_ROOT'].'/florrie/lib/controller.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/florrie/lib/forms.php';


class Auth extends Controller {

	public function __construct($config) {

		parent::__construct($config);

		$this->model = $this->loadModel('User');
	}


	// Allow a user to log into the system
	public function login() {

		$submitted = filter_input(INPUT_POST, 'submitted');

		// Process form data if it has been submitted
		if($submitted !== null) {

			// Defaults go here
			$values = array(
				'username' => null, 
				'password' => null
			);

			try {

				ProcessFormInput($values);

				$user = $this->model->matchesCredentials($values['username'], $values['password']);

				$_SESSION['user'] = $user;

				// Generate CSRF token: https://www.owasp.org/index.php/PHP_CSRF_Guard
				$_SESSION['csrf'] = hash('ripemd320', mt_rand(0,mt_getrandmax()));

				// Installation complete; redirect to the homepage
				header('Location: '.$this->config['florrie']['url']);
				return;
			}
			catch (FormException $e) {

				// TODO: Type the right values, damnit!
				die('Form Error Handling? Maybe later. Error: '.$e->getMessage());

			}
		}

		$this->render('login', array());
	}


	// Gets rid of a user's session variables
	public function logout() {

		unset($_SESSION['user']);
	}


	// Index: Render a single strip
	public function index($id = false) {

		if($id === false) {

			throw new NotFoundException('Strip ID not provided');
		}

		// Get the strip and display it
		$strip = $this->model->getStrip($id);

		$this->render('index', array('strip' => $strip));
	}


	// Show the latest strip
	public function latest() {

		$strip = $this->model->getLatest();

		$this->render('index', array('strip' => $strip));
	}


	// Show a random strip
	public function random() {

		$strip = $this->model->getRandom();

		$this->render('index', array('strip' => $strip));
	}


	// Route a request to a controller function, based on the URI data
	public function route($uriArray = array()) {

		// Get the first value (if any) of the array
		$value = current($uriArray);

		// If a strip ID has been sent in, display that
		if($value !== false && (is_int($value) || ctype_digit($value))) {

			$this->index($value);
		}


		// The parent router can handle everything else
		parent::route($uriArray);
	}
}
?>