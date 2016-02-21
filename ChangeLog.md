XML APIs for the XP Framework ChangeLog
========================================================================

## ?.?.? / ????-??-??

## 7.0.0 / 2016-02-21

* Added version compatibility with XP 7 - @thekid

## 6.3.1 / 2016-01-24

* Fix code to use `nameof()` instead of the deprecated `getClassName()`
  method from lang.Generic. See xp-framework/core#120
  (@thekid)

## 6.3.0 / 2015-12-20

* **Heads up: Dropped PHP 5.4 support**. *Note: As the main source is not
  touched, unofficial PHP 5.4 support is still available though not tested
  with Travis-CI*.
  (@thekid)
* Officially addited support for HHVM (3.5+) support - @thekid

## 6.2.2 / 2014-12-09

* Rewrote code to ue `literal()` instead of `xp::reflect()`. See
  xp-framework/rfc#298
  (@thekid)

## 6.2.1 / 2015-07-12

* Added forward compatibility with XP 6.4.0
  . Rewrote code using `create()` to PHP 5.4 syntax
  . Replaced `raise()` with throw statement
  (@thekid)

## 6.2.0 / 2015-06-13

* Added forward compatibility with PHP7 - @thekid
* Fixed HHVM compatibility issue in `xml.DomXSLProcessor` - @thekid

## 6.1.0 / 2015-05-31

* Fixed issue #1: Merge "Invoke htmlspecialchars() w/ fixed encoding" from
  XP 5. See https://github.com/xp-framework/xp-framework/pull/367
  (@thekid)

## 6.0.1 / 2015-02-12

* Changed dependency to use XP ~6.0 (instead of dev-master) - @thekid

## 6.0.0 / 2015-10-01

* Changed default encoding to `UTF-8` - (@thekid)
* Heads up: Converted classes to PHP 5.3 namespaces - (@thekid)
