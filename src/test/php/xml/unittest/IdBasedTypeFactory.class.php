<?php namespace xml\unittest;

/**
 * Type factory
 */
#[@xmlmapping(factory= 'forName', pass= ['@id'])]
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
      case 'dialog': return \lang\XPClass::forName('xml.unittest.DialogType');
      case 'button': return \lang\XPClass::forName('xml.unittest.ButtonType');
      default: throw new \lang\IllegalArgumentException('Unknown attribute "'.$id.'"');
    }
  }
}
