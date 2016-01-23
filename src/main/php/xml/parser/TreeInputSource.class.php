<?php namespace xml\parser;

use io\streams\MemoryInputStream;
use xml\Tree;


/**
 * Input source
 *
 * @see      xp://xml.parser.XMLParser#parse
 */
class TreeInputSource extends \lang\Object implements InputSource {
  protected
    $stream = null,
    $source = '';
 
  /**
   * Constructor.
   *
   * @param   xml.Tree input
   * @param   string source
   */
  public function __construct($input, $source= '(string)') {
    $this->stream= new MemoryInputStream($input->getSource(INDENT_DEFAULT));
    $this->source= $source;
  }

  /**
   * Get stream
   *
   * @return  io.streams.InputStream
   */
  public function getStream() {
    return $this->stream;
  }

  /**
   * Get source
   *
   * @return  string
   */
  public function getSource() {
    return $this->source;
  }

  /**
   * Creates a string representation of this InputSource
   *
   * @return  string
   */
  public function toString() {
    return nameof($this).'<'.$this->source.'>';
  }
}
