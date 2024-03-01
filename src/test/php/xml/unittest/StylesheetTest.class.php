<?php namespace xml\unittest;

use unittest\Assert;
use unittest\Test;
use util\collections\Vector;
use xml\Stylesheet;

class StylesheetTest {

  /**
   * Helper method
   *
   * @param   xml.Node starting node
   * @param   string tagname
   * @return  util.collections.Vector<xml.Node>
   */
  protected function getElementsByTagName($node, $tagname) {
    $r= create('new util.collections.Vector<xml.Node>()');
    foreach (array_keys($node->getChildren()) as $key) {
      if ($tagname == $node->nodeAt($key)->getName()) {
        $r[]= $node->nodeAt($key);
      }
      if ($node->nodeAt($key)->hasChildren()) {
        $r->addAll($this->_getElementsByTagName(
          $node->nodeAt($key),
          $tagname
        ));
      }
    }
    return $r;
  }

  #[Test]
  public function emptyStylesheet() {
    Assert::equals(
      '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"/>',
      trim((new Stylesheet())->getSource(INDENT_DEFAULT))
    );
  }

  #[Test]
  public function setOutputMethod() {
    $s= new Stylesheet();
    $s->setOutputMethod('text', false, 'utf-8');
    
    Assert::equals(
      '<xsl:output method="text" encoding="utf-8" indent="no"></xsl:output>',
      trim($this->getElementsByTagName($s->root, 'xsl:output')->get(0)->getSource(INDENT_NONE))
    );
  }

  #[Test]
  public function withOutputMethod() {
    $s= (new Stylesheet())->withOutputMethod('text', false, 'utf-8');
    
    Assert::equals(
      '<xsl:output method="text" encoding="utf-8" indent="no"></xsl:output>',
      trim($this->getElementsByTagName($s->root, 'xsl:output')->get(0)->getSource(INDENT_NONE))
    );
  }

  #[Test]
  public function addImport() {
    $s= new Stylesheet();
    $s->addImport('portlets/welcome.portlet.xsl');
    
    Assert::equals(
      '<xsl:import href="portlets/welcome.portlet.xsl"></xsl:import>',
      trim($this->getElementsByTagName($s->root, 'xsl:import')->get(0)->getSource(INDENT_NONE))
    );
  }

  #[Test]
  public function withImport() {
    $s= (new Stylesheet())->withImport('portlets/welcome.portlet.xsl');
    
    Assert::equals(
      '<xsl:import href="portlets/welcome.portlet.xsl"></xsl:import>',
      trim($this->getElementsByTagName($s->root, 'xsl:import')->get(0)->getSource(INDENT_NONE))
    );
  }

  #[Test]
  public function addInclude() {
    $s= new Stylesheet();
    $s->addInclude('portlets/welcome.portlet.xsl');
    
    Assert::equals(
      '<xsl:include href="portlets/welcome.portlet.xsl"></xsl:include>',
      trim($this->getElementsByTagName($s->root, 'xsl:include')->get(0)->getSource(INDENT_NONE))
    );
  }

  #[Test]
  public function withInclude() {
    $s= (new Stylesheet())->withInclude('portlets/welcome.portlet.xsl');
    
    Assert::equals(
      '<xsl:include href="portlets/welcome.portlet.xsl"></xsl:include>',
      trim($this->getElementsByTagName($s->root, 'xsl:include')->get(0)->getSource(INDENT_NONE))
    );
  }

  #[Test]
  public function addParam() {
    $s= new Stylesheet();
    $s->addParam('session');
    
    Assert::equals(
      '<xsl:param name="session"></xsl:param>',
      trim($this->getElementsByTagName($s->root, 'xsl:param')->get(0)->getSource(INDENT_NONE))
    );
  }

  #[Test]
  public function withParam() {
    $s= (new Stylesheet())->withParam('session');
    
    Assert::equals(
      '<xsl:param name="session"></xsl:param>',
      trim($this->getElementsByTagName($s->root, 'xsl:param')->get(0)->getSource(INDENT_NONE))
    );
  }

  #[Test]
  public function addVariable() {
    $s= new Stylesheet();
    $s->addVariable('session');
    
    Assert::equals(
      '<xsl:variable name="session"></xsl:variable>',
      trim($this->getElementsByTagName($s->root, 'xsl:variable')->get(0)->getSource(INDENT_NONE))
    );
  }

  #[Test]
  public function addVariables() {
    $s= new Stylesheet();
    $s->addVariable('session');
    $s->addVariable('language');
    
    $variables= $this->getElementsByTagName($s->root, 'xsl:variable');
    Assert::equals(
      '<xsl:variable name="session"></xsl:variable>',
      trim($variables->get(0)->getSource(INDENT_NONE))
    );
    Assert::equals(
      '<xsl:variable name="language"></xsl:variable>',
      trim($variables->get(1)->getSource(INDENT_NONE))
    );
  }

  #[Test]
  public function withVariable() {
    $s= (new Stylesheet())->withVariable('session');
    
    Assert::equals(
      '<xsl:variable name="session"></xsl:variable>',
      trim($this->getElementsByTagName($s->root, 'xsl:variable')->get(0)->getSource(INDENT_NONE))
    );
  }

  #[Test]
  public function addMatchTemplate() {
    $s= new Stylesheet();
    $s->addTemplate((new \xml\XslTemplate())->matching('/'));
    
    Assert::equals(
      '<xsl:template match="/"></xsl:template>',
      trim($this->getElementsByTagName($s->root, 'xsl:template')->get(0)->getSource(INDENT_NONE))
    );
  }

  #[Test]
  public function withMatchTemplate() {
    $s= (new Stylesheet())->withTemplate((new \xml\XslTemplate())->matching('/'));
    
    Assert::equals(
      '<xsl:template match="/"></xsl:template>',
      trim($this->getElementsByTagName($s->root, 'xsl:template')->get(0)->getSource(INDENT_NONE))
    );
  }

  #[Test]
  public function addNamedTemplate() {
    $s= new Stylesheet();
    $s->addTemplate((new \xml\XslTemplate())->named('sitemap'));
    
    Assert::equals(
      '<xsl:template name="sitemap"></xsl:template>',
      trim($this->getElementsByTagName($s->root, 'xsl:template')->get(0)->getSource(INDENT_NONE))
    );
  }

  #[Test]
  public function withNamedTemplate() {
    $s= (new Stylesheet())->withTemplate((new \xml\XslTemplate())->named('sitemap'));
    
    Assert::equals(
      '<xsl:template name="sitemap"></xsl:template>',
      trim($this->getElementsByTagName($s->root, 'xsl:template')->get(0)->getSource(INDENT_NONE))
    );
  }
}