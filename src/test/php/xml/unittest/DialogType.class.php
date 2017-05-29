<?php namespace xml\unittest;

/**
 * Test class for Marshaller / Unmarshaller tests
 *
 * @see  xp://xml.unittest.UnmarshallerTest
 * @see  xp://xml.unittest.MarshallerTest
 * @see  rfc://0040
 */
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
  #[@xmlmapping(element= 'caption')]
  public function setCaption($caption) {
    $this->caption= $caption;
  }

  /**
   * Get caption
   *
   * @return  string caption
   */
  #[@xmlfactory(element= 'caption')]
  public function getCaption() {
    return $this->caption;
  }
  
  /**
   * Add a button
   *
   * @param   xml.unittest.ButtonType $button
   * @return  xml.unittest.ButtonType the added button
   */
  #[@xmlmapping(element= 'button', class= 'xml.unittest.ButtonType')]
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
  #[@xmlfactory(element= 'button')]
  public function getButtons() {
    return $this->buttons;
  }
  
  /**
   * Set flags
   *
   * @param   string $flag1
   * @param   string $flag2
   */
  #[@xmlmapping(element= 'flags', pass= ['substring-before(., "|")', 'substring-after(., "|")'])]
  public function setFlags($flag1, $flag2) {
    $this->flags= [$flag1, $flag2];
  }
  
  /**
   * Get flags
   *
   * @return string[]
   */
  #[@xmlfactory(element= 'flags')]
  public function getFlags() {
    return $this->flags;
  }
  
  /**
   * Set options
   *
   * @param   string $name
   * @param   string $value
   */
  #[@xmlmapping(element= 'options/option', pass= ['@name', '@value'])]
  public function setOptions($name, $value) {
    $this->options[$name]= $value;
  }
  
  /**
   * Get options
   *
   * @return [:string]
   */
  #[@xmlfactory(element= 'options')]
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
