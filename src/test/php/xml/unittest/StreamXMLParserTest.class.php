<?php namespace xml\unittest;

use io\streams\MemoryInputStream;
use xml\parser\StreamInputSource;

/**
 * Tests XML parser API with io.streams.InputStream source
 *
 * @see  xp://xml.unittest.AbstractXMLParserTest
 */
class StreamXMLParserTest extends AbstractXMLParserTest {
  
  /**
   * Returns an XML document by prepending the XML declaration to 
   * the given string and returning it.
   *
   * @param   string $str
   * @param   bool $decl default TRUE
   * @return  xml.parser.InputSource XML the source XML
   */
  protected function source($str, $decl= true) {
    return new StreamInputSource(
      new MemoryInputStream(($decl ? '<?xml version="1.0" encoding="utf-8"?>' : '').$str),
      $this->name.' test'
    );
  }
}