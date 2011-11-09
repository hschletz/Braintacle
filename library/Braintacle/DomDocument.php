<?php
/**
 * Interface to XML documents
 *
 * $Id$
 *
 * Copyright (C) 2011 Holger Schletz <holger.schletz@web.de>
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
 * @package Library
 * @filesource
 */
/**
 * Interface to XML documents
 *
 * This class extends the DOMDocument class with application specific
 * functionality.
 * @package Library
 */
class Braintacle_DomDocument extends DOMDocument
{

    /**
     * Constructor
     *
     * This constructor provides reasonable defaults, so it can be typically
     * invoked without arguments and subsequent initilaization.
     * @param string $version Default: 1.0
     * @param string $encoding Default: UTF-8
     */
    function __construct($version='1.0', $encoding='UTF-8')
    {
        parent::__construct($version, $encoding);
        $this->formatOutput = true;
    }

    /**
     * Validate document
     *
     * The document gets validated against one of the RELAX NG schema files in
     * the xml/ directory. A RuntimeException is thrown on error. Details are
     * available from the generated warnings.
     * @param string $schema Name of the schema (filename without path or .rng extension)
     */
    public function forceValid($schema)
    {
        if (!ctype_alpha($schema)) {
            throw new UnexpectedValueException($schema . ' is not a valid schema name');
        }
        if (!$this->relaxNGValidate(APPLICATION_PATH . "/../xml/$schema.rng")) {
            throw new RuntimeException('Validation of XML document failed');
        }
    }

}
