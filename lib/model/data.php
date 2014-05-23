<?php
/*
	Abstract Data
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

abstract static class FlorrieData
{
	//
	// Abstract Methods
	//
	//	Mostly CRUD (create, read, update, delete), but they must be
	//		implemented by each object
	//
	//___________________________________________________________________________


	abstract static public function Create();
	abstract static public function Delete();
	abstract static public function Get();
	abstract static public function Save();
}
?>
