<?php

class DObject
{
    protected $required = array(
        'dbConnection'
            => 'object',
        'entity'
            => 'string'
    );

    protected $optional = array(
    );

    protected $database = array(
        'hostname' => 'string',
        'username' => 'string',
        'password' => 'string',
        'database' => 'string',
        'schema'   => array(
            'Field'    => 'string',
            'Type'     => 'string',
            'Null'     => 'string',
            'Key'      => 'string',
            'Default'  => 'string',
            'Extra'    => 'string'
        ),
        'schemakey'=> 'string',
        'relationships'=> array(
            'CONSTRAINT_CATALOG'    => 'string',
            'CONSTRAINT_SCHEMA'     => 'string',
            'CONSTRAINT_NAME'       => 'string',
            'TABLE_CATALOG'         => 'string',
            'TABLE_SCHEMA'          => 'string',
            'TABLE_NAME'            => 'string',
            'COLUMN_NAME'           => 'string',
            'ORDINAL_POSITION'      => 'string',
            'POSITION_IN_UNIQUE_CONSTRAINT'=> 'string',
            'REFERENCED_TABLE_SCHEMA'=> 'string',
            'REFERENCED_TABLE_NAME' => 'string',
            'REFERENCED_COLUMN_NAME' => 'STRING'
        )
    );

    private $data = array();

    function __construct($init_data)
    {
        foreach ($this->required as $key => $type) {
            if (isset($init_data{$key})) {
                $this->data{$key} = $init_data{$key};
            } else {
                throw new Exception('Constructing ' . get_class($this) . ' failed.\n Required Attribute: ' . $key . 'was NOT provided, or is the wrong type.');
            }
        }

        foreach ($this->optional as $key => $type) {
            if (isset($init_data{$key})) {
                $this->data{$key} = $init_data{$key};
            }
        }

        foreach ($this->database as $key => $type) {
            if (isset($init_data{$key})) {
                $this->data{$key} = $init_data{$key};
            }
        }

        // This is where the magic happens!
        $this->getSchema();
        $this->getRelatives();

        return $this;
    }

    function get($object)
    {
        if (isset($this->data{$object}))
        {
            return $this->data{$object};
        } elseif (isset($this->data['schema']{$object})) {
            return $this->data['schema']{$object};
        } elseif (isset($this->data['relatives']{$object})) {
            return $this->data['relatives']{$object};
        }

        return false;
    }

    function getSchema(){
        $schema = $this->get('dbConnection')->query('describe ' . $this->get('entity'));

        while ($row = $schema->fetch_array(MYSQLI_ASSOC)) {

            $this->data['schema'][$row['Field']] = array();
            foreach ($this->database['schema'] as $key => $value)
            {
                array_push($this->data['schema'][$row['Field']], array($key => $row{$key}));
                if ($key == 'Key' && $row{$key} == 'PRI')
                {
                    $this->data['schemakey'] = $row['Field'];
                }
            }
        }

        $schema->close();
    }

    function getObjectMapAsArray()
    {
        return $returnArray = array(
            'required' => $this->required,
            'optional' => $this->optional,
            'database' => $this->database
        );
    }

    function getInstanceMapAsArray()
    {
        return $this->data;
    }

    function getRelatives(){
        $query = 'SELECT * FROM `information_schema`.`KEY_COLUMN_USAGE`
        WHERE `TABLE_NAME` like \'' . $this->get('entity') . '\'';

        $result = $this->get('dbConnection')->query($query);

        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {

            $this->data['relationships'][$row['CONSTRAINT_NAME']] = array();
            foreach ($this->database['relationships'] as $key => $value)
            {
                array_push($this->data['relationships'][$row['CONSTRAINT_NAME']], array($key => $row{$key}));
            }
        }

        $result->close();
    }

    function getAllArray()
    {
        $resultArray = array();

        $query = 'SELECT * FROM ' . $this->get('entity') . ';';

        $result = $this->get('dbConnection')->query($query);

        while ($row = $result->fetch_array(MYSQLI_ASSOC))
        {
            array_push($resultArray, $row);
        }

        return $resultArray;
    }

    function getAllJSON()
    {
        $resultArray = array();

        $query = 'SELECT * FROM ' . $this->get('entity') . ';';

        $result = $this->get('dbConnection')->query($query);

        while ($row = $result->fetch_array(MYSQLI_ASSOC))
        {
            array_push($resultArray, $row);
        }

        return json_encode($resultArray);
    }

    function getPrimaryWhereFieldLikeString($fieldName, $fieldString)
    {
        $query = 'SELECT `' . $this->get('schemakey') . '` FROM ' . $this->get('entity') . '
        WHERE `' . $fieldName . '` like \'' . $fieldString . '\'
        LIMIT 1;';

        echo $query;

        $result = $this->get('dbConnection')->query($query);

        $row = $result->fetch_array(MYSQLI_ASSOC);

        return $row{$this->get('schemakey')};
    }

    function getRowArrayWhereFieldLikeString($fieldName, $fieldString)
    {
        $resultArray = array();

        $query = 'SELECT * FROM ' . $this->get('entity') . '
        WHERE `' . $fieldName . '` like \'' . $fieldString . '\';';

        echo $query;

        $result = $this->get('dbConnection')->query($query);

        while ($row = $result->fetch_array(MYSQLI_ASSOC))
        {
            array_push($resultArray, $row);
        }

        return $resultArray;
    }

    function getRowArrayWherePrimaryEqualsInt($thisRow = 0)
    {
        $query = 'SELECT * FROM ' . $this->get('entity') . '
        WHERE ' . $this->get('schemakey') . ' = ' . $thisRow . ';';

        $result = $this->get('dbConnection')->query($query);

        $row = $result->fetch_array(MYSQLI_ASSOC);

        return $row;
    }

    function getRowJSONWherePrimaryEqualsInt($thisRow = 0)
    {

        $thisRow = $this->get('dbConnection')->real_escape_string('$thisRow');

        $query = 'SELECT * FROM ' . $this->get('entity') . '
        WHERE ' . $this->get('schemakey') . ' = ' . $thisRow . ';';

        $result = $this->get('dbConnection')->query($query);

        $row = $result->fetch_array(MYSQLI_ASSOC);

        return json_encode($row);
    }

    function getMySQLEscapeString($escape)
    {
        
        return $this->get('dbConnection')->real_escape_string($escape);
    }

    function getAllWhereFieldLikeAsArray($fieldName, $fieldLike)
    {

    }

//    function getRowAndRelativesArrayWherePrimaryEqualsInt($thisRow = 0)
//    {
//        $query = 'SELECT * FROM ' . $this->get('entity') . '
//        WHERE ' . $this->get('schemakey') . ' = ' . $thisRow . ';';
//
//        $result = $this->get('dbConnection')->query($query);
//
//        $row = $result->fetch_array(MYSQLI_ASSOC);
//
//        while ($relationship = $this->get('relationships'))
//        {
//            var_dump($relationship);
//        }
//
//        return $row;
//    }
}