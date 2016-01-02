<?php
/**
 * Upload an inventory file to the communication server
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
 */

namespace Library;

/**
 * Upload an inventory file to the communication server
 */
class InventoryUploader
{
    /**
     * Server URI
     * @var string
     */
    protected $_serverUri;

    /**
     * HTTP adapter
     * @var string|\Zend\Http\Client\Adapter\AdapterInterface
     */
    protected $_adapter;

    /**
     * Constructor
     *
     * @param string $serverUri
     * @param string|\Zend\Http\Client\Adapter\AdapterInterface $adapter default: 'Zend\Http\Client\Adapter\Socket'
     */
    public function __construct($serverUri, $adapter = 'Zend\Http\Client\Adapter\Socket')
    {
        $this->_serverUri = $serverUri;
        $this->_adapter = $adapter;
    }

    /**
     * Upload a file
     *
     * @param string $fileName File name
     * @return \Zend\Http\Response Server response
     */
    public function uploadFile($fileName)
    {
        return $this->uploadData(\Library\FileObject::fileGetContents($fileName));
    }

    /**
     * Upload inventory data
     *
     * @param string $data Inventory data (XML or zlib-compressed XML)
     * @return \Zend\Http\Response Server response
     */
    public function uploadData($data)
    {
        $client = new \Zend\Http\Client(
            $this->_serverUri,
            array(
                'adapter' => $this->_adapter,
                'strictredirects' => true, // required for POST requests
                'useragent' => 'Braintacle_local_upload', // Substring 'local' required for correct server operation
            )
        );
        $client->setMethod('POST')
               ->setHeaders(array('Content-Type' => 'application/x-compress'))
               ->setRawBody($data);
        return $client->send();
    }
}
