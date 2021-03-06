<?php
/*
	Controller - Florrie Base Module
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


// Authentication exception
class AuthException extends exception {

	// A loggable message, not to be displayed to the user
	public $secureMessage;
}

// Database exception
class DBException extends exception {}

// Form validation errors
class FormException extends exception {

	// Contains any and all form data that needs to be passed back
	public $formData;
}

// Initialization exception
class InitException extends exception {}

// HTTP404 - file not found
class NotFoundException extends exception {}

// Florrie install not present/detected
class NotInstalledException extends exception {}

// An unrecoverable error
class ServerException extends exception {}

?>
