<?php namespace xml\unittest;

use xml\{Xmlfactory, Xmlmapping};

class DialogType {
  public
    $id       = '',
    $caption  = '',
    $buttons  = null,
    $flags    = [],
    $options  = [];

  /**
   * Constructor
   */
  public function __construct() {
    $this->buttons= create('new util.collections.Vector<xml.unittest.ButtonType>()');
  }

  /**
   * Set ID
   *
   * @param   string $id
   */
  #[Xmlmapping(element: '@id')]
  public function setId($id) {
    $this->id= $id;
  }

  /**
   * Get ID
   *
   * @return  string id
   */
  #[Xmlfactory(element: '@id')]
  public function getId() {
    return $this->id;
  }

  /**
   * Set caption
   *
   * @param   string $caption
   */
  #[Xmlmapping(element: 'caption')]
  public function setCaption($caption) {
    $this->caption= $caption;
  }

  /**
   * Get caption
   *
   * @return  string caption
   */
  #[Xmlfactory(element: 'caption')]
  public function getCaption() {
    return $this->caption;
  }
  
  /**
   * Add a button
   *
   * @param   xml.unittest.ButtonType $button
   * @return  xml.unittest.ButtonType the added button
   */
  #[Xmlmapping(element: 'button', class: 'xml.unittest.ButtonType')]
  public function addButton($button) {
    $this->buttons->add($button);
    return $button;
  }
  
  /**
   * Returns number of buttons
   *
   * @return  int
   */
  public function numButtons() {
    return $this->buttons->size();
  }

  /**
   * Returns button at a given position
   *
   * @param   int
   * @return  xml.unittest.ButtonType 
   */
  public function buttonAt($offset) {
    return $this->buttons->get($offset);
  }

  /**
   * Returns whether buttons exist
   *
   * @return  int
   */
  public function hasButtons() {
    return !$this->buttons->isEmpty();
  }
  
  /**
   * Retrieve this dialog's buttons
   *
   * @return  util.collections.Vector<xml.unittest.ButtonType>
   */
  #[Xmlfactory(element: 'button')]
  public function getButtons() {
    return $this->buttons;
  }
  
  /**
   * Set flags
   *
   * @param   string $flag1
   * @param   string $flag2
   */
  #[Xmlmapping(element: 'flags', pass: ['substring-before(., "|")', 'substring-after(., "|")'])]
  public function setFlags($flag1, $flag2) {
    $this->flags= [$flag1, $flag2];
  }
  
  /**
   * Get flags
   *
   * @return string[]
   */
  #[Xmlfactory(element: 'flags')]
  public function getFlags() {
    return $this->flags;
  }
  
  /**
   * Set options
   *
   * @param   string $name
   * @param   string $value
   */
  #[Xmlmapping(element: 'options/option', pass: ['@name', '@value'])]
  public function setOptions($name, $value) {
    $this->options[$name]= $value;
  }
  
  /**
   * Get options
   *
   * @return [:string]
   */
  #[Xmlfactory(element: 'options')]
  public function getOptions() {
    return $this->options;
  }
  
  /**
   * Returns whether another object is equal to this value object
   *
   * @param   var $cmp
   * @return  bool
   */
  public function equals($cmp) {
    return (
      $cmp instanceof self &&
      $this->id === $cmp->id &&
      $this->caption === $cmp->caption &&
      $this->options === $cmp->options &&
      $this->flags === $cmp->flags &&
      $this->buttons->equals($cmp->buttons)
    );
  }
}