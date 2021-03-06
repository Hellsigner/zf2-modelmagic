<?php
/**
 * Created by Dmitry Prokopenko <hellsigner@gmail.com>
 * Date: 03.06.15
 * Time: 12:16
 */

namespace ModelMagic\Entity;

use ModelMagic\EntityManager\EntityManagerAwareInterface;
use ModelMagic\EntityManager\EntityManagerInterface;
use Traversable;

class ModelMagic implements
    \ArrayAccess,
    \Countable,
    \IteratorAggregate,
    \Serializable,
    ModelMagicInterface,
    JsonSerializableInterface,
    EntityManagerAwareInterface,
    ActiveRecordInterface
{
    /**
     * Table name for this entity. Must be overridden, if EntityRepository for this entity will be used.
     */
    const TABLE_NAME = null;

    /**
     * Primary column
     */
    const PRIMARY_COLUMN = 'id';

    /**
     * Array of column names, protected for writing in ORM mode. Example: primary key columns.
     *
     * @var array
     */
    protected $protectWriteColumns = array();

    /**
     * Array of column names, protected for reading in ORM mode. Example: password hash column.
     *
     * @var array
     */
    protected $protectReadColumns = array();

    /**
     * Internal storage.
     *
     * @var array
     */
    protected $fields = array();

    /**
     * Flag for internal usage.
     *
     * @var bool
     */
    protected $isNew = true;

    /**
     * EntityManager DI.
     *
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @param array $data
     */
    public function __construct(array $data = array())
    {
        (!empty($data)) && ($this->fromArray($data));
    }

    /**
     * @param array $data
     * @return $this
     */
    public function fromArray(array $data)
    {
        foreach ($data as $key => $val) {
            if ($this->isNew || !in_array($key, $this->protectWriteColumns)) {
                $this->remove($key);
            }
        }
        foreach ($data as $key => $val) {
            if ($this->isNew || !in_array($key, $this->protectWriteColumns)) {
                $this->set($key, $val);
            }
        }
        ($this->isNew) && ($this->isNew = false);
        return $this;
    }

    /**
     * @param $json
     * @return $this
     */
    public function fromJSON($json)
    {
        $decoded = json_decode($json, true);
        return $this->fromArray($decoded);
    }

    /**
     * @return string
     */
    public function toJSON()
    {
        return json_encode($this->toArray());
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $data = array();
        foreach ($this->fields as $field => $val) {
            if (!in_array($field, $this->protectReadColumns)) {
                $data[$field] = $this->get($field);
            }
        }
        return $data;
    }

    /**
     * Alias for ZF2 ArraySerializable interface[1].
     *
     * @param $data
     * @return ModelMagic
     */
    public function exchangeArray($data)
    {
        foreach ($data as $key => $val) {
            if ($this->isNew || !in_array($key, $this->protectWriteColumns)) {
                $this->set($key, $val);
            }
        }
        ($this->isNew) && ($this->isNew = false);
        return $this;
    }

    /**
     * Alias for ZF2 ArraySerializable interface[2].
     *
     * @return array
     */
    public function getArrayCopy()
    {
        return $this->toArray();
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->fields[$key]);
    }

    /**
     * @param string $key
     *
     * @return null
     */
    public function get($key)
    {
        return isset($this->$key) ? $this->fields[$key] : null;
    }

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function set($key, $value)
    {
        $this->fields[$key] = $value;
    }

    /**
     * @param $key
     */
    public function remove($key)
    {
        unset($this->fields[$key]);
    }

    /**
     * @param string $name
     *
     * @return mixed|null
     */
    public function __get($name)
    {
        if (method_exists($this, 'get' . $name)) {
            return $this->{'get' . $name}();
        } else {
            return $this->get($name);
        }
    }

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function __set($name, $value)
    {
        if (method_exists($this, 'set' . $name)) {
            $this->{'set' . $name}($value);
        } else {
            $this->set($name, $value);
        }
    }

    /**
     * @param string $key
     * @return void
     */
    public function __unset($key)
    {
        $this->remove($key);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {
        return isset($this->fields[$offset]);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        return $this->fields[$offset];
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->fields[$offset] = $value;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->fields[$offset]);
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     */
    public function count()
    {
        return count($this->fields);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->fields);
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     */
    public function serialize()
    {
        return serialize(array($this->fields, $this->protectReadColumns, $this->protectWriteColumns));
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return void
     */
    public function unserialize($serialized)
    {
        $this->protectReadColumns = $serialized[1];
        $this->protectWriteColumns = $serialized[2];
        $this->fromArray($serialized[0]);
    }

    /**
     * Magic method: will work only in PHP >= 5.6
     * http://php.net/manual/en/language.oop5.magic.php#object.debuginfo
     *
     * @return array
     */
    public function __debuginfo()
    {
        return array(
            'fields' => $this->fields,
            'protectReadColumns' => $this->protectReadColumns,
            'protectWriteColumns' => $this->protectWriteColumns);
    }

    /**
     * @return EntityManagerInterface
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @return void
     */
    public function setEntityManager(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * ActiveRecord method implementation.
     */
    public function save()
    {
        $repo = $this->getEntityManager()->getRepository(get_class($this));
        $repo->replace($this->toArray());
        $this->reset($repo->get(static::PRIMARY_COLUMN)->toArray());
        return $this;
    }

    /**
     * Force redefine data (ignoring read/write constraint)
     * Internal method, not for common use.
     *
     * @param array $data
     * @return ModelMagic
     */
    protected function reset(array $data)
    {
        $this->fields = array();
        $this->isNew = true;
        foreach ($data as $key => $val) {
            if ($this->isNew || !in_array($key, $this->protectWriteColumns)) {
                $this->set($key, $val);
            }
        }
        ($this->isNew) && ($this->isNew = false);
        return $this;
    }
}
