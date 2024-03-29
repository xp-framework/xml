<?php namespace xml\unittest;

use io\FileNotFoundException;
use lang\{ElementNotFoundException, IllegalArgumentException};
use test\verify\Runtime;
use test\{Assert, Expect, Test};
use xml\{DomXSLProcessor, TransformerException, Xslmethod};

#[Runtime(extensions: ['dom', 'xsl'])]
class DomXslProcessorTest extends AbstractProcessorTest {

  /**
   * Returns the XSL processor instance to be used
   *
   * @return  xml.IXSLProcessor
   */
  public function processorInstance() {
    return new DomXSLProcessor();
  }

  /**
   * Returns the XSL processor's default output charset
   *
   * @return  string
   */
  public function processorCharset() { 
    return 'utf-8';
  }
  
  /**
   * Callback method without xslmethod annotation
   *
   * @return  string
   */
  public function nonXslMethod() {
    return '@@ILLEGAL@@';
  }
  
  /**
   * Callback method
   *
   * @return  string
   */
  #[Xslmethod]
  public function XslMethod() {
    return '@@SUCCESS@@';
  }
  
  #[Test]
  public function callXslHook() {
    $this->processor->registerInstance('proc', $this);
    $this->processor->setXMLBuf('<document/>');
    $this->processor->setXslBuf('<?xml version="1.0"?>
      <xsl:stylesheet
       version="1.0"
       xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
       xmlns:php="http://php.net/xsl"
      >
        <xsl:template match="/">
          <xsl:value-of select="php:function(\'XSLCallback::invoke\', \'proc\', \'XslMethod\')"/>
        </xsl:template>
      </xsl:stylesheet>
    ');
    $this->processor->run();
  }
  
  #[Test, Expect(ElementNotFoundException::class)]
  public function callNonXslHook() {
    $this->processor->registerInstance('proc', $this);
    $this->processor->setXMLBuf('<document/>');
    $this->processor->setXslBuf('<?xml version="1.0"?>
      <xsl:stylesheet
       version="1.0"
       xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
       xmlns:php="http://php.net/xsl"
      >
        <xsl:template match="/">
          <xsl:value-of select="php:function(\'XSLCallback::invoke\', \'proc\', \'nonXslMethod\')"/>
        </xsl:template>
      </xsl:stylesheet>
    ');
    $this->processor->run();
  }
  
  #[Test, Expect(IllegalArgumentException::class)]
  public function callNonRegisteredInstance() {
    $this->processor->setXMLBuf('<document/>');
    $this->processor->setXslBuf('<?xml version="1.0"?>
      <xsl:stylesheet
       version="1.0"
       xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
       xmlns:php="http://php.net/xsl"
      >
        <xsl:template match="/">
          <xsl:value-of select="php:function(\'XSLCallback::invoke\', \'notregistered\', \'irrelevant\')"/>
        </xsl:template>
      </xsl:stylesheet>
    ');
    $this->processor->run();
  }
  
  /**
   * Test error handling
   *
   */
  #[Test, Expect(TransformerException::class)]
  public function malformedXML() {
    $this->processor->setXMLBuf('@@MALFORMED@@');
  }
  
  /**
   * Test error handling
   *
   */
  #[Test, Expect(TransformerException::class)]
  public function malformedXSL() {
    $this->processor->setXSLBuf('@@MALFORMED@@');
  }
  
  /**
   * Test that errors in libxml error stack do not affect XSL processor
   * instances created before the error occurs
   *
   */
  #[Test]
  public function errorStackDoesNotAffectProcessorCreatedBefore() {
    $i= $this->processorInstance();
  
    // Fill up error stack artificially
    $doc= new \DOMDocument();
    $doc->loadXML('@@MALFORMED@@');
    
    // Should work
    $i->setXMLBuf('<document/>');
    $i->setXSLBuf('
      <xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
        <xsl:output method="xml" encoding="utf-8"/>
        <xsl:template match="/">
          <b>Hello</b>
        </xsl:template>
      </xsl:stylesheet>
    ');
    $i->run();
    $this->assertXmlEquals('<b>Hello</b>', $i->output());
  }

  /**
   * Test that errors in libxml error stack do not affect XSL processor
   * instances created after the error occurs
   *
   */
  #[Test]
  public function errorStackDoesNotAffectProcessorCreatedAfter() {
  
    // Fill up error stack artificially
    $doc= new \DOMDocument();
    $doc->loadXML('@@MALFORMED@@');
    
    // Should work
    $i= $this->processorInstance();
    $i->setXMLBuf('<document/>');
    $i->setXSLBuf('
      <xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
        <xsl:output method="xml" encoding="utf-8"/>
        <xsl:template match="/">
          <b>Hello</b>
        </xsl:template>
      </xsl:stylesheet>
    ');
    $i->run();
    $this->assertXmlEquals('<b>Hello</b>', $i->output());
  }

  /**
   * Test that cleared errors in libxml error stack do not affect 
   * errors occurring within a transformation
   *
   */
  #[Test]
  public function errorStackDoesNotAffectErrorHandling() {
  
    // Fill up error stack artificially
    $doc= new \DOMDocument();
    $doc->loadXML('@@MALFORMED@@');
    
    // Should work
    $i= $this->processorInstance();
    try {
      $i->setXMLBuf('<document>&nbsp;</document>');
      $this->fail('Malformed XML did not trigger exception');
    } catch (\xml\TransformerException $e) {
      Assert::true((bool)strstr($e->getMessage(), "Entity 'nbsp' not defined"));
    }
  }
  
  #[Test]
  public function defaultCallbacks() {

    // Should work
    $this->processor->setXMLBuf('<document><string>lower string</string></document>');
    $this->processor->setXSLBuf('
      <xsl:stylesheet version="1.0"
       xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
       xmlns:php="http://php.net/xsl"
       exclude-result-prefixes="php"
      >
        <xsl:output method="xml" encoding="utf-8"/>
        <xsl:template match="/document">
          <i><xsl:value-of select="php:function(\'XSLCallback::invoke\', \'xp.string\', \'strtoupper\', string(string))"/></i>
        </xsl:template>
      </xsl:stylesheet>
    ');
    $this->processor->run();
    $this->assertXmlEquals('<i>LOWER STRING</i>', $this->processor->output());
  }
  
  #[Test]
  public function setXSLDoc() {
    $doc= new \DOMDocument();
    $doc->loadXML('
      <xsl:stylesheet 
       version="1.0" 
       xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
        <xsl:output method="text"/>
        <xsl:template match="/*"><xsl:value-of select="name(.)"/></xsl:template>
      </xsl:stylesheet>
    ');
    $this->processor->setXSLDoc($doc);
  }
  
  #[Test]
  public function setXMLDoc() {
    $doc= new \DOMDocument();
    $doc->loadXML('<document/>');
    $this->processor->setXMLDoc($doc);
  }
  
  #[Test]
  public function processDocuments() {
    $this->setXSLDoc();
    $this->setXMLDoc();
    $this->processor->run();
    Assert::equals('document', $this->processor->output());
  }
}