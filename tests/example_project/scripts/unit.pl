use warnings;
print("Hello, World!\n");
print("What is your name? ");
my $name = <STDIN>;
chomp($name);
print("Hello, $name\n");