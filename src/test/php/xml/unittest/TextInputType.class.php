<?php namespace xml\unittest;

use xml\{Xmlfactory, Xmlmapping};

class TextInputType {
  protected $id= '';
  protected $disabled= false;

  /**
   * Cast to a bool
   *
   * @param   string $string
   * @return  bool
   */
  public function asBool($string) {
    switch ($string) {
      case 'true': return true;
      case 'false': return false;
      default: throw new \lang\IllegalArgumentException('Unrecognized boolean value '.$value);
    }
  }

  /**
   * Cast to a string
   *
   * @param   bool bool
   * @return  string
   */
  public function toBool($bool) {
    return $bool ? 'true' : 'false';
  }

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
   * Set disabled
   *
   * @param   bool $disabled
   */
  #[Xmlmapping(['element' => '@disabled', 'cast' => 'asBool'])]
  public function setDisabled($disabled) {
    $this->disabled= $disabled;
  }

  /**
   * Get disabled
   *
   * @return  bool disabled
   */
  #[Xmlfactory(['element' => '@disabled', 'cast' => 'toBool'])]
  public function getDisabled() {
    return $this->disabled;
  }
}