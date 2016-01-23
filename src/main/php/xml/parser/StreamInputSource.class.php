<?php namespace xml\parser;



/**
 * Input source
 *
 * @see      xp://xml.parser.XMLParser#parse
 */
class StreamInputSource extends \lang\Object implements InputSource {
  protected
    $stream = null,
    $source = '';
 
  /**
   * Constructor.
   *
   * @param   io.streams.InputStream input
   * @param   string source
   */
  public function __construct(\io\streams\InputStream $input, $source= '(stream)') {
    $this->stream= $input;
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
