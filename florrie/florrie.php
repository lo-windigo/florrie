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


// Include the exception classes & error handling
require_once $_SERVER['DOCUMENT_ROOT'].'/florrie/lib/error.php';


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
	const CONFIG     = '/config/florrie.cfg';
	const DEBUG      = true;
	const MODELS     = '/florrie/model/';
	const STRIPS     = '/strips/';


	// Data members:
	//  config - The configuration for this controller
	public $config;


	// Set up all of the basic stuff required to run the comic
	public function __construct() {

		// TODO: What should be initialized in the main class?
	}


	//----------------------------------------
	// Test the write permissions
	//----------------------------------------
	static public function filesWritable() {

		// TODO: Allow for FTP writing as well
		$config = $_SERVER['DOCUMENT_ROOT'].self::CONFIG;
		$strips = $_SERVER['DOCUMENT_ROOT'].'/strips/test';
		$err = '[filesWriteable] ';

		// Check that the configuration directory is writeable
		if(!is_writable(dirname($config))) {

			throw new ServerException($err.'Configuration directory ('.
				dirname($config).') is not writeable');
		}

		// Check that the configuration file is writeable, whether present or 
		//	not
		if(file_exists($config) && !is_writeable($config)) {

			throw new ServerException($err.'Existing configuration file ('.
				$config.') is not writeable');
		}
		else {

		   	if(file_put_contents($config, 'test file') <= 0) {

				throw new ServerException($err.'Configuration file ('.$config.
					') is not writeable');
			}
			else {

				unlink($config);
			}
		}

		// Check that the strips directory is writeable
		if(!is_writable(dirname($strips)) ||
			file_put_contents($strips, 'test file') <= 0) {

			throw new ServerException($err.'Strip directory ('.
				dirname($strips).') is not writeable');
		}
		else {

			unlink($strips);
		}

		return true;
	}


	//----------------------------------------
	// Get any installed plugins
	//----------------------------------------
	public function getPlugins()
	{
		// TODO: Work Ongoing!
		//	Love,
		//	- Windigo
		return;
	}


	//----------------------------------------
	// Check to see if Florrie's installed
	//----------------------------------------
	public function installed()
	{
		try
		{
			// Attempt to get required components, like configuration files
			$this->readConfig();
			$this->getPlugins();

			// If all of these checks occur without issue, then it must be installed!
			return true;
		}
		catch (exception $e)
		{
			return false;
		}
	}


	//----------------------------------------
	// Read & store the configuration file
	//----------------------------------------
	protected function readConfig() {

		// Check to see if the configuration file exists
		$configFile = $_SERVER['DOCUMENT_ROOT'].self::CONFIG;

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

		$config = $parse($configNode);

		// Save the configuration values for later
		$this->config = $config;
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
		return (file_put_contents($_SERVER['DOCUMENT_ROOT'].Florrie::CONFIG,
			$configData) > 0);
	}
}
?>