<?php

/*

class.Diff.php

A class containing a diff implementation

Created by Stephen Morley - http://stephenmorley.org/ - and released under the
terms of the CC0 1.0 Universal legal code:

http://creativecommons.org/publicdomain/zero/1.0/legalcode

*/

// A class containing functions for computing diffs and formatting the output.
class DiffObject{

  // define the constants
  const UNMODIFIED = 'UNM';
  const DELETED    = 'DEL';
  const INSERTED   = 'ADD';
  const CHANGED    = 'CHG';

  /* Returns the diff for two strings. The return value is an array, each of
   * whose values is an array containing two values: a line (or character, if
   * $compareCharacters is true), and one of the constants DIFF::UNMODIFIED (the
   * line or character is in both strings), DIFF::DELETED (the line or character
   * is only in the first string), and DIFF::INSERTED (the line or character is
   * only in the second string). The parameters are:
   *
   * $string1           - the first string
   * $string2           - the second string
   * $compareCharacters - true to compare characters, and false to compare
   *                      lines; this optional parameter defaults to false
   */
  public static function compare($a, $b, &$diffArr=array(), $breadcrumps=''){
      
    if(is_object($a) && is_object($b)) {
        if($breadcrumps == ''){
            $breadcrumps .= get_class($a);
        }
        
        if(get_class($a)!=get_class($b)){
          $diffArr[] = self::createDiffRow(self::CHANGED, $breadcrumps, get_class($a), get_class($b));
          return $diffArr;
        }

        $keyExists = array();
        foreach($a as $key => $val) {
            $keyExists[$key] = true;

            if(!isset($b->$key)){
                $diffArr[] = self::createDiffRow(self::DELETED, self::getBreadcrump($breadcrumps, $a, $key), gettype($val), 'DEL');
            }else{
                self::compare($val, $b->$key, $diffArr, self::getBreadcrump($breadcrumps, $a, $key));
            }
        }
      
        foreach($b as $key => $val) {
            if(!isset($keyExists[$key])){
                $diffArr[] = self::createDiffRow(self::INSERTED, self::getBreadcrump($breadcrumps, $b, $key), '', 'NEW');
            }
        }
          
    }else if(is_array($a) && is_array($b)) {
        if($breadcrumps == ''){
            $breadcrumps .= 'array';
        }
        
        $keyExists = array();
        foreach($a as $key => $val) {
            
            $keyExists[$key] = true;
            
            if(!isset($b[$key])){
                $diffArr[] = self::createDiffRow(self::DELETED, self::getBreadcrump($breadcrumps, $a, $key), gettype($val), 'DEL');
            }else{
                self::compare($val, $b[$key], $diffArr, self::getBreadcrump($breadcrumps, $a, $key));
            }
        }
      
        foreach($b as $key => $val) {
            if(!isset($keyExists[$key])){
                $diffArr[] = self::createDiffRow(self::INSERTED, self::getBreadcrump($breadcrumps, $b, $key), '', 'NEW');
            }
        }
          
    }else{
        if($a !== $b){
            $diffArr[] = self::createDiffRow(self::CHANGED, sprintf('%s', $breadcrumps), $a, $b);
        }
    }

    return $diffArr;
  }
  
  public static function toTable($fDiffArr){
      $table = new Table($fDiffArr);
      
      $table->draw();
  }

    private static function createDiffRow($fState, $fBreadCrump, $fA, $fB){
        return array(
            'st' => $fState,
            'br' => $fBreadCrump,
            'a' =>  $fA,
            'b' => $fB
        );
    }

    private static function getBreadcrump($breadcrumps, $val, $key) {
        $postfix = '';
        
        if(is_object($val)){
            $postfix = '->' . $key;
        }else if(is_array($val)){
            $postfix = '[' . $key . ']';
        }
        
        return $breadcrumps . $postfix;
    }

}

class Table {
    protected $opentable = "\n<table cellspacing=\"0\" cellpadding=\"2\" border=\"1\">\n";
    protected $closetable = "</table>\n";
    protected $openrow = "\t<tr>\n";
    protected $closerow = "\t</tr>\n";

    function __construct($data) {
        $this->string = $this->opentable;
        $this->string .= $this->buildHeader($data);
        foreach ($data as $row) {
            $this->string .= $this->buildrow($row);
        }
        $this->string .= $this->closetable;
    }

    function buildHeader($data){
        $html = $this->openrow;
        
        $keyArr = array_keys($data[0]);
        foreach($keyArr as $key){
            $html .= $this->addHeader($key);
        }
        
        $html .= $this->closerow;
        return $html;
    }
    
    function addHeader($field, $style = "null") {
        if ($style == "null") {
            $html =  "\t\t<th>" . $field . "</th>\n";
        } else {
            $html = "\t\t<th class=\"" . $style . "\">"  . $field . "</th>\n";
        }
        return $html;
    }
    
    function addfield($field, $style = "null") {
        if ($style == "null") {
            $html =  "\t\t<td>" . $field . "</td>\n";
        } else {
            $html = "\t\t<td class=\"" . $style . "\">"  . $field . "</td>\n";
        }
        return $html;
    }

    function buildrow($row) {
        $html = $this->openrow;
        foreach ($row as $field) {
            $html .= $this->addfield($field);
        }
        $html .= $this->closerow;
        return $html;
    }

    function draw() {
        echo $this->string;
    }
}

?>
