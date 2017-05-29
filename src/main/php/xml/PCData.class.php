<?php namespace xml;

/**
 * PCData allows to insert literal XML into a nodes contents.
 *
 * Example:
 * <code>
 *   $tree= new Tree();
 *   $tree->addChild(new Node('data', new PCData('Hello<br/>World')));
 * </code>
 *
 * The output will then be:
 * <pre>
 *   <document>
 *     <data>Hello<br/>World</data>
 *   </document>
 * </pre>
 *
 * Note: The XML passed to PCDatas constructor is not validated!
 * Passing incorrect XML to this class will result in a not-
 * wellformed output document.
 */
class PCData implements \lang\Value {
  public $pcdata= '';
    
  /**
   * Constructor
   *
   * @param   string pcdata
   */
  public function __construct($pcdata) {
    $this->pcdata= $pcdata;
  }

  /**
   * Creates a string representation of this object
   *
   * @return  string
   */
  public function toString() {
    return nameof($this).'('.$this->pcdata.')';
  }

  /** @return string */
  public function hashCode() {
    return 'C'.md5($this->pcdata);
  }

  /**
   * Compare this tree to a given value
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self ? strcmp($this->pcdata, $value->pcdata) : 1;
  }
}
