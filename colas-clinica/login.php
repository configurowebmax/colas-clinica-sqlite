<?php
//require/load the authentication file script
require_once('auth.php');
require_once('DBConnection.php');
$page = isset($_GET['page']) ? $_GET['page'] : 'home';
// Generate Login Form Token
$_SESSION['formToken']['login'] = password_hash(uniqid(), PASSWORD_DEFAULT);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LOGIN | Sistema de Colas Clínica</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@48,400,0,0" />
    <link rel="stylesheet" href="./css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/custom.css">
    <script src="./js/jquery-3.6.0.min.js"></script>
    <script src="./js/popper.min.js"></script>
    <script src="./js/bootstrap.min.js"></script>
    <script src="./js/script.js"></script>
</head>

<body class="bg-dark bg-gradient">
    <div class="h-100 d-flex jsutify-content-center align-items-center">
        <div class='w-100'>
            <h3 class="py-5 text-center text-light">Sistema de Colas Clínica</h3>
            <div class="card my-3 col-md-4 offset-md-4">
                <div class="card-body">
                    <!-- Login Form Wrapper -->
                    <form action="" id="login-form">
                        <input type="hidden" name="formToken" value="<?= $_SESSION['formToken']['login'] ?>">
                        <center><small>Por favor, introduzca sus credenciales.</small></center>
                        <div class="mb-3">
                            <label for="username" class="control-label">Usuario</label>
                            <input type="text" id="username" autofocus name="username" class="form-control rounded-0" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="control-label">Contraseña</label>
                            <input type="password" id="password" name="password" class="form-control rounded-0" required>
                        </div>
                        <div class="mb-3 d-flex w-100 justify-content-between align-items-end">
                            <!-- <a href="registration.php">Does not have an Account? Signup Here</a> -->
                            <a href="./patient_side.php" target="_blank" onclick="reloadLogin()">Página del formulario del paciente</a>
                            <button class="btn btn-sm btn-primary rounded-0 my-1">Acceder</button>
                        </div>
                    </form>
                    <!-- Login Form Wrapper -->
                </div>
            </div>
        </div>
    </div>

    <script>
        function reloadLogin() {
            setTimeout(function() {
                window.location.reload();
            }, 100);
        }
    </script>

    <script>
        $(function() {
            $('#login-form').submit(function(e) {
                e.preventDefault();
                $('.pop_msg').remove()
                var _this = $(this)
                var _el = $('<div>')
                _el.addClass('pop_msg')

                // Store the original button text
                var originalBtnText = _this.find('button[type="submit"]').text()

                // Disable button and show loading state
                _this.find('button').attr('disabled', true)
                _this.find('button[type="submit"]').text('Accediendo...')

                // Log the form data being sent
                console.log('Enviando datos:', $(this).serialize())

                $.ajax({
                    url: './LoginRegistration.php?a=login',
                    method: 'POST',
                    data: $(this).serialize(),
                    dataType: 'JSON',
                    error: function(xhr, status, error) {
                        // Enhanced error logging
                        console.log('Error Status:', status);
                        console.log('Error:', error);
                        console.log('Response Text:', xhr.responseText);
                        console.log('Status Code:', xhr.status);

                        _el.addClass('alert alert-danger')

                        // More descriptive error message based on the type of error
                        let errorMsg = "Ocurrió un error durante el inicio de sesión: ";
                        if (xhr.status === 404) {
                            errorMsg += "No se encontró el archivo LoginRegistration.php";
                        } else if (xhr.status === 500) {
                            errorMsg += "Error interno del servidor";
                        } else if (xhr.responseText) {
                            try {
                                // Try to parse response as JSON
                                let response = JSON.parse(xhr.responseText);
                                errorMsg += response.msg || "Error desconocido";
                            } catch (e) {
                                // If not JSON, show raw response
                                errorMsg += xhr.responseText;
                            }
                        } else {
                            errorMsg += error || "Error desconocido";
                        }

                        _el.text(errorMsg)
                        _this.prepend(_el)
                        _el.show('slow')

                        // Reset button state
                        _this.find('button').attr('disabled', false)
                        _this.find('button[type="submit"]').text(originalBtnText)
                    },
                    success: function(resp) {
                        console.log('Respuesta recibida:', resp)

                        if (resp.status == 'success') {
                            _el.addClass('alert alert-success')
                            _el.text(resp.msg || "Login exitoso")
                            setTimeout(() => {
                                location.replace('./');
                            }, 2000);
                        } else {
                            _el.addClass('alert alert-danger')
                            _el.text(resp.msg || "Error en las credenciales")
                            // Reset button state on error
                            _this.find('button').attr('disabled', false)
                            _this.find('button[type="submit"]').text(originalBtnText)
                        }

                        _el.hide()
                        _this.prepend(_el)
                        _el.show('slow')
                    }
                })
            })
        })
    </script>

</body>

</html>