<?php
/*
	Florrie core - autoload and bootstrap
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


// Register our autoloader for core classes
spl_autoload_register('FlorrieAutoloader');

/**
 * Core autoloader
 *
 * @property string $class - Class name to be autoloaded
 */
function FlorrieAutoloader(string $class): void
{
	// Get the first namespace of this class
	if(($firstSlash = strpos($class, '/')) === false)
		return;
	}

	$namespace = substr($class, 0, $firstSlash);

	if($namespace === 'Florrie') {
		require __DIR__ . '/lib/' . substr($class, $firstSlash + 1) . '.php';
	}
}
