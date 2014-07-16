<?php
/*
	Florrie Abstract SQL Layer
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

abstract static class FlorrieDb
{
	//
	// Abstract Methods
	//
	//	Mostly CRUD (create, read, update, delete), but they must be
	//		implemented by each object
	//
	//___________________________________________________________________________


	abstract public function Create();
	abstract public function Delete();
	abstract public function Get();
	abstract public function Save();



	//
	// Common Methods
	//
	//	These functions are shared among all types of databases, so they are
	//		defined in this class and shared.
	//
	//___________________________________________________________________________


	// CheckConnection()
	// Purpose: Checks to make sure a database connection is present
	// Return: Throws an exception if database is not connected
	public function CheckConnection($prefix)
	{
		if(empty($conn))
		{
			$error = 'Database not connected.';

			if(!empty($prefix))
			{
				$error = $prefix.$error;
			}

			throw new exception($error);
		}
	}
}

?>
