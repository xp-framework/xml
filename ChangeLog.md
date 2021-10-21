XML APIs for the XP Framework ChangeLog
========================================================================

## ?.?.? / ????-??-??

## 11.0.0 / 2021-10-21

* Made compatible with XP 11 - @thekid
* Implemented xp-framework/rfc#341, dropping compatibility with XP 9
  (@thekid)

## 10.0.0 / 2020-04-10

* Implemented xp-framework/rfc#334: Drop PHP 5.6:
  . **Heads up:** Minimum required PHP version now is PHP 7.0.0
  . Rewrote code base, grouping use statements
  . Converted `newinstance` to anonymous classes
  . Rewrote `isset(X) ? X : default` to `X ?? default`
  (@thekid)

## 9.0.4 / 2020-04-09

* Implemented RFC #335: Remove deprecated key/value pair annotation syntax
  (@thekid)

## 9.0.3 / 2020-04-04

* Made compatible with XP 10 - @thekid

## 9.0.2 / 2019-08-20

* Made compatible with PHP 7.4 - refrain using `{}` for string offsets
  (@thekid)
* Replaced all calls to the deprecated `xp::stringOf()` to use the method
  from `util.Objects`.
  (@thekid)

## 9.0.1 / 2018-04-02

* Fixed compatiblity with PHP 7.2 - @thekid

## 9.0.0 / 2017-05-29

* Merged PR #3: XP9 Compat. **Heads up:** xml.Tree, xml.Node, xml.CData and
  xml.PCData now implement `lang.Value` instead of extending `lang.Object`.
  (@thekid)

## 8.0.2 / 2017-05-20

* Refactored code to use `typeof()` instead of `xp::typeOf()`, see
  https://github.com/xp-framework/rfc/issues/323
  (@thekid)

## 8.0.1 / 2017-01-31

* Code QA (no functional changes!) - @thekid

## 8.0.0 / 2016-08-28

* Improved speed of XSL callbacks by using direct invocations instead
  of `call_user_func_array` / `func_get_args` indirection
  (@thekid)
* **Heads up: Dropped PHP 5.5 support!** - @thekid
* Added forward compatibility with XP 8.0.0
  (@thekid)

## 7.0.1 / 2016-04-20

* Merged pull request #2: Updated obsolete PHP namespace seperator...
  (@djuelg, @kiesel)

## 7.0.0 / 2016-02-21

* **Adopted semantic versioning. See xp-framework/rfc#300** - @thekid 
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

* Changed dependency to use XP 6.0 (instead of dev-master) - @thekid

## 6.0.0 / 2015-10-01

* Changed default encoding to `UTF-8` - (@thekid)
* Heads up: Converted classes to PHP 5.3 namespaces - (@thekid)
