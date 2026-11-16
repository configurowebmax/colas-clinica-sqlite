<?php
if (session_id() === "")
    session_start();
require_once('DBConnection.php');
/**
 * Login Registration Class
 */
class Master extends DBConnection
{
    function __construct()
    {
        parent::__construct();
    }
    function __destruct()
    {
        parent::__destruct();
    }
    function save_patient()
    {
        try {  // Agregamos manejo de errores
            if (!isset($_POST['user_id']))
                $_POST['user_id'] = $_SESSION['user_id'] ?? null;

            $resp = ['status' => 'failed'];  // Inicializamos la respuesta

            foreach ($_POST as $k => $v) {
                if (!in_array($k, ['formToken']) && !is_array($_POST[$k]) && !is_numeric($v)) {
                    $_POST[$k] = $this->escapeString($v);
                }
            }
            extract($_POST);

            // Verificamos el token primero
            if (!isset($formToken) || $formToken != $_SESSION['formToken']['patient-form']) {
                $resp['msg'] = "Comprobación de seguridad: el token del formulario no es válido.";
                return json_encode($resp);
            }

            $allowed_field = ['fullname', 'contact', 'address', 'age', 'weight', 'bp_rate', 'status'];
            $cols = [];
            $vals = [];

            foreach ($_POST as $k => $v) {
                if (in_array($k, $allowed_field)) {
                    $cols[] = $k;
                    $vals[] = $v;
                }
            }

            // Manejo de la fecha
            $from = date("Y-m-d") . " 00:00:00";
            $to = date("Y-m-d") . " 23:59:59";
            $from = new DateTime($from, new DateTimeZone(tZone));
            $from->setTimezone(new DateTimeZone('UTC'));
            $from = $from->format("Y-m-d H:i:s");
            $to = new DateTime($to, new DateTimeZone(tZone));
            $to->setTimezone(new DateTimeZone('UTC'));
            $to = $to->format("Y-m-d H:i:s");

            if (empty($patient_id)) {
                // Nuevo paciente
                $queue_no = 0;
                $get_queue = $this->querySingle("SELECT `queue_no` FROM `patient_list` WHERE `date_created` BETWEEN '{$from}' AND '{$to}' ORDER BY strftime('%s', `date_created`) DESC LIMIT 1");
                if ($get_queue) {
                    $queue_no = $get_queue;
                }
                $queue_no = intval($queue_no) + 1;
                $queue_no = sprintf("%'.04d", $queue_no);

                $cols[] = 'queue_no';
                $vals[] = $queue_no;

                $cols = "`" . implode("`, `", $cols) . "`";
                $vals = "'" . implode("', '", $vals) . "'";
                $sql = "INSERT INTO `patient_list` ({$cols}) VALUES ({$vals})";
            } else {
                // Actualizar paciente existente
                $updates = [];
                foreach ($cols as $k => $v) {
                    $updates[] = "`{$v}` = '{$vals[$k]}'";
                }
                $sql = "UPDATE `patient_list` SET " . implode(", ", $updates) . " WHERE `patient_id` = '{$patient_id}'";
            }

            error_log("SQL Query: " . $sql);  // Debug
            $qry = $this->query($sql);

            if ($qry) {
                $resp['status'] = 'success';
                if (empty($patient_id)) {
                    if (isset($encoded_by) && $encoded_by == 'patient') {
                        $id = $this->lastInsertRowID();
                        $queue_no = $this->querySingle("SELECT `queue_no` FROM `patient_list` WHERE `patient_id` = '{$id}'");
                        $resp['queue'] = $queue_no;
                        $resp['msg'] = "Ahora estás en la cola. Tu número de cola es <b>{$queue_no}</b>.";
                    } else {
                        $resp['msg'] = 'Se ha añadido un nuevo paciente con éxito';
                    }
                } else {
                    $resp['msg'] = 'Los detalles del paciente se han actualizado correctamente';
                }
                $_SESSION['message']['success'] = $resp['msg'];
            } else {
                $resp['msg'] = 'Error: ' . $this->lastErrorMsg() . ", SQL: {$sql}";
            }

            return json_encode($resp);
        } catch (Exception $e) {
            return json_encode([
                'status' => 'failed',
                'msg' => 'Error: ' . $e->getMessage(),
                'sql' => $sql ?? ''
            ]);
        }
    }
    function delete_patient()
    {
        extract($_POST);
        $allowedToken = $_SESSION['formToken']['patients'];
        if (!isset($token) || (isset($token) && $token != $allowedToken)) {
            $resp['status'] = 'failed';
            $resp['msg'] = "Comprobación de seguridad: el token no es válido.";
        } else {
            $sql = "DELETE FROM `patient_list` where `patient_id` = '{$id}'";
            $delete = $this->query($sql);
            if ($delete) {
                $resp['status'] = 'success';
                $resp['msg'] = 'Los datos del paciente se han eliminado correctamente';
                $_SESSION['message']['success'] = $resp['msg'];
            } else {
                $resp['status'] = 'failed';
                $resp['msg'] = $this->lastErrorMsg();
            }
        }
        return json_encode($resp);
    }
    function get_current_queueu()
    {
        try {
            $resp = ['status' => 'failed'];  // Inicializamos la respuesta

            extract($_POST);
            $from = date("Y-m-d") . " 00:00:00";
            $to = date("Y-m-d") . " 23:59:59";
            $from = new DateTime($from, new DateTimeZone(tZone));
            $from->setTimezone(new DateTimeZone('UTC'));
            $from = $from->format("Y-m-d H:i:s");
            $to = new DateTime($to, new DateTimeZone(tZone));
            $to->setTimezone(new DateTimeZone('UTC'));
            $to = $to->format("Y-m-d H:i:s");

            $allowedToken = $_SESSION['formToken']['patients_queueu'];
            if (!isset($token) || $token != $allowedToken) {
                $resp['msg'] = "Comprobación de seguridad: el token no es válido.";
                return json_encode($resp);
            }

            $sql = "SELECT * FROM `patient_list` WHERE `date_created` BETWEEN '{$from}' AND '{$to}' AND `status` = 0 ORDER BY strftime('%s', `date_created`) ASC LIMIT 1";
            $get = $this->query($sql);

            if ($get) {
                $data = $get->fetchArray(SQLITE3_ASSOC);
                if ($data) {
                    $resp['status'] = 'success';
                    $resp['data'] = $data;
                    $this->query("UPDATE `patient_list` SET `notify` = 0 WHERE `patient_id` = '{$data['patient_id']}'");
                } else {
                    $resp['msg'] = "No hay pacientes pendientes en la cola.";
                }
            } else {
                $resp['msg'] = "Error al ejecutar la consulta: " . $this->lastErrorMsg();
            }

            return json_encode($resp);
        } catch (Exception $e) {
            return json_encode([
                'status' => 'failed',
                'msg' => 'Error: ' . $e->getMessage(),
                'sql' => $sql ?? ''
            ]);
        }
    }
    function notify_queue()
    {
        extract($_POST);
        $allowedToken = $_SESSION['formToken']['patients'];
        if (!isset($token) || (isset($token) && $token != $allowedToken)) {
            $resp['status'] = 'failed';
            $resp['msg'] = "Comprobación de seguridad: el token no es válido.";
        } else {
            $update =  $this->query("UPDATE `patient_list` set `notify` = 1 where `patient_id` = '{$patient_id}'");
            if ($update) {
                $resp['status'] = 'success';
                $resp['msg'] = "Se notificará nuevamente al paciente en el monitor de colas.";
                $_SESSION['message']['success'] = $resp['msg'];
            }
        }
        return json_encode($resp);
    }
    function today_patients()
    {
        $from = date("Y-m-d", strtotime(date("Y-m-d"))) . " 00:00:00";
        $to =  date("Y-m-d", strtotime(date("Y-m-d"))) . " 23:59:59";
        $from = new DateTime($from, new DateTimeZone('America/Bogota'));
        $from->setTimezone(new DateTimeZone('UTC'));
        $from = $from->format("Y-m-d");
        $to = new DateTime($to, new DateTimeZone('America/Bogota'));
        $to->setTimezone(new DateTimeZone('UTC'));
        $to = $to->format("Y-m-d");

        $total = $this->querySingle("SELECT COUNT(`patient_id`) FROM `patient_list` where date(`date_created`) BETWEEN '{$from}' and '{$to}'");
        return number_format($total);
    }
    function today_patients_pending()
    {
        $from = date("Y-m-d", strtotime(date("Y-m-d"))) . " 00:00:00";
        $to =  date("Y-m-d", strtotime(date("Y-m-d"))) . " 23:59:59";
        $from = new DateTime($from, new DateTimeZone('America/Bogota'));
        $from->setTimezone(new DateTimeZone('UTC'));
        $from = $from->format("Y-m-d");
        $to = new DateTime($to, new DateTimeZone('America/Bogota'));
        $to->setTimezone(new DateTimeZone('UTC'));
        $to = $to->format("Y-m-d");

        $total = $this->querySingle("SELECT COUNT(`patient_id`) FROM `patient_list` where date(`date_created`) BETWEEN '{$from}' and '{$to}' and `status` = 0");
        return number_format($total);
    }
    function today_patients_done()
    {
        $from = date("Y-m-d", strtotime(date("Y-m-d"))) . " 00:00:00";
        $to =  date("Y-m-d", strtotime(date("Y-m-d"))) . " 23:59:59";
        $from = new DateTime($from, new DateTimeZone('America/Bogota'));
        $from->setTimezone(new DateTimeZone('UTC'));
        $from = $from->format("Y-m-d");
        $to = new DateTime($to, new DateTimeZone('America/Bogota'));
        $to->setTimezone(new DateTimeZone('UTC'));
        $to = $to->format("Y-m-d");

        $total = $this->querySingle("SELECT COUNT(`patient_id`) FROM `patient_list` where date(`date_created`) BETWEEN '{$from}' and '{$to}' and  `status` = 1");
        return number_format($total);
    }
}
$a = isset($_GET['a']) ? $_GET['a'] : '';
$master = new Master();
switch ($a) {
    case 'save_patient':
        echo $master->save_patient();
        break;
    case 'delete_patient':
        echo $master->delete_patient();
        break;
    case 'get_current_queueu':
        echo $master->get_current_queueu();
        break;
    case 'notify_queue':
        echo $master->notify_queue();
        break;
    default:
        // default action here
        break;
}
