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



require_once $_SERVER['DOCUMENT_ROOT'].'/florrie/controller/admin.php';


class Install extends Admin {

	public function __construct() {

		//----------------------------------------
		// Set up the templating system
		//----------------------------------------

		// Include & initialize the Twig templating library
		require_once $_SERVER['DOCUMENT_ROOT'].'/florrie/lib/twig/lib/Twig/Autoloader.php';
		Twig_Autoloader::register();

		// Use the system-level template directory
		$this->templateDir = $_SERVER['DOCUMENT_ROOT'].'/florrie/templates/';
	}


	// Handle a Florrie installation via HTML form
	public function index() {

		if(!(empty($this->config) || empty($this->config['florrie']))) {

			$e = 'The install page cannot be accessed if 
				Florrie has already been installed';

			throw new ServerError($e);
		}

		$submitted = filter_input(INPUT_POST, 'submitted');

		// Process form data if it has been submitted
		if($submitted !== false) {

			// Defaults go here
			$values = array(
				'florrie-name' => null, 
				'florrie-url' => null, 
				'florrie-desc' => null, 
				'florrie-theme' => null, 
				'data-db' => 'florrie', 
				'data-server' => 'localhost', 
				'data-port' => 3307, 
				'data-user' => null, 
				'data-pass' => null 
			);
			$error = false;

			// Process all form fields
			foreach(&$values as $index => $value) {

				$input = filter_input(INPUT_POST, $value, FILTER_SANITIZE_STRING);

				// If no value was submitted and no default exists, raise an 
				// error
				if(($input === null || $input === false) && empty($value)) {

					$error = true;
				}
				else {

					$value = $input;
				}
			}

			if($error) {

				// TODO: Type the right values, damnit!
				die('Form Error Handling? Maybe later.');

			}
			else {

				// Convert the configuration values to a multidimensional array 
				// that can be converted to XML
				$config = $this->convertToConfigArray($values);

				// TODO: Convert the mutlidimensional array to XML!


				// Installation complete; redirect to the homepage
				header('Location: /');
				return;
			}
		}

		$this->render('install', array(
			'scripts' => array('/florrie/templates/js/install.js'),
			'ftp' => $this->filesWritable()?"false":"true"
		));
	}


	// Render a page and pass it appropriate variables
	protected function render($templateName, $data = array()) {

		// Check to make sure the template dir is valid
		if(realpath($this->templateDir) === false) {
			
			throw new ServerException(get_class($this).' Template directory not set');
		}

		// Set up the template system 
		$loader = new Twig_Loader_Filesystem($this->templateDir);
		$twig = new Twig_Environment($loader);

		// Load the template requested, and display it
		$template = $twig->loadTemplate($this::TEMPLATE_PRE.$templateName.
			$this::TEMPLATE_EXT);

		$template->display($data);
	}


	// Test to see if the file locations are writeable
	protected function filesWritable() {

		$config = $_SERVER['DOCUMENT_ROOT'].'/florrie.cfg';
		$strips = $_SERVER['DOCUMENT_ROOT'].'/strips/test';

		if(!is_writable(dirname($config)) || !is_writable(dirname($strips))) {

			echo 'Not writable!';
			return false;
		}

		if(file_put_contents($config, 'test file') <= 0) {

			echo 'Cant Write Config';
			return false;
		}

		if(file_put_contents($strips, 'test file') <= 0) {

			echo 'Cant Write strips';
			unlink($config);
			return false;
		}

		unlink($config);
		unlink($strips);

		return true;
	}
}
?>
