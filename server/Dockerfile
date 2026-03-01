# Sample container image for running the CGI wrapper.

FROM docker.io/library/perl:stable

# DBD::mysql does not compile in this container environment, and DBD::MariaDB is not supported yet.
RUN cpanm --notest --no-man-pages CGI::Tiny Date::Calc DBD::Pg DBD::SQLite XML::Simple
