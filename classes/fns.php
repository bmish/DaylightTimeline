<?php
function getImageNames($dirName) {
	if (!is_dir($dirName)) {
		return array();
	}
	
	$imageNames = array();
	if ($handle = opendir($dirName)) {
	    while (false !== ($entry = readdir($handle))) {
			$filePath = $dirName.'/'.$entry;
			if (is_file($filePath) && isImage($filePath)) {
				$imageNames[] = $entry;
			}
	    }
	}
	
	return $imageNames;
}

function isImage($filePath) {
	return getimagesize($filePath) !== false;
}

function imageNamesToCamImages($imageNames) {
	$camImages = array();
	for($ind = 0; $ind < count($imageNames); $ind++) {
		$camImages[] = new CamImage($imageNames[$ind]);
	}
	
	return $camImages;
}

function getCamImages() {
	global $SNAPSHOT_DIR_NAME;
	
	$imageNames = getImageNames($SNAPSHOT_DIR_NAME);
	return imageNamesToCamImages($imageNames);
}

function getNewestCamImageInArray($camImages) {
	if (empty($camImages)) {
		return new CamImage("");
	}
	
	$newestCamImage = $camImages[0];
	for ($ind = 1; $ind < count($camImages); $ind++) {
		if ($camImages[$ind]->getDate() > $newestCamImage->getDate()) {
			$newestCamImage = $camImages[$ind];
	    }
	}
	
	return $newestCamImage;
}
?>