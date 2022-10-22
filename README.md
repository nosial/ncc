# ![NCC](assets/icon/ncc_32px.png "NCC")   NCC

Nosial Code Compiler is a program written in PHP designed to be a multi-purpose compiler, package manager and toolkit.
This program is a complete re-write of the now defunct [PHP Package Manager (PPM)](https://git.n64.cc/intellivoid/ppm)
toolkit offering more features, security and proper code licensing and copyrighting for the components used for the project.

NCC Cannot compile, read or use PPM packages (.ppm) files or work with project sources designed to be built with PPM, however
a PPM extension may be built in the future to allow for backwards compatibility.


## Notes

 > While NCC has windows compatibility in mind, not all compiler extensions or features will work correctly. NCC is
 > designed to be used in production in a Unix environment and Windows should only be used for development purposes.

 > Compiler extensions requires their own set of dependencies to be met, for example Java compilers will require JDK

 > NCC is designed to run only on a PHP 8.0+ environment, compiler extensions can have support for different PHP versions.

 > Third-party dependencies and included libraries has a dedicated namespace for `ncc` to avoid user land conflicts if
 > the user wishes to install and use one of the same dependencies that NCC uses.

## Authors
 - Zi Xing Narrakas (netkas) <[netkas@n64.cc](mailto:netkas@64.cc)>

## Special Thanks
 - Marc Gutt (mgutt) <[marc@gutt.it](mailto:marc@gutt.it)>

## Copyright
- Copyright (c) 2022-2022, Nosial - All Rights Reserved
- Copyright (c) 2004-2022, Fabien Potencier
- Copyright (c) 2010, dealnews.com, Inc. All rights reserved.
- Copyright (c) 2013 Austin Hyde
- Copyright (C) 2009-2016 Laurent Jouanneau
- Copyright (c) 2011, Nikita Popov
- Copyright (c) 2010-2016 Arne Blankerts <arne@blankerts.de> and Contributors

# Licenses

Multiple licenses can be found at [LICENSE](LICENSE)