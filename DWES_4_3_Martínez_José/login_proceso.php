<?php
if (!filter_has_var(INPUT_COOKIE, "visitas")) {
    setcookie('visitas', '0', time() + 3600); //Creamos una cookie por primera vez en caso de que esta no exista
} else {//Si existe la cookie
    $visitasTest = intval(filter_input(INPUT_COOKIE, "visitas"));
    if (is_numeric($visitasTest) && filter_has_var(INPUT_POST, "Entrar") && $visitasTest < 3) {
        setcookie('visitas', $visitasTest + 1, time() + 3600); //Actualizamos la cookie cada vez que se envie una contraseña
    } else {
        setcookie('visitas', '', time() - 20000); //Eliminamos la cookie cuando el número de intentos sea igual que 3
    }
}

$tiempo = time();
//Guardamos la fecha actual de la sesión con hora, minutos y segundos también incluidos.
$tiempo_actual = date('Y-m-d  H:i:s', $tiempo);
$ultimaCon = ["usuario" => filter_input(INPUT_POST, "usuarioExistente"), "fecha" => $tiempo_actual];
if (!filter_has_var(INPUT_POST, "ultimaConexion") || !filter_input(INPUT_POST, "ultimaConexion")) {
    setcookie('ultimaConexion', serialize($ultimaCon), time() + 3600);
}
?>
<?php
if (filter_has_var(INPUT_POST, "Registrarse")) {
    header("Location: registro.html");
    die();
} else {
    ?>

    <html>
        <head>
            <meta charset="UTF-8">
            <title></title>
        </head>
        <body>
            <?php
            require_once './funcionesValidacion.php';
            require_once './funcionesBaseDeDatos.php';
            //creación de la conexión
            $conexionBD = new mysqli();
            $mensajeError = "";
            $mensajeExito = "";

            try {
                $conexionBD->connect("localhost", "root", "", "espectaculos");
            } catch (Exception $ex) {
                $mensajeError .= "ERROR: " . $ex->getMessage();
            }
            if (filter_has_var(INPUT_POST, "Entrar")) {
                try {
                    $usuarioLogin = validarUsuarioExistente(filter_input(INPUT_POST, "usuarioExistente"), $conexionBD);
//                    echo var_dump($usuarioLogin);
                    if ($usuarioLogin) {
                        $conexionBD->autocommit(false);
                        $consultaSesiones = $conexionBD->query("SELECT contraseña FROM usuarios WHERE login='$usuarioLogin'");
                        if ($consultaSesiones->num_rows > 0) {
                            $contraseña = $consultaSesiones->fetch_all(MYSQLI_ASSOC);
                            foreach ($contraseña as $contraseñaExistente) {
//                                echo var_dump(filter_input(INPUT_POST, "contraseñaExistente"), $contraseñaExistente);
                                $contraseñaEncriptada = hash("sha512", filter_input(INPUT_POST, "contraseñaExistente"));
                                $esValida = $contraseñaEncriptada === $contraseñaExistente['contraseña'];
                                if ($esValida) {
                                    $mensajeExito .= "Inicio de Sesión realizado con éxito";
                                } else {
                                    $mensajeError .= "No se ha podido iniciar sesión, la contraseña o el usuario no son correctos.";
                                }
                            }
                        } else {
                            $mensajeError .= "La consulta no se ha podido realizar.";
                        }
                    } else {
                        $mensajeError .= "Los datos son inválidos o incorrectos.";
                    }
                } catch (Exception $ex) {
                    $mensajeError .= "ERROR: " . $ex->getMessage();
                }
                ?>
                <h2>LISTA DE MENSAJES: </h2>
                <h2>Mensajes de error: </h2>
                <ul>
                    <li><?php
                        if (isset($mensajeError)) {
                            echo $mensajeError;
                        }
                        ?></li>
                </ul>
                <h2>Mensajes de exito: </h2>
                <ul>
                    <li><?php
                        if (isset($mensajeExito)) {
                            echo $mensajeExito;
                        }
                        ?></li>
                </ul>
                <h2>Contraseñas introducidas: </h2>
                <ul>
                    <li><?php
                        //Si la cookie existe vamos a devolver el total de veces que se han introducido contraseñas.
                        if (isset($visitasTest)) {
                            echo "Se han probado un total de $visitasTest contraseñas.";
                            ?></li>
                        <br><br>
                        <li><?php
                            if ($visitasTest == 3) {
                                //Si el valor es igual a 3 devolvemos el siguiente mensaje:
                                echo "Se ha superado el límite de intentos";
                            }
                        }
                        ?></li>
                </ul>

                <h2>Fecha de la última sesión: </h2>
                <ul>
                    <li><?php
                        //Si el array existe, vamos a deserializarlo y devolver la fecha en el mensaje correspondiente.
                        if (filter_has_var(INPUT_COOKIE, "ultimaConexion")) {
                            $datosUltimaSesion = unserialize(filter_input(INPUT_COOKIE, "ultimaConexion"));
                            echo "La ultima sesión fue el " . $datosUltimaSesion['fecha'] . ".";
                        }
                        ?></li>
                </ul>
                <?php
            }
            ?>
        </body>
    </html>
    <?php
}    