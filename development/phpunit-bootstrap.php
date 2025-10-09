<?php

// The BypassFinals PHPUnit extension cannot be used because it is executed only
// after data providers have been executed. If a data provider uses a final
// class, that class gets loaded without the BypassFinals hack, and the class
// can no longer be mocked later. The bootstrap script is run before the data
// providers, making it work here.
\DG\BypassFinals::enable();
