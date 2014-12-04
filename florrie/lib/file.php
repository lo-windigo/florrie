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


//----------------------------------------
// Get a file uploaded through a HTML form
//----------------------------------------
function processFileUpload($config, $index, $fileDir, $fileName, $fileCheck = false) {

	// Check to make sure the form's been filled out
	if(empty($_FILES[$index]) ||
	   empty($_FILES[$index]['tmp_name']) ||
	   $_FILES[$index]['error'] !== UPLOAD_ERR_OK) {

		throw new FormException('Upload field was empty');
	}

	// If the file uploaded successfully:
	// - $_FILES[x]['error'] is set to UPLOAD_ERR_OK if upload was successful,
	//   otherwise an error constant
	// - is_uploaded_file() returns TRUE if file has been uploaded via form
	if(!is_uploaded_file($_FILES[$index]['tmp_name'])) {

		throw new FormException('File upload failed - problem uploading');
	}

	// Prepare the filename; pull out any directory shennanigans first
	$fileName = basename($fileName);

	// Append the original file extension, if it exists
	$ext = strrchr($_FILES[$index]['name'], '.');

	if($ext) {
		$fileName .= $ext;
	}

	// Glue the file path together
	$fullPath = fileContext($config).$fileDir.'/'.$fileName;

	// Move file to it's final location
	// TODO: Does this work with the FTP wrapper? I friggin' hope so.
	move_uploaded_file($_FILES[$index]['tmp_name'], $fullPath);

	// TODO: Hey, you'd better resize this image


	// Return the final filename/path for use later
	return $fileName;
}
?>
