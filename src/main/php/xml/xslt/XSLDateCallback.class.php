<?php namespace xml\xslt;

use lang\{Enum, XPClass};
use util\{Date, DateMath, TimeInterval, TimeZone};
use xml\Xslmethod;

/**
 * XSL callbacks for Date operations
 *
 * @ext       ext/date
 * @see       xp://util.Date
 * @see       xp://util.DateMath
 * @test      xp://xml.unittest.XslCallbackTest
 * @purpose   XSL callback
 */
class XSLDateCallback {

  /**
   * Format the given date with the format specifier
   *
   * @param   string date
   * @param   string format
   * @param   string timezone default NULL
   * @return  string
   */
  #[Xslmethod]
  public function format($date, $format, $timezone= null) {
    $timezone= empty($timezone) ? null : $timezone;
    return (new Date($date))->toString($format, new TimeZone($timezone));
  }
  
  /**
   * Diff two dates with the given interval
   *
   * @param   string type
   * @param   string strdate1
   * @param   string strdate2
   * @return  int
   */
  #[Xslmethod]
  public function diff($type, $strdate1, $strdate2) {
    return DateMath::diff(
      Enum::valueOf(XPClass::forName('util.TimeInterval'), strtoupper($type)),
      new Date($strdate1),
      new Date($strdate2)
    );
  }
}