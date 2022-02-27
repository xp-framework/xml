<?php namespace xml;

use lang\Value;
use util\Objects;

define('INDENT_DEFAULT',    0);
define('INDENT_WRAPPED',    1);
define('INDENT_NONE',       2);

define('XML_ILLEGAL_CHARS',   "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x0b\x0c\x0e\x0f\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1a\x1b\x1c\x1d\x1e\x1f");

/**
 * Represents a node
 *
 * @see   xp://xml.Tree#addChild
 * @test  xp://xml.unittest.NodeTest
 */
class Node implements Value {
  const XML_ILLEGAL_CHARS   = XML_ILLEGAL_CHARS;

  public 
    $name         = '',
    $attribute    = [],
    $content      = null,
    $children     = [];

  /**
   * Constructor
   *
   * ```php
   * $n= new Node('document');
   * $n= new Node('text', 'Hello World');
   * $n= new Node('article', '', ['id' => 42]);
   * ```
   *
   * @param   string name
   * @param   string content default NULL
   * @param   [:string] attribute default array() attributes
   * @throws  lang.IllegalArgumentException
   */
  public function __construct($name, $content= null, $attribute= []) {
    $this->name= $name;
    $this->attribute= $attribute;
    $this->setContent($content);
  }

  /**
   * Create a node from an array
   *
   * Usage example:
   * <code>
   *   $n= Node::fromArray($array, 'elements');
   * </code>
   *
   * @param   array arr
   * @param   string name default 'array'
   * @return  xml.Node
   */
  public static function fromArray($a, $name= 'array') {
    $n= new self($name);
    $sname= rtrim($name, 's');
    foreach ($a as $field => $value) {
      $nname= is_numeric($field) || '' == $field ? $sname : $field;
      if (is_array($value)) {
        $n->addChild(self::fromArray($value, $nname));
      } else if (is_object($value)) {
        if (method_exists($value, '__toString')) {
          $n->addChild(new self($nname, $value->__toString()));
        } else {
          $n->addChild(self::fromObject($value, $nname));
        }
      } else {
        $n->addChild(new self($nname, $value));
      }
    }
    return $n;  
  }
  
  /**
   * Create a node from an object. Will use class name as node name
   * if the optional argument name is omitted.
   *
   * Usage example:
   * <code>
   *   $n= Node::fromObject($object);
   * </code>
   *
   * @param   lang.Generic obj
   * @param   string name default NULL
   * @return  xml.Node
   */
  public static function fromObject($obj, $name= null) {
    if (method_exists($obj, '__serialize')) {
      $vars= $obj->__serialize();
    } else if (method_exists($obj, '__sleep')) {
      $vars= [];
      foreach ($obj->__sleep() as $var) $vars[$var]= $obj->{$var};
    } else {
      $vars= get_object_vars($obj);
    }

    if (null === $name) {
      $class= get_class($obj);
      $name= (false !== ($p= strrpos($class, '\\'))) ? substr($class, $p+ 1) : $class;
    }

    return self::fromArray($vars, $name);
  }

  /**
   * Set Name
   *
   * @param   string name
   */
  public function setName($name) {
    $this->name= $name;
  }

  /**
   * Get Name
   *
   * @return  string
   */
  public function getName() {
    return $this->name;
  }
  
  /**
   * Set content
   *
   * @param  string|xml.PCData|xml.CData $content
   * @throws xml.XMLFormatException in case content contains illegal characters
   * @return void
   */
  public function setContent($content) {
    if (null === $content) {
      $this->content= null;
    } else if ($content instanceof PCData || $content instanceof CData) {
      $this->content= $content;
    } else {
      $c= (string)$content;
      if (strlen($c) > ($p= strcspn($c, XML_ILLEGAL_CHARS))) {
        throw new XMLFormatException('Content contains illegal character at position '.$p. ' / chr('.ord($c[$p]).')');
      }
      $this->content= $c;
    }
  }
  
