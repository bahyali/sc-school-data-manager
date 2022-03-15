<?php

namespace App\Classes;

use App\Models\DataChange;

interface IConflict
{
    public function getType(); // Type of conflict
    public function setType($type);

    public function getAffectedRecords(); // Revisions
    public function addAffectedRecord($revision); // Revisions

    public function getColumn(); // Conflicting Columns
    public function getValues(); // Conflicting Columns & Values
    public function setValues($values);
    public function addValue($column, $value);
}

class Conflict implements IConflict
{
    protected $affectedRecords = [];
    protected $values = [];
    protected $type = '';
    protected $column;

    public function __construct($type, $affectedRecords, $column, $values)
    {
        $this->affectedRecords = $affectedRecords;
        $this->type = $type;
        $this->column = $column;
        $this->values = $values;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getAffectedRecords()
    {
        return $this->affectedRecords;
    }

    public function addAffectedRecord($revision)
    {
        array_push($this->affectedRecords, $revision);
    }

    public function setValues($values)
    {
        $this->values = $values;
    }

    public function getValues()
    {
        return $this->values;
    }

    public function addValue($column, $value)
    {
        $this->values[$column] = $value;
    }

    public function getColumn()
    {
        return $this->column;
    }

    public function persist()
    {
        $hash = md5(serialize([
            'type' => $this->type,
            'column' => $this->column,
            'values' => $this->values
        ]));

        $dataChange = DataChange::updateOrCreate(
            [
                'type' => $this->type,
                'column' => $this->column,
                'hash' => $hash
            ],
            [
                'hash' => $hash
            ]
        );

        foreach ($this->values as $id => $value)
            $dataChange->values()->updateOrCreate([
                'data_change_id' => $dataChange->id,
                'revision_id' => $id,
                'value' => $value
            ]);

        return $dataChange;
    }

    public function toArray()
    {
        return [
            'affectedRecords' => $this->affectedRecords,
            'type' => $this->type,
            'column' => $this->column,
            'values' => $this->values
        ];
    }
}
