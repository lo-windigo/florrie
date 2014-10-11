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



// TODO: File context
function fileContext($config) {

	return $_SERVER['DOCUMENT_ROOT'].'/';
}

//----------------------------------------
// Add or overwrite a file
//----------------------------------------
function writeFile($config, $filePath, $data) {

	// Assemble the full path
	$fullPath = fileContext($config).$filePath;

	// Use return value to determine success
	if(file_put_contents($fullPath, $data) === false) {

		$error = 'File write failed: "'.$fullPath.'"';

		throw new ServerException($error);
	}
}


//----------------------------------------
// Delete a file
//----------------------------------------
function deleteFile($config, $filePath) {

	// Assemble the full path
	$fullPath = fileContext($config).$filePath;

	// Catch PHP warnings thrown by unlink (Boo, PHP. Boo)
	set_error_handler(function() use ($filePath) {

		$error = 'Cannot delete file "'.$filePath.'"';

		throw new ServerException($error);
	});

	unlink($fullPath);

	restore_error_handler();
}


// Get a file uploaded through a HTML form
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
