<?php namespace xml\parser;

use io\File;
use io\streams\FileInputStream;


/**
 * Input source
 *
 * @see      xp://xml.parser.XMLParser#parse
 */
class FileInputSource implements InputSource {
  protected
    $file   = null,
    $stream = null;
 
  /**
   * Constructor.
   *
   * @param   io.File file
   */
  public function __construct(File $file) {
    $this->file= $file;
    $this->stream= new FileInputStream($this->file);
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
    return $this->file->getURI();
  }

  /**
   * Creates a string representation of this InputSource
   *
   * @return  string
   */
  public function toString() {
    return nameof($this).'<'.$this->file->toString().'>';
  }
}