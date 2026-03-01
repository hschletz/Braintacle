#!/usr/bin/perl

# CGI wrapper for running without mod_perl.

use strict;
use warnings;

# Prepend this script's directory to the module search path.
use FindBin;
use lib "$FindBin::Dir";

BEGIN {
    # The MOD_PERL environment variable is set by mod_perl. It is evaluated in
    # some places to determine the environment (CGI or mod_perl) and take
    # appropriate action. If this variable is present in a CGI environment, that
    # code would fail mysteriously. Bail out early with a more helpful error
    # message.
    if (exists $ENV{MOD_PERL}) {
        die "MOD_PERL environment variable must not be set for $FindBin::Script";
    }
}

use Apache::Ocsinventory;
use Apache::Ocsinventory::Server::Modperl2 ('APACHE_OK');
use Braintacle::Request;
use CGI::Tiny;

cgi {
    my $cgi = $_;

    my $request = Braintacle::Request->new(\%ENV, $cgi->headers);
    my $status = Apache::Ocsinventory::handler($request);
    if ($status != APACHE_OK) {
        $cgi->set_response_status($status);
    }
    my $headers = $request->headers_out;
    foreach my $header (keys %$headers) {
        $cgi->add_response_header($header, $headers->{$header});
    }
    $cgi->render_chunk(data => $request->getBody())
}
