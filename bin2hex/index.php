<?php

/*
    Name    : bin2hex web gui
    Author  : Droid-MAX
    Created : 05/11/2020
    Update  : 05/11/2020
    Version : 0.1 beta

    This is a upload an convert php script for bin2hex. Allowing you to convert binary data files to intel hex format files via bin2hex.
	

*/

function logFile($logPath = '', $logName = null, $startingLogName = null)
{
	if (file_exists($logPath.$logName))
	{
		$fileSize = filesize($logPath.$logName);
		//if file size is 20MB or bigger
		if ($fileSize >= 20971520)
		{
			$newLogName = 'overflow.log';
			//$newLogName = $this->rename_if_exists($logPath,$startingLogName);
			$log = $logPath.$newLogName;
			$logName = $newLogName;
			$fh = fopen($log, "a") or die("can't open file");
			clearstatcache();
			chmod($log, 0777);
			clearstatcache();
		}
		else
		{
			$log = $logPath.$logName;
		}

	}
	else
	{
		$log = $logPath.$logName;
		fopen($log, 'a') or die("can't open file");
		clearstatcache();
		chmod($log, 0777);
		clearstatcache();
	}
	return array($log, $logPath, $logName);
}

function logText($file, $text){
	$log = fopen($file, 'a') or die("can't open file");
	fwrite($log, $text);
	fclose($log);
}

set_time_limit(0);
ini_set('display_errors','On');


// 20971520Byte = 20MB
$maxFileSize = 20971520;
$allowedMimeTypes = array('application/octet-stream');

$error 			= false;
$uploadSuccess 		= false;
$renderHTML5 		= false;

$newline 		= "\n";
$fileSplit 		= '_';
$timeStamp 		= time();
$filePath 		= realpath('./').'/';
$bin2hexCommand 	= '/usr/bin/bin2hex';
$webPath                = 'bin2hex/';
$uploadLocation 	= 'upload/';
$convertedLocation 	= 'converted/';


