    $sql = <<<EOM
ALTER TABLE <!----table_name----> DROP COLUMN <!----column_name---->;
EOM;
    parent::down($sql);
