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
require_once $_SERVER['DOCUMENT_ROOT'].'/florrie/lib/file.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/florrie/lib/forms.php';



class Admin extends Controller {


	public function __construct($config) {

		// Check for user credentials
		if(empty($_SESSION['user'])) {

			// TODO: Save page user was attempting to visit

			// Users must be logged in!
			header('Location: /login', true, 307);
			exit;
		}

		parent::__construct($config);
	}


	//----------------------------------------
	// Index of admin functions
	//----------------------------------------
	public function index() {

		// Short 'n sweet
		$this->render('admin-index');
	}


	//----------------------------------------
	// Add a strip to the comic system
	//----------------------------------------
	public function addstrip() {

		// Defaults go here
		$values = array(
			'csrf' => null, 
			'display' => null, 
			'posted' => new DateTime(), 
			'title' => null
		);

		// Process form data if it has been submitted
		if(submitted()) {

			try {

				processFormInput($values);

				// Create a slug for this comic
				$stripModel = $this->loadModel('strip');
				$values['slug'] = $stripModel->createSlug($values['title']);

				// Handle strip file upload
				$values['img'] = processFileUpload($this->config, 'img',
				   Florrie::STRIPS, $values['slug']);

				// Add the new strip
				$stripModel->addStrip($values);

				header('Location: /admin/stripadded');
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


	//----------------------------------------
	// Remove a strip from the comic system
	//----------------------------------------
	public function delstrip($stripId) {

		// If no ID was provided, get out!
		if(empty($stripId)) {

			throw new ServerException('No strip ID provided to delStrip');
		}

		// Create a slug for this comic
		$stripModel = $this->loadModel('strip');

		$strip = $stripModel->getStrip($stripId);

		// Process form data if it has been submitted
		if(submitted()) {

			// Check the CSRF values
			$values = array(
				'csrf' => null
			);

			processFormInput($values);

			// Remove this strip!
			$stripModel->delStrip($strip);

			header('Location: /admin/stripdeleted');
			return;
		}

		$this->render('admin-delstrip', array('strip' => $strip));
	}


	//----------------------------------------
	// Success message: add strip
	//----------------------------------------
	public function stripadded() {

		$this->render('admin-stripadded');
	}


	//----------------------------------------
	// Success message: delete strip
	//----------------------------------------
	public function stripdeleted() {

		$this->render('admin-stripdeleted');
	}



	//========================================
	// Protected (internal) methods
	//========================================

	//----------------------------------------
	// Add some extra administrative data to the render function
	//----------------------------------------
	protected function render($page, $data = array()) {

		$adminData = array(
			'user' => $_SESSION['user'],
			'csrf' => $_SESSION['csrf']
		);
		$data = array_merge($data, $adminData);

		parent::render($page, $data);
	}
}
?>
