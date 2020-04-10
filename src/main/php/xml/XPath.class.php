<?php namespace xml;

use lang\IllegalArgumentException;

/**
 * XPath class
 *
 * ```php
 * use xml\XPath;
 * 
 * $xml= '<dialog id="file.open">
 *  <caption>Open a file</caption>
 *    <buttons>
 *      <button name="ok"/>
 *      <button name="cancel"/>
 *    </buttons>
 * </dialog>';
 *   
 * echo (new XPath($xml))->query('/dialog/buttons/button/@name'));
 * ```
 *
 * @ext      dom
 * @test     xp://xml.unittest.XPathTest
 * @purpose  Provide XPath functionality
 */
class XPath {
  public $context= null;

  static function __static() {
    libxml_use_internal_errors(true);
  }
  
  /**
   * Helper method
   *
   * @param   string xml
   * @return  php.DOMDocument
   * @throws  xml.XMLFormatException if the given XML is not well-formed or unparseable
   */
  protected function loadXML($xml) {
    $doc= new \DOMDocument();
    if (!$doc->loadXML($xml)) {
      $errors= libxml_get_errors();
      libxml_clear_errors();
      $e= new XMLFormatException(
        rtrim($errors[0]->message), 
        $errors[0]->code, 
        $errors[0]->file, 
        $errors[0]->line, 
        $errors[0]->column
      );
      \xp::gc(__FILE__);
      throw $e;
    }
    return $doc;
  }
  
  /**
   * Constructor
   *
   * @param  string|xml.Tree|php.DomDocument $arg
   * @throws lang.IllegalArgumentException
   * @throws xml.XMLFormatException in case the argument is a string and not valid XML
   */
  public function __construct($arg) {
    if ($arg instanceof \DOMDocument) {
      $this->context= new \DOMXPath($arg);
    } else if ($arg instanceof Tree) {
      $this->context= new \DOMXPath($this->loadXML(
        $arg->getDeclaration().$arg->getSource(INDENT_NONE)
      ));
    } else if (is_string($arg)) {
      $this->context= new \DOMXPath($this->loadXML($arg));
    } else {
      throw new IllegalArgumentException('Unsupported parameter type '.typeof($arg)->getName());
    }
  }
  
  /**
   * Execute xpath query and return results
   *
   * @param   string xpath
   * @param   php.dom.DOMNode node default NULL
   * @return  php.dom.DOMNodeList
   * @throws  xml.XPathException if evaluation fails
   */
  public function query($xpath, $node= null) {
    if ($node) {
      $r= $this->context->evaluate($xpath, $node);
    } else {
      $r= $this->context->evaluate($xpath);
    }
    if (false === $r) {
      throw new XPathException('Cannot evaluate "'.$xpath.'"');
    }
    return $r;
  }
}