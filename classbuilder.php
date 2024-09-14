<?php

use Application\core\Database;

require_once "vendor/autoload.php";

if (php_sapi_name() != "cli" || php_sapi_name() == false) {
    require_once "Application/core/config.php";
    http_response_code(301);
    header("Location: " . ENDERECO_SITE);
    exit;
}

$php_script = $argv[0] ?? "";
$p1 = $argv[1] ?? "";
$p2 = $argv[2] ?? "";

function builder($op, $p2)
{
    switch ($op) {
        case 'model':
            $table = $p2;
            createAppModel($table);
            break;

        case 'service':
            $class = $p2;
            createAppService($class);
            break;

        case 'controller':
            $class = $p2;
            createAppController($class);
            break;

        default:
            echo "\nInvalid action ==> '$op'! \n" .
                "Use model, service or controller. \n" .
                "Ex: 'php classbuilder.php model table_name'\n" .
                "or 'php classbuilder.php controller Produtos'.\n\n";
            break;
    }
}

function createAppController(string $classname)
{
    $dirController = "Application/controllers/";
    $contentClass = "<?php \n\nuse Application\core\Controller;\nuse Application\core\Util;\n\n" . "class $classname extends Controller\n{\n";
    $contentClass .= "    public function index() \n    {\n    }\n";
    $contentClass .= "\n}\n";
    $file = fopen($dirController . $classname . ".php", 'w');
    fwrite($file, $contentClass);
    echo "\nController $classname has created.\n";
}

function createAppService(string $classname)
{
    $dirServices = "Application/services/";
    $contentClass = "<?php \n\n" . "namespace Application\services; \n\nuse Application\core\Service;\n\n" . "class $classname extends Service\n{\n";
    $contentClass .= "    public function __construct() \n    {\n    }\n";
    $contentClass .= "\n}\n";
    $file = fopen($dirServices . $classname . ".php", 'w');
    fwrite($file, $contentClass);
    echo "\nService $classname has created.\n";
}

