<?php
/**
 * Class representing a Braintacle user account
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
 *
 * @package Models
 */
/**
 * Class representing a Braintacle user account
 *
 * This is the interface to Braintacle's user accounts. Available properties are:
 *
 * * Id: Login name
 * * FirstName: First name (optional)
 * * LastName: Last name (optional)
 * * MailAddress: E-Mail address (optional)
 * * Comment: Comment (optional)
 *
 * There is intentionally no property for the password. The password is
 * write-only for security reasons and can only be set via {@link create()}.
 *
 * @method string getId() Retrieve login name
 * @method string getFirstName() Retrieve first name
 * @method string getLastName() Retrieve last name
 * @method string getMailaddress() Retrieve E-Mail address
 * @method string getComment() Retrieve comment
 * @package Models
 */
class Model_Account extends Model_Abstract
{

    /**
     * Constants for access privileges
     *
     * There are 2 equivalent columns that specify the privileges: 'accesslvl'
     * (OLD_PRIVILEGE_* constants) and new_accesslvl (PRIVILEGE_* constants).
     * This is a mess, and both columns should be always be used for maximum
     * compatibility.
     *
     * Braintacle does not support different privileges. All accounts are
     * created with admin privileges, and only admin accounts can log in. For
     * this reason, constants are defined only for the ADMIN privilege.
     */
    const PRIVILEGE_ADMIN = 'sadmin';
    /**
     * See description for PRIVILEGE_ADMIN
     */
    const OLD_PRIVILEGE_ADMIN = 1;

    /** {@inheritdoc} */
    protected $_propertyMap = array(
        'Id' => 'id',
        'FirstName' => 'firstname',
        'LastName' => 'lastname',
        'MailAddress' => 'email',
        'Comment' => 'comments',
    );

    /** {@inheritdoc} */
    protected $_types = array(
        'Comment' => 'clob',
    );

    /**
     * Authentication service
     * @var \Library\Authentication\AuthenticationService
     */
    protected $_authService;

    /**
     * Constructor
     *
     * @param \Library\Authentication\AuthenticationService $authService Authentication service
     */
    public function __construct(\Library\Authentication\AuthenticationService $authService)
    {
        parent::__construct();
        $this->_authService = $authService;
    }

    /**
     * Update existing account
     *
     * @param array $data List of properties to set. Unknown keys will be ignored.
     * @param string $password New password. If empty, password will remain unchanged.
     */
    public function update($data, $password)
    {
        $id = $this['Id'];
        if (!$id) {
            throw new UnexpectedValueException('No login name specified');
        }

        // Compose array of columns to set
        foreach ($data as $property => $value) {
            if (isset($this->_propertyMap[$property])) { // Ignore unknown keys
                $update[$this->_propertyMap[$property]] = $value;
            }
        }
        // Set password if specified
        if ($password) {
            $update['passwd'] = md5($password);
        }

        Model_Database::getAdapter()->update(
            'operators',
            $update,
            array('id=?' => $id)
        );

        if (isset($data['Id']) and $id == $this->_authService->getIdentity()) {
            // If the account name of the logged in user is changed, the
            // identity must be updated to remain valid.
            $this->_authService->changeIdentity($data['Id']);
        }
    }
}
