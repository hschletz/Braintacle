<?php

/**
 * Tests for Model\Package\Metadata
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

namespace Model\Test\Package;

use Model\Package\Metadata;

/**
 * Tests for Model\Package\Metadata
 */
class MetadataTest extends \Model\Test\AbstractTest
{
    /** {@inheritdoc} */
    public function getDataSet()
    {
        return new \PHPUnit\DbUnit\DataSet\DefaultDataSet();
    }

    public function testSetPackageData()
    {
        $data = array(
            'Id' => '12345678',
            'Priority' => '5',
            'DeployAction' => 'store',
            'ActionParam' => '',
            'HashType' => 'hash_type',
            'Hash' => 'hash',
            'NumFragments' => '42',
            'Warn' => '0',
            'WarnMessage' => "warn_message\"\n\r\r\n",
            'WarnCountdown' => '23',
            'WarnAllowAbort' => '0',
            'WarnAllowDelay' => '0',
            'PostInstMessage' => '',
        );
        $model = new Metadata();
        $model->setPackageData($data);

        $this->assertEquals(1, $model->childNodes->length);
        $node = $model->documentElement;
        $this->assertEquals('DOWNLOAD', $node->tagName);
        $this->assertFalse($node->hasChildNodes());

        $this->assertEquals('12345678', $node->getAttribute('ID'));
        $this->assertEquals('5', $node->getAttribute('PRI'));
        $this->assertEquals('hash', $node->getAttribute('DIGEST'));
        $this->assertEquals('HTTP', $node->getAttribute('PROTO'));
        $this->assertEquals('42', $node->getAttribute('FRAGS'));
        $this->assertEquals('HASH_TYPE', $node->getAttribute('DIGEST_ALGO'));
        $this->assertEquals('Hexa', $node->getAttribute('DIGEST_ENCODE'));
        $this->assertEquals('warn_message&quot;<br><br>', $node->getAttribute('NOTIFY_TEXT'));
        $this->assertEquals('23', $node->getAttribute('NOTIFY_COUNTDOWN'));
        $this->assertEquals('rien', $node->getAttribute('GARDEFOU'));
    }

    public function packageDataActionParamsProvider()
    {
        return array(
            array('store', 'STORE', 'action_param', '', ''),
            array('launch', 'LAUNCH', '', 'action_param', ''),
            array('execute', 'EXECUTE', '', '', 'action_param'),
        );
    }

    /**
     * Test setPackageData()
     * @dataProvider packageDataActionParamsProvider
     * @param string $action Action to test
     * @param string $act Expected value for "ACT" attribute
     * @param string $path Expected value for "PATH" attribute
     * @param string $name Expected value for "NAME" attribute
     * @param string $command Expected value for "COMMAND" attribute
     */
    public function testSetPackageDataActionParams($action, $act, $path, $name, $command)
    {
        $data = array(
            'Id' => '12345678',
            'Priority' => '5',
            'DeployAction' => $action,
            'ActionParam' => 'action_param',
            'HashType' => 'hash_type',
            'Hash' => 'hash',
            'NumFragments' => '0',
            'Warn' => '0',
            'WarnMessage' => '',
            'WarnCountdown' => '',
            'WarnAllowAbort' => '0',
            'WarnAllowDelay' => '0',
            'PostInstMessage' => '',
        );
        $model = new Metadata();
        $model->setPackageData($data);
        $node = $model->documentElement;

        $this->assertEquals($act, $node->getAttribute('ACT'));
        $this->assertEquals($path, $node->getAttribute('PATH'));
        $this->assertEquals($name, $node->getAttribute('NAME'));
        $this->assertEquals($command, $node->getAttribute('COMMAND'));
    }

    public function setPackageDataBooleanValuesProvider()
    {
        return array(
            array('', '0'),
            array('0', '0'),
            array(0, '0'),
            array(false, '0'),
            array(null, '0'),
            array('1', '1'),
            array(1, '1'),
            array(true, '1'),
        );
    }

