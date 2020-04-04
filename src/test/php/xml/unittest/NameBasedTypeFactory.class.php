<?php namespace xml\unittest;

use lang\IllegalArgumentException;
use lang\XPClass;

#[@xmlmapping(['factory' => 'forName'])]
class NameBasedTypeFactory {
  
  /**
   * Factory method
   *
   * @param   string $name
   * @return  lang.XPClass
   * @throws  lang.IllegalArgumentException
   */
  public static function forName($name) {
    switch ($name) {
      case 'dialog': return XPClass::forName('xml.unittest.DialogType');
      case 'button': return XPClass::forName('xml.unittest.ButtonType');
      default: throw new IllegalArgumentException('Unknown tag "'.$name.'"');
    }
  }
}
