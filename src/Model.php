<?php
/**
 * Created by Florence Okosun.
 * Project: Checkpoint Two
 * Date: 11/4/2015
 * Time: 4:07 PM
 */

namespace Florence;

use PDOException;

abstract class Model implements ModelInterface
{
    /**
    * @var properties array to hold column name and values
    */
    protected  $properties = [];

    /**
    * @param string $key rep column name
    * @param string $val rep column value
    * sets into $propertie the $key => $value pairs
    */
    public  function __set($key, $val)
    {
        $this->properties[$key] = $val;
    }

    /**
    * @param string $key reps the column name
    * @return $key and $value
    */
    public function __get($key)
    {
        return $this->properties[$key];
    }

    /**
    * Gets the name of the child class only
    * without the namespace
    * @var $className
    * @var $table
    * @return $table
    */
    public static function getTableName()
    {
        $className = explode('\\', get_called_class());
        $table = strtolower(end($className) .'s');

        return $table;
    }

        /**
    * returns a particular record
    * @param $row reps the record id
    * @param $connection initialised to null
    * @return associative array
    */

    public static function find($id, $connection = null)
    {
        if (is_null($connection)) {
            $connection = new Connection();
        }

        try {
            $sql = "SELECT " . "*" . " FROM " . self::getTableName() . " WHERE id = " . $id;
            $record = $connection->prepare($sql);
            $record->execute();
            $count = $record->rowCount();
                if ($count < 1) {
                    throw new RecordNotFoundException('Sorry, record with id ' . $id . ' does not exist');
                }
            } catch (RecordNotFoundException $e) {
                return $e->getMessage();
            } catch(PDOException $e) {
                return $e->getExceptionMessage();
            }

            $result = new static;

            $result = $record->fetchAll($connection::FETCH_CLASS,get_called_class());

            //$result->id = $result->properties[0]['id'];

             return $result[0];


        }

    /**
    * fetches all records from the database
    * @param $connection initialised to null
    * @return associative array
    */
    public static function getAll($connection = null)
    {

        if (is_null($connection)) {
            $connection = new Connection();
        }

        try
        {
            $sql = "SELECT " . "*" . " FROM ". self::getTableName();
            $row = $connection->prepare($sql);
            $row->execute();
        } catch (PDOException $e) {
            return $e->getMessage();
        }

        return $row->fetchAll($connection::FETCH_ASSOC);
    }

    private function update()
    {
        $connection = $this->getConnection();

        $columnNames = "";
        $columnValues = "";
        $count = 0;

        $update = "UPDATE " . $this->getTableName() . " SET " ;
        foreach ($this->properties as $key => $val) {
            $count++;

            if(  ($key == 'id') ) {
                continue;
            }
            $columnNames .= $key;
            $columnValues .= ':' . $key;

            if ($count < count($this->properties))
            {
                $columnNames .= ', ';
                $columnValues .= ', ';
            }

        }

        $update .= $columnNames . "(". $columnValues . ")". " WHERE id = " . $this->properties['id'];
         // echo $update;
        $stmt = $connection->prepare($update);

            foreach ($this->properties as $key => $val) {
                if($key == 'id') {
                    continue;
                }
                $stmt->bindValue(':'.$key, $val);
                //var_export($stmt);
            }
        $stmt->execute();
        echo $stmt->rowCount();
    }

    private function create()
    {
        $connection = $this->getConnection();
        $columnNames = "";
        $columnValues = "";
        $count = 0;

        $create = "INSERT" . " INTO " . $this->getTableName()." (";
            foreach ($this->properties as $key => $val) {
                $columnNames .= $key;
                $columnValues .= ':' . $key;
                $count++;

                if ($count < count($this->properties))
                {
                    $columnNames .= ', ';
                    $columnValues .= ', ';
                }
            }

        $create .= $columnNames.') VALUES (' .$columnValues.')';
        $stmt = $connection->prepare($create);
            foreach ($this->properties as $key => $val) {
                $stmt->bindValue(':'.$key, $val);
            }
        echo $stmt->execute();
        return $stmt->rowCount();
    }

    public function getConnection($connection = null)
    {
        if(is_null($connection))
        {
            return new Connection();
        }
    }
    /**
    * inserts record into the database
    * @param $connection initialised to null
    * @return rowCount
    */
    public function save()
    {

        if ($this->id) {
            $this->update();
        } else {
            $this->create();
        }
    }

    /**
    * @param row reps record id
    * @param $connection initialised to null
    * @return boolean
    */
    public static function destroy($id, $connection= null)
    {
        if(is_null($connection)) {
            $connection = new Connection();
        }

        try {
            $sql = "DELETE" . " FROM " . self::getTableName()." WHERE id = ". $id;
            $delete = $connection->prepare($sql);
            $delete->execute();
            $count = $delete->rowCount();

            if ($count < 1) {
                throw new RecordNotFoundException('Sorry, record with id ' . $id . ' does not exist');
            }
        } catch (RecordNotFoundException $e) {
            return $e->getExceptionMessage();
        } catch(PDOException $e) {
            return $e->getExceptionMessage();
        }
        return ($count > 0) ? true : false;
    }
}
