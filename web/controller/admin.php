<?php
/*
	Admin Controller
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
require_once __DIR__.'/../../florrie/lib/file.php';
require_once __DIR__.'/../lib/forms.php';



class Admin extends Controller {


	public function __construct() {

		// Check for user credentials
		if(empty($_SESSION['user'])) {

			// TODO: Save page user was attempting to visit
			$_SESSION['page-attempted'] = filter_input(INPUT_GET, 'p', FILTER_SANITIZE_URL);

			// Users must be logged in!
			header('Location: /login', true, 307);
			exit;
		}

		parent::__construct();
	}


	//========================================
	// Views
	//========================================


	//----------------------------------------
	// Index of admin functions
	//----------------------------------------
	public function index() {

		$stripModel = $this->getStripModel();

		$strips = $stripModel->getStrips();
		$strips = array_slice($strips, -5);

		$this->render('admin-index', array(
			'strips' => $strips,
			'section' => 'strips'
		));
	}


	//----------------------------------------
	// List all available strips
	//----------------------------------------
	public function allstrips() {

		$stripModel = $this->getStripModel();

		// TODO: Pagination? Limits? This could get messy.
		$strips = $stripModel->getStrips();
		$strips = array_reverse($strips);

		$this->render('admin-allstrips', array(
			'strips' => $strips,
			'section' => 'strips'
		));
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
				$stripModel = $this->getStripModel();
				$values['slug'] = $stripModel->createSlug($values['title']);

				// Handle strip file upload
				$values['img'] = processFileUpload($this->config, 'img',
				   Florrie::STRIPS, $values['slug']);

				// Resize the strip image
				// TODO: Always saves file as a JPEG! Make sure to assign
				//	.jpg extension somewhere!
				resizeImage($this->config, Florrie::STRIPS.$values['img']);

				// Add the new strip
				$stripModel->addStrip($values);

				header('Location: /admin/stripsaved');
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

		$this->render('admin-addstrip', array(
			'values' => $values,
			'section' => 'strips'

		));
	}


	//----------------------------------------
	// Add an administrative user
	//----------------------------------------
	public function adduser() {

		// Defaults go here
		$values = array(
			'csrf' => null, 
			'desc' => null, 
			'password' => null, 
			'username' => null
		);

		// Process form data if it has been submitted
		if(submitted()) {

			try {
				processFormInput($values);

				// Create a slug for this comic
				$userModel = Florrie::loadModel('user');

				// Add the new strip
				$userModel->addUser($values);

				header('Location: /admin/usersaved');
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

		$this->render('admin-addstrip', array(
			'values' => $values,
			'section' => 'strips'

		));
	}


	//----------------------------------------
	// Edit an existing strip
	//----------------------------------------
	public function editstrip($stripId) {

		// If no ID was provided, get out!
		if(empty($stripId)) {

			throw new ServerException('No strip ID provided to editStrip');
		}

		// Get the strip in question, and load it up
		$stripModel = $this->getStripModel();
		$stripObject = $stripModel->getStrip($stripId);

		// Defaults go here
		$values = array(
			'csrf'         => null,
			'change-order' => null,
			'display'      => $stripObject->display,
			'title'        => $stripObject->title,
			'posted'       => $stripObject->posted
		);

		// Process form data if it has been submitted
		if(submitted()) {

			try {

				processFormInput($values);

				// Create a slug for this comic
				$values['slug'] = $stripModel->createSlug($values['title']);

				// Only process a new strip if it's been uploaded
				if(!empty($_FILES['img']['tmp_name'])) {

					// Handle strip file upload
					$values['img'] = processFileUpload($this->config, 'img',
					   Florrie::STRIPS, $values['slug']);

					resizeImage($this->config, Florrie::STRIPS.$values['img']);
				}

				// Assign new values to the strip object
				foreach($values as $member => $value) {

					if(!empty($value)) {
						$stripObject->$member = $value;
					}
				}

				// Save strip details
				$stripModel->updateStrip($stripObject);

				// Handle a change in strip order
				if($values['change-order'] !== null) {

					if($values['change-order'] === 'last') {

						$target = false;
					}
					else {

						$target = $values['change-order'];
					}
					
					$stripModel->orderBefore($stripObject, $target);
				}

				header('Location: /admin/stripsaved');
				return;
			}
			catch (FormException $e) {

				// TODO: Type the right values, damnit!
				echo 'Form Error Handling? Maybe later. Error: '.$e->getMessage();

				echo '<br>';

				print_r($e->formData);

				exit;
			}
			catch (exception $e) {

				// TODO: Actual error handling
				die('EditStrip Error case: miscellaneous! Error: '.$e->getMessage());
			}
		}

		// Get all strips for the order drop-down
		$strips = $stripModel->getStrips();

		// Append the strip ID for template logic
		$values['id'] = $stripObject->id;

		$this->render('admin-editstrip', array(
			'values' => $values,
			'strips' => $strips,
			'section' => 'strips'
		));
	}


	//----------------------------------------
	// Remove a strip from the comic system
	//----------------------------------------
	public function delstrip($stripId) {

		// If no ID was provided, get out!
		if(empty($stripId)) {

			throw new ServerException('No strip ID provided to delStrip');
		}

		$strip = $stripModel->getStrip($stripId);

		// Process form data if it has been submitted
		if(submitted()) {

			try {

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
			catch (FormException $e) {

				// TODO: Type the right values, damnit!
				die('Form Error Handling? Maybe later. Error: '.$e->getMessage());
			}
			catch (exception $e) {

				// TODO: Actual error handling
				die('DelStrip Error case: miscellaneous! Error: '.$e->getMessage());
			}

		}

		$this->render('admin-delstrip', array(
			'strip' => $strip,
			'section' => 'strips'

		));
	}


	//----------------------------------------
	// General settings administration
	//----------------------------------------
	public function settings() {

		$settings = Florrie::convertToFlatArray($this->config);
		$themes = Florrie::getThemes();

		// Process form data if it has been submitted
		if(submitted()) {

			try {

				// Check the CSRF values
				$values = array(
					'csrf' => null
				);

				$values = array_merge($settings, $values);

				processFormInput($values);

				// Save the configuration
				$configArray = Florrie::convertToConfigArray($values);
				if(Florrie::saveConfig($configArray)) {

					header('Location: /admin/settingssaved');
					return;
				}

				throw new exception('No config file written!');
			}
			catch (FormException $e) {

				// TODO: Type the right values, damnit!
				die('Form Error Handling? Maybe later. Error: '.$e->getMessage());
			}
			catch (exception $e) {

				// TODO: Actual error handling
				die('DelStrip Error case: miscellaneous! Error: '.$e->getMessage());
			}
		}

		// TODO: Sync $values with $settings!

		$this->render('admin-settings', array(
			'settings' => $settings,
			'themes' => $themes,
			'section' => 'settings'
		));
	}


	//----------------------------------------
	// Success message: settings saved
	//----------------------------------------
	public function settingssaved() {

		$this->render('admin-settingssaved', array(
			'section' => 'settings'
		));
	}


	//----------------------------------------
	// Success message: delete strip
	//----------------------------------------
	public function stripdeleted() {

		$this->render('admin-stripdeleted', array(
			'section' => 'strips'
		));
	}


	//----------------------------------------
	// Success message: strip saved
	//----------------------------------------
	public function stripsaved() {

		$this->render('admin-stripsaved', array(
			'section' => 'strips'
		));
	}


	//----------------------------------------
	// Success message: user saved
	//----------------------------------------
	public function usersaved() {

		$this->render('admin-usersaved', array(
			'section' => 'users'
		));
	}



	//----------------------------------------
	// User administration
	//----------------------------------------
	public function users() {

		$userModel = Florrie::loadModel('user');
		$users = $userModel->getUsers();

		$this->render('admin-users', array(
			'users' => $users,
			'section' => 'users'
		));
	}


	//========================================
	// Protected (internal) methods
	//========================================


	//----------------------------------------
	// Get a strip model with appropriate settings
	//----------------------------------------
	protected function getStripModel() {

		// Create a slug for this comic
		$stripModel = Florrie::loadModel('strip');
		$stripModel->unpublished = true;

		return $stripModel;
	}


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
