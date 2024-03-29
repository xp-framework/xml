<?php namespace xml\meta;

use Traversable;
use lang\{Reflection, IllegalArgumentException};
use xml\{QName, Tree, Node, XMLFormatException, Xmlfactory, Xmlns};

/**
 * Marshalls XML from objects by using annotations.
 *
 * @ext   dom
 * @test  xml.unittest.MarshallerTest
 * @see   http://castor.org/xml-mapping.html
 */
class Marshaller {

  /**
   * Iterate over class methods with @xmlfactory annotation
   *
   * @param   object $instance
   * @param   lang.reflection.Type $type
   * @param   xml.Node $node
   * @param   [:var] $inject
   */
  protected static function recurse($instance, $type, $node, $inject) {

    // Calculate element name
    if (null === $node->getName()) {
      if (($factory= $type->annotation(Xmlfactory::class)) && ($element= $factory->argument('element'))) {
        $node->setName($element);
      } else {
        $node->setName(strtolower($type->declaredName()));
      }
    }

    // Namespace handling
    if ($xmlns= $type->annotation(Xmlns::class)) {
      $map= $xmlns->arguments();
      $node->setName(key($map).':'.$node->getName());
      foreach ($map as $prefix => $url) {
        $node->setAttribute('xmlns:'.$prefix, $url);
      }
    }

    foreach ($type->methods()->annotated(Xmlfactory::class) as $method) {
      $annotation= $method->annotation(Xmlfactory::class);
      if (null === ($element= $annotation->argument('element'))) continue;
      
      // Pass injection parameters at end of list
      $arguments= [];
      if ($injection= $annotation->argument('inject')) {
        foreach ($injection as $name) {
          if (!isset($inject[$name])) throw new IllegalArgumentException(
            'Injection parameter "'.$name.'" not found for '.$method->toString()
          );
          $arguments[]= $inject[$name];
        }
      }
      
      $result= $method->invoke($instance, $arguments);

      // Cast result if specified
      if ($cast= $annotation->argument('cast')) {
        switch (sscanf($cast, '%[^:]::%s', $c, $m)) {
          case 1: $target= [$instance, $c]; break;
          case 2: $target= [$c, $m]; break;
          default: throw new IllegalArgumentException('Unparseable cast "'.$cast.'"');
        }
        $result= $target($result);
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
        $node->addChild(new Node($element, $result));
      } else if (is_array($result)) {
        $child= $node->addChild(new Node($element));
        foreach ($result as $key => $val) {
          $child->addChild(new Node($key, $val));
        }
      } else if ($result instanceof Traversable) {
        foreach ($result as $value) {
          if (is_object($value)) {
            self::recurse($value, Reflection::type($value), $node->addChild(new Node($element)), $inject);
          } else {
            $node->addChild(new Node($element, $value));
          }
        }
      } else if (is_object($result)) {
        self::recurse($result, Reflection::type($result), $node->addChild(new Node($element)), $inject);
      }
    }
  }

  /**
   * Marshal an object to xml
   *
   * @param   ?xml.Node target
   * @param   object $instance
   * @param   [:var] inject
   * @return  xml.Node the given target
   */
  public function marshalTo($target= null, $instance, $inject= []) {
    $type= Reflection::type($instance);

    // Create node if not existant
    $target ?? $target= new Node(null);

    self::recurse($instance, $type, $target, $inject);
    return $target;
  }
}