<?php
/**
 * DOM document
 *
 * Copyright (C) 2011-2020 Holger Schletz <holger.schletz@web.de>
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

namespace Library;

use TheSeer\fDOM\fDOMException;

/**
 * DOM document
 */
class DomDocument extends \TheSeer\fDOM\fDOMDocument
{
    /**
     * Retrieve full path to the RELAX NG schema file defining this document type
     *
     * This is not implemented (throws an exception). Subclasses can override
     * this method to support validation.
     *
     * @return string
     * @throws \LogicException if not implemented
     */
    public function getSchemaFilename()
    {
        throw new \LogicException(get_class($this) . ' has no schema defined');
    }

    /**
     * Validate document, return status
     *
     * The document is validated against the RELAX NG schema defined by
     * getSchemaFilename() which must be implemented by a subclass. Details are
     * available from the generated warnings.
     *
     * @return bool Validation result
     */
    public function isValid()
    {
        return $this->relaxNGValidate($this->getSchemaFilename());
    }

    /**
     * Validate document, throw exception on error
     *
     * The document gets validated against the RELAX NG schema defined by
     * getSchemaFilename() which must be implemented by a subclass.
     * A \RuntimeException is thrown on error. Details are shown in the
     * exception message.
     *
     * **Warning:** The libXML error buffer gets reset before validation. It
     * will only contain errors relevant to the current validation afterwards.
     *
     * @throws \RuntimeException if document is not valid
     */
    public function forceValid()
    {
        libxml_clear_errors();
        if (!$this->isValid()) {
            $message = 'Validation of XML document failed.';
            foreach (libxml_get_errors() as $error) {
                $message .= sprintf(' line %d: %s', $error->line, $error->message);
            }
            throw new \RuntimeException($message);
        }
    }

    /**
     * Write XML content to file
     *
     * This is a reimplementation with improved error handling. An exception is
     * thrown on error, and no file remains on disk.
     *
     * @param string $filename
     * @param integer $options
     * @return integer number of bytes written
     * @throws \RuntimeException if a write error occurs
     */
    public function save($filename, $options = 0)
    {
        // Don't use parent::save(). It won't report a disk full condition, and
        // a truncated file would remain on disk.
        $xml = $this->saveXml(null, $options);
        $fileSystem = new \Symfony\Component\Filesystem\Filesystem;
        $fileSystem->dumpFile($filename, $xml);
        return strlen($xml);
    }
}
