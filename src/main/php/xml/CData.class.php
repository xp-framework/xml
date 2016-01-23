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
 *
 * @purpose  Wrapper
 */
class CData extends \lang\Object {
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
}
