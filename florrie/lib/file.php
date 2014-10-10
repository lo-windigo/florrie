<?php
/*
	File Abstraction Layer
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


//----------------------------------------
// Constants
//----------------------------------------
define('FLORRIE_FS', 'fs');
define('FLORRIE_FTP', 'ftp');



//----------------------------------------
// Get the preferred filesystem method
//----------------------------------------
function fileMethod($config) {

	// TODO: Parse config, use FTP if present
	return FLORRIE_FS;
}


//----------------------------------------
// Add or overwrite a file
//----------------------------------------
function writeFile($config, $name, $data) {

	if(fileMethod($config) === FLORRIE_FS) {

		$success = file_put_contents($name, $data);

		if($success === false) {

			$error = 'File write failed: "'.$this->method.'"';

			throw new ServerException($error);
		}
	}
	//else if(fileMethod($config) === FLORRIE_FTP) {

		// TODO: FTP Support
	//}
	else {

		$error = 'Unsupported filesystem protocol: "'.fileMethod($config).'"';

		throw new ServerException($error);
	}
}


//----------------------------------------
// Delete a file
//----------------------------------------
function deleteFile($config, $name) {

	if(fileMethod($config) === FLORRIE_FS) {

		// Catch PHP warnings (Boo, PHP. Boo)
		set_error_handler(function() {

			$error = 'Cannot delete file "'.$name.
				'" with method "'.fileMethod($config).'"';

			throw new ServerException($error);
		});

		unlink($name);

		restore_error_handler();
	}
	//else if(fileMethod($config) === FLORRIE_FTP) {

		// TODO: FTP Support
	//}
	else {

		$error = 'Unsupported filesystem protocol: "'.fileMethod($config).'"';

		throw new ServerException($error);
	}
}


// TODO: getUploadedFile
function getUploadedFile($index) {

	// Check to make sure the form's been filled out
	if(empty($_FILES[$index]) || empty($_FILES[$index]['error']) ||
		empty($_FILES[$index]['tmp_name'])))
	{
		throw new exception('Missing file upload data');
	}

	// If the file uploaded successfully
	($_FILES[$index]['error'] == UPLOAD_ERR_OK &&
		is_uploaded_file($_FILES[$index]['tmp_name'])) or
		throw new exception('File upload failed');

	($formFile = fopen($_FILES[$index]['tmp_name'], 'r')) or
		throw new exception('Cannot access uploaded file');

	// Try to save the file to the filesystem
	$this->SaveFile($formFile, $folder, $filename);
}
?>