  /**
   * Get content (all CDATA)
   *
   * @return  string content
   */
  public function getContent() {
    return $this->content;
  }

  /**
   * Set an attribute
   *
   * @param   string name
   * @param   string value
   */
  public function setAttribute($name, $value) {
    $this->attribute[$name]= $value;
  }

  /**
   * Sets all attributes
   *
   * @param   [:string] attributes
   */
  public function setAttributes($attrs) {
    $this->attribute= $attrs;
  }
  
  /**
   * Retrieve an attribute by its name. Returns the default value if the
   * attribute is non-existant
   *
   * @param   string name
   * @param   var default default NULL
   * @return  string
   */
  public function getAttribute($name, $default= null) {
    return isset($this->attribute[$name]) ? $this->attribute[$name] : $default;
  }

  /**
   * Retrieve all attributes
   *
   * @return   [:string] attributes
   */
  public function getAttributes() {
    return $this->attribute;
  }

  /**
   * Checks whether a specific attribute is existant
   *
   * @param   string name
   * @return  bool
   */
  public function hasAttribute($name) {
    return isset($this->attribute[$name]);
  }
  
  /**
   * Retrieve XML representation
   *
   * Setting indent to 0 (INDENT_DEFAULT) yields this result:
   * <pre>
   *   <item>  
   *     <title>Website created</title>
   *     <link/>
   *     <description>The first version of the XP web site is online</description>
   *     <dc:date>2002-12-27T13:10:00</dc:date>
   *   </item>
   * </pre>
   *
   * Setting indent to 1 (INDENT_WRAPPED) yields this result:
   * <pre>
   *   <item>
   *     <title>
   *       Website created
   *     </title>
   *     <link/>
   *     <description>
   *       The first version of the XP web site is online
   *     </description>
   *     <dc:date>
   *       2002-12-27T13:10:00
   *     </dc:date>  
   *   </item>
   * </pre>
   *
   * Setting indent to 2 (INDENT_NONE) yields this result (wrapped for readability,
   * returned XML is on one line):
   * <pre>
   *   <item><title>Website created</title><link></link><description>The 
   *   first version of the XP web site is online</description><dc:date>
   *   2002-12-27T13:10:00</dc:date></item>
   * </pre>
   *
   * @param   int indent default INDENT_WRAPPED
   * @param   string encoding defaults to XP default encoding
   * @param   string inset default ''
   * @return  string XML
   */
  public function getSource($indent= INDENT_WRAPPED, $encoding= \xp::ENCODING, $inset= '') {
    $xml= $inset.'<'.$this->name;
    $conv= \xp::ENCODING != $encoding;
    
    if ('string' == ($type= gettype($this->content))) {
      $content= $conv
        ? iconv(\xp::ENCODING, $encoding, htmlspecialchars($this->content, ENT_COMPAT, \xp::ENCODING))
        : htmlspecialchars($this->content, ENT_COMPAT, \xp::ENCODING)
      ;
    } else if ('float' == $type) {
      $content= ($this->content - floor($this->content) == 0)
        ? number_format($this->content, 0, null, null)
        : $this->content
      ;
    } else if ($this->content instanceof PCData) {
      $content= $conv
        ? iconv(\xp::ENCODING, $encoding, $this->content->pcdata)
        : $this->content->pcdata
      ;
    } else if ($this->content instanceof CData) {
      $content= '<![CDATA['.str_replace(']]>', ']]]]><![CDATA[>', $conv
        ? iconv(\xp::ENCODING, $encoding, $this->content->cdata)
        : $this->content->cdata
      ).']]>';
    } else {
      $content= $this->content; 
    }
    
    if (INDENT_NONE === $indent) {
      foreach ($this->attribute as $key => $value) {
        $xml.= ' '.$key.'="'.htmlspecialchars(
          $conv ? iconv(\xp::ENCODING, $encoding, $value) : $value,
          ENT_COMPAT,
          \xp::ENCODING
        ).'"';
      }
      $xml.= '>'.$content;
      foreach ($this->children as $child) {
        $xml.= $child->getSource($indent, $encoding, $inset);
      }
      return $xml.'</'.$this->name.'>';
    } else {
      if ($this->attribute) {
        $sep= (sizeof($this->attribute) < 3) ? '' : "\n".$inset;
        foreach ($this->attribute as $key => $value) {
          $xml.= $sep.' '.$key.'="'.htmlspecialchars(
            $conv ? iconv(\xp::ENCODING, $encoding, $value) : $value,
            ENT_COMPAT,
            \xp::ENCODING
          ).'"';
        }
        $xml.= $sep;
      }

      // No content and no children => close tag
      if (null === $content || 0 === strlen($content)) {
        if (!$this->children) return $xml."/>\n";
        $xml.= '>';
      } else {
        $xml.= '>'.($indent ? "\n  ".$inset.$content : trim($content));
      }

      if ($this->children) {
        $xml.= ($indent ? '' : $inset)."\n";
        foreach ($this->children as $child) {
          $xml.= $child->getSource($indent, $encoding, $inset.'  ');
        }
        $xml= ($indent ? substr($xml, 0, -1) : $xml).$inset;
      }
      return $xml.($indent ? "\n".$inset : '').'</'.$this->name.">\n";
    }
  }
  
