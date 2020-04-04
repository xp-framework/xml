<?php namespace xml\unittest;

use lang\IllegalArgumentException;
use lang\XPClass;

#[@xmlmapping(['factory' => 'forName', 'pass' => ['@id']])]
class IdBasedTypeFactory {
  
  /**
   * Factory method
   *
   * @param   string $id
   * @return  lang.XPClass
   * @throws  lang.IllegalArgumentException
   */
  public static function forName($id) {
    switch ($id) {
      case 'dialog': return XPClass::forName('xml.unittest.DialogType');
      case 'button': return XPClass::forName('xml.unittest.ButtonType');
      default: throw new IllegalArgumentException('Unknown attribute "'.$id.'"');
    }
  }
}
