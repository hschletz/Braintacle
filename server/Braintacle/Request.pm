# Reimplemenation of the mod_perl request object.
#
# This is the object that is provided to the handler() function. This module
# implements a subset of the mod_perl request object (only the methods that are
# actually used) and some additional accessor methods.

package Braintacle::Request;

use strict;
use warnings;
use feature 'signatures';

use Braintacle::DirConfig;

# Constructor.
sub new($class, $env, $headers)
{
    # CGI::Tiny provides header names all lowercase, but the application
    # capitalizes the first letter when looking up a header.
    my %requestHeaders;
    foreach my $key (keys %$headers) {
        $requestHeaders{ucfirst($key)} = $headers->{$key};
    }

    my $request = {
        body => '',
        dirConfig => Braintacle::DirConfig->new($env),
        env => $env,
        requestHeaders => \%requestHeaders,
        responseHeaders => {},
    };

    return bless $request, $class;
}

# Nonstandard method to retrieve the content that was written via print().
sub getBody($self)
{
    return $self->{body};
}

# Set Content-Type header on response.
sub content_type($self, $contentType)
{
    $self->{responseHeaders}->{'Content-type'} = $contentType;
}

# Get variables via the DirConfig object.
sub dir_config($self, $key = undef)
{
    return $self->{dirConfig}->get($key);
}

# Get request headers.
sub headers_in($self)
{
    return $self->{requestHeaders};
}

# Get response headers.
sub headers_out($self)
{
    return $self->{responseHeaders};
}

# Get request method.
sub method($self)
{
    return $self->{env}->{REQUEST_METHOD};
}

# Append string to response body.
sub print($self, $content)
{
    $self->{body} .= $content;
}

# Get environment variable. Name is looked up in uppercase.
sub subprocess_env($self, $var)
{
    return $self->{env}->{uc($var)}
}

1;
