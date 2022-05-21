<?php

namespace App\Classes;

interface IConflictFinder
{
    function addRecord($record);
    function getRecords();
    function setRecords($records);
    function run($persist);
}


class ConflictFinder implements IConflictFinder
{
    protected $records = [];
    protected $school_id;

    protected $ignore = ['created_at', 'updated_at', 'id', 'hash'];

    protected $tracked_columns = ['name', 'number', 'principal_name', 'address_line_1', 'address_line_2', 'address_line_3', 'status'];

    private $conflictTypes = [];

    public function __construct()
    {
        $this->conflictTypes = [
            'similarity' => [
                'columns' => [
                    'principal_name'
                ],
                'func' => function (&$values) {
                    if (count($values) > 1) {
                        $reset_values = array_values($values);

                        $scores = array_filter(
                            $this->detectSimilarity($reset_values),
                            function ($score) {
                                return $score[2] > 50;
                            }
                        );

                        $strings = array_reduce($scores, function ($carry, $score) {
                            array_push($carry, ...array_slice($score, 0, 2));

                            return $carry;
                        }, []);

                        $values = array_filter($values, function ($value) use ($strings) {
                            return in_array($value, $strings);
                        });

                        return count($scores) > 0;
                    }
                }
            ],
            'change' => [
                'columns' => [
                    'name',
                    'status'
                ],
                'func' => function ($values) {
                    return count($values) > 1;
                }
            ]
        ];
    }
    function addRecord($record)
    {
        array_push($this->records, $record);
    }

    function getRecords()
    {
        return $this->records;
    }

    function setRecords($records)
    {
        $this->records = $records;
    }

    function run($persist)
    {
        if (count($this->records) <= 1)
            return; // changes tracking has to be between at least two records.

        $changes = $this->findChanges($this->groupColumns($this->records));

        $conflicts = [];

        foreach ($changes as $column => $values) {
            $records = array_filter($this->records, function ($record) use ($values) {
                return in_array($record['id'], array_keys($values));
            });

            // Search for conflicts
            if ($conflict_type = $this->detectConflict($column)) {
                if ($this->conflictTypes[$conflict_type]['func']($values)) {
                    $conflict = new Conflict($conflict_type, $records, $column, $values, $this->school_id);

                    if ($persist)
                        $conflict->persist();

                    $conflicts[] = $conflict->toArray();
                }
            }
        }

        return $conflicts;
    }

    private function detectSimilarity($values)
    {
        $scores = [];
        foreach ($values as $i => $string1)
            foreach (array_slice($values, $i + 1) as $j => $string2) {
                similar_text($string1, $string2, $q);
                $scores[] = [$string1, $string2, $q];
            }

        return $scores;
    }

    private function detectConflict($column)
    {
        foreach ($this->conflictTypes as $key => $type) {
            if (in_array($column, $type['columns']))
                return $key;
        }
    }

    private function findChanges($groupedColumns)
    {
        return array_filter($groupedColumns, function ($values) {
            return count($values) > 1;
        });
    }

    private function groupColumns($records)
    {
        $result = [];

        foreach ($records as $record) {
            foreach (array_keys($record) as $key) {
                if (in_array($key, $this->tracked_columns))
                    $result[$key][$record['id']] = $record[$key];
            }
        }

        return $this->removeDuplicates($result);
    }

    private function removeDuplicates($input)
    {
        $result = [];
        foreach ($input as $column => $values) {
            $result[$column] = array_filter(array_unique($values), function ($item) {
                return $item != null;
            });
        }
        return $result;
    }



    function setSchoolId($id)
    {
        $this->school_id = $id;
    }
}
