<?php
/*
	Error Raising/Logging
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


//----------------------------------------
// Base exception
//----------------------------------------

class FlorrieException extends exception {

	// Class members:
	// - priority: Numerical priority
	public $priority;

	// Set up the exception
	public function __construct($msg, $code=0, $exception=NULL) {

		parent::__construct($msg, $code, $exception);

		// Set a default priority
		$this->setPriority();

		// Handle error logging

	}


	// Get the priority for this exception
	public getPriority() {
		return $this->priority;
	}


	// Set the priority for this exception
	public setPriority($priority=E_USER_NOTICE) {

		if(is_int($priority)) {
			$this->priority = $priority;
		}
	}
}


//----------------------------------------
// Custom exceptions
//----------------------------------------

// Authentication exception
class AuthException extends FlorrieException {

	// A loggable message, not to be displayed to the user
	public $secureMessage;
}

// Database exception
class DBException extends FlorrieException {

	// Set up the exception
	public function __construct($msg, $code=0, $exception=NULL) {

		parent::__construct($msg, $code, $exception);

		// Set a default priority
		$this->setPriority(E_USER_WARNING);
	}


}


// Form validation errors
class FormException extends FlorrieException {

	// Contains any and all form data that needs to be passed back
	public $formData;
}

// Initialization exception
class InitException extends FlorrieException {

	// Set up the exception
	public function __construct($msg, $code=0, $exception=NULL) {

		parent::__construct($msg, $code, $exception);

		// Set a default priority
		$this->setPriority(E_USER_ERROR);
	}


}

// HTTP404 - file not found
class NotFoundException extends FlorrieException {}

// Florrie install not present/detected
class NotInstalledException extends FlorrieException {}

// An unrecoverable error
class ServerException extends FlorrieException {

	// Set up the exception
	public function __construct($msg, $code=0, $exception=NULL) {

		parent::__construct($msg, $code, $exception);

		// Set a default priority
		$this->setPriority(E_USER_ERROR);
	}
}

?>
