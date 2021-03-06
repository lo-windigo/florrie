<?php
/*
	Abstract Base Model
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


abstract class BaseModel {

	const FORCE_INSTALL = 'yes-i-really-want-to-install';
	const MYSQL_DATE = 'Y-m-d H:i:s';



	//----------------------------------------
	// Constructor
	//----------------------------------------
	public function __construct() {

		// Save the database connection for later
		$this->db = Florrie::getDB();
	}


	//----------------------------------------
	// Remove the database tables for this model
	//----------------------------------------
	public function delTable($table) {

		$q = 'DROP TABLE IF EXISTS :table';
		$statement = $this->db->prepare($q);
		$statement->bindValue(':table', $table, PDO::PARAM_STR);
		$statement->execute();
	}


	//----------------------------------------
	// Remove a collection of tables
	//----------------------------------------
	public function delTables($tables) {

		foreach($tables as $table) {

			$this->delTable($table);
		}
	}


	//----------------------------------------
	// Get a DSN for a MySQL connection
	//----------------------------------------
	static public function getDSN($db = 'florrie',
	   $server = 'localhost', $port = '3306') {

		return 'mysql:host='.$server.';port='.$port.';dbname='.$db;
	}


	//----------------------------------------
	// Install the database tables for this model
	//----------------------------------------
	public function installTables($force = false) {

		// Make sure tables do not exist (unless forced)
		if($this->tablesExist()) {

			if($force === self::FORCE_INSTALL) {

				$this->deleteTables();
			}
			else {

				$e = 'Error: attempted to install tables, but tables exist';
				throw new DBException($e);
			}
		}
	}


	//----------------------------------------
	// Check to see if a database tables exist at all
	//----------------------------------------
	public function tableExists($table) {

		$q = 'SHOW TABLES LIKE :table';

		$statement = $this->db->prepare($q);

		$statement->bindValue(':table', $table, PDO::PARAM_STR);

		$statement->execute();

		return (bool)$statement->fetch();
	}
}
