<?php
/*
	Core Functionality
	Copyright © 2021 Jacob Hume

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

namespace Florrie;

class Config {

	//----------------------------------------
	// Read & store the configuration file
	//----------------------------------------
	static protected function loadConfig() {

		// Check to see if the configuration file exists
		$configFile = __DIR__.self::CONFIG;

		if(!file_exists($configFile)) {

			throw new NotInstalledException('Configuration file not present!');
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

		self::$config = $parse($configNode);
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


}

