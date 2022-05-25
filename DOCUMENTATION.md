# NCC Documentation

This document serves the purpose of presenting the documentation for using/developing
NCC, from basic installation, basic usage, standards and much more.

## Table of contents

 - Introduction
   - What is NCC?
   - Advantages over other software
 
 ------------------------------------------------------------------------------------


# Introduction (May 24, 2022)

This section serves the basic introduction of NCC, what it's used for and how you can
use it in your own projects or use it to run and build other projects that are designed
to be used with NCC. 

## What is NCC?

NCC (*Acronym for **N**osial **C**ode **C**ompiler*) is a multi-purpose compiler,
package manager and toolkit. Allowing projects to be managed and built more easily
without having to mess with all the traditional tools that comes with your language
of choice. Right now NCC only supports PHP as it's written in PHP but extensions
for other languages/frameworks can be built into the software in the future when
the need comes for it.

NCC can make the process of building your code into a redistributable package much
more efficient by treating each building block of your project as a component that
is interconnected in your environment instead of the more popular route taken by
package/dependency managers such as [composer](https://getcomposer.org/), 
[npm](https://www.npmjs.com/) or [pypi (or pip)](https://pypi.org/).