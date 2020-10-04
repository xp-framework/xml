<?php namespace xml\unittest;

use xml\{Xmlfactory, Xmlmapping};

/**
 * Test class for Marshaller / Unmarshaller tests. Used by
 * DialogType.
 *
 * @see  xp://xml.unittest.DialogType
 */
class ButtonType {
  public $id= '';
  public $caption= '';

  /**
   * Set ID
   *
   * @param   string $id
   */
  #[Xmlmapping(['element' => '@id'])]
  public function setId($id) {
    $this->id= $id;
  }

  /**
   * Get ID
   *
   * @return  string id
   */
  #[Xmlfactory(['element' => '@id'])]
  public function getId() {
    return $this->id;
  }

  /**
   * Set caption
   *
   * @param   string $caption
   */
  #[Xmlmapping(['element' => '.'])]
  public function setCaption($caption) {
    $this->caption= $caption;
  }

  /**
   * Get caption
   *
   * @return  string caption
   */
  #[Xmlfactory(['element' => '.'])]
  public function getCaption() {
    return $this->caption;
  }  
}