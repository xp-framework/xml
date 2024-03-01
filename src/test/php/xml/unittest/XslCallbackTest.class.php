<?php namespace xml\unittest;

use lang\{ElementNotFoundException, IllegalArgumentException};
use test\verify\Runtime;
use test\{Assert, Expect, Test};
use util\Date;
use xml\{DomXSLProcessor, Node, XSLCallback, Xslmethod};

#[Runtime(extensions: ['dom', 'xsl'])]
class XslCallbackTest {

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
  #[Xslmethod]
  public function sayHello($name= 'World') {
    return 'Hello '.$name;
  }

  /**
   * Simple XSL callback method
   *
   * @param   string in
   * @return  string
   */
  #[Xslmethod]
  public function uberCoder($in) {
    return 'Übercoder='.$in;
  }
  
  #[Test]
  public function callSayHello() {
    Assert::equals('Hello Test', $this->runTransformation(
      '<document/>', 
      'this::sayHello',
      ["'Test'"]
    ));
  }

  #[Test]
  public function callUberCoderFromUtf8XmlAndUtf8Xsl() {
    Assert::equals('Übercoder=Übercoder', $this->runTransformation(
      '<?xml version="1.0" encoding="utf-8"?><document/>', 
      'this::uberCoder',
      ["'Übercoder'"],
      'utf-8'
    ));
  }

  #[Test]
  public function callUberCoderFromIso88591XmlAndUtf8Xsl() {
    Assert::equals('Übercoder=Übercoder', $this->runTransformation(
      '<?xml version="1.0" encoding="iso-8859-1"?><document/>', 
      'this::uberCoder',
      ["'Übercoder'"],
      'utf-8'
    ));
  }

  #[Test]
  public function callUberCoderFromUtf8XmlAndIso88591Xsl() {
    Assert::equals('Übercoder=Übercoder', $this->runTransformation(
      '<?xml version="1.0" encoding="utf-8"?><document/>', 
      'this::uberCoder',
      [iconv(\xp::ENCODING, 'iso-8859-1', "'Übercoder'")],
      'iso-8859-1'
    ));
  }

  #[Test]
  public function callUberCoderFromIso88591XmlAndIso88591Xsl() {
    Assert::equals('Übercoder=Übercoder', $this->runTransformation(
      '<?xml version="1.0" encoding="iso-8859-1"?><document/>', 
      'this::uberCoder',
      [iconv(\xp::ENCODING, 'iso-8859-1', "'Übercoder'")],
      'iso-8859-1'
    ));
  }

  #[Test]
  public function callSayHelloOmittingOptionalParameter() {
    Assert::equals('Hello World', $this->runTransformation(
      '<document/>', 
      'this::sayHello',
      []
    ));
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function callOnNotRegisteredCallback() {
    $this->runTransformation('<irrelevant/>', 'not-registered::irrelevant', []);
  }

  #[Test, Expect(ElementNotFoundException::class)]
  public function callNonXslMethod() {
    $this->runTransformation('<irrelevant/>', 'this::setUp', []);
  }

  #[Test, Expect(ElementNotFoundException::class)]
  public function callNonExistantMethod() {
    $this->runTransformation('<irrelevant/>', 'this::nonExistantMethod', []);
  }

  #[Test]
  public function dateFormatCallback() {
    $date= new Date('2009-09-20 21:33:00');
    Assert::equals($date->toString('Y-m-d H:i:s T'), $this->runTransformation(
      Node::fromObject($date, 'date')->getSource(),
      'xp.date::format',
      ['string(/date/value)', "'Y-m-d H:i:s T'"]
    ));
  }

  #[Test]
  public function dateFormatCallbackWithTZ() {
    $date= new Date('2009-09-20 21:33:00');
    $tz= new \util\TimeZone('Australia/Sydney');
    Assert::equals($date->toString('Y-m-d H:i:s T', $tz), $this->runTransformation(
      Node::fromObject($date, 'date')->getSource(),
      'xp.date::format',
      ['string(/date/value)', "'Y-m-d H:i:s T'", "'".$tz->name()."'"]
    ));
  }

  #[Test]
  public function dateFormatCallbackWithEmptyTZ() {
    $date= new Date('2009-09-20 21:33:00');
    Assert::equals($date->toString('Y-m-d H:i:s T'), $this->runTransformation(
      Node::fromObject($date, 'date')->getSource(),
      'xp.date::format',
      ['string(/date/value)', "'Y-m-d H:i:s T'", "''"]
    ));
  }

  #[Test]
  public function dateFormatCallbackWithoutTZ() {
    $date= new Date('2009-09-20 21:33:00');
    Assert::equals($date->toString('Y-m-d H:i:s T'), $this->runTransformation(
      Node::fromObject($date, 'date')->getSource(),
      'xp.date::format',
      ['string(/date/value)', "'Y-m-d H:i:s T'"]
    ));
  }

  #[Test]
  public function stringUrlencodeCallback() {
    Assert::equals('a+%26+b%3F', $this->runTransformation(
      '<url>a &amp; b?</url>',
      'xp.string::urlencode',
      ['string(/)']
    ));
  }

  #[Test]
  public function stringUrldecodeCallback() {
    Assert::equals('a & b?', $this->runTransformation(
      '<url>a+%26+b%3F</url>',
      'xp.string::urldecode',
      ['string(/)']
    ));
  }

  #[Test]
  public function stringReplaceCallback() {
    Assert::equals('Hello World!', $this->runTransformation(
      '<string>Hello Test!</string>',
      'xp.string::replace',
      ['string(/)', "'Test'", "'World'"]
    ));
  }

  #[Test]
  public function stringNl2BrCallback() {
    Assert::equals("Line 1<br />\nLine 2", $this->runTransformation(
      "<string>Line 1\nLine 2</string>",
      'xp.string::nl2br',
      ['string(/)']
    ));
  }
}