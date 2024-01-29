<?php

class DB
{
    private object $db;
    private array $data;

    /**
     * @param object $db
     */
    public function __construct(object $db)
    {
        $this->db = $db;
    }

    /**
     * @param $filename
     * @return void
     */
    public function import($filename): void
    {
        $this->data = array_map('str_getcsv', file($filename));
        $this->save($filename);
    }

    /**
     * @param string $filename
     * @return void
     */
    public function save(string $filename): void
    {
        $tables = [
            'employees.csv' => 'employees',
            'positions.csv' => 'positions',
            'timesheet.csv' => 'timesheet'
        ];
        $fieldsForAllTables = [
            'employees' => ['employee_id', 'name', 'position_id'],
            'positions' => ['position_id', 'name', 'hourly_rate'],
            'timesheet' => ['id', 'task', 'employee_id', 'start_time', 'end_time']
        ];

        if (array_key_exists($filename, $tables)) {
            $table = $tables[$filename];
            $fields = $fieldsForAllTables[$table];
            $placeholders = implode(", ", $fields);
            $countRecords = 0;

            $existingData = $this->getList($table);
            if ($filename === 'timesheet.csv') $idAndNameEmployees = $this->getIdAndNameEmployees();

            foreach ($this->data as $row) {
                if (!in_array($row, $existingData)) {
                    switch ($filename) {
                        case 'timesheet.csv':
                            foreach ($existingData as $array) {
                                foreach ($idAndNameEmployees as $employee) {
                                    if ($row[1] == $employee['name']) $row[1] = $employee['employee_id'];
                                }

                                if ($row[0] == $array[$fields[1]] &&
                                    $row[1] == $array[$fields[2]] &&
                                    $row[2] == $array[$fields[3]] &&
                                    $row[3] == $array[$fields[4]]) {
                                    break 3;
                                }
                            }
                        case 'positions.csv':
                            foreach ($existingData as $array) {
                                if ($row[0] == $array[$fields[1]] &&
                                    $row[1] == $array[$fields[2]]) {
                                    break 3;
                                }
                            }
                        case 'employees.csv':
                            foreach ($existingData as $array) {
                                if ($row[0] == $array[$fields[1]] &&
                                    $row[1] == $array[$fields[2]]) {
                                    break 3;
                                }
                            }

                            $stmt = $this->db->prepare("SELECT p.position_id FROM positions p WHERE p.name='$row[1]'");
                            $stmt->execute();
                            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            $row[1] = $data[0]["position_id"];
                    }

                    $id = count($existingData) + $countRecords;
                    array_unshift($row, $id);

                    $values = "'" . implode("', '", $row) . "'";
                    $stmt = $this->db->prepare("INSERT INTO $table ($placeholders) VALUES ($values)");
                    $stmt->execute();
                    $countRecords++;
                }
            }

            if ($countRecords === 0) {
                die("Imported 0 $table\n" . "Incorrect: " . (count($this->data) - 1) . "\n");
            } else die("Imported " . count($this->data) . " $table\n");
        }
    }

