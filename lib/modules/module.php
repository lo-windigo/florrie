<?php
/*
	Abstract Module Class - Florrie Base Modules
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


abstract class FlorrieModule
{
	// Class Constants
	//	CONFIG	- The config file section that contains this module's
	//		settings


	// Data Members
	public $something;

	// GetConfigSection
	// Purpose:	Get the index of the configuration values for this module
	public function GetConfigSection()
	{

	}
}
?>
