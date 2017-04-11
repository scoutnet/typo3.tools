<?php
namespace ScoutNet\Typo3Tools;

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
define("TYPO3_MODE", "generator"); //Pass Access denied. ;)

class generateTCAandExtTables
{
    protected $document_root = null;
    protected $typo3_src = null;
    protected $module_dir = null;
    protected $module = null;
    protected $class = null;

    public function __construct($arguments)
    {
        /**
         * @TODO: Use getopt
         */
        foreach ($arguments as $arg) {
            if (preg_match("/-docroot=(.*)/", $arg, $match)) {
                $this->document_root = $match[1];
            }
            if (preg_match("/-typo3src=(.*)/", $arg, $match)) {
                $this->typo3_src = $match[1];
            }
            if (preg_match("/-module-dir=(.*)/", $arg, $match)) {
                $this->module_dir = $match[1];
            }
            if (preg_match("/-class=(.*)/", $arg, $match)) {
                $this->class = $match[1];
            }
        }

        if(!isset($this->typo3_src) && !isset($this->document_root)) {
            throw new \Exception("typo3src or docroot required!");
        }

        if ($this->typo3_src == null) {
            $this->typo3_src = $this->document_root . "public_html/typo3_src/";
        }

        if(!isset($this->class)) {
            throw new \Exception("class is required!");
        }

        //Get module name
        $matches = explode("\\", $this->class);
        $this->module = substr($this->convertFieldname($matches[1]), 1);

        if ($this->module_dir == null) {
            $this->module_dir = $this->document_root . "public_html/typo3conf/ext/" . $this->module . "/";
        }

        include($this->typo3_src . '/vendor/autoload.php');
    }

    public function run() {
        $this->writeTCAFile($this->expandTCAFile($this->getVarAnnotation()));
        $this->writeExtTablesFile($this->getVarAnnotation());
    }


    private function getVarAnnotation($class = null)
    {
        if (file_exists($this->module_dir . 'Classes/Domain/Model/'.ucfirst($this->getModelname()).'.php') == false) {
            throw new \Exception("No file!");
        }
        require_once($this->module_dir . 'Classes/Domain/Model/'.ucfirst($this->getModelname()).'.php');
        $reflection = new \ReflectionClass($this->class);

        $fields = array();
        foreach ($reflection->getProperties(\ReflectionProperty::IS_PROTECTED) as $property) {
            //Only use properties from class not from parent
            if ($property->class == $reflection->getName()) {
                preg_match("/@var(.*)/", $property->getDocComment(), $annotation);
                $fields[] = array(
                    "name" => $property->name,
                    "type" => trim($annotation[1]),
                );
            }
        }

        return $fields;
    }

    private function expandTCAFile(array $fields)
    {
        $array = require_once(getcwd() . "/template_files/tx_module_domain_model_name.template");

        //Expand array with fields
        foreach ($fields as $field) {
            if (strtolower($field['type']) == "string" || strtolower($field['type']) == "integer" || strtolower($field['type']) == "double") {


                $array['columns'][$this->convertFieldname($field['name'])] = array(
                    'label' => $field['name'],
                    'config' => array(
                        'type' => 'input',
                        'size' => 20,
                        'max' => 256,
                        'eval' => 'required,trim',
                    ),
                );
            }
        }
        return $array;
    }

    private function writeTCAFile(array $tca_array) {
        $handle = fopen($this->module_dir."/Configuration/TCA/tx_".$this->removeUnderscore($this->module)."_domain_model_".$this->getModelname().".php", "w+");
        fwrite($handle, file_get_contents(getcwd().'/template_files/ext_access_denied.template'));
        fwrite($handle, "return ".var_export($tca_array, true).';');
        fclose($handle);
    }

    private function writeExtTablesFile(array $fields) {
        $content = file_get_contents(getcwd()."/template_files/ext_tables_table.template");

        $content = preg_replace('/---tablename---/', "tx_".$this->removeUnderscore($this->module)."_domain_model_".$this->getModelname(), $content);
        $content = preg_replace('/---properties---/', $this->fields2sql($fields), $content);

        $handle = fopen($this->module_dir."ext_tables.sql", "w+");
        fwrite($handle, $content);
        fclose($handle);
    }

    private function fields2sql(array $fields) {
        $sql = "";
        foreach($fields as $field) {
            $sql .= "  ".$this->convertFieldname($field['name']). " ";
            switch(strtolower($field['type'])) {
                case 'integer':
                case 'int':
                    $sql .= "INT(11)";
                    break;
                case 'string':
                    $sql .= "TEXT";
                    break;
                case 'double':
                    $sql .= "DOUBLE";
                    break;
            }
            $sql .= ",\n";
       }
       return $sql;
    }

    private function convertFieldname($fieldname) {
        return preg_replace_callback('/([A-Z])/',
            function($m) {
                return '_' . strtolower($m[1]);
            }, $fieldname);
    }

    private function removeUnderscore($string) {
        return str_replace("_", "", $string);
    }

    /**
     * @return string name of model, first char lowercase
     */
    private function getModelname() {
        return lcfirst(substr($this->class, strrpos($this->class, '\\')+1));
    }
}

try {
    $class = new generateTCAandExtTables($argv);
    return $class->run();
} catch(\Exception $e) {
    echo "An error occurs: ".$e->getMessage();
}