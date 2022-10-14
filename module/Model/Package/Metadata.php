<?php

/**
 * Package metadata XML
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

namespace Model\Package;

/**
 * Package metadata XML
 *
 * The schema defines a single element with all metadata in its attributes.
 * These should be accessed via setPackageData() and getPackageData().
 */
class Metadata extends \Library\DomDocument
{
    /** {@inheritdoc} */
    public function getSchemaFilename()
    {
        return \Model\Module::getPath('data/RelaxNG/PackageMetadata.rng');
    }

    /**
     * Set attributes from given package
     *
     * Attributes are populated with values from the given package and
     * hardcoded defaults.
     */
    public function setPackageData(array $data)
    {
        $node = $this->createElement('DOWNLOAD');

        $node->setAttribute('ID', $data['Id']);
        $node->setAttribute('PRI', $data['Priority']);
        $node->setAttribute('ACT', strtoupper($data['DeployAction']));
        $node->setAttribute('DIGEST', $data['Hash']);
        $node->setAttribute('PROTO', 'HTTP');
        $node->setAttribute('FRAGS', $data['NumFragments']);
        $node->setAttribute('DIGEST_ALGO', strtoupper($data['HashType']));
        $node->setAttribute('DIGEST_ENCODE', 'Hexa');
        $node->setAttribute('PATH', ($data['DeployAction'] == 'store' ? $data['ActionParam'] : ''));
        $node->setAttribute('NAME', ($data['DeployAction'] == 'launch' ? $data['ActionParam'] : ''));
        $node->setAttribute('COMMAND', ($data['DeployAction'] == 'execute' ? $data['ActionParam'] : ''));
        $node->setAttribute('NOTIFY_USER', $data['Warn'] ? '1' : '0');
        $node->setAttribute('NOTIFY_TEXT', $this->escapeMessage($data['WarnMessage']));
        $node->setAttribute('NOTIFY_COUNTDOWN', $data['WarnCountdown']);
        $node->setAttribute('NOTIFY_CAN_ABORT', $data['WarnAllowAbort'] ? '1' : '0');
        $node->setAttribute('NOTIFY_CAN_DELAY', $data['WarnAllowDelay'] ? '1' : '0');
        $node->setAttribute('NEED_DONE_ACTION', $data['PostInstMessage'] ? '1' : '0');
        $node->setAttribute('NEED_DONE_ACTION_TEXT', $this->escapeMessage($data['PostInstMessage']));
        $node->setAttribute('GARDEFOU', 'rien');

        if ($this->hasChildNodes()) {
            $this->replaceChild($node, $this->firstChild);
        } else {
            $this->appendChild($node);
        }
    }

    /**
     * Retrieve package data from attributes
     *
     * Only action and notification settings are returned. All other relevant
     * attributes are available from the database. Data is enforced to be valid.
     *
     * @return array
     */
    public function getPackageData()
    {
        $this->forceValid();
        $node = $this->documentElement;
        $map = array(
            'store' => 'PATH',
            'launch' => 'NAME',
            'execute' => 'COMMAND'
        );
        $action = strtolower($node->getAttribute('ACT'));
        return array(
            'DeployAction' => $action,
            'ActionParam' => $node->getAttribute($map[$action]),
            'Warn' => $node->getAttribute('NOTIFY_USER'),
            'WarnMessage' => $this->unescapeMessage($node->getAttribute('NOTIFY_TEXT')),
            'WarnCountdown' => $node->getAttribute('NOTIFY_COUNTDOWN'),
            'WarnAllowAbort' => $node->getAttribute('NOTIFY_CAN_ABORT'),
            'WarnAllowDelay' => $node->getAttribute('NOTIFY_CAN_DELAY'),
            'PostInstMessage' => $node->getAttribute('NEED_DONE_ACTION') ?
                $this->unescapeMessage($node->getAttribute('NEED_DONE_ACTION_TEXT')) :
                ''
        );
    }

    /**
     * Escape user notification messages
     *
     * The Windows agent interprets user notification messages as HTML. For this
     * reason, line breaks are converted to BR tags.
     *
     * The agent passes the message via command line internally. Its command
     * line parser does not handle double quotes properly which must be
     * transformed to HTML entities. Unfortunately this may not work well with
     * HTML attributes which should be enclosed in single quotes instead. There
     * is no easy way to distinct between attribute delimiters and literal
     * quotation marks.
     *
     * @param string $message User notification message
     * @return string Escaped string
     */
    protected function escapeMessage($message)
    {
        $message = str_replace('"', '&quot;', $message);
        $message = str_replace(array("\r\n", "\n\r", "\n", "\r"), '<br>', $message);
        return $message;
    }

    /**
     * Unescape string encoded by escapeMessage()
     *
     * The returned string may not be identical to the original string because
     * escapeMessage() is not fully reversible, but should sufficiently match
     * the original content. Line breaks are returned as \\n.
     *
     * @param string $message Escaped user notification message
     * @return string Unescaped string
     */
    protected function unescapeMessage($message)
    {
        $message = preg_replace('#<br\s*/?>#i', "\n", $message);
        $message = str_replace('&quot;', '"', $message);
        return $message;
    }
}
