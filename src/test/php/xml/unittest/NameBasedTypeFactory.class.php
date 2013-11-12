<?php namespace xml\unittest;

/**
 * Type factory
 */
#[@xmlmapping(factory= 'forName')]
class NameBasedTypeFactory extends \lang\Object {
  
  /**
   * Factory method
   *
   * @param   string $name
   * @return  lang.XPClass
   * @throws  lang.IllegalArgumentException
   */
  public static function forName($name) {
    switch ($name) {
      case 'dialog': return \lang\XPClass::forName('xml.unittest.DialogType');
      case 'button': return \lang\XPClass::forName('xml.unittest.ButtonType');
      default: throw new \lang\IllegalArgumentException('Unknown tag "'.$name.'"');
    }
  }
}
