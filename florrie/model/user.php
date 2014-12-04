<?php
/*
	User Model
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


require_once $_SERVER['DOCUMENT_ROOT'].'/florrie/lib/auth.php';


class UserModel {


	//========================================
	// Public methods
	//========================================


	//----------------------------------------
	// Constructor
	//----------------------------------------
	public function __construct($db) {

		// Save the database connection for later
		$this->db = $db;
	}


	//----------------------------------------
	// Add a user to the system
	//----------------------------------------
	public function addUser($user, $desc, $pass = false) {

		$q = <<<Q
INSERT INTO users (
	user,
	pass,
	display
)
VALUES (
	:user,
	:pass,
	:display
)	
Q;

		$statement = $this->db->prepare($q);
		$statement->bindValue(':user', $user, PDO::PARAM_STR);
		$statement->bindValue(':display', $desc, PDO::PARAM_STR);

		if($pass) {

			// If password is present, hash it before saving
			$pass = hashPassword($pass);
			$statement->bindValue(':pass', $pass, PDO::PARAM_STR);
		}
		else {

			$statement->bindValue(':pass', null, PDO::PARAM_NULL);
		}

		$statement->execute();
	}


	//----------------------------------------
	// Authenticate a user against the DB
	//----------------------------------------
	public function authenticateUser($user, $pass) {

		// First, we're going to need user details
		$user = $this->getUser($user);

		// Throw exception if user hasn't been found
		if(!is_object($user) || empty($user->pass)) {

			throw new AuthException('User does not exist');
		}

		// Check password hash, and return user if it matches
		$pass = hashPassword($pass, $user->pass);

		if(hash_equals($user->pass, $pass)) {

			return $user;
		}

		// Password doesn't match - throw an error!
		throw new AuthException('authenticateUser failed!');	
	}


	//----------------------------------------
	// Update user details
	//----------------------------------------
	public function updateUser($userObj) {

		$q = <<<Q
UPDATE users
SET
	user = :user,
	pass = :pass,
	display = :display
WHERE
	id = :id
Q;

		$statement = $this->db->prepare($q);

		$statement->bindValue(':user', $userObj->user, PDO::PARAM_STR);
		$statement->bindValue(':display', $userObj->display, PDO::PARAM_STR);
		$statement->bindValue(':pass', $userObj->pass, PDO::PARAM_STR);
		$statement->bindValue(':id', $userObj->id, PDO::PARAM_STR);

		$statement->execute();
	}



	//----------------------------------------
	// Get a user's data for the database
	//----------------------------------------
	public function getUser($user) {

		$q = <<<Q
SELECT
	user,
	pass,
	display
FROM users
WHERE
	user LIKE :user
LIMIT 0, 1
Q;

		$statement = $this->db->prepare($q);
		$statement->bindValue(':user', $user);
		$statement->execute();

		$user = $statement->fetch();

		if(is_object($user)) {

			return $user;
		}

		return null;
	}
}
?>
