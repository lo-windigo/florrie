<?php
/*
	Admin Controller
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

session_start();


require_once $_SERVER['DOCUMENT_ROOT'].'/florrie/lib/controller.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/florrie/lib/forms.php';



class Admin extends Controller {

	public function __construct($config) {

		//----------------------------------------
		// Check for user credentials
		//----------------------------------------
		
		if(empty($_SESSION['user'])) {

			// Users must be logged in!
			header('Location: /login', true, 307);
			exit;
		}
		//----------------------------------------


		parent::__construct($config);
	}


	public function index() {

		$this->render('admin-index');
	}


	//----------------------------------------
	// Add a strip to the comic system
	//----------------------------------------
	public function addstrip() {

		// Defaults go here
		$values = array(
			'display' => null, 
			'posted' => new DateTime(), 
			'title' => null
		);

		// Process form data if it has been submitted
		if(submitted()) {

			try {

				processFormInput($values);

				// Handle strip file upload
				$values['img'] = processFileUpload($this->config, 'strip', Florrie::STRIPS.$slug);

				// Add the new strip
				$stripModel = $this->getModel('strip');
				$stripModel->addStrip($values);

				return;
			}
			catch (FormException $e) {

				// TODO: Type the right values, damnit!
				die('Form Error Handling? Maybe later. Error: '.$e->getMessage());

			}
			catch (exception $e) {

				// TODO: Actual error handling
				die('AddStrip Error case: miscellaneous! Error: '.$e->getMessage());
			}
		}

		$this->render('admin-addstrip', array('values' => $values));
	}


	//========================================
	// Protected (internal) methods
	//========================================

	//----------------------------------------
	// Add some extra administrative data to the render function
	//----------------------------------------
	protected function render($page, $data = array()) {

		$data = array_merge($data, array('user' => $_SESSION['user']));

		parent::render($page, $data);
	}
}
?>
