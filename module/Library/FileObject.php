<?php

/**
 * Replacement for \SplFileObject
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

namespace Library;

use ReturnTypeWillChange;

/**
 * Replacement for \SplFileObject
 *
 * This is a drop-in replacement for \SplFileObject, reimplemented to throw
 * exceptions on errors, thus simplifying file operations. There are some
 * additional static methods for general filesystem operations.
 *
 * The Iterator implementation is slightly incompatible with \SplFileObject
 * which honors the SKIP_EMPTY flag only in a foreach() loop, not for direct
 * method invocation. This inconsistency cannot be implemented in PHP.
 *
 * Not all methods are implemented yet.
 */
class FileObject extends \SplFileInfo implements \Iterator
{
    /**
     * The file pointer
     * @var resource
     */
    protected $_file;

    /**
     * Flags set via setFlags()
     * @var integer
     */
    protected $_flags = 0;

    /**
     * Current line for iterator
     * @var string
     */
    protected $_currentLine;

    /**
     * Current key for iterator
     * @var integer
     */
    protected $_currentKey;

    /**
     * EOF indicator for iterator
     * @var bool
     */
    protected $_iteratorValid;

    /**
     * Constructor
     *
     * @param string $filename
     * @param string $openMode
     */
    public function __construct($filename, $openMode = 'r')
    {
        $this->_file = @fopen($filename, $openMode);
        if (!$this->_file) {
            throw new \RuntimeException("Error opening file '$filename', mode '$openMode'");
        }
        parent::__construct($filename);
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        if ($this->_file) {
            fclose($this->_file);
        }
    }

    /**
     * Return underlying stream resource.
     *
     * @return resource
     */
    public function getStreamResource()
    {
        return $this->_file;
    }

    /**
     * Set flags
     *
     * @param integer $flags Any of the \SplFileObject constants
     * @throws \LogicException if READ_CSV is set (not implemented yet)
     */
    public function setFlags($flags)
    {
        if ($flags & \SplFileObject::READ_CSV) {
            throw new \LogicException('READ_CSV not implemented');
        }
        $this->_flags = $flags;
    }

    /**
     * Get flags set via setFlags()
     *
     * @return integer
     */
    public function getFlags()
    {
        return $this->_flags;
    }

    /**
     * Return EOF status
     *
     * @return bool
     */
    public function eof()
    {
        return feof($this->_file);
    }

    /**
     * Read a fixed-length chunk of data
     *
     * The returned string may be shorter than $length if EOF is reached.
     * Reading beyond EOF is allowed and yields an empty string.
     *
     * @param integer $length Number of bytes to read
     * @return string
     * @throws \InvalidArgumentException if $length is <= 0
     * @throws \RuntimeException if an error occurs
     */
    public function fread($length)
    {
        if ($length <= 0) {
            throw new \InvalidArgumentException("fread() length must be > 0, $length given");
        }
        $data = @fread($this->_file, $length);
        // In contrast to the fread() documentation, runtime errors typically
        // yield empty or truncated strings instead of FALSE. In case of EOF
        // this is a valid result.
        if ($data === false or (strlen($data) != $length and !$this->eof())) {
            throw new \RuntimeException('Error reading from file ' . $this->getPathname());
        }
        return $data;
    }

    /**
     * Read a single line
     *
     * @return string
     * @throws \RuntimeException if an error occurs, including reading beyond EOF
     */
    public function fgets()
    {
        $line = @fgets($this->_file);
        if ($line === false) {
            throw new \RuntimeException('Error reading from file ' . $this->getPathname());
        }
        if ($this->_flags & \SplFileObject::DROP_NEW_LINE) {
            $line = rtrim($line, "\r\n");
        }
        return $line;
    }

    /**
     * Get current iterator line
     *
     * @return string|false
     */
    #[ReturnTypeWillChange]
    public function current()
    {
        if ($this->_currentKey == -1) {
            $this->next();
        }
        return $this->_currentLine;
    }

    /**
     * Get current iterator line number
     */
    public function key(): int
    {
        return $this->_currentKey;
    }

    /**
     * Advance iterator
     *
     * @throws \RuntimeException if an error occurs
     */
    public function next(): void
    {
        try {
            $this->_currentLine = $this->fgets();
        } catch (\RuntimeException $e) {
            if ($this->eof()) {
                $this->_currentLine = false;
                $this->_iteratorValid = false;
            } else {
                throw $e;
            }
        }
        if ($this->_flags & \SplFileObject::SKIP_EMPTY and $this->_currentLine === '') {
            $this->next();
        } else {
            $this->_currentKey++;
        }
    }

    /**
     * Rewind iterator and file pointer
     *
     * @throws \RuntimeException if an error occurs
     */
    public function rewind(): void
    {
        if (!@rewind($this->_file)) {
            throw new \RuntimeException('Error rewinding file ' . $this->getPathname());
        }
        $this->_currentKey = -1;
        $this->_iteratorValid = !$this->eof();
    }

    /**
     * Return iterator status
     */
    public function valid(): bool
    {
        return $this->_iteratorValid;
    }

    /**
     * Reads entire file into a string
     *
     * This is a wrapper for \file_get_contents() which throws an exception when
     * an error is encountered.
     *
     * @param string $filename Name of the file to read
     * @return string File content
     * @throws \RuntimeException if an error occurs during reading.
     */
    public static function fileGetContents($filename)
    {
        $content = @file_get_contents($filename);
        if ($content === false) {
            throw new \RuntimeException("Error reading from file $filename");
        } else {
            return $content;
        }
    }
}
