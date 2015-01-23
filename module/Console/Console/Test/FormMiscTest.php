<?php
/**
 * Miscellaneous Form tests
 *
 * Copyright (C) 2011-2015 Holger Schletz <holger.schletz@web.de>
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

namespace Console\Test;

/**
 * Miscellaneous Form tests
 */
class FormMiscTest extends \PHPUnit_Framework_TestCase
{
    public function testSetDataIgnoresSubmitButtons()
    {
        $text1 = new \Zend\Form\Element\Text('text');
        $submit1 = new \Zend\Form\Element\Submit('submit');
        $submit1->setValue('this should remain unchanged');

        $text2 = new \Zend\Form\Element\Text('text');
        $submit2 = new \Zend\Form\Element\Submit('submit');
        $submit2->setValue('this should remain unchanged');

        $fieldset = new \Zend\Form\Fieldset('fieldset');
        $fieldset->add($text2);
        $fieldset->add($submit2);

        $form = new \Console\Form\Form;
        $form->add($text1);
        $form->add($submit1);
        $form->add($fieldset);

        $form->setData(
            array(
                'text' => 'value',
                'submit' => 'this should be ignored',
                'fieldset' => array(
                    'text' => 'value',
                    'submit' => 'this should be ignored',
                )
            )
        );
        $this->assertEquals('value', $text1->getValue());
        $this->assertEquals('value', $text2->getValue());
        $this->assertEquals('this should remain unchanged', $submit1->getValue());
        $this->assertEquals('this should remain unchanged', $submit2->getValue());
    }
}
