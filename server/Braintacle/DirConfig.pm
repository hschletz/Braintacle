# Variables which would be set via PerlSetVar in mod_perl.
#
# In absence of mod_perl, these variables are configured as regular environment
# variables.

package Braintacle::DirConfig;

use strict;
use warnings;
use feature 'signatures';

sub new($class, $env)
{
    my $dirConfig = {
        env => $env
    };

    return bless $dirConfig, $class;
}

sub get($self, $key)
{
    return $key ? $self->{env}->{$key} : $self;
}

1;
