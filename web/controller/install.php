<?php
/*
	New Installation Controller
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



require_once __DIR__.'/../lib/controller.php';
require_once __DIR__.'/../lib/forms.php';


class Install extends WebController {

	// Route the various steps of the installation
	public static function index() {

		$config = Florrie::getConfig();

		// Check for a previous install
		if(!(empty($config) || empty($config['florrie']))) {

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

		// Check for MySQL PDO support
		// TODO: Other database drivers
		if(!(class_exists('PDO') && in_array('mysql', PDO::getAvailableDrivers()))) {

			$missingRequirements[] = <<<MYSQL
Your system does not support PDO, and more specifically, the MySQL PDO driver.
Please make sure this is installed. On Debian/Ubuntu, you can install the
'php5-mysql' package.
MYSQL;
		}

		// Check for the GD static functions
		// TODO: Technically only required to resize images. Maybe make it optional?
		if(!function_exists('imagecreatefromstring')) {

			$missingRecommends[] = <<<GD
Florrie uses the GD image manipulation libraries to resize images; please make
sure this library is installed.
GD;
		}

		// Use OpenSSL static functions to generate salt
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

			self::installFlorrie($missingRecommends);
		}
		else {

			self::requirements($missingRequirements, $missingRecommends);
		}
	}


	//----------------------------------------
	// Internal controller static functions
	//----------------------------------------

	// Handle a Florrie installation via HTML form
	protected static function installFlorrie($missingRecommends) {

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

				// Process the form submission
				processFormInput($values);

				// Attempt to install Florrie
				Florrie::install($values);

				// Installation complete; redirect to the homepage
				header('Location: /admin');
				return;
			}
			catch(FormException $error) {

				// TODO: Type the right values, damnit!
				die('Form Error Handling? Maybe later. '.$error->getMessage());

			}
			// Generic error
			catch(exception $error) {

				// TODO: actual error message, perhaps
				die('Generic install error: '.$error);
			}
		}

		self::render('install', array(
			'data'            => $values,
			'ftp'             => Florrie::filesWritable()?'false':'true',
			'recommendations' => $missingRecommends,
			'scripts'         => array('/florrie/templates/js/install.js'),
			'themes'          => WebModule::getThemes()
		));
	}


	// Display the requirements for Florrie, if they are not met
	protected static function requirements($missingRequirements, $missingRecommends) {

		self::render('requirements', array(
			'recommendations' => $missingRecommends,
			'requirements'    => $missingRequirements,
		));
	}
}
