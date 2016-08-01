<?php
/**
 * Display standard error page
 *
 * Copyright (C) 2011-2016 Holger Schletz <holger.schletz@web.de>
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

// Absence of the controller variable hints an invalid route which requires
// special treatment.
if (!isset($this->controller)) {
    $this->layout()->noRoute = true;
    print "<p class='textcenter'><strong>Error:</strong> No route matched.</p>\n";
    return;
}

print "<h1>An error occurred</h1>\n";
print $this->htmlElement('h2', $this->message);

// @codeCoverageIgnoreStart
if (isset($this->exception)) {
    try {
        $config = $this->getHelperPluginManager()->getServiceLocator()->get('Library\UserConfig');
    } catch (\Exception $e) {
        error_log($e->getMessage());
        print "<p>Cannot load config file. See web server error log for details.</p>\n";
        return;
    }
    if (@$config['debug']['display backtrace']) {
        print "<h3>Exception Message trace:</h3>\n";
        $exception = $this->exception;
        while ($exception) {
            print $this->htmlElement(
                'p',
                '<strong>Message:</strong> ' . $this->escapeHtml($exception->getMessage())
            );
            print $this->htmlElement(
                'p',
                sprintf(
                    '<strong>Source:</strong> %s, line %d',
                    $this->escapeHtml($exception->getFile()),
                    $this->escapeHtml($exception->getLine())
                )
            );
            print "<h4>Backtrace:</h4>\n";
            print $this->htmlElement('pre', $this->escapeHtml($exception->getTraceAsString()));

            $exception = $exception->getPrevious();
        }

        // The additional debug information below might contain sensitive data.
        if ($this->controller == 'login') {
            print 'Details hidden for security reasons.';
            return;
        }

        $request = $this->request;
        print "<h3>Request Parameters:</h3>\n";

        print "<h4>Method</h4>\n";
        print $this->htmlElement('p', $this->escapeHtml($request->getMethod()));

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
        error_log($this->exception->getMessage());
        print "<p class='textcenter'>See web server error log for details.</p>\n";
    }
}
