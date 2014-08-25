<?php
/**
 * Tests for the FormYesNo helper
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
 */

namespace Library\Test\View\Helper;

use \Zend\Dom\Document\Query as Query;

/**
 * Tests for the FormYesNo helper
 */
class FormYesNoTest extends AbstractTest
{
    /**
     * Tests for the __invoke() method
     */
    public function testInvoke()
    {
        $helper = $this->_getHelper();
        $result = $helper('TestCaption', array('hiddenName' => 'hiddenValue'));
        $document = new \Zend\Dom\Document($result);

        $this->assertCount(1, Query::execute('//p[text()="TestCaption"]', $document));
        $this->assertCount(1, Query::execute('//form[@action=""][@method="POST"]', $document));
        $this->assertCount(
            1,
            Query::execute('//input[@type="hidden"][@name="hiddenName"][@value="hiddenValue"]', $document)
        );
        $this->assertCount(1, Query::execute('//input[@type="submit"][@name="yes"][@value="Yes"]', $document));
        $this->assertCount(1, Query::execute('//input[@type="submit"][@name="no"][@value="No"]', $document));
    }
}
