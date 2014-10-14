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



require_once $_SERVER['DOCUMENT_ROOT'].'/florrie/lib/controller.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/florrie/lib/forms.php';


class Install extends Controller {

	public function __construct() {

		//----------------------------------------
		// Set up the templating system
		//----------------------------------------

		// Include & initialize the Twig templating library
		require_once $_SERVER['DOCUMENT_ROOT'].'/florrie/lib/twig/lib/Twig/Autoloader.php';
		Twig_Autoloader::register();

		// Use the system-level template directory
		$this->templateDir = $_SERVER['DOCUMENT_ROOT'].'/florrie/templates/';

		// If you're here, there's no config
		$this->config = array();
	}


	// Route the various steps of the installation
	public function index() {

		// Check for a previous install
		if(!(empty($this->config) || empty($this->config['florrie']))) {

			$e = 'The install page cannot be accessed if '.
				'Florrie has already been installed';

			throw new ServerError($e);
		}

		// Check for requirements: if any of these fail, Florrie cannot be 
		//  installed until they are resolved.
		$missingRequirements = $missingRecommends = array();

		// SHA512 used for hashing passwords securely
		if(!defined(CRYPT_SHA512) || !CRYPT_SHA512) {

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

		if(!Florrie::filesWritable()) {

			//$missingRecommends[] = <<<WRITE
			$missingRequirements[] = <<<WRITE
Florrie cannot write to the strips folder, or the configuration file.
WRITE;
		}

		if(empty($missingRequirements)) {

			$this->install($missingRecommends);
		}
		else {

			$this->requirements($missingRequirements, $missingRecommends);
		}
	}


	//----------------------------------------
	// Internal controller functions
	//----------------------------------------

	// Handle a Florrie installation via HTML form
	protected function install($missingRecommends) {

		// Process form data if it has been submitted
		if(submitted()) {

			// Defaults go here
			$values = array(
				'florrie-name' => null, 
				'florrie-url' => null, 
				'florrie-desc' => null, 
				'florrie-theme' => null, 
				'florrie-maxheight' => null, 
				'florrie-maxwidth' => null, 
				'data-db' => 'florrie', 
				'data-server' => 'localhost', 
				'data-port' => 3307, 
				'data-user' => null, 
				'data-pass' => null 
			);

			try {

				processFormInput($values);

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
			catch(FormException $error) {

				// TODO: Type the right values, damnit!
				die('Form Error Handling? Maybe later. '.$error->getMessage());

			}
		}

		$this->render('install', array(
			'data'            => $values,
			'ftp'             => Florrie::filesWritable()?"false":"true",
			'recommendations' => $missingRecommends,
			'scripts'         => array('/florrie/templates/js/install.js'),
			'themes'          => Florrie::getThemes()
		));
	}


	// Display the requirements for Florrie, if they are not met
	protected function requirements($missingRequirements, $missingRecommends) {

		$this->render('requirements', array(
			'recommendations' => $missingRecommends,
			'requirements'    => $missingRequirements,
		));
	}
}
