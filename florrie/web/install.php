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



require_once $_SERVER['DOCUMENT_ROOT'].'/florrie/lib/controller.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/florrie/lib/forms.php';


class Install extends Controller {

	public function __construct($config = null) {

		if($config === null) {

			$config = array();
		}

		// If you're installing, there's no config
		$this->config = $config;

		$this->initTemplates();
	}


	// Route the various steps of the installation
	public function index() {

		// Check for a previous install
		if(!(empty($this->config) || empty($this->config['florrie']))) {

			$e = 'The install page cannot be accessed if '.
				'Florrie has already been installed';

			throw new ServerException($e);
		}

		// Check for requirements: if any of these fail, Florrie cannot be 
		//  installed until they are resolved.
		$missingRequirements = $missingRecommends = array();

		// SHA512 used for hashing passwords securely
		// Can't check if the constant is defined, it lies. PHP 4 EVA!
		if(/*!defined(CRYPT_SHA512) ||*/ !CRYPT_SHA512) {

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

		// Check for the GD functions
		// TODO: Technically only required to resize images. Maybe make it optional?
		if(!function_exists('imagecreatefromstring')) {

			$missingRecommends[] = <<<GD
Florrie uses the GD image manipulation libraries to resize images; please make
sure this library is installed.
GD;
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

		// Defaults go here
		$values = array(
			'florrie-name'      => null, 
			'florrie-url'       => null, 
			'florrie-desc'      => null, 
			'florrie-theme'     => null, 
			'florrie-maxheight' => null, 
			'florrie-maxwidth'  => null, 
			'data-db'           => 'florrie', 
			'data-server'       => 'localhost', 
			'data-port'         => 3307, 
			'data-user'         => null, 
			'data-pass'         => null,
			'username'          => null,
			'password'          => null,
			'desc'              => null
		);

		// Process form data if it has been submitted
		if(submitted()) {

			try {

				processFormInput($values);

				// Connect to the database, and save connection
				$options = array(
					PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
					PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);

				// Compile the db values into a DSN
				// TODO: Database independent? Let people choose?
				$dsn = BaseModel::getDSN(
					$values['data-db'],
					$values['data-server'],
					$values['data-port']);

				$this->db = new PDO($dsn, $values['data-user'],
					$values['data-pass'], $options);

				// Install the database tables
				// TODO: Dynamically get modules
				$userModel = $this->loadModel('User');
				$stripModel = $this->loadModel('Strip');
				
				$userModel->installTables();
				$stripModel->installTables();

				// Add the administrative user to the system
				$userModel->addUser(
					$values['username'],
				   	$values['desc'],
					$values['password']);

				// Some values need to be removed from the array; they're
				//  redundant or shouldn't be saved in plain text 
				unset(
					$values['username'],
					$values['password'],
					$values['desc']);

				// Save the configuration
				$configArray = Florrie::convertToConfigArray($values);
				Florrie::saveConfig($configArray);

				// Installation complete; redirect to the homepage
				// TODO: The homepage SUCKS after install. Maybe send somewhere 
				//	better?
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
			'ftp'             => Florrie::filesWritable()?'false':'true',
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
