<?php
/**
 * Class representing a registry value
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
 * A registry value
 *
 * Properties:
 *
 * - **Id** ID
 * - **Name** User-defined display name
 * - **RootKey** Root key, one of the HKEY_* constants
 * - **SubKeys** Path to the key that contains the value, with components separated by backslashes
 * - **ValueConfigured** Name of the registry value to inventory. If NULL, all values be inventoried.
 * - **ValueInventoried** Name of the actually inventoried value.
 * - **FullPath** Textual representation of configured value
 *
 * The ValueInventoried property is only valid for Model_RegistryData's 'Value'
 * property. If ValueConfigured is set to inventory all values, it contains the
 * name of the registry value for each data item. Otherwise, it is identical to
 * ValueConfigured.
 *
 * Don't confuse 'Value' with its content (which is accessible via
 * Model_RegistryData). In registry terms, 'Value' refers to the name of the
 * entry that holds the data, while 'Key' is the container that holds the value.
 * While this terminology differs from common usage, it is used throughout the
 * official Windows documentation and API, and Braintacle follows this
 * convention.
 *
 * @package Models
 */
class Model_RegistryValue extends Model_Abstract
{

    /**
     * Root key
     **/
    const HKEY_CLASSES_ROOT = 0;

    /**
     * Root key
     **/
    const HKEY_CURRENT_USER = 1;

    /**
     * Root key
     **/
    const HKEY_LOCAL_MACHINE = 2;

    /**
     * Root key
     **/
    const HKEY_USERS = 3;

    /**
     * Root key
     **/
    const HKEY_CURRENT_CONFIG = 4;

    /**
     * Root key
     **/
    const HKEY_DYN_DATA = 5;

    /** {@inheritdoc} */
    protected $_propertyMap = array(
        // Values from 'regconfig' table
        'Id' => 'id',
        'Name' => 'name',
        'RootKey' => 'regtree',
        'SubKeys' => 'regkey',
        'ValueConfigured' => 'regvalue',
    );

    /** {@inheritdoc} */
    protected $_types = array(
        'Id' => 'integer',
        'RootKey' => 'enum',
    );

    /**
     * Data for the ValueInventoried property
     * @var string
     **/
    protected $_valueInventoried;

    /**
     * Textual representations of root keys, in the order used by the Windows registry editor
     **/
    protected static $_rootKeys = array(
        self::HKEY_CLASSES_ROOT => 'HKEY_CLASSES_ROOT',
        self::HKEY_CURRENT_USER => 'HKEY_CURRENT_USER',
        self::HKEY_LOCAL_MACHINE => 'HKEY_LOCAL_MACHINE',
        self::HKEY_USERS => 'HKEY_USERS',
        self::HKEY_CURRENT_CONFIG => 'HKEY_CURRENT_CONFIG',
        self::HKEY_DYN_DATA => 'HKEY_DYN_DATA',
    );

    /**
     * Instantiate a new object with data for the given ID
     *
     * @param integer $id ID of an existing value definition
     * @return \Model_RegistryValue
     * @throws \RuntimeException if given ID id invalid
     **/
    public function fetchById($id)
    {
        $value = self::createStatementStatic($id)->fetchObject(__CLASS__);
        if (!$value) {
            throw new \RuntimeException('Invalid registry value ID: ' . $id);
        }
        return $value;
    }

    /**
     * Fetch all registry value definitions
     *
     * @return \Model_RegistryValue[]
     */
    public function fetchAll()
    {
        return $this->_fetchAll(self::createStatementStatic());
    }

    /**
     * Generate statement to retrieve all value definitions
     *
     * @param integer $id Return only given value. Default: return all values.
     * @return Zend_Db_Statement Statement
     **/
    public static function createStatementStatic($id=null)
    {
        $select = Model_Database::getAdapter()->select()
            ->from('regconfig')
            ->order('name');
        if ($id) {
            $select->where('id = ?', $id);
        }
        return $select->query();
    }

