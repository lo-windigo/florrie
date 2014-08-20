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


class Admin extends Controller {

	public function index() {

		// TODO: An entire admin section
	}


	// Take form input array and convert to multi-dimensional configuration 
	// array, for use with the config file
	protected function convertToConfigArray($flatConfig) {

		$config = array();

		foreach($flatConfig as $index => $value) {

			$indexes = explode('-', $index);

			$treeValue = $this->treeBuilder($indexes, $value);

			$config = array_merge_recursive($config, $treeValue);
		}

		return $config;
	}


	// Recursive function to build multidimensional config arrays
	protected function configBuilder($indexes, $value) {

		$index = array_pop($indexes);

		if($index === null) {

			return $value;
		}

		return array($index => $this->configBuilder($indexes, $value));
	}


	// Write configuration values to the config file
	protected function saveConfig($config) {
	}
}
?>