if (isset($_FILES) && $_FILES) {

	if (!is_writable($uploadLocation)) {
		echo "Upload dir needs write permission";
		/*
		# example file permissions fix for Apache users on Linux
		sudo chown -R www-data:www-data upload/
		sudo chmod -R 755 upload/
		*/
		exit;
	}

	// Custom Params
	$offsetAddr 		= isset($_POST['offset_addr']) 		? (string)$_POST['offset_addr'] : '0x8000000';
	$recordLength 		= isset($_POST['record_length'])	? (int)$_POST['record_length'] 	: '16';

	// Build up the bin2hex params from the values posted from the html form
	$customParams  = ' '.$offsetAddr; 				// Format the video size
	$customParams .= ' '.$recordLength; 		    // Format the video bit rate
	
	// Check the uploaded mime type
	if (in_array($_FILES["file"]["type"],$allowedMimeTypes ))
	{
		// Check the uploaded file size 
		if ($_FILES["file"]["size"] < $maxFileSize || $maxFileSize == 0) {
		
			if ($_FILES["file"]["error"] > 0)
			{
				$error =  "Return Code: " . $_FILES["file"]["error"];
			}
			else
			{
				$uploadSuccess  = "Upload: " . $_FILES["file"]["name"] . "<br />";
				$uploadSuccess .= "Type: " . $_FILES["file"]["type"] . "<br />";
				$uploadSuccess .= "Size: " . $_FILES["file"]["size"] . " bytes<br />";
				$uploadSuccess .= "Temp file: " . $_FILES["file"]["tmp_name"] . "<br />";
				$uploadedFilename = $timeStamp.$fileSplit.strtolower($_FILES["file"]["name"]);

				if (file_exists($uploadLocation . $uploadedFilename))
				{
					$error = 'Error - '.$uploadedFilename. " already exists. ";
				}
				else
				{

					move_uploaded_file($_FILES["file"]["tmp_name"],$uploadLocation . $uploadedFilename);
					$uploadSuccess .= "Stored in: " . $uploadLocation . $uploadedFilename;

					$file_uploaded = $uploadLocation . $uploadedFilename;
					$source_ext = pathinfo($uploadedFilename, PATHINFO_EXTENSION);

					// Try and make a folder for neater file structure.
					if(!file_exists($filePath.$convertedLocation.$timeStamp))
					{
						mkdir($filePath.$convertedLocation.$timeStamp);
						$uploadSuccess .= '<a href="'.$convertedLocation.$timeStamp.'/">View converted files</a>';
						$convertedLocation = $convertedLocation.$timeStamp.'/';
					}
					else
					{
						$uploadSuccess .= '<a href="'.$convertedLocation.'">View converted files</a>';
					}

					// Main func
					if (isset($_POST['offset_addr']) && isset($_POST['record_length'])) {

						$command = $bin2hexCommand.' '.$filePath.$file_uploaded.' '.$filePath.$convertedLocation.$uploadedFilename.$customParams.' 2>&1';
						exec($command, $output, $status);
						$output = 'File: '.$file_uploaded."\n".implode("\n", $output);
						// Log to file
						list($log, $logPath, $logName)=logFile($filePath.'logs/',date('d-m-Y').'.log');
						logText($logPath.$logName, $output);

					}

					// Check if we can render the html5
					if (isset($_POST['offset_addr']) && isset($_POST['record_length'])) {
						$renderHTML5 = true;
					}

					if ($renderHTML5) {
						// Render the HTML5
						$output_start = "<html><head></head><body>";
						$output .= '<strong>Download link:</strong> <a href="http://'.$_SERVER['HTTP_HOST'].'/'.$webPath.$convertedLocation.$uploadedFilename.'">HEX file</a>'.$newline;
						$output .= '</p>'.$newline;
						$output_end = '</body></html>'.$newline;

						list($log, $logPath, $logName)=logFile($filePath.$convertedLocation,$uploadedFilename.'.html');
						logText($logPath.$logName, $output_start.$output.$output_end);

						// redirect to the html
						header('Location: http://'.$_SERVER['HTTP_HOST'].'/'.$webPath.$convertedLocation.$uploadedFilename.'.html');
					}
				}
			}
		}
		else
		{
			$error = "Error - Invalid file size<br />";
			$error .= "Size: " . $_FILES["file"]["size"] . " bytes<br />";
		}
	}
	else
	{
		$error  = "Error - Invalid file type<br />";
		$error .= "Type: " . $_FILES["file"]["type"] . "<br />";
	}
}
?>
<html>
<head>
	<title>Bin2Hex GUI</title>
	<style type="text/css">
	.clear {
		clear:both;
	}
	.error {
		color:#f00;
		font-wight:bold;
		padding:10px;
	}
	.uploadSuccess {
		color:#45862d;
		font-wight:bold;
		padding:10px;
	}
	.fieldset {
		clear:both;
		padding:5px;
	}
	.fieldset label {
		width:200px;
		float:left;
	}
	.fieldset input {
		width:300px;
		float:left;
	}
	</style>
</head>
<body>

	<h1>bin2hex</h1>
	<?php
	if ($error) {
		echo '<div class="error">'.$error.'</div>';
	}
	if ($uploadSuccess) {
		echo '<div class="uploadSuccess">'.$uploadSuccess.'</div>';
	}
	?>

	<form action="" method="post" enctype="multipart/form-data">
		<div class="fieldset">
			<label for="file">Binary file:</label>
			<input type="file" name="file" id="file" accept="application/octet-stream,.bin" />
		</div>

		<div class="fieldset">
			<label for="file">Offset Address:</label>
			<input type="text" id="offset_addr" name="offset_addr" />
		</div>
		
		<div class="fieldset">
			<label for="file">Record Length:</label>
			<input type="text" id="record_length" name="record_length" />
		</div>

		<div class="clear"></div>

		<input type="submit" name="submit" value="Upload and convert" />

	</form>

</body>
</html>
