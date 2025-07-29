<?php
// process_proyecto.php
require_once 'db_connect.php'; // Incluye el archivo de conexión a la base de datos

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Obtener y sanear los datos del formulario
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $presupuesto = floatval($_POST['presupuesto'] ?? 0); // Convertir a float
    $fecha_inicio = trim($_POST['fecha_inicio'] ?? '');
    $fecha_fin = trim($_POST['fecha_fin'] ?? NULL); // Puede ser nulo

    // 2. Validaciones básicas en el servidor
    $errors = [];

    if (empty($nombre)) {
        $errors[] = "El nombre del proyecto es obligatorio.";
    }
    if ($presupuesto <= 0) {
        $errors[] = "El presupuesto debe ser un número positivo.";
    }
    if (empty($fecha_inicio)) {
        $errors[] = "La fecha de inicio es obligatoria.";
    }
    if (!empty($fecha_fin) && $fecha_fin < $fecha_inicio) {
        $errors[] = "La fecha de fin no puede ser anterior a la fecha de inicio.";
    }

    // 3. Si no hay errores, insertar en la base de datos
    if (empty($errors)) {
        try {
            // Preparar la consulta SQL (usando prepared statements para seguridad)
            $sql = "INSERT INTO PROYECTO (nombre, descripcion, presupuesto, fecha_inicio, fecha_fin) VALUES (:nombre, :descripcion, :presupuesto, :fecha_inicio, :fecha_fin)";
            $stmt = $pdo->prepare($sql);

            // Bind de los parámetros
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':descripcion', $descripcion);
            $stmt->bindParam(':presupuesto', $presupuesto);
            $stmt->bindParam(':fecha_inicio', $fecha_inicio);
            $stmt->bindParam(':fecha_fin', $fecha_fin); // PHP automáticamente maneja NULL para fechas si es una cadena vacía

            // Ejecutar la consulta
            $stmt->execute();

            echo "
            <!DOCTYPE html>
            <html lang='es'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>Proyecto Registrado</title>
                <style>
                    body { font-family: Arial, sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
                    .message-box { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
                    .message-box h2 { color: #28a745; }
                    .message-box a { color: #007bff; text-decoration: none; margin-top: 10px; display: inline-block; }
                    .message-box a:hover { text-decoration: underline; }
                </style>
            </head>
            <body>
                <div class='message-box'>
                    <h2>¡Éxito!</h2>
                    <p>El proyecto '<strong>" . htmlspecialchars($nombre) . "</strong>' ha sido registrado correctamente.</p>
                    <a href='form_proyecto.html'>Registrar otro proyecto</a><br>
                    <a href='view_data.php'>Ver proyectos y donantes</a>
                </div>
            </body>
            </html>
            ";

        } catch (PDOException $e) {
            // Manejo de errores de la base de datos
            echo "
            <!DOCTYPE html>
            <html lang='es'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>Error de Registro</title>
                <style>
                    body { font-family: Arial, sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
                    .message-box { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
                    .message-box h2 { color: #dc3545; }
                    .message-box a { color: #007bff; text-decoration: none; margin-top: 10px; display: inline-block; }
                    .message-box a:hover { text-decoration: underline; }
                </style>
            </head>
            <body>
                <div class='message-box'>
                    <h2>Error al registrar el proyecto:</h2>
                    <p>" . htmlspecialchars($e->getMessage()) . "</p>
                    <a href='form_proyecto.html'>Volver al formulario de proyectos</a>
                </div>
            </body>
            </html>
            ";
        }
    } else {
        // Si hay errores de validación, mostrarlos al usuario
        echo "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Errores de Validación</title>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
                .message-box { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
                .message-box h2 { color: #ffc107; }
                .message-box ul { list-style: none; padding: 0; text-align: left; }
                .message-box li { margin-bottom: 5px; }
                .message-box a { color: #007bff; text-decoration: none; margin-top: 10px; display: inline-block; }
                .message-box a:hover { text-decoration: underline; }
            </style>
        </head>
        <body>
            <div class='message-box'>
                <h2>Errores en el formulario:</h2>
                <ul>";
        foreach ($errors as $error) {
            echo "<li>- " . htmlspecialchars($error) . "</li>";
        }
        echo "
                </ul>
                <a href='form_proyecto.html'>Volver al formulario de proyectos</a>
            </div>
        </body>
        </html>
        ";
    }
} else {
    // Si se accede directamente al script sin POST
    header("Location: form_proyecto.html");
    exit();
}
?>