<?php
namespace strangerfw\utils;
/**
 * strangerfw\utils\LoadSeed
 * @author satoukentadashi
 *
 */
class LoadSeed
{
    /**
     *
     * @param DbConnect $dbh
     * @param string $seed
     */
    public static function load(&$dbh, $seed)
    {
        $error = new \strangerfw\utils\Logger('ERROR');
        $debug = new \strangerfw\utils\Logger('DEBUG');
        try {
            $file_name = DB_PATH . '/seeds/' . $seed . '.csv';
            $debug->log("LoadSeed::load() file_name:${file_name}");

            $file = new \SplFileObject($file_name, 'r');
            $file->setFlags(\SplFileObject::READ_CSV);
            $columns = null;
            $class_name_org = \strangerfw\utils\StringUtil::convertTableNameToClassName($seed);
            echo "INSERT INTO ${class_name} \n";
            foreach ($file as $row)
            {
                $debug->log("LoadSeed::load() row:" . print_r($row, true));
                $data = [];
                if(!$columns){
                    $columns = $row;
                    continue;
                }

                if(!isset($row[0])) continue;
                for ($i = 0; $i < count($row); $i++){
                    $data[$class_name_org][$columns[$i]] = $row[$i];
                }
                $debug->log("LoadSeed::load() data:" . print_r($data, true));
                $class_name = "\\" . $class_name_org;
                $dbh->beginTransaction();
                $debug->log("LoadSeed::load() class_name: ${class_name}");
                $model = new $class_name($dbh);
                $debug->log("LoadSeed::load() model is ".get_class($model));
                $model->save($data);
                $dbh->commit();
            }
        } catch (\Exception $e) {
            echo "  Fail bulk insert\n";
            echo "  " . $e->getMessage()."\n";
            $error->log("LoadSeed::load() Error:" . $e->getMessage());
        }
        return true;
    }
}

