<?php namespace xml\meta;

use xml\{QName, Tree, XMLFormatException};

/**
 * Marshalls XML from objects by using annotations.
 *
 * Example:
 * ```php
 * // [...create transmission object...]
 *
 * $xml= Marshaller::marshal($transmission);
 * ```
 *
 * @test  xp://xml.unittest.MarshallerTest
 * @ext   dom
 * @see   http://castor.org/xml-mapping.html
 */
class Marshaller {

  /**
   * Iterate over class methods with @xmlfactory annotation
   *
   * @param   object instance
   * @param   lang.XPClass class
   * @param   xml.Node node
   * @param   [:var] inject
   */
  protected static function recurse($instance, $class, $node, $inject) {
  
    // Calculate element name
    if ('' == $node->getName()) {
      if ($class->hasAnnotation('xmlfactory', 'element')) {
        $node->setName($class->getAnnotation('xmlfactory', 'element'));
      } else {
        $node->setName(strtolower($class->getSimpleName()));
      }
    }

    // Namespace handling
    if ($class->hasAnnotation('xmlns')) {
      $node->setName(key($class->getAnnotation('xmlns')).':'.$node->getName());
      foreach ($class->getAnnotation('xmlns') as $prefix => $url) {
        $node->setAttribute('xmlns:'.$prefix, $url);
      }
    }

    foreach ($class->getMethods() as $method) {
      if (!$method->hasAnnotation('xmlfactory', 'element')) continue;
      
      $element= $method->getAnnotation('xmlfactory', 'element');
      
      // Pass injection parameters at end of list
      $arguments= [];
      if ($method->hasAnnotation('xmlfactory', 'inject')) {
        foreach ($method->getAnnotation('xmlfactory', 'inject') as $name) {
          if (!isset($inject[$name])) throw new \lang\IllegalArgumentException(
            'Injection parameter "'.$name.'" not found for '.$method->toString()
          );
          $arguments[]= $inject[$name];
        }
      }
      
      $result= $method->invoke($instance, $arguments);

      // Cast result if specified
      if ($method->hasAnnotation('xmlfactory', 'cast')) {
        $cast= $method->getAnnotation('xmlfactory', 'cast');
        switch (sscanf($cast, '%[^:]::%s', $c, $m)) {
          case 1: $target= [$instance, $c]; break;
          case 2: $target= [$c, $m]; break;
          default: throw new \lang\IllegalArgumentException('Unparseable cast "'.$cast.'"');
        }
        $result= call_user_func([$instance, $method->getAnnotation('xmlfactory', 'cast')], $result);
      }
      
      // Attributes = "@<name>", Node content= ".", Name = "name()"
      if ('@' === $element[0]) {
        $node->setAttribute(substr($element, 1), $result);
        continue;
      } else if ('.' === $element) {
        $node->setContent($result);
        continue;
      } else if ('name()' === $element) {
        $node->setName($result);
        continue;
      }
      
      // Create subnodes based on runtime type of method:
      //
      // - For scalar types, create a node with the element's name and set
      //   the node's content to the value
      //
      // - For arrays we iterate over keys and values (FIXME: we assume the 
      //   array is a string => scalar map!)
      //
      // - For lists, add a node with the element's name and invoke
      //   the recurse() method for each value in the collection.
      //
      // - For objects, add a new node and invoke the recurse() method
      //   on it.
      if (is_scalar($result) || null === $result) {
        $node->addChild(new \xml\Node($element, $result));
      } else if (is_array($result)) {
        $child= $node->addChild(new \xml\Node($element));
        foreach ($result as $key => $val) {
          $child->addChild(new \xml\Node($key, $val));
        }
      } else if ($result instanceof \Traversable) {
        foreach ($result as $value) {
          if (is_object($value)) {
            self::recurse($value, typeof($value), $node->addChild(new \xml\Node($element)), $inject);
          } else {
            $node->addChild(new \xml\Node($element, $value));
          }
        }
      } else if (is_object($result)) {
        self::recurse($result, typeof($result), $node->addChild(new \xml\Node($element)), $inject);
      }
    }
  }

  /**
   * Marshal an object to xml
   *
   * @param   object instance
   * @param   xml.QName qname default NULL
   * @return  string xml
   * @deprecated  Use marshalTo() instead
   */
  public static function marshal($instance, $qname= null) {
    $class= typeof($instance);

    // Create XML tree and root node. Use the information provided by the
    // qname argument if existant, use the class` non-qualified (and 
    // lowercased) name otherwise.
    $tree= new Tree();
    if ($qname) {
      $prefix= $qname->prefix ? $qname->prefix : $qname->localpart[0];
      $tree->root()->setName($prefix.':'.$qname->localpart);
      $tree->root()->setAttribute('xmlns:'.$prefix, $qname->namespace);
    } else if ($class->hasAnnotation('xmlns')) {
      $tree->root()->setName($class->getSimpleName());
    } else {
      $tree->root()->setName(strtolower($class->getSimpleName()));
    }
    
    self::recurse($instance, $class, $tree->root(), []);
    return $tree->getSource(INDENT_DEFAULT);
  }
 
  /**
   * Marshal an object to xml
   *
   * @param   xml.Node target
   * @param   object $instance
   * @param   [:var] inject
   * @return  xml.Node the given target
   */
  public function marshalTo(\xml\Node $target= null, $instance, $inject= []) {
    $class= typeof($instance);

    // Create node if not existant
    if (null === $target) $target= new \xml\Node(null);

    self::recurse($instance, $class, $target, $inject);
    return $target;
  }
}