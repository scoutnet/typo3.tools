#!/usr/bin/php
<?php

$versions = json_decode(file_get_contents("https://get.typo3.org/json"),true );

$branch = '7';
$latest = $versions[$branch]['latest'];
$url = $versions[$branch]['releases'][$latest]['url']['tar'];
$fileName = "typo3_src-".$latest.".tar.gz";
$dirName = "typo3_src-".$latest;

echo "Current version of ".$branch." Branch is ".$latest."\n";

if (is_dir($dirName)) {
	echo "Already Downloaded\n";
	exit(2);
}

echo "Download url is: ".$url."\n";


$sc = getScreenSize();

DEFINE ("TW", $sc['width']);

# download file:
set_time_limit(0);
//This is the file where we save the    information
$fp = fopen (dirname(__FILE__) . '/'.$fileName, 'w+');
$ch = curl_init(str_replace(" ","%20",$url));

curl_setopt($ch, CURLOPT_TIMEOUT, 50);
curl_setopt($ch, CURLOPT_FILE, $fp); 
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, 'progress');
curl_setopt($ch, CURLOPT_NOPROGRESS, false);

// get curl response
curl_exec($ch); 

// clear progress
echo str_repeat(' ', TW)."\r";
echo "Download Done.\n";// Saved File to ".$fileName."\n";
curl_close($ch);
fclose($fp);


echo "Unpacking\n";
exec ('tar xzf '.$fileName);
echo "Delete ".$fileName."\n";
unlink($fileName);

if (is_link("public_html/typo3_src")) {
	echo "Delete old Symlink\n";
	unlink('public_html/typo3_src');
}
echo "Create Symlink\n";
exec ("ln -s ../".$dirName." public_html/typo3_src");


function progress($res, $size, $down) {
	if (TW > 0) {
		if($size > 0 && $down % (1024 * 100)) {
			$progress = "[".round($down/1024/1024, 1).' MB/'.round($size/1024/1024, 1)." MB]  ";
			$count = $down / $size * (TW - strlen($progress));
			echo $progress.str_repeat('.', $count)."\r";
		}
	} else {
		if ($size > 0 ) {//&& (($down / $size * 100) % 10) == 0) {
			echo "Download ".round($down / $size * 100)."%\n";
		}
	}
}

function getScreenSize() { 
	$settings = array( 
		'width' => '80',
		'height' => '24',
	);

	preg_match_all("/rows.([0-9]+);.columns.([0-9]+);/", strtolower(exec('stty -a |grep columns')), $output);
	if(sizeof($output) == 3) {
		$settings['height'] = intval($output[1][0]);
		$settings['width'] = intval($output[2][0]);
	}

	return $settings;
}
