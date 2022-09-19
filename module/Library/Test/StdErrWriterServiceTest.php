<?php

/**
 * Tests for the Library\Log\Writer\StdErr service
 *
 * Copyright (C) 2011-2022 Holger Schletz <holger.schletz@web.de>
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

namespace Library\Test;

class StdErrWriterServiceTest extends \PHPUnit\Framework\TestCase
{
    public function testService()
    {
        $application = \Library\Application::init('Library');
        $serviceManager = $application->getServiceManager();

        $writer = $serviceManager->get('Library\Log\Writer\StdErr');
        $this->assertInstanceOf(\Laminas\Log\Writer\Stream::class, $writer);
    }
}
