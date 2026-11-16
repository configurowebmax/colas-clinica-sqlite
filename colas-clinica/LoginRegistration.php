<?php
session_start();
require_once('DBConnection.php');
/**
 * Login Registration Class
 */
class LoginRegistration extends DBConnection
{
    function __construct()
    {
        parent::__construct();
    }
    function __destruct()
    {
        parent::__destruct();
    }
    function login()
    {
        /**
         * Login Form
         * 
         */
        //Extracting Post array to variables.
        extract($_POST);
        // Retrieving Allowed Token
        $allowedToken = $_SESSION['formToken']['login'];
        if (!isset($formToken) || (isset($formToken) && $formToken != $allowedToken)) {
            // throw new ErrorException("Security Check: Form Token is valid.");
            $resp['status'] = 'failed';
            $resp['msg'] = "Comprobación de seguridad: el token del formulario no es válido.";
        } else {
            // Query Statement
            $sql = "SELECT * FROM user_list where username = :username ";
            // Preparing Query Statement
            $stmt = $this->prepare($sql);
            // binding Query Value/s
            $stmt->bindValue(':username', $username, SQLITE3_TEXT);
            // Executing Query
            $result = $stmt->execute();
            // Fetching Result
            $data = $result->fetchArray();
            if (!empty($data)) {
                // print_r($data['password']);exit;
                //Verifying Password
                $password_verify = password_verify($password, $data['password']);
                if ($password_verify) {
                    if ($data['status'] == 1) {
                        // Login Success
                        $resp['status'] = "success";
                        $resp['msg'] = "Acceso exitoso";
                        foreach ($data as $k => $v) {
                            if (!is_numeric($k) && !in_array($k, ['password']))
                                $_SESSION[$k] = $v;
                        }
                    } elseif ($data['status'] == 0) {
                        // Pending
                        $resp['status'] = "failed";
                        $resp['msg'] = "Su cuenta aún se encuentra sujeta a estado de aprobación.";
                    } elseif ($data['status'] == 2) {
                        // Denied
                        $resp['status'] = "failed";
                        $resp['msg'] = "Se ha denegado el acceso al sistema a su cuenta. Póngase en contacto con la administración para resolver el problema.";
                    } elseif ($data['status'] == 3) {
                        // Blocked
                        $resp['status'] = "failed";
                        $resp['msg'] = "Su cuenta ha sido bloqueada. Póngase en contacto con la administración para resolver el problema.";
                    } else {
                        $resp['status'] = "failed";
                        $resp['msg'] = "Estado no válido. Póngase en contacto con la administración para resolverlo.";
                    }
                } else {
                    // Invalid Password
                    $resp['status'] = "failed";
                    $resp['msg'] = "Nombre de usuario o contraseña no válidos.";
                }
            } else {
                // Invalid Username
                $resp['status'] = "failed";
                $resp['msg'] = "Nombre de usuario o contraseña no válidos.";
            }
        }
        return json_encode($resp);
    }
    function logout()
    {
        // Destroying user session
        session_destroy();
        header("location:./");
    }
    function register_user()
    {
        /**
         * User Registration Form Action
         */

        //escaping input values
        foreach ($_POST as $k => $v) {
            if (!in_array($k, ['user_id', 'formToken']) && !is_numeric($v) && !is_array($_POST[$k])) {
                $_POST[$k] = $this->escapeString($v);
            }
        }
        //extracting Post array values
        extract($_POST);
        $allowedToken = $_SESSION['formToken']['registration'];
        if (!isset($formToken) || (isset($formToken) && $formToken != $allowedToken)) {
            // throw new ErrorException("Security Check: Form Token is valid.");
            $resp['status'] = 'failed';
            $resp['msg'] = "Comprobación de seguridad: el token del formulario no es válido.";
        } else {
            // Table column 
            $dbColumn = "(`fullname`, `username`, `password`, `status`, `type`)";
            // Encypting Password
            $password = password_hash($password, PASSWORD_DEFAULT);
            // Table Values
            $values = "('{$fullname}', '{$username}', '{$password}', 0, 2)";
            // insertion Query Statement
            $sql = "INSERT INTO `user_list` {$dbColumn} VALUES {$values}";
            // Executing Insertion Query
            $insert = $this->query($sql);
            if ($insert) {
                // Successfull insertion
                $resp['status'] = 'success';
                $resp['msg'] = "Su cuenta ha sido creada exitosamente pero está sujeta a aprobación.";
            } else {
                // Insertion Failed
                $resp['status'] = 'failed';
                $resp['msg'] = "Error: " . $this->lastErrorMsg();
            }
        }
        echo json_encode($resp);
    }
    function save_user()
    {
        /**
         * Update User Form Action
         */
        foreach ($_POST as $k => $v) {
            if (!in_array($k, ['user_id', 'formToken', 'password']) && !is_numeric($v) && !is_array($_POST[$k])) {
                $_POST[$k] = htmlspecialchars($this->escapeString($v));
            }
        }
        //extracting Post array values
        extract($_POST);
        $allowedToken = $_SESSION['formToken']['manage_user'];
        if (!isset($formToken) || (isset($formToken) && $formToken != $allowedToken)) {
            // throw new ErrorException("Security Check: Form Token is valid.");
            $resp['status'] = 'failed';
            $resp['msg'] = "Comprobación de seguridad: el token del formulario no es válido.";
        } else {
            $password = password_hash($password, PASSWORD_DEFAULT);
            if (!empty($user_id)) {

                // UPDATE Query Statement
                $sql = "UPDATE `user_list` set `fullname` = '{$fullname}', `username` = '{$username}', `password` = '{$password}', `status` = '{$status}', `type` = '{$type}' where `user_id` = '{$user_id}'";
            } else {
                $sql = "INSERT INTO `user_list` (`fullname`, `username`, `password`, `status`, `type`) VALUES ('{$fullname}', '{$username}', '{$password}', '{$status}', '{$type}')";
            }

            // Executing update Query
            $save = $this->query($sql);
            if ($save) {
                // Successfull Update
                $resp['status'] = 'success';
                if (!empty($user_id)) {
                    $resp['msg'] = "La cuenta de usuario se ha actualizado correctamente.";
                } else {
                    $resp['msg'] = "La cuenta de usuario se ha añadido correctamente.";
                }
                $_SESSION['message']['success'] = $resp['msg'];
            } else {
                // Update Failed
                $resp['status'] = 'failed';
                $resp['msg'] = "Error: " . $this->lastErrorMsg();
            }
        }
        echo json_encode($resp);
    }
    function update_password()
    {
        /**
         * Update Account Password Form Action
         */

        //extracting Post array values
        extract($_POST);
        $allowedToken = $_SESSION['formToken']['account-form'];
        if (!isset($formToken) || (isset($formToken) && $formToken != $allowedToken)) {
            // throw new ErrorException("Security Check: Form Token is valid.");
            $resp['status'] = 'failed';
            $resp['msg'] = "Comprobación de seguridad: el token del formulario no es válido.";
        } else {
            $password = $this->querySingle("SELECT `password` FROM `user_list` where `user_id`='{$_SESSION['user_id']}' ");
            $is_verify = password_verify($current_password, $password);
            if (!$is_verify) {
                $resp['status'] = 'failed';
                $resp['msg'] = "Current Password is incorrect.";
            } else {
                if ($new_password != $confirm_new_password) {
                    $resp['status'] = 'failed';
                    $resp['msg'] = "La nueva contraseña no coincide.";
                } else {
                    $new_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update = $this->query("UPDATE `user_list` set `password` = '{$new_password}' where `user_id` = '{$_SESSION['user_id']}'");
                    if ($update) {
                        $resp['status'] = 'success';
                        $resp['msg'] = "La contraseña se ha actualizado correctamente.";
                        $_SESSION['message']['success'] = $resp['msg'];
                    } else {
                        $resp['status'] = 'failed';
                        $resp['msg'] = $this->lastErrorMsg();
                    }
                }
            }
        }
        echo json_encode($resp);
    }
}
$a = isset($_GET['a']) ? $_GET['a'] : '';
$LG = new LoginRegistration();
switch ($a) {
    case 'login':
        echo $LG->login();
        break;
    case 'logout':
        echo $LG->logout();
        break;
    case 'register_user':
        echo $LG->register_user();
        break;
    case 'save_user':
        echo $LG->save_user();
        break;
    case 'update_password':
        echo $LG->update_password();
        break;
    default:
        // default action here
        break;
}
