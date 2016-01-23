<?php namespace xml\parser;

use io\streams\MemoryInputStream;


/**
 * Input source
 *
 * @see      xp://xml.parser.XMLParser#parse
 */
class StringInputSource extends \lang\Object implements InputSource {
  protected
    $stream = null,
    $source = '';
 
  /**
   * Constructor.
   *
   * @param   string input
   * @param   string source
   */
  public function __construct($input, $source= '(string)') {
    $this->stream= new MemoryInputStream($input);
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
