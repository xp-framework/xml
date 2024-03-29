<?php namespace xml\xslt;

use xml\Xslmethod;

/**
 * XSL callbacks for string operations
 *
 * @purpose   XSL callback
 * @test      xp://xml.unittest.XslCallbackTest
 */
class XSLStringCallback {

  /**
   * urlencode() string
   *
   * @param   string string
   * @return  string
   */
  #[Xslmethod]
  public function urlencode($string) {
    return urlencode($string);
  }
  
  /**
   * urldecode() string
   *
   * @param   string string
   * @return  string
   */
  #[Xslmethod]
  public function urldecode($string) {
    return urldecode($string);
  }
  
  /**
   * strtolower() string
   *
   * @param   string string
   * @return  string
   */
  #[Xslmethod]
  public function strtolower($string) {
    return strtolower($string);
  }    

  /**
   * strtoupper() string
   *
   * @param   string string
   * @return  string
   */
  #[Xslmethod]
  public function strtoupper($string) {
    return strtoupper($string);
  }
  
  /**
   * Substitute one string through another in a given string
   *
   * @param   string str
   * @param   string search
   * @param   string replace
   * @return  string
   */
  #[Xslmethod]
  public function replace($str, $search, $replace) {
    return str_replace($search, $replace, $str);
  }
  
  /**
   * Convert newlines to <br/>
   *
   * @param   string string
   * @return  string
   */
  #[Xslmethod]
  public function nl2br($string) {
    return nl2br($string);
  }

  /**
   * Break wrap words in long texts by given column
   *
   * @param   string string The input string
   * @param   int width Break at this column
   * @param   string break The string to insert when doing a break (defaults to "\n")
   * @param   bool cut Do word wrapping within words (defaults to TRUE)
   * @return  string
   */
  #[Xslmethod]
  public function wordwrap($string, $width, $break= "\n", $cut= true) {
    return wordwrap($string, $width, $break, $cut);
  }
}