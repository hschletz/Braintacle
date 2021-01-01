<?php
/**
 * Tests for PrintForm controller plugin
 *
 * Copyright (C) 2011-2021 Holger Schletz <holger.schletz@web.de>
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

namespace Console\Test\Mvc\Controller\Plugin;

/**
 * Tests for PrintForm controller plugin
 */
class PrintFormTest extends \Library\Test\Mvc\Controller\Plugin\AbstractTest
{
    public function testInvokeWithConsoleForm()
    {
        $plugin = $this->_getPlugin(false);

        // Set up \Console\Form\Form using default renderer
        $form = $this->createMock('Console\Form\Form');
        $form->expects($this->once())
             ->method('render')
             ->will($this->returnValue('\Console\Form\Form default renderer'));

        // Evaluate plugin return value
        $viewModel = $plugin($form);
        $this->assertInstanceOf('Laminas\View\Model\ViewModel', $viewModel);
        $this->assertEquals('plugin/PrintForm.php', $viewModel->getTemplate());
        $this->assertEquals($form, $viewModel->form);

        // Invoke template and test output
        $application = \Library\Application::init('Console');
        $renderer = $application->getServiceManager()->get('ViewRenderer');
        $output = $renderer->render($viewModel);
        $this->assertEquals('\Console\Form\Form default renderer', $output);
    }
}