    /**
     * {@inheritdoc}
     **/
    public function getProperty($property, $rawValue=false)
    {
        switch ($property) {
            case 'ValueInventoried':
                if ($this->_valueInventoried) {
                    $value = $this->_valueInventoried;
                } else {
                    $value = $this->getValueConfigured();
                }
                break;
            case 'FullPath':
                $value  = self::rootKey($this->getRootKey());
                $value .= '\\';
                $value .= $this->getSubKeys();
                $value .= '\\';
                $valueConfigured = $this->getValueConfigured();
                if ($valueConfigured == '') {
                    $valueConfigured = '*';
                }
                $value .= $valueConfigured;
                break;
            default:
                $value = parent::getProperty($property, $rawValue);
        }
        if (!$rawValue and $property == 'ValueConfigured' and $value == '*') {
            $value = null;
        }
        return $value;
    }

    /** {@inheritdoc} */
    public function getPropertyType($property)
    {
        if ($property == 'ValueInventoried') {
            $type = 'text';
        } else {
            $type = parent::getPropertyType($property);
        }
        return $type;
    }

    /** {@inheritdoc} */
    public function setProperty($property, $value)
    {
        if ($property == 'ValueInventoried') {
            $this->_valueInventoried = $value;
        } else {
            parent::setProperty($property, $value);
        }
    }

    /**
     * Retrieve textual representations of root keys
     *
     * The keys of the returned array are the HKEY_* constants. The ordering is
     * the same as in the Windows registry editor.
     * @return array
     **/
    public static function rootKeys()
    {
        return self::$_rootKeys;
    }

    /**
     * Retrieve textual representation of a given root key
     * @param integer $root One of the HKEY_* constants
     * @return string
     */
    public static function rootKey($root)
    {
        if (!isset(self::$_rootKeys[$root])) {
            throw new UnexpectedValueException('Invalid root key: ' . $root);
        }
        return self::$_rootKeys[$root];
    }

    /**
     * Add a value definition
     *
     * @param string $name Name of new value
     * @param integer $rootKey One of the HKEY_* constants
     * @param string $subKeys Path to the key that contains the value, with components separated by backslashes
     * @param string $value Inventory only given value (default: all values for the given key)
     * @throws RuntimeException if a value with the same name already exists.
     * @throws DomainException if $rootkey is not one of the HKEY_* constants or $subKeys is empty
     **/
    public function add($name, $rootKey, $subKeys, $value=null)
    {
        $db = Model_Database::getAdapter();
        if ($db->fetchOne('SELECT name FROM regconfig WHERE name = ?', $name)) {
            throw new RuntimeException('Value already exists: ' . $name);
        }
        if (!isset(self::$_rootKeys[$rootKey])) {
            throw new DomainException('Invalid root key: ' . $rootKey);
        }
        if (empty($subKeys)) {
            throw new DomainException('Subkeys must not be empty');
        }

        if (!$value) {
            $value = '*';
        }
        $db->insert(
            'regconfig',
            array(
                'name' => $name,
                'regtree' => $rootKey,
                'regkey' => $subKeys,
                'regvalue' => $value
            )
        );
    }

    /**
     * Rename a value definition
     *
     * @param string $name New name. If identical with existing name, do nothing.
     * @throws RuntimeException if a definition with the same name already exists.
     * @throws DomainException if $name is empty
     **/
    public function rename($name)
    {
        if ($name == $this->getName()) {
            return;
        }

        if (empty($name)) {
            throw new DomainException('Name must not be empty.');
        }
        $db = Model_Database::getAdapter();
        if ($db->fetchOne('SELECT name FROM regconfig WHERE name = ?', $name)) {
            throw new RuntimeException('Value already exists: ' . $name);
        }

        $db->beginTransaction();
        $db->update(
            'registry',
            array('name' => $name),
            array('name = ?' => $this->getName())
        );
        $db->update(
            'regconfig',
            array('name' => $name),
            array('id = ?' => $this->getId())
        );
        $db->commit();
        $this->setName($name);
    }

    /**
     * Delete this value definition and its inventoried data
     **/
    public function delete()
    {
        $db = Model_Database::getAdapter();
        $db->beginTransaction();
        $db->delete('registry', array('name = ?' => $this->getName()));
        $db->delete('regconfig', array('id = ?' => $this->getId()));
        $db->commit();
    }

}