    /**
     * Test setPackageData() with boolean values
     * @dataProvider setPackageDataBooleanValuesProvider
     * @param mixed $input
     * @param string $expected
     */
    public function testSetPackageDataBooleanValues($input, $expected)
    {
        $data = array(
            'Id' => '12345678',
            'Priority' => '5',
            'DeployAction' => 'store',
            'ActionParam' => '',
            'HashType' => 'hash_type',
            'Hash' => '',
            'NumFragments' => '0',
            'Warn' => $input,
            'WarnMessage' => '',
            'WarnCountdown' => '',
            'WarnAllowAbort' => $input,
            'WarnAllowDelay' => $input,
            'PostInstMessage' => '',
        );
        $model = new Metadata();
        $model->setPackageData($data);
        $node = $model->documentElement;
        $this->assertSame($expected, $node->getAttribute('NOTIFY_USER'));
        $this->assertSame($expected, $node->getAttribute('NOTIFY_CAN_ABORT'));
        $this->assertSame($expected, $node->getAttribute('NOTIFY_CAN_DELAY'));
    }

    public function setPackageDataPostinstMessageProvider()
    {
        $messageEscaped = '&quot;<br><br>';
        $messageUnescaped = "\"\n\r\r\n";
        return array(
            array($messageUnescaped, $messageEscaped, '1'),
            array('', '', '0'),
        );
    }

    /**
     * Test setPackageData() behavior on PostInstMessage
     *
     * @param string $inputMessage Input message
     * @param string $documentMessage Expected message in the document
     * @param string$documentFlag Expected value of NEED_DONE_ACTION attribute
     * @dataProvider setPackageDataPostinstMessageProvider
     */
    public function testSetPackageDataPostinstMessage($inputMessage, $documentMessage, $documentFlag)
    {
        $data = array(
            'Id' => '12345678',
            'Priority' => '5',
            'DeployAction' => 'store',
            'ActionParam' => '',
            'HashType' => 'hash_type',
            'Hash' => 'hash',
            'NumFragments' => '42',
            'Warn' => '0',
            'WarnMessage' => '',
            'WarnCountdown' => '23',
            'WarnAllowAbort' => '0',
            'WarnAllowDelay' => '0',
            'PostInstMessage' => $inputMessage,
        );
        $model = new Metadata();
        $model->setPackageData($data);
        $node = $model->documentElement;
        $this->assertSame($documentFlag, $node->getAttribute('NEED_DONE_ACTION'));
        $this->assertSame($documentMessage, $node->getAttribute('NEED_DONE_ACTION_TEXT'));
    }

    public function testSetPackageDataOverwrite()
    {
        $data = array(
            'Id' => '12345678',
            'Priority' => '5',
            'DeployAction' => 'store',
            'ActionParam' => '',
            'HashType' => 'hash_type',
            'Hash' => '',
            'NumFragments' => '0',
            'Warn' => '0',
            'WarnMessage' => '',
            'WarnCountdown' => '',
            'WarnAllowAbort' => '0',
            'WarnAllowDelay' => '0',
            'PostInstMessage' => '',
        );
        $model = new Metadata();
        $model->setPackageData($data);
        $data['Priority'] = 7;
        $model->setPackageData($data);
        $this->assertEquals(7, $model->documentElement->getAttribute('PRI'));
    }

