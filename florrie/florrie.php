<?php
/*
	Core Functionality
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


// Include the exception classes
require_once 'lib/error.php';


// Main class - kicks things off, starts the party
class Florrie {

	//----------------------------------------
	// Class Constants
	//
	//  CONFIG	   - Configuration File
	//  DEBUG      - Produce debug output
	//  MODELS     - System modules
	//  STRIPS     - Comic strip images
	//----------------------------------------
	const CONFIG = '/../config/florrie.cfg';
	const DEBUG  = true;
	const MODELS = '/model/';
	const STRIPS = '/strips/';


	// Data members:
	//  config - The configuration for this controller
	//	db     - Stored database connection
	public static $config, $db;


	//----------------------------------------
	// Initialize Florrie
	//----------------------------------------
	static public function initialize() {

		try {
			// Attempt to get required components, like configuration files
			self::getConfig();
			self::getPlugins();
		}
		// Florrie is missing a core component; it must not be installed
		catch (exception $e) {
			throw new NotInstalledException('Florrie initialization failed',
				null, $e);
		}
	}


	//----------------------------------------
	// Test the write permissions
	//----------------------------------------
	static public function filesWritable() {

		// TODO: Allow for FTP writing as well
		$configFile = __DIR__.self::CONFIG;
		$stripsFile = __DIR__.self::CONFIG.'test';
		$err = '[filesWriteable] ';

		// Check that the configuration directory is writeable
		if(!is_writable(dirname($configFile))) {

			throw new ServerException($err.'Configuration directory ('.
				dirname($configFile).') is not writeable');
		}

		// Check that the configuration file is writeable, whether present or 
		//	not
		if(file_exists($configFile) && !is_writeable($configFile)) {

			throw new ServerException($err.'Existing configuration file ('.
				$configFile.') is not writeable');
		}
		else {

		   	if(file_put_contents($configFile, 'test file') <= 0) {

				throw new ServerException($err.'Configuration file ('.$configFile.
					') is not writeable');
			}
			else {

				unlink($configFile);
			}
		}

		// Check that the strips directory is writeable
		if(!is_writable(dirname($stripsFile)) ||
			file_put_contents($stripsFile, 'test file') <= 0) {

			throw new ServerException($err.'Strip directory ('.
				dirname($stripsFile).') is not writeable');
		}
		else {

			unlink($stripsFile);
		}

		return true;
	}


	//----------------------------------------
	// Get a database connection
	//----------------------------------------
	static public function getDB() {

		// Check for an existing connection
		if(!isset(self::$db)) {

			// Check the configuration values are present
			$config = self::getConfig();

			if(empty($config['data']) ||
				empty($config['data']['server']) ||
				empty($config['data']['port']) ||
				empty($config['data']['db']) ||
				empty($config['data']['user']) ||
				empty($config['data']['pass'])) {

				throw new DBException('Database configuration values not present');
			}

			// TODO: Also, plug in correct config values!
			// If not connected, connect & save connection object
			self::$db = self::connectDB(
				$config['data']['user'],
				$config['data']['pass'],
				$config['data']['db'],
				$config['data']['server'],
				$config['data']['port']);
		}

		return self::$db;
	}


	//----------------------------------------
	// Manually connect to the database
	//----------------------------------------
	static public function connectDB($user, $pass, $db, $server, $port) {

		// Compile the db values into a DSN
		// TODO: Database independent? Let people choose?
		$dsn = BaseModel::getDSN($db, $server, $port);

		// Attempt to connect to the database
		$db = new PDO($dsn, $user, $pass,
			// Establish the options for the DB conneciton:
			// - FETCH_OBJ: return a PHP object from queries
			// - ERRMODE_EXCEPTION: Throw exceptions on DB errors
			array(
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
			);

		// Quit it with the safety nets! Let me juggle flaming chainsaws!
		// Allows mass updates/deletes with questionably-specific criteria
		$db->exec('SET SQL_SAFE_UPDATES=0');

		return $db;
	}


	//----------------------------------------
	// Get a model object
	//----------------------------------------
	static public function loadModel($name) {

		$modulePath = __DIR__.self::MODELS.strtolower($name).'.php';
		$name .= 'Model';

		if(!file_exists($modulePath)) {
			
			throw new ServerException('Module does not exist: '.$name);
		}

		// Create a new module object, and return it
		require_once $modulePath;

		return new $name();
	}


	//----------------------------------------
	// Get any installed plugins
	//----------------------------------------
	static public function getPlugins()
	{
		// TODO: Work Ongoing!
		//	Love,
		//	- Windigo
	}


	//----------------------------------------
	// Install Florrie
	//----------------------------------------
	static public function install($configs)
	{
		// Set the config values
		$configArray = self::convertToConfigArray($configs);
		self::$config = $configArray;

		// Install the database tables
		$models = array();

		// TODO: Dynamically get modules
		$models[] = $userModel = self::loadModel('User');
		$models[] = $stripModel = self::loadModel('Strip');

		// Install each module's tables
		foreach($models as $model) {
			$model->installTables();
		}

		// Add the administrative user to the system
		$userModel->addUser(
			$configs['username'],
			$configs['desc'],
			$configs['password']);

		// Some values need to be removed from the array; they're
		//  redundant or shouldn't be saved in plain text 
		unset(
			$configs['username'],
			$configs['password'],
			$configs['desc']);

		// Save the configuration
		self::saveConfig($configArray);
	}


	//----------------------------------------
	// Return the configuration array
	//----------------------------------------
	static public function getConfig() {

		if(empty(self::$config)) {

			// Save the configuration values for later
			self::$config = self::readConfig();
		}

		return self::$config;
	}


	//----------------------------------------
	// Read & store the configuration file
	//----------------------------------------
	static protected function readConfig() {

		// Check to see if the configuration file exists
		$configFile = __DIR__.self::CONFIG;

		if(!file_exists($configFile)) {

			throw new InitException('Configuration file not present!');
		}


		// If this is the first time getting the config, try to parse the
		//	configuration file. The second argument returns a multidimensional
		//	array based on sections
		$configDoc = new DOMDocument();
		$configDoc->load($configFile);

		// If we failed to get the configuration, throw an exception
		if($configDoc === false) {

			throw new InitException('Unable to parse "'.basename(self::CONFIG).'".');
		}

		// Get the base configuration node
		$configNode = $configDoc->documentElement;

		// Parse the configuration file into an associative array, recursively,
		//	using a anonymous function
		$parse = function($node) use (&$parse) {

			// If we have children, we will need to start an array and fill it with 
			//   the child nodes' values, recursively
			$values = array();

			foreach($node->childNodes as $child) {

				// Recurse through this child if it's an XML element
				if($child->nodeType == XML_ELEMENT_NODE) {
					$values[$child->nodeName] = $parse($child);
				}
			}

			// If there are no child elements on this node, return its value
			if(empty($values)) {
				return $node->nodeValue;
			}

			// Otherwise, return the child node's values
			return $values;
		};

		return $parse($configNode);
	}


	//----------------------------------------
	// Take form input array and convert to multi-dimensional configuration 
	// array, for use with the config file
	//----------------------------------------
	static public function convertToConfigArray($flatConfig) {

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
	// Flatten a multidimensional config array
	//----------------------------------------
	static public function convertToFlatArray($configArray) {

		$flatConfig = array();

		// Recursive function to flatten multidimensional config arrays
		$builder = function(&$flatConfig, $configArray, $flatIndex = false)
			use (&$builder) {

			// Once we get down to the value, add it to the flat array
			// B-B-BASE CASE!
			if(!is_array($configArray)) {

				$flatConfig[$flatIndex] = $configArray;
				return;
			}

			if($flatIndex) {

				$flatIndex .= '-';
			}
			else {

				$flatIndex = '';
			}

			// Process the config array recursively
			foreach($configArray as $index => $subArray) {

				$builder($flatConfig, $subArray, $flatIndex.$index);
			}
		};

		$builder($flatConfig, $configArray);

		return $flatConfig;
	}


	//----------------------------------------
	// Write configuration values to the config file
	//----------------------------------------
	static public function saveConfig($configArray) {

		$configXML = new DOMDocument();
		$configXML->formatOutput = true;

		// Recursive function to build config nodes
		$builder = function($values, &$parent) use (&$builder) {

			// BASE CASE: Set the value of the parent node, and return
			if(!is_array($values)) {

				// This chokes on ampersands. Booo, PHP.
				//$parent->nodeValue = $values;

				$value = $parent->ownerDocument->createTextNode($values);

				$parent->appendChild($value);

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
		$configFile = __DIR__.self::CONFIG;

		return (file_put_contents($configFile, $configData) > 0);
	}
}
?>
