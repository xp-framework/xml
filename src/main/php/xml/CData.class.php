<?php namespace xml;

/**
 * CData allows to insert a CDATA section:
 *
 * Example:
 * <code>
 *   $tree= new Tree();
 *   $tree->addChild(new Node('data', new CData('<Hello World>')));
 * </code>
 *
 * The output will then be:
 * <pre>
 *   <document>
 *     <data><![CDATA[<Hello World>]]></data>
 *   </document>
 * </pre>
 */
class CData implements \lang\Value {
  public $cdata= '';
    
  /**
   * Constructor
   *
   * @param   string cdata
   */
  public function __construct($cdata) {
    $this->cdata= $cdata;
  }

  /**
   * Creates a string representation of this object
   *
   * @return  string
   */
  public function toString() {
    return nameof($this).'('.$this->cdata.')';
  }

  /** @return string */
  public function hashCode() {
    return 'C'.md5($this->cdata);
  }

  /**
   * Compare this tree to a given value
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self ? strcmp($this->cdata, $value->cdata) : 1;
  }
}