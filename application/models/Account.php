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
     * Constructor
     * @param string $id Optional: populate instance with data for given account
     * @throws RuntimeException if $id is given and no account with that name exists
     */
    public function __construct($id=null)
    {
        parent::__construct();

        if (!$id) {
            return;
        }

        $row = Model_Database::getAdapter()->select()
               ->from('operators', array_values($this->_propertyMap))
               ->where('id=?', $id)
               ->query()
               ->fetch(Zend_Db::FETCH_ASSOC);
        if (!$row) {
            throw new RuntimeException('Invalid login name supplied');
        }
        foreach ($row as $column => $value) {
            $this->$column = $value;
        }
    }

    /**
     * Return a statement object with all accounts
     * @param string $order Property to sort by
     * @param string Sorting order (asc|desc)
     * @return Zend_Db_Statement
     */
    public static function createStatementStatic($order='Id', $direction='asc')
    {
        $dummy = new self;
        $map = $dummy->getPropertyMap();
        return Model_Database::getAdapter()
            ->select()
            ->from('operators', array_values($map))
            ->order(self::getOrder($order, $direction, $map))
            ->query();
    }

    /**
     * Create new account
     * @param array $data List of properties to set. Unknown keys will be ignored.
     * @param string $password Password for the new account, must not be empty.
     * @throws UnexpectedValueException if no ID or password is given.
     */
    public static function create($data, $password)
    {
        if (!$data['Id']) {
            throw new UnexpectedValueException('No login name specified');
        }
        if (!$password) {
            throw new UnexpectedValueException('No password specified');
        }

        $dummy = new self;
        $map = $dummy->getPropertyMap();

        // Compose array of columns to set
        foreach ($data as $property => $value) {
            if (isset($map[$property])) { // Ignore unknown keys
                $insert[$map[$property]] = $value;
            }
        }
        // Set password
        $insert['passwd'] = md5($password);
        // Set admin privilege
        $insert['accesslvl'] = self::OLD_PRIVILEGE_ADMIN;
        $insert['new_accesslvl'] = self::PRIVILEGE_ADMIN;

        Model_Database::getAdapter()->insert('operators', $insert);
    }

    /**
     * Update existing account
     * @param string $id Login name of account to update
     * @param array $data List of properties to set. Unknown keys will be ignored.
     * @param string $password New password. If empty, password will remain unchanged.
     * @throws UnexpectedValueException if no ID is given.
     */
    public static function update($id, $data, $password)
    {
        if (!$id) {
            throw new UnexpectedValueException('No login name specified');
        }

        $dummy = new self;
        $map = $dummy->getPropertyMap();

        // Compose array of columns to set
        foreach ($data as $property => $value) {
            if (isset($map[$property])) { // Ignore unknown keys
                $update[$map[$property]] = $value;
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
        if (isset($data['Id'])) {
            // If the account of the logged in user is changed, the session data
            // must be updated, so that the identity stays valid in case of a
            // changed login name.
            $authStorage = Zend_Auth::getInstance()->getStorage();
            if ($id == $authStorage->read()) {
                $authStorage->write($data['Id']);
            }
        }
    }

    /**
     * Delete account
     * @param string $id Login name of account to delete
     * @throws RuntimeException if the account to delete is logged in for the current session
     */
    public static function delete($id)
    {
        if ($id == Zend_Auth::getInstance()->getIdentity()) {
            throw new RuntimeException('Your own account cannot be deleted.');
        }

        Model_Database::getAdapter()->delete(
            'operators',
            array('id=?' => $id)
        );
    }

    /**
     * Attempt login with given credentials
     * @param string $id Login name
     * @param string $password Password
     * @return bool Login success. Don't forget to check this!
     */
    public static function login($id, $password)
    {
        $adapter = new Zend_Auth_Adapter_DbTable;
        $adapter->setTableName('operators')
                ->setIdentityColumn('id')
                ->setCredentialColumn('passwd')
                ->setCredentialTreatment("? AND (accesslvl=1 OR new_accesslvl='sadmin')")
                ->setIdentity($id)
                ->setCredential(md5($password));

        return Zend_Auth::getInstance()->authenticate($adapter)->isValid();
    }

}
