<?php
/**
 * Authentication service
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

namespace Library\Authentication;

/**
 * Authentication service
 *
 * Provided by the service manager as Library\AuthenticationService, this is the
 * service that should be used for authentication against the application's
 * database.
 */
class AuthenticationService extends \Zend\Authentication\AuthenticationService
implements \Zend\ServiceManager\ServiceLocatorAwareInterface
{
    /**
     * @internal
     */
    protected $_serviceLocator;

    /**
     * @internal
     */
    public function setServiceLocator(\Zend\ServiceManager\ServiceLocatorInterface $serviceLocator)
    {
        $this->_serviceLocator = $serviceLocator;
    }

    /**
     * @internal
     */
    public function getServiceLocator()
    {
        return $this->_serviceLocator;
    }

    /**
     * Attempt login with given credentials
     *
     * @param string $id Login name - empty name will not authenticate
     * @param string $password Password
     * @return bool Login success. Don't forget to check this!
     */
    public function login($id, $password)
    {
        if (!$id) {
            return false;
        }
        $adapter = new \Zend\Authentication\Adapter\DbTable($this->getServiceLocator()->get('Db'));
        $adapter->setTableName('operators')
                ->setIdentityColumn('id')
                ->setCredentialColumn('passwd')
                ->setCredentialTreatment("? AND (accesslvl=1 OR new_accesslvl='sadmin')") // TODO: drop access controls
                ->setIdentity($id)
                ->setCredential(md5($password)); // TODO: provide more secure authentication method

        return $this->authenticate($adapter)->isValid();
    }

    /**
     * Change identity of the logged in user
     *
     * The new identity is not validated, i.e. not checked for a valid account
     * name. However, the service must have an identity before this method is
     * called.
     *
     * @param $id New identity
     * @throws \InvalidArgumentException if $id is empty
     * @throws \LogicException if the session is not authenticated
     */
    public function changeIdentity($id)
    {
        if (!$id) {
            throw new \InvalidArgumentException('No identity provided');
        }
        if (!$this->hasIdentity()) {
            throw new \LogicException('Cannot change identity: not authenticated yet');
        }
        $this->storage->write($id);
    }
}
