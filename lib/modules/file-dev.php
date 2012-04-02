<?php	// File handling function

require_once('../scripts/common.php');	// require common bits
//require_once('errors.php');				// Include error logging functions



// DeleteFile() - Delete a file from the filesystem using FTP
function DeleteFile($folder, $filename)
{
	if(unlink('../../'.$folder.'/'.$filename))
	{
		return true;
	}
	else
	{
		if(DEBUG) {echo 'Deleting the file failed!';}
		return false;					// Wicked shit
	}
}



// SaveFile()
//	Saves an uploaded file to a specific folder via FTP
// Arguments:	index	 - Index of the POST variable that contains the file upload
//				folder	 - Existing folder where the file should be moved
//				filename - New name of the file
function SaveFile($index, $folder, $filename)
{
	// Check to make sure the form's been filled out
	if(!isset($_FILES[$index], $_FILES[$index]['error'], $_FILES[$index]['tmp_name']))
	{
		if(DEBUG)
		{
			echo 'Form File Upload 1: ';
			print_r($_FILES);
		}
		return false;					// Shit again
	}

	// If the temporary uploaded file exists
	if(!($_FILES[$index]['error'] == UPLOAD_ERR_OK && is_uploaded_file($_FILES[$index]['tmp_name'])))
	{
		if(DEBUG) {echo 'Form File Upload 2';}
		return false;					// Shit-tacular
	}

	// Try to upload the file
	if(!move_uploaded_file($_FILES[$index]['tmp_name'], '../../'.$folder.'/'.$filename))
	{
		if(DEBUG) {echo 'Form File Upload 3';}
		return false;
	}

	return true;
}	?>