    /**
     * Test getPackageData()
     * @dataProvider packageDataActionParamsProvider
     * @param string $action Action to test
     * @param string $act Value for "ACT" attribute
     * @param string $path Value for "PATH" attribute
     * @param string $name Value for "NAME" attribute
     * @param string $command Value for "COMMAND" attribute
     */
    public function testGetPackageDataActionParams($action, $act, $path, $name, $command)
    {
        $messageEscaped = '&quot;<br><br/><br /><BR>';
        $messageUnescaped = "\"\n\n\n\n";
        $model = new Metadata();
        $node = $model->createElement('DOWNLOAD');
        $node->setAttribute('ID', '1');
        $node->setAttribute('PRI', '5');
        $node->setAttribute('ACT', $act);
        $node->setAttribute('DIGEST', '');
        $node->setAttribute('PROTO', 'HTTP');
        $node->setAttribute('FRAGS', '1');
        $node->setAttribute('DIGEST_ALGO', 'SHA1');
        $node->setAttribute('DIGEST_ENCODE', 'Hexa');
        $node->setAttribute('PATH', $path);
        $node->setAttribute('NAME', $name);
        $node->setAttribute('COMMAND', $command);
        $node->setAttribute('NOTIFY_USER', '1');
        $node->setAttribute('NOTIFY_TEXT', "warn$messageEscaped");
        $node->setAttribute('NOTIFY_COUNTDOWN', '23');
        $node->setAttribute('NOTIFY_CAN_ABORT', '0');
        $node->setAttribute('NOTIFY_CAN_DELAY', '0');
        $node->setAttribute('NEED_DONE_ACTION', '0');
        $node->setAttribute('NEED_DONE_ACTION_TEXT', '');
        $node->setAttribute('GARDEFOU', 'rien');
        $model->appendChild($node);

        $result = $model->getPackageData();
        $this->assertCount(8, $result);
        $this->assertEquals($action, $result['DeployAction']);
        $this->assertEquals('action_param', $result['ActionParam']);
        $this->assertEquals('1', $result['Warn']);
        $this->assertEquals("warn$messageUnescaped", $result['WarnMessage']);
        $this->assertEquals('23', $result['WarnCountdown']);
        $this->assertEquals('0', $result['WarnAllowAbort']);
        $this->assertEquals('0', $result['WarnAllowDelay']);
    }

    public function packageDataPostinstMessageProvider()
    {
        $messageEscaped = '&quot;<br><br/><br /><BR>';
        $messageUnescaped = "\"\n\n\n\n";
        return array(
            array('0', $messageEscaped, ''),
            array('1', $messageEscaped, $messageUnescaped),
            array('0', '', ''),
            array('1', '', ''),
        );
    }

    /**
     * Test getPackageData() behavior on PostInstMessage
     *
     * @param string $documentFlag value of NEED_DONE_ACTION attribute
     * @param mixed $documentMessage value of NEED_DONE_ACTION_TEXT attribute
     * @param mixed $resultMessage Expected result for PostInstMessage
     * @dataProvider packageDataPostinstMessageProvider
     */
    public function testGetPackageDataPostinstMessage($documentFlag, $documentMessage, $resultMessage)
    {
        $model = new Metadata();
        $node = $model->createElement('DOWNLOAD');
        $node->setAttribute('ID', '1');
        $node->setAttribute('PRI', '5');
        $node->setAttribute('ACT', 'STORE');
        $node->setAttribute('DIGEST', '');
        $node->setAttribute('PROTO', 'HTTP');
        $node->setAttribute('FRAGS', '1');
        $node->setAttribute('DIGEST_ALGO', 'SHA1');
        $node->setAttribute('DIGEST_ENCODE', 'Hexa');
        $node->setAttribute('PATH', 'PATH');
        $node->setAttribute('NAME', '');
        $node->setAttribute('COMMAND', '');
        $node->setAttribute('NOTIFY_USER', '0');
        $node->setAttribute('NOTIFY_TEXT', '');
        $node->setAttribute('NOTIFY_COUNTDOWN', '23');
        $node->setAttribute('NOTIFY_CAN_ABORT', '0');
        $node->setAttribute('NOTIFY_CAN_DELAY', '0');
        $node->setAttribute('NEED_DONE_ACTION', $documentFlag);
        $node->setAttribute('NEED_DONE_ACTION_TEXT', $documentMessage);
        $node->setAttribute('GARDEFOU', 'rien');
        $model->appendChild($node);
        $result = $model->getPackageData();
        $this->assertEquals($resultMessage, $result['PostInstMessage']);
    }

    public function testGetPackageDataForcesValidDocument()
    {
        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('Validation of XML document failed');
        $model = new Metadata();
        @$model->getPackageData();
    }
}
