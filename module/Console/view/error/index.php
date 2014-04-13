<?php
/**
 * Display standard error page
 *
 * Copyright (C) 2011-2014 Holger Schletz <holger.schletz@web.de>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 */

print "<h1>An error occurred</h1>\n";
print $this->htmlTag('h2', $this->message);

// @codeCoverageIgnoreStart
if (\Library\Application::isDevelopment() and isset($this->exception)) {
    print "<h3>Exception Message trace:</h3>\n";
    $exception = $this->exception;
    while ($exception) {
        print $this->htmlTag(
            'p',
            '<strong>Message:</strong> ' . $this->escapeHtml($exception->getMessage())
        );
        print $this->htmlTag(
            'p',
            sprintf(
                '<strong>Source:</strong> %s, line %d',
                $this->escapeHtml($exception->getFile()),
                $this->escapeHtml($exception->getLine())
            )
        );
        $exception = $exception->getPrevious();
    }

    // The additional debug information below might contain sensitive data.
    if ($this->controller == 'login') {
        print 'Details hidden for security reasons.';
        return;
    }

    print "<h3>Stack trace:</h3>\n";
    print $this->htmlTag('pre', $this->escapeHtml($this->exception->getTraceAsString()));

    $request = $this->request;
    print "<h3>Request Parameters:</h3>\n";

    print "<h4>Method</h4>\n";
    print $this->htmlTag('p', $this->escapeHtml($request->getMethod()));

    print "<h4>URL parameters</h4>\n";
    \Zend\Debug\Debug::dump($request->getQuery());

    print "<h4>POST parameters</h4>\n";
    \Zend\Debug\Debug::dump($request->getPost());

    print "<h4>Files</h4>\n";
    \Zend\Debug\Debug::dump($request->getFiles());

    print "<h4>HTTP headers</h4>\n";
    \Zend\Debug\Debug::dump($request->getHeaders()->toArray());

    print "<h4>Environment variables</h4>\n";
    \Zend\Debug\Debug::dump($request->getEnv());
} else {
    print "<p class='textcenter'>Details can be found in the web server error log.</p>\n";
}