    /**
     * @param string $table
     * @return array
     */
    public function getList(string $table): array
    {
        $stmt = $this->db->prepare("SELECT * FROM $table");
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

    /**
     * @param string $table
     * @return array
     */
    public function getIdAndNameEmployees(): array
    {
        $stmt = $this->db->prepare("SELECT e.employee_id, e.name FROM employees e");
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

    /**
     * @param string $nameEmployee
     * @return array
     */
    public function get(string $nameEmployee): array
    {
        $stmt = $this->db->prepare("SELECT t.* FROM employees e LEFT JOIN timesheet t ON e.employee_id=t.employee_id WHERE name='$nameEmployee'");
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

    /**
     * @param string $nameEmployee
     * @return array
     */
    public function remove(string $id): array
    {
        $stmt = $this->db->prepare("DELETE FROM timesheet WHERE id=$id");
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

    /**
     * @return array
     */
    public function top5longTasks(): array
    {
        $stmt = $this->db->prepare("SELECT EXTRACT(hour FROM (t.end_time - t.start_time)) AS spent_hours, t.task AS title FROM timesheet t ORDER BY (t.end_time - t.start_time) DESC LIMIT 5;");
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

    /**
     * @return array
     */
    public function top5costTasks(): array
    {
        $stmt = $this->db->prepare("SELECT (EXTRACT(hour FROM (t.end_time - t.start_time))) * p.hourly_rate AS total_cost, t.task AS title FROM timesheet t LEFT JOIN employees e ON (t.employee_id=e.employee_id) LEFT JOIN positions p ON (e.position_id=p.position_id) ORDER BY ((EXTRACT(hour FROM (t.end_time - t.start_time))) * p.hourly_rate) DESC LIMIT 5;");
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

    public function top5employees(): array
    {
        $stmt = $this->db->prepare("SELECT SUM(EXTRACT(hour FROM (t.end_time - t.start_time))) AS total_hours, e.name FROM timesheet t LEFT JOIN employees e ON t.employee_id=e.employee_id GROUP BY e.name ORDER BY SUM(EXTRACT(hour FROM (t.end_time - t.start_time))) DESC LIMIT 5;");
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }
}

$host = '0.0.0.0';
$port = '5432';
$dbname = 'test_db';
$user = 'root';
$password = 'pass';

try {
    $db = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $postgres = new DB($db);

    function openFile($filename)
    {
        if (!file_exists($filename)) {
            die("Файл не найден: " . $filename . "\n");
        }
        $handle = fopen($filename, "r");
        if (!$handle) {
            die("Ошибка открытия файла: " . $filename . "\n");
        }
        return $handle;
    }

    function checkAllowedFile($filename)
    {
        $allowed_files = ['employees.csv', 'positions.csv', 'timesheet.csv'];
        if (!in_array($filename, $allowed_files)) {
            die("Поддерживается импорт исключительно employees.csv, positions.csv или timesheet.csv\n");
        }
    }

    function display($timesheetsAboutEmployee)
    {
        $sep = "\t\t";
        $header = array("id", "employee_id", "task", "start_time", "end_time");
        $border = "+-----------+-------------+--------------------+------------------------------+------------------------------+\n";
        $header = implode($sep, $header);
        $header = $border . $header . "\n" . $border;
        $timesheetsAboutEmployee = array_map(function ($row) use ($sep) {
            return implode($sep, $row);
        }, $timesheetsAboutEmployee);
        $timesheetsAboutEmployee = implode("\n", $timesheetsAboutEmployee);
        $timesheetsAboutEmployee = $timesheetsAboutEmployee . " \n" . $border;
        print_r($header . $timesheetsAboutEmployee);
    }

    function generateReport($db, $reportType)
    {
        $reportFunctionMapping = [
            'top5longTasks' => 'top5longTasks',
            'top5costTasks' => 'top5costTasks',
            'top5employees' => 'top5employees'
        ];

        if (!array_key_exists($reportType, $reportFunctionMapping)) {
            print_r("Допустимые названия отчетов - top5longTasks, top5costTasks, top5employees");
            return;
        }

        $nameReport = $reportFunctionMapping[$reportType];

        $reportData = $db->$nameReport();

        if (empty($reportData)) {
            print_r("Данные отсутвуют \n");
        } else {
            $sep = "\t";
            $header = $reportType === 'top5employees' ? array("total_hours", "name") : ($reportType === 'top5costTasks' ? array("total_cost", "title") : array("spent_hours", "title"));
            $border = "+------------+-------------+\n";
            $header = implode($sep, $header);
            $header = $border . $header . "\n" . $border;
            $reportData = array_map(function ($row) use ($sep) {
                return implode($sep . $sep, $row);
            }, $reportData);
            $reportData = implode("\n", $reportData);
            $reportData = $reportData . " \n" . $border;
            print_r($header . $reportData);
        }
    }

    $operation = $argv[1];

    switch ($operation) {
        case "import":
            $filename = $argv[2];
            openFile($filename);
            checkAllowedFile($filename);
            $postgres->import($filename);
            break;

        case "get":
            $nameEmployee = $argv[2];
            $timesheetsAboutEmployee = $postgres->get($nameEmployee);
            display($timesheetsAboutEmployee);
            break;

        case "remove":
            $idForRemove = $argv[2];
            $remove = $postgres->remove($idForRemove);
            if (empty($remove)) print_r("Запись с id = $idForRemove не была найдена \n");
            else print_r("Timesheet id $idForRemove removed \n");
            break;

        case "report":
            $reportType = $argv[2];
            generateReport($postgres, $reportType);
            break;

        default:
            print_r("Invalid operation. Supported operations are 'import', 'get', 'remove', 'report'\n");
            break;
    }
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage() . "\n");
}
