<?php namespace net\xp_framework\unittest\xml;

use xml\XSLCallback;
use xml\DomXSLProcessor;
use xml\Node;
use util\Date;

/**
 * TestCase for XSL callbacks
 *
 * @see   xp://xml.XSLCallback
 * @see   xp://xml.xslt.XSLDateCallback
 * @see   xp://xml.xslt.XSLStringCallback
 */
#[@action([
#  new \unittest\actions\ExtensionAvailable('dom'),
#  new \unittest\actions\ExtensionAvailable('xsl')
#])]
class XslCallbackTest extends \unittest\TestCase {

  /**
   * Runs a transformation
   *
   * @param   string xml
   * @param   string callback
   * @param   string[] arguments
   * @param   string xslEncoding default 'utf-8'
   * @return  string
   */
  protected function runTransformation($xml, $callback, $arguments, $xslEncoding= 'utf-8') {
    sscanf($callback, '%[^:]::%s', $name, $method);
    $p= new DomXSLProcessor();
    $p->registerInstance('this', $this);
    $p->setXMLBuf($xml);
    $p->setXSLBuf(sprintf('<?xml version="1.0" encoding="%s"?>
      <xsl:stylesheet 
       version="1.0" 
       xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
       xmlns:php="http://php.net/xsl"
      >
        <xsl:output method="text"/>
        
        <xsl:template match="/">
          <xsl:value-of select="php:function(\'XSLCallback::invoke\', \'%s\', \'%s\'%s)"/>
        </xsl:template>
      </xsl:stylesheet>
      ',
      $xslEncoding,
      $name,
      $method,
      $arguments ? ', '.implode(', ', $arguments) : ''
    ));
    $p->run();
    return $p->output();
  }
  
  /**
   * Simple XSL callback method
   *
   * @param   string name default 'World'
   * @return  string
   */
  #[@xslmethod]
  public function sayHello($name= 'World') {
    return 'Hello '.$name;
  }

  /**
   * Simple XSL callback method
   *
   * @param   string in
   * @return  string
   */
  #[@xslmethod]
  public function uberCoder($in) {
    return '�bercoder='.$in;
  }
  
  #[@test]
  public function callSayHello() {
    $this->assertEquals('Hello Test', $this->runTransformation(
      '<document/>', 
      'this::sayHello',
      array("'Test'")
    ));
  }

  #[@test]
  public function callUberCoderFromUtf8XmlAndUtf8Xsl() {
    $this->assertEquals('Übercoder=Übercoder', $this->runTransformation(
      '<?xml version="1.0" encoding="utf-8"?><document/>', 
      'this::uberCoder',
      array("'Übercoder'"),   // Need this in utf-8 because XSL is in utf-8
      'utf-8'
    ));
  }

  #[@test]
  public function callUberCoderFromIso88591XmlAndUtf8Xsl() {
    $this->assertEquals('Übercoder=Übercoder', $this->runTransformation(
      '<?xml version="1.0" encoding="iso-8859-1"?><document/>', 
      'this::uberCoder',
      array("'Übercoder'"),   // Need this in utf-8 because XSL is in utf-8
      'utf-8'
    ));
  }

  #[@test]
  public function callUberCoderFromUtf8XmlAndIso88591Xsl() {
    $this->assertEquals('Übercoder=Übercoder', $this->runTransformation(
      '<?xml version="1.0" encoding="utf-8"?><document/>', 
      'this::uberCoder',
      array("'�bercoder'"),  // Need this in iso-8859-1 because XSL is in iso-8859-1
      'iso-8859-1'
    ));
  }

  #[@test]
  public function callUberCoderFromIso88591XmlAndIso88591Xsl() {
    $this->assertEquals('Übercoder=Übercoder', $this->runTransformation(
      '<?xml version="1.0" encoding="iso-8859-1"?><document/>', 
      'this::uberCoder',
      array("'�bercoder'"),  // Need this in iso-8859-1 because XSL is in iso-8859-1
      'iso-8859-1'
    ));
  }

  #[@test]
  public function callSayHelloOmittingOptionalParameter() {
    $this->assertEquals('Hello World', $this->runTransformation(
      '<document/>', 
      'this::sayHello',
      array()
    ));
  }

  #[@test, @expect('lang.IllegalArgumentException')]
  public function callOnNotRegisteredCallback() {
    $this->runTransformation('<irrelevant/>', 'not-registered::irrelevant', array());
  }

  #[@test, @expect('lang.ElementNotFoundException')]
  public function callNonXslMethod() {
    $this->runTransformation('<irrelevant/>', 'this::setUp', array());
  }

  #[@test, @expect('lang.ElementNotFoundException')]
  public function callNonExistantMethod() {
    $this->runTransformation('<irrelevant/>', 'this::nonExistantMethod', array());
  }

  #[@test]
  public function dateFormatCallback() {
    $date= new Date('2009-09-20 21:33:00');
    $this->assertEquals($date->toString('Y-m-d H:i:s T'), $this->runTransformation(
      Node::fromObject($date, 'date')->getSource(),
      'xp.date::format',
      array('string(/date/value)', "'Y-m-d H:i:s T'")
    ));
  }

  #[@test]
  public function dateFormatCallbackWithTZ() {
    $date= new Date('2009-09-20 21:33:00');
    $tz= new \util\TimeZone('Australia/Sydney');
    $this->assertEquals($date->toString('Y-m-d H:i:s T', $tz), $this->runTransformation(
      Node::fromObject($date, 'date')->getSource(),
      'xp.date::format',
      array('string(/date/value)', "'Y-m-d H:i:s T'", "'".$tz->getName()."'")
    ));
  }

  #[@test]
  public function dateFormatCallbackWithEmptyTZ() {
    $date= new Date('2009-09-20 21:33:00');
    $this->assertEquals($date->toString('Y-m-d H:i:s T'), $this->runTransformation(
      Node::fromObject($date, 'date')->getSource(),
      'xp.date::format',
      array('string(/date/value)', "'Y-m-d H:i:s T'", "''")
    ));
  }

  #[@test]
  public function dateFormatCallbackWithoutTZ() {
    $date= new Date('2009-09-20 21:33:00');
    $this->assertEquals($date->toString('Y-m-d H:i:s T'), $this->runTransformation(
      Node::fromObject($date, 'date')->getSource(),
      'xp.date::format',
      array('string(/date/value)', "'Y-m-d H:i:s T'")
    ));
  }

  #[@test]
  public function stringUrlencodeCallback() {
    $this->assertEquals('a+%26+b%3F', $this->runTransformation(
      '<url>a &amp; b?</url>',
      'xp.string::urlencode',
      array('string(/)')
    ));
  }

  #[@test]
  public function stringUrldecodeCallback() {
    $this->assertEquals('a & b?', $this->runTransformation(
      '<url>a+%26+b%3F</url>',
      'xp.string::urldecode',
      array('string(/)')
    ));
  }

  #[@test]
  public function stringReplaceCallback() {
    $this->assertEquals('Hello World!', $this->runTransformation(
      '<string>Hello Test!</string>',
      'xp.string::replace',
      array('string(/)', "'Test'", "'World'")
    ));
  }

  #[@test]
  public function stringNl2BrCallback() {
    $this->assertEquals("Line 1<br />\nLine 2", $this->runTransformation(
      "<string>Line 1\nLine 2</string>",
      'xp.string::nl2br',
      array('string(/)')
    ));
  }
}
