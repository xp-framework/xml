<?php namespace xml\meta;

use DOMDocument, DOMNode, DOMNodeList;
use io\streams\Streams;
use lang\{Reflection, IllegalArgumentException};
use xml\parser\InputSource;
use xml\{XMLFormatException, XPath, Xmlmapping, Xmlns};

/**
 * Creates objects from XML by using annotations.
 *
 * @ext   dom
 * @test  xp://xml.unittest.UnmarshallerTest
 * @see   http://castor.org/xml-mapping.html
 */
class Unmarshaller {

  static function __static() {
    libxml_use_internal_errors(true);
  }

  /**
   * Retrieve content of a DomElement
   *
   * @param   php.DomElement element
   * @return  string
   */
  protected static function contentOf($element) {
    if ($element instanceof DOMNodeList) {
      return $element->length ? $element->item(0)->textContent : null;
    } else if (is_scalar($element)) {
      return $element;
    } else if ($element instanceof DOMNode) {
      switch ($element->nodeType) {
        case 1:   // DOMElement
          return $element->textContent;

        case 2:   // DOMAttr
          return $element->value;

        case 3:   // DOMText
        case 4:   // DOMCharacterData
          return $element->data;
      }
    }
    return null;
  }

  /**
   * Recursively unmarshal
   *
   * @param   xml.XPath $xpath
   * @param   php.DomElement $element
   * @param   lang.reflection.Type $type
   * @param   [:var] $inject
   * @return  object
   * @throws  lang.ClassNotFoundException
   * @throws  xml.XPathException
   */
  protected static function recurse($xpath, $element, $type, $inject) {

    // Namespace handling
    if ($xmlns= $type->annotation(Xmlns::class)) {
      foreach ($xmlns->arguments() as $prefix => $url) {
        $xpath->context->registerNamespace($prefix, $url);
      }
    }
    
    $instance= $type->newInstance();
    foreach ($type->methods()->annotated(Xmlmapping::class) as $method) {
      $annotation= $method->annotation(Xmlmapping::class);
      if (null === ($select= $annotation->argument('element'))) continue;

      // Perform XPath query
      $result= $xpath->query($select, $element);

      // Iterate over results, invoking the method for each node.
      foreach ($result as $node) {
        if ($class= $annotation->argument('class')) {

          // * If the xmlmapping annotation has a key "class", call recurse()
          //   with the given XPath, the node as context and the key's value
          //   as classname
          $arguments= [self::recurse($xpath, $node, Reflection::type($class), $inject)];
        } else if ($factory= $annotation->argument('factory')) {

          // * If the xmlmapping annotation has a key "factory", call recurse()
          //   with the given XPath, the node as context and the results from
          //   the specified method as class name. The specified factory method 
          //   is passed the node's tag name if no "pass" key is available.
          //   In case it is, call the factory method with the arguments 
          //   constructed from the "pass" key.
          if ($pass= $annotation->argument('pass')) {
            $args= [];
            foreach ($pass as $path) {
              $args[]= self::contentOf($xpath->query($path, $node));
            }
          } else {
            $args= [$node->nodeName];
          }
          $arguments= [self::recurse(
            $xpath, 
            $node, 
            Reflection::type($instance->method($factory)->invoke($args)),
            $inject
          )];
        } else if ($pass= $annotation->argument('pass')) {
        
          // * If the xmlmapping annotation has a key "pass" (expected to be an
          //   array of XPaths relative to the node), construct the method's
          //   argument list from the XPaths' results.
          $arguments= [];
          foreach ($pass as $path) {
            $arguments[]= self::contentOf($xpath->query($path, $node));
          }
        } else if ($cast= $annotation->argument('cast')) {
          switch (sscanf($cast, '%[^:]::%s', $c, $m)) {
            case 1: $target= [$instance, $c]; break;
            case 2: $target= [$c, $m]; break;
            default: throw new IllegalArgumentException('Unparseable cast "'.$cast.'"');
          }

          // * If the xmlmapping annotation contains a key "cast", cast the node's
          //   contents using the given callback method before passing it to the method.
          $arguments= [$target($node->textContent)];
        } else if ($type= $annotation->argument('type')) {

          // * If the xmlmapping annotation contains a key "type", cast the node's
          //   contents to the specified type before passing it to the method.
          $value= $node->textContent;
          settype($value, $type);
          $arguments= [$value];
        } else {

          // * Otherwise, pass the node's content to the method
          $arguments= [$node->textContent];
        }
        
        // Pass injection parameters at end of list
        if ($injection= $annotation->argument('inject')) {
          foreach ($injection as $name) {
            if (!isset($inject[$name])) throw new IllegalArgumentException(
              'Injection parameter "'.$name.'" not found for '.$method->toString()
            );
            $arguments[]= $inject[$name];
          }
        }
        
        $method->invoke($instance, $arguments);
      }
    }

    return $instance;
  }

  /**
   * Unmarshal XML to an object
   *
   * @param   xml.parser.InputSource $input
   * @param   string $classname
   * @param   [:var] $inject
   * @return  object
   * @throws  lang.ClassNotFoundException
   * @throws  xml.XMLFormatException
   * @throws  lang.reflection.InvocationFailed
   * @throws  lang.IllegalArgumentException
   */
  public function unmarshalFrom(InputSource $input, $classname, $inject= []) {
    libxml_clear_errors();
    $doc= new DOMDocument();
    if (!$doc->load(Streams::readableUri($input->getStream()))) {
      $e= libxml_get_last_error();
      throw new XMLFormatException(trim($e->message), $e->code, $input->getSource(), $e->line, $e->column);
    }

    $xpath= new XPath($doc);
    $type= Reflection::type($classname);

    // Class factory based on tag name, reference to a static method which is called with 
    // the class name and returns an XPClass instance.
    if ($mapping= $type->annotation(Xmlmapping::class)) {
      if ($factory= $mapping->argument('factory')) {
        if ($pass= $mapping->argument('pass')) {
          $args= [];
          foreach ($pass as $path) {
            $args[]= self::contentOf($xpath->query($path, $doc->documentElement));
          }
        } else {
          $args= [$doc->documentElement->nodeName];
        }

        $type= Reflection::type($type->method($factory)->invoke(null, $args));
      }
    }

    return self::recurse($xpath, $doc->documentElement, $type, $inject);
  }
}