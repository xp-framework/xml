<?php namespace xml;
 
use io\FileUtil;
use lang\Value;
use util\Objects;
use xml\parser\ParserCallback;
use xml\parser\XMLParser;

/**
 * The Tree class represents a tree which can be exported
 * to and imported from an XML document.
 *
 * @test  xp://unittest.xml.TreeTest
 * @see   xp://xml.parser.XMLParser
 */
class Tree implements ParserCallback, Value {
  public 
    $root     = null,
    $nodeType = null;

  public
    $_cnt     = null,
    $_cdata   = null,
    $_objs    = null;

  protected 
    $version  = '1.0',
    $encoding = \xp::ENCODING;
  
  /**
   * Constructor
   *
   * @param   string rootName default 'document'
   */
  public function __construct($rootName= 'document') {
    $this->root= new Node($rootName);
    $this->nodeType= literal('xml.Node');
  }

  /**
   * Retrieve root node
   *
   * @return   xml.Node
   */
  public function root() {
    return $this->root;
  }

  /**
   * Set encoding
   *
   * @param   string e encoding
   */
  public function setEncoding($e) {
    $this->encoding= strtolower($e);
  }

  /**
   * Set encoding and return this tree
   *
   * @param   string e encoding
   * @return  xml.Tree
   */
  public function withEncoding($e) {
    $this->setEncoding($e);
    return $this;
  }
  
  /**
   * Retrieve encoding
   *
   * @return  string encoding
   */
  public function getEncoding() {
    return $this->encoding;
  }
  
  /**
   * Returns XML declaration
   *
   * @return  string declaration
   */
  public function getDeclaration() {
    return sprintf(
      '<?xml version="%s" encoding="%s"?>',
      $this->version,
      strtoupper($this->encoding)
    );
  }
  
  /**
   * Retrieve XML representation
   *
   * @param   bool indent default TRUE whether to indent
   * @return  string
   */
  public function getSource($indent= true) {
    return $this->root->getSource($indent, $this->encoding, '');
  }

  /**
   * Sets root node and returns this tree
   *
   * @param   xml.Node child 
   * @return  xml.Tree this
   * @throws  lang.IllegalArgumentException in case the given argument is not a Node
   */   
  public function withRoot(Node $root) {
    $this->root= $root;
    return $this;
  }
  
  /**
   * Add a child to this tree
   *
   * @param   xml.Node child 
   * @return  xml.Node the added child
   * @throws  lang.IllegalArgumentException in case the given argument is not a Node
   */   
  public function addChild(Node $child) {
    return $this->root->addChild($child);
  }

  /**
   * Construct an XML tree from a string.
   *
   * <code>
   *   $tree= Tree::fromString('<document>...</document>');
   * </code>
   *
   * @param   string string
   * @param   string c default __CLASS__ class name
   * @return  xml.Tree
   * @throws  xml.XMLFormatException in case of a parser error
   */
  public static function fromString($string, $c= __CLASS__) {
    $parser= new XMLParser();
    $tree= new $c();

    $parser->setCallback($tree);
    $parser->parse($string, 1);

    // Fetch actual encoding from parser
    $tree->setEncoding($parser->getEncoding());

    unset($parser);
    return $tree;
  }
  
  /**
   * Construct an XML tree from a file.
   *
   * <code>
   *   $tree= Tree::fromFile(new File('foo.xml'));
   * </code>
   *
   * @param   io.File file
   * @param   string c default __CLASS__ class name
   * @return  xml.Tree
   * @throws  xml.XMLFormatException in case of a parser error
   * @throws  io.IOException in case reading the file fails
   */ 
  public static function fromFile($file, $c= __CLASS__) {
    $parser= new XMLParser();
    $tree= new $c();
    
    $parser->setCallback($tree);
    $parser->parse(FileUtil::getContents($file));

    // Fetch actual encoding from parser
    $tree->setEncoding($parser->getEncoding());

    unset($parser);
    return $tree;
  }
  
  /**
   * Callback function for XMLParser
   *
   * @param   resource parser
   * @param   string name
   * @param   string attrs
   * @see     xp://xml.parser.XMLParser
   */
  public function onStartElement($parser, $name, $attrs) {
    $this->_cdata= '';

    $element= new $this->nodeType($name, null, $attrs);
    if (!isset($this->_cnt)) {
      $this->root= $element;
      $this->_objs[1]= $element;
      $this->_cnt= 1;
    } else {
      $this->_cnt++;
      $this->_objs[$this->_cnt]= $element;
    }
  }
 
  /**
   * Callback function for XMLParser
   *
   * @param   resource parser
   * @param   string name
   * @see     xp://xml.parser.XMLParser
   */
  public function onEndElement($parser, $name) {
    if ($this->_cnt > 1) {
      $node= $this->_objs[$this->_cnt];
      $node->setContent($this->_cdata);
      $parent= $this->_objs[$this->_cnt- 1];
      $parent->addChild($node);
      $this->_cdata= '';
    } else {
      $this->root()->setContent($this->_cdata);
      $this->_cdata= '';
    }
    $this->_cnt--;
  }

  /**
   * Callback function for XMLParser
   *
   * @param   resource parser
   * @param   string cdata
   * @see     xp://xml.parser.XMLParser
   */
  public function onCData($parser, $cdata) {
    $this->_cdata.= $cdata;
  }

  /**
   * Callback function for XMLParser
   *
   * @param   resource parser
   * @param   string data
   * @see     xp://xml.parser.XMLParser
   */
  public function onDefault($parser, $data) {
    // NOOP
  }

  /**
   * Callback function for XMLParser
   *
   * @param   xml.parser.XMLParser instance
   */
  public function onBegin($instance) {
    $this->encoding= $instance->getEncoding();
  }

  /**
   * Callback function for XMLParser
   *
   * @param   xml.parser.XMLParser instance
   * @param   xml.XMLFormatException exception
   */
  public function onError($instance, $exception) {
    unset($this->_cnt, $this->_cdata, $this->_objs);
  }

  /**
   * Callback function for XMLParser
   *
   * @param   xml.parser.XMLParser instance
   */
  public function onFinish($instance) {
    unset($this->_cnt, $this->_cdata, $this->_objs);
  }

  /** @return string */
  public function toString() {
    return sprintf(
      "%s(version=%s encoding=%s)@{\n  %s\n}",
      nameof($this),
      $this->version,
      $this->encoding,
      str_replace("\n", "\n  ", $this->root->toString())
    );
  }

  /** @return string */
  public function hashCode() {
    return md5($this->version.$this->encoding.$this->root->hashCode());
  }

  /**
   * Compare this tree to a given value
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self
      ? Objects::compare(
        [$this->version, $this->encoding, $this->root],
        [$value->version, $value->encoding, $value->root]
      )
      : 1
    ;
  }
} 
