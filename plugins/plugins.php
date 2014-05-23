<?php
/*
	Florrie Plugins Module
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

class FlorriePlugins extends 
{
	// Class Constants
	// 	- CONFIG: node that contains the module config
	const PLUGIN_DIR = 'xxx';
	const PLUGIN_CONTROLLER = 'plugin.php';


	// Class Constructor
	// Purpose: Do some stuff to make data things. More later!
	public function __construct($config)
	{

	}	



	// GetPlugins
	// Purpose: Get all the plugins in the plugin directory
	public function GetPlugins()
	{
		// Get all of the subdirectories in the plugin directory
		$pluginDirs = ;
		$plugins = array();

		// Check all of the subdirectories and initiate their controllers
		foreach($pluginDirs as $dir)
		{
			$controller = $pluginDirs[$i].DIRECTORY_SEPARATOR.PLUGIN_CONTROLLER;

			if(file_exists($controller)
				/*&& IS_VALID_PHP_OR_SOMETHING*/)
			{
				$plugins[] = include($controller);
			}
		}

		$this->plugins = $plugins;
	}

}
?>
