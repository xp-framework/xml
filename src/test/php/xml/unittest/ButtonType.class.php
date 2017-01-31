<?php namespace xml\unittest;

/**
 * Test class for Marshaller / Unmarshaller tests. Used by
 * DialogType.
 *
 * @see  xp://xml.unittest.DialogType
 */
class ButtonType extends \lang\Object {
  public $id= '';
  public $caption= '';

  /**
   * Set ID
   *
   * @param   string $id
   */
  #[@xmlmapping(element= '@id')]
  public function setId($id) {
    $this->id= $id;
  }

  /**
   * Get ID
   *
   * @return  string id
   */
  #[@xmlfactory(element= '@id')]
  public function getId() {
    return $this->id;
  }

  /**
   * Set caption
   *
   * @param   string $caption
   */
  #[@xmlmapping(element= '.')]
  public function setCaption($caption) {
    $this->caption= $caption;
  }

  /**
   * Get caption
   *
   * @return  string caption
   */
  #[@xmlfactory(element= '.')]
  public function getCaption() {
    return $this->caption;
  }  
}
