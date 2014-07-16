<?php
/*
	Controller - Florrie Base Module
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


class Florrie
{
	// Class Constants:
	//	CONFIG	- Configuration File
	public const CONFIG = '../../config/config.xml';

	protected $config;

	// Constructor
	// Purpose:	Set up all of the basic stuff required to run the comic
	public function __construct()
	{
		// If Florrie hasn't been installed yet, we should probably address that
		if(!$this->Installed())
		{
			// Include the installation class
			require_once('install.php');

			// Create a new object that will show an installation wizard
			$page = new FlorrieInstall();
		}


	}



	// GetConfig
	// Purpose:	Get the configuration file, if present, and return the
	//	configuration array
	// Return:	array
	protected function GetConfig()
	{
		// Return the cached config array if present
		if(!empty($this->config) && is_array($this->config))
		{
			return $this->config;
		}

		// If this is the first time getting the config, try to parse the
		//	configuration file. The second argument returns a multidimensional
		//	array based on sections

		// Old INI-file code
		//	$config = parse_ini_file(self::CONFIG, true);

		$config = new DOMDocument('UTF-8');
		$config->load(self::CONFIG);

		// If the configuration file was parsed successfully, and returned a
		//	properly formed array, return those values after caching
		if(!empty($config) && is_array($config))
		{
			$this->config = $config;
			return $config;
		}

		// If unable to grab any configuration values, throw an exception
		throw new exception('Unable to parse "'.basename(self::CONFIG).'".');
	}



	// GetPlugins
	// Purpose:	Get the configuration file, if present, and return the
	//	configuration array
	// Return:	void, but an exception may be thrown
	public function GetPlugins()
	{
		// Work Ongoing!
		//	Love,
		//	- Windigo
		return;
	}


	// Installed
	// Purpose:	Check to see if Florrie's installed or not
	public function Installed()
	{
		try
		{
			// Attempt to get required components, like configuration files
			$this->GetConfig();
			$this->GetPlugins();

			// If all of these checks occur without issue, then it must be installed!
			return true;
		}
		catch (exception $e)
		{
			// Log stuff

			// Florrie install is missing or incomplete
			return false;
		}
	}
}
?>
