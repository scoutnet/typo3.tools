#!/usr/bin/php

<?php
/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2017 Andre Flemming <daslampe(at)lano-crew.org>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
$module = $argv[1];
$tca_file = $argv[2];

/*
 * ScoutNet.de-Special
 * Plugins in development named plugins.typo3.module_name and linked as module_name
 */
if (!isset($argv[3])) {
    $module_dir = $module;
} else {
    $module_dir = $argv[3];
}

define("TYPO3_MODE", "generator"); //Pass Access denied. ;)


/**
 * @param string $module_dir
 * @param string $file
 * @return mixed[] TCA-Array
 */
function getTCAArray($module_dir, $file)
{
    $array = require_once(getcwd() . "/" . $module_dir . "/Configuration/TCA/" . $file . ".php");
    //New Typo3 TCA-file
    if (is_array($array)) {
        return $array;
    }

    //deal with old TCA-File
    require_once(getcwd() . "/" . $module_dir . "/Configuration/TCA/" . $file . ".php");
    foreach ($TCA as $key => $value) {
        return $TCA[$key];
    }

}

/**
 * @param string $prefix Mostly filename of TCA-File
 * @param string $module
 * @param array $array
 * @return mixed[] ID => Label, to generate XLIFF-File
 */
function parseTCAArray($prefix, $module, array &$array)
{
    $lang_array = array();

    //crtl
    //Isn't already in lang-file
    if (!preg_match("/^LLL:EXT:/", $array['ctrl']['title'])) {
        $lang_array[$prefix . '.title'] = $array['ctrl']['title'];
        //Manipulate TCA file
        $array['ctrl']['title'] = 'LLL:EXT:' . $module . '/Resources/Language/locallang.xlf:' . $prefix . '.title';
    }

    //columns
    foreach ($array['columns'] as $column => $value) {
        if (!preg_match("/^LLL:EXT:/", $value['label'])) {
            $lang_array[$prefix . '.' . $column] = $value['label'];
            //Manipulate TCA file
            $array['columns'][$column]['label'] = 'LLL:EXT:' . $module . '/Resources/Language/locallang.xlf:' . $prefix . '.' . $column;
        }
    }

    return $lang_array;
}

/**
 * @param string $product_name
 * @param array $lang_array
 */
function createXliffFile($product_name, array $lang_array)
{
    ?>
    <? xml version = "1.0" encoding = "UTF-8"?>
    <xliff version="1.0">
        <file source-language="en" datatype="plaintext" original="messages"
              date="<?php echo date("Y-m-d") . 'T' . date("H:i:s") . 'Z'; ?>"
              product-name="<?php echo $product_name; ?>">
            <header />
            <body>
            <?php foreach ($lang_array as $key => $value) { ?>
                <trans-unit id="<?php echo $key; ?>" xml:space="preserve">
            <source><?php echo $value; ?></source>
        </trans-unit>
            <?php } ?>
            </body>
        </file>
    </xliff>
    <?php
}

$tca_array = getTCAArray($module_dir, $tca_file);
$lang_array = parseTCAArray($tca_file, $module, $tca_array);

echo "################################################################################\r\n";
echo "File: typo3conf/" . basename($module_dir) . "/Resources/Private/Language/locallang.xlf\r\n";
echo "################################################################################\r\n";
createXliffFile($module, $lang_array);

echo "\r\n";
echo "################################################################################\r\n";
echo "File: typo3conf/" . basename($module_dir) . "/Configuration/TCA/" . $tca_file . ".php\r\n";
echo "################################################################################\r\n";
echo "<?php
return ";
var_export($tca_array);
echo ';';


