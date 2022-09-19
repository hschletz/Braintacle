<?php

/**
 * Tests for the Library\Now service
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

class NowTest extends \PHPUnit\Framework\TestCase
{
    public function testService()
    {
        $application = \Library\Application::init('Library');
        $serviceManager = $application->getServiceManager();

        // Service must not be shared so that a different result is returned
        // each time.
        $now1 = $serviceManager->get('Library\Now');
        sleep(1);
        $now2 = $serviceManager->get('Library\Now');
        $this->assertGreaterThan($now1->getTimestamp(), $now2->getTimestamp());
    }
}
