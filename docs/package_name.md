# Naming a package

**Updated on Tuesday, July 26, 2022**

NCC Follows the same naming convention as Java's naming
convention. The purpose of naming a package this way is
to easily create a "Name" of the package, this string
of information contains 

 - The developer/organization behind the package
 - The package name itself


# Naming conventions

Package names are written in all lower-case due to the
fact that some operating systems treats file names
differently, for example on Linux `Aa.txt` and `aa.txt`
are two entirely different file names because of the
capitalization and on Windows it's treated as the same
file name.

Organizations or small developers use their domain name
in reverse to begin their package names, for example
`net.nosial.example` is a package named `example`
created by a programmer at `nosial.net`

Just like the Java naming convention, to avoid conflicts
of the same package name developers can use something
different, for example as pointed out in Java's package
naming convention developers can instead use something
like a region to name packages, for example
`net.nosial.region.example`


# References

For Java's package naming conventions see
[Naming a Package](https://docs.oracle.com/javase/tutorial/java/package/namingpkgs.html)
from the Oracle's Java documentation resource, as the 
same rules apply to NCC except for *some* illegal naming
conventions such as packages not being able to begin
with `int` or numbers