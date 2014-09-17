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


	// Route the various steps of the installation
	public function index() {

		// Check for a previous install
		if(!(empty($this->config) || empty($this->config['florrie']))) {

			$e = 'The install page cannot be accessed if 
				Florrie has already been installed';

			throw new ServerError($e);
		}

		// Check for requirements: if any of these fail, Florrie cannot be 
		//  installed until they are resolved.
		$missingRequirements = $missingRecommends = array();

		// SHA512 used for hashing passwords securely
		if(empty(CRYPT_SHA512)) {

			$missingRequirements[] = <<<SHA
Your system does not support SHA512 hashing; this prevents Florrie from
securely handling passwords. Upgrading to PHP 5.3 or newer will fix this.
SHA;
		}

		// RIPE used for CSRF
		if(!(function_exists('hash_algos') && in_array('ripemd320', hash_algos()))) {
			$missingRequirements[] = <<<RIPE
Your system does not support ripemd320 hashing; this prevents Florrie from
generating CSRF tokens. However, IT DOESN'T HAVE TO BE THIS WAY! If you
encounter this error, file a bug and we can fix it with little effort. Yay!
RIPE;
		}

		// Use OpenSSL functions to generate salt
		if(!function_exists('openssl_random_pseudo_bytes')) {

			$missingRecommends[] = <<<OPENSSL
We recommend OpenSSL for much more secure pseudo random number generation.
Your numbers will still be random-ish without it, but not what they could be!
OPENSSL;
		}

		if(!$this->filesWriteable) {

			//$missingRecommends[] = <<<WRITE
			$missingRequirements[] = <<<WRITE
Florrie cannot write to the strips folder, or the configuration file.
WRITE;
		}

		if(empty($missingRequirements)) {

			$this->install();
		}
		else {

			$this->requirements($missingRequirements, $missingRecommends);
		}
	}


	//----------------------------------------
	// Internal controller functions
	//----------------------------------------

	// Test to see if the file locations are writeable
	protected function filesWritable() {

		$config = $_SERVER['DOCUMENT_ROOT'].Florrie::CONFIG;
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


	// Handle a Florrie installation via HTML form
	protected function install($missingRecommends) {

		$submitted = filter_input(INPUT_POST, 'submitted');

		// Process form data if it has been submitted
		if($submitted !== null) {

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
			foreach($values as $index => &$value) {

				$input = filter_input(INPUT_POST, $index, FILTER_SANITIZE_STRING);

				// If no value was submitted and no default exists, raise an 
				// error
				if(($input === null || $input === false) && empty($value)) {

					//$error = true;
					if($error === false) {

						$error = '';
					}

					$error .= $index.' ';
				}
				else if(!empty($input)) {

					$value = $input;
				}
			}

			if($error) {

				// TODO: Type the right values, damnit!
				die('Form Error Handling? Maybe later. Bad indexes: '.$error);

			}
			else {

				// Compile the db values into a DSN
				// TODO: Database independent? Let people choose?
				$values['data-dsn'] = 'mysql:host='.$values['data-server'].
					';port='.$values['data-port'].';dbname='.
					$values['data-db'];

				unset(
					$values['data-server'],
					$values['data-port'],
					$values['data-db']
				);

				$configArray = $this->convertToConfigArray($values);

				$this->saveConfig($configArray);

				// TODO: Install the database. Details, details.

				// Installation complete; redirect to the homepage
				header('Location: /');
				return;
			}
		}

		$themes = $this->getThemes();

		$this->render('install', array(
			'scripts' => array('/florrie/templates/js/install.js'),
			'ftp' => $this->filesWritable()?"false":"true",
			'themes' => $themes
		));
	}


	// Display the requirements for Florrie, if they are not met
	protected function requirements() {
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
}
?>