  /**
   * Add a child node
   *
   * @param   xml.Node child
   * @return  xml.Node added child
   * @throws  lang.IllegalArgumentException in case the given argument is not a Node
   */
  public function addChild(Node $child) {
    $this->children[]= $child;
    return $child;
  }

  /**
   * Add a child node and return this node
   *
   * @param   xml.Node child
   * @return  xml.Node this
   * @throws  lang.IllegalArgumentException in case the given argument is not a Node
   */
  public function withChild(Node $child) {
    $this->addChild($child);
    return $this;
  }

  /**
   * Set children to given list of children
   *
   * @param xml.Node[] children
   */
  public function setChildren(array $children) {
    $this->children= [];
    foreach ($children as $child) {
      $this->addChild($child);
    }
  }

  /**
   * Retrieve node children
   *
   * @return   xml.Node[] children
   */
  public function getChildren() {
    return $this->children;
  }

  /**
   * Clear node children
   *
   */
  public function clearChildren() {
    $this->setChildren([]);
  }

  /**
   * Retrieve number of children
   *
   * @return  int
   */
  public function numChildren() {
    return sizeof($this->children);
  }

  /**
   * Determine whether node has node children
   *
   * @return   bool
   */
  public function hasChildren() {
    return 0 < sizeof($this->children);
  }

  /**
   * Retrieve nth node child
   *
   * @param    int pos
   * @return   xml.Node
   * @throws   lang.ElementNotFoundException if array index out of bounds
   */
  public function nodeAt($pos) {
    if (!isset($this->children[$pos])) {
      throw new ElementNotFoundException('Cannot access node at position '.$pos);
    }

    return $this->children[$pos];
  }
  
  /** @return string */
  public function toString() {
    $a= '';
    foreach ($this->attribute as $name => $value) {
      $a.= ' @'.$name.'= '.Objects::stringOf($value);
    }
    $s= nameof($this).'('.$this->name.$a.') {';
    if ($this->children) {
      $s.= null === $this->content ? "\n" : "\n  ".Objects::stringOf($this->content)."\n";
      foreach ($this->children as $child) {
        $s.= '  '.str_replace("\n", "\n  ", $child->toString())."\n";
      }
    } else {
      $s.= null === $this->content ? ' ' : ' '.Objects::stringOf($this->content).' ';
    }
    return $s.'}';
  }

  /** @return string */
  public function hashCode() {
    return md5($this->name.$this->content.Objects::hashOf($this->attribute).Objects::hashOf($this->children));
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
        [$this->name, $this->content, $this->attribute, $this->children],
        [$value->name, $value->content, $value->attribute, $this->children]
      )
      : 1
    ;
  }
}