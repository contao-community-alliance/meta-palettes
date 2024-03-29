Changelog
=========

2.1.0 (2022-11-23)
------------------

 - Support Contao 5 and Symfony components v6

2.0.9 (2022-02-08)
------------------

 - Fix order of diagnostics steps and allow failures for PHP 8 ([#48](https://github.com/contao-community-alliance/meta-palettes/pull/48))
 - Fix subpalette position for DC General ([#47](https://github.com/contao-community-alliance/meta-palettes/pull/47))
 - Fix retrieving value from DC General driven model ([#44](https://github.com/contao-community-alliance/meta-palettes/pull/44))

2.0.8 (2022-01-27)
------------------

[Full Changelog](https://github.com/contao-community-alliance/meta-palettes/compare/2.0.7...2.0.8)

 - Allow PHP 8
 - Support symfony 5
 - Support doctrine/dbal 3

2.0.5 (2018-11-23)
------------------

[Full Changelog](https://github.com/contao-community-alliance/meta-palettes/compare/2.0.4...2.0.5)

 - Add missing requirements 
 - Quote identifiers to avoid unquoted reserved words (#40) 

2.0.4 (2018-08-26)
------------------

[Full Changelog](https://github.com/contao-community-alliance/meta-palettes/compare/2.0.3...2.0.4)

 - Revert public flag on services which aren't required to be public 

2.0.3 (2018-08-25)
------------------

[Full Changelog](https://github.com/contao-community-alliance/meta-palettes/compare/2.0.2...2.0.3)

 - Improve travis configuration
 - Make required services public

2.0.2 (2018-02-11)
------------------

[Full Changelog](https://github.com/contao-community-alliance/meta-palettes/compare/2.0.1...2.0.2)

 - Update requirements

2.0.1 (2018-01-08)
------------------

[Full Changelog](https://github.com/contao-community-alliance/meta-palettes/compare/2.0.0...2.0.1)

 - Throw exception if recursive inheritance is detected (#37)
 - Fix issues with metasubselectpalettes (#38)
 - Fix issue with not properly parsed legend name

2.0.0 (2017-12-04)
------------------

[Full Changelog](https://github.com/contao-community-alliance/meta-palettes/compare/1.10.1...2.0.0)


 - Move classes to `ContaoCommunityAlliance\MetaPalettes` namespace
 - Drop support of deprecated root namespace
 - Drop support of Bit3 namespace
 - Add support of nested metapalettes
 - Remove hook methods from `MetaPalettes` class
