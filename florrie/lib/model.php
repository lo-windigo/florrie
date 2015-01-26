<?php
/*
	Abstract Base Model
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


abstract class BaseModel {

	const FORCE_INSTALL = 'yes-i-really-want-to-install';
	const MYSQL_DATE = 'Y-m-d H:i:s';

	// Remove the database tables for this model
	abstract public function deleteTables() {}

	// Install the database tables for this model
	abstract public function installTables($force) {}

	// Check to see if a database tables exist at all
	public function tableExist($table) {

		$q = 'SHOW TABLES LIKE :table';

		$statement = $this->db->prepare($q);

		$statement->bind(':table', $table);

		$statement->execute();

		return (bool)$statement->fetch();
	}
}
