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

		// Use the system-level template directory
		$this->templateDir = $_SERVER['DOCUMENT_ROOT'].'/florrie/templates/';
	}


	public function index() {

		$this->render('admin');
	}


	//----------------------------------------
	// Add a strip to the comic system
	//----------------------------------------
	public function addstrip() {

		// Process form data if it has been submitted
		if(Submitted()) {

			// Defaults go here
			$values = array(
				'display' => null, 
				'img' => null, 
				'posted' => new DateTime(), 
				'title' => null
			);

			try {

				ProcessFormInput($values);

				// TODO: Handle strip file upload!
				//$

				$stripModel = $this->getModel('strip');

				// Add the new strip
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

		$this->render('addstrip');
	}


	//========================================
	// Protected (internal) methods
	//========================================

	//----------------------------------------
	// Get the installed/available themes
	//----------------------------------------
	protected function getThemes() {

		$themes = array();
		$themesDir = $_SERVER['DOCUMENT_ROOT'].Florrie::THEMES;

		// TODO: Actually fetch installed themes
		$themes['default'] = "Default Theme";

		return $themes;
	}


	//----------------------------------------
	// Take form input array and convert to multi-dimensional configuration 
	// array, for use with the config file
	//----------------------------------------
	protected function convertToConfigArray($flatConfig) {

		$configArray = array();

		// Recursive function to build multidimensional config arrays
		$builder = function(&$indexes, $value) use (&$builder) {

			$index = array_shift($indexes);

			if(is_null($index)) {

				return $value;
			}

			return array($index => $builder($indexes, $value));
		};

		foreach($flatConfig as $index => $value) {

			$indexes = explode('-', $index);

			$treeValue = $builder($indexes, $value);

			$configArray = array_merge_recursive($configArray, $treeValue);
		}

		return $configArray;
	}


	//----------------------------------------
	// Add some extra administrative data to the render function
	//----------------------------------------
	protected function render($page, $data = array()) {

		$data = array_merge($data, array('user' => $_SESSION['user']));

		parent::render($page, $data);
	}


	//----------------------------------------
	// Write configuration values to the config file
	//----------------------------------------
	protected function saveConfig($configArray) {

		$configXML = new DOMDocument();
		$configXML->formatOutput = true;

		// Recursive function to build config nodes
		$builder = function($values, &$parent) use (&$builder) {

			// BASE CASE: Set the value of the parent node, and return
			if(!is_array($values)) {

				$parent->nodeValue = $values;
				return;
			}

			// Create nodes for each config value, and add it as a child
			foreach($values as $index => $value) {

				$thisNode = $parent->ownerDocument->createElement($index);

				$builder($value, $thisNode);

				$parent->appendChild($thisNode);
			}
		};

		$configNode = $configXML->createElement('config');

		$configNode->appendChild(
			new DOMComment('!!! DO NOT MODIFY DIRECTLY: USE ADMIN SECTION !!!')
		);

		$builder($configArray, $configNode);

		$configXML->appendChild($configNode);

		$configData = $configXML->saveXML();

		// TODO Use file API!
		return (file_put_contents($_SERVER['DOCUMENT_ROOT'].Florrie::CONFIG,
			$configData) > 0);
	}
}
?>