function createAppModel(string $tablename)
{
    $dirModels = "Application/models/";
    $classname = implode('', array_map('ucfirst', explode('_', $tablename)));
    $primaryKeyColumn = "";
    $primaryKeyType = "";

    try {
        $Database = new Database();
        $tableDesc = $Database->query("DESCRIBE $tablename");
        $columns = [];

        foreach ($tableDesc as $tableColumn) {
            $columns[] = [
                'name' => $tableColumn["Field"],
                'type' => $tableColumn["Type"],
                'key' => $tableColumn["Key"]
            ];

            if ($tableColumn["Key"] == "PRI") {
                $primaryKeyColumn = $tableColumn["Field"];
                $primaryKeyType = $tableColumn["Type"];
            }
        }

        $contentClass = "<?php \n\n" . "namespace Application\models; \n\nuse Application\core\Database;\n\n" . "class $classname \n{\n";

        // attributes
        foreach ($columns as $cl) {
            $contentClass .= '    private $' . $cl['name'] . "; \n";
        }
        $contentClass .= "\n" . '    private $Database;' . "\n" . '    public static $logsql;' . "\n\n";

        // construct
        $contentClass .= '    public function __construct($conteudo = []) ' . "\n    {\n";
        $contentClass .= '        $this->Database = new Database("' . $tablename . '");' . "\n\n";
        foreach ($columns as $cl) {
            $contentClass .= '        $this->' . $cl['name'] . ' = $conteudo["' . $cl['name'] . '"] ?? "";' . "\n";
        }
        $contentClass .= "    }\n\n";

        foreach ($columns as $cl) {
            // get
            $contentClass .= '    public function get' . implode('', array_map('ucfirst', explode('_', $cl['name']))) . "() \n    {\n";
            $contentClass .= '        return $this->' . $cl['name'] . ";\n";
            $contentClass .= "    }\n\n";

            // set
            $contentClass .= '    public function set' . implode('', array_map('ucfirst', explode('_', $cl['name']))) . "($" . $cl['name'] . ") \n    {\n";
            $contentClass .= '        $this->' . $cl['name'] . ' = $' . $cl['name'] . ";\n";
            $contentClass .= "    }\n\n";
        }

        // getlogsql
        $contentClass .= "    public static function getLogsql() \n    {\n";
        $contentClass .= '        return self::$logsql' . ";\n";
        $contentClass .= "    }\n\n";

        // setlogsql
        $contentClass .= '    public static function setLogsql($logsql) ' . "\n    {\n";
        $contentClass .= '        self::$logsql = $logsql' . ";\n";
        $contentClass .= "    }\n\n";

        //salvar
        $contentClass .= "    public function salvar() \n    {\n";
        if (!empty($primaryKeyColumn)) {

            $contentClass .= '        $campos = [];' . "\n" . '        $param = [""];' . "\n\n";

            foreach ($columns as $cl) {
                if ($cl['name'] != $primaryKeyColumn) {
                    $contentClass .= '        $campos[] = "' . $cl['name'] . '"; $param[0] .= "' . (str_contains($cl['type'], 'int') ? 'i' : 's') . '"; $param[] = $this->' . $cl['name'] . ";\n";
                }
            }
            $contentClass .= "\n";
            $contentClass .= '        if (!empty($this->' . $primaryKeyColumn . ")) {\n";
            $contentClass .= '            $where = "' . $primaryKeyColumn . ' = ? "; ' . "\n";
            $contentClass .= '            $param[0] .= "' . (str_contains($primaryKeyType, 'int') ? 'i' : 's') . '"; ' . "\n";
            $contentClass .= '            $param[] .= $this->' . $primaryKeyColumn . '; ' . "\n";
            $contentClass .= '            $this->Database->update($campos, $where, $param)' . ";\n";
            $contentClass .= '            self::setLogSql($this->Database->log)' . ";\n";
            $contentClass .= '            return $this->' . $primaryKeyColumn . ";\n";
            $contentClass .= "        }\n\n";
            $contentClass .= '        $this->' . $primaryKeyColumn . ' = $this->Database->insert($campos, $param)' . ";\n";
            $contentClass .= '        self::setLogSql($this->Database->log)' . ";\n";
            $contentClass .= '        return $this->' . $primaryKeyColumn . ";\n";
        }
        $contentClass .= "    }\n\n";

        //delete
        $contentClass .= '    public static function delete($id) ' . "\n    {\n";
        if (!empty($primaryKeyColumn)) {
            $contentClass .= '        $Database = new Database("' . $tablename . '");' . "\n";
            $contentClass .= '        $Database->delete("' . $primaryKeyColumn . ' = ?", ["' . (str_contains($primaryKeyType, 'int') ? 'i' : 's') . '", $id]);' . "\n";
            $contentClass .= '        return true;' . "\n";
        }
        $contentClass .= "    }\n\n";

        //excluir
        $contentClass .= '    public function excluir() ' . "\n    {\n";
        if (!empty($primaryKeyColumn)) {
            $contentClass .= '        if (!empty($this->' . $primaryKeyColumn . ')) {' . "\n";
            $contentClass .= '            self::delete($this->' . $primaryKeyColumn . ');' . "\n";
            $contentClass .= "            return true;\n";
            $contentClass .= "        }\n";
            $contentClass .= '        return false;' . "\n";
        }
        $contentClass .= "    }\n\n";

        //listar
        $contentClass .= '    public static function listar(string $campos = "*", string $where = "", string $order = "", string $limit = "", array $param = [], string $tabela = "", string $join = ""): array' . " \n    {\n";
        $contentClass .= '        $Database = new Database("' . $tablename . '");' . "\n";
        $contentClass .= '        $result = $Database->select($campos, $where, $order, $limit, $param, $tabela, $join);' . "\n";
        $contentClass .= '        $data = [];' . "\n\n";
        $contentClass .= '        if (count($result) > 0) {' . "\n";
        $contentClass .= '            foreach ($result as $row) {' . "\n";
        $contentClass .= '                $data[] = new ' . $classname . '($row);' . "\n";
        $contentClass .= "            }\n";
        $contentClass .= "        }\n";
        $contentClass .= '        return $data;' . "\n";
        $contentClass .= "    }\n\n";

        $contentClass .= '    public static function ler($id): object' . " \n    {\n";
        if (!empty($primaryKeyColumn)) {
            $contentClass .= '        $where = "' . $primaryKeyColumn . ' = ?";' . "\n";
            $contentClass .= '        $param = ["' . (str_contains($primaryKeyType, 'int') ? 'i' : 's') . '", $id];' . "\n";
            $contentClass .= '        $result = self::listar("*", $where, "", "1", $param, "", "");' . "\n";
            $contentClass .= '        $data = count($result) > 0 ? $result[0] : new ' . $classname . '();' . "\n";
            $contentClass .= '        return $data;' . "\n";
        }
        $contentClass .= "    }\n\n";

        $contentClass .= '    public static function countRegistros(string $where = "", array $param = [])' . " \n    {\n";
        $contentClass .= '        $Database = new Database("' . $tablename . '");' . "\n";
        $contentClass .= '        $result = $Database->nRegistros($where, $param);' . "\n";
        $contentClass .= '        return (int) $result;' . "\n";
        $contentClass .= "    }\n";

        $contentClass .= "\n}\n";

        $file = fopen($dirModels . $classname . ".php", 'w');
        fwrite($file, $contentClass);
        echo "\nModel $classname has created.\n";
    } catch (Throwable $e) {
        print PHP_EOL . $e->getMessage() . PHP_EOL;
    }
}

builder($p1, $p2);
