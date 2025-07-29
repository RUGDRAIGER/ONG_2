<?php
// db_connect.php

// Parámetros de conexión a la base de datos
$host = 'localhost'; // En XAMPP, el host de MySQL es localhost
$db_name = 'ORGANIZACION';
$username = 'root'; // Usuario por defecto de MySQL en XAMPP (sin contraseña)
$password = '';     // Contraseña por defecto de MySQL en XAMPP (vacía)

try {
    // Cadena de conexión DSN (Data Source Name)
    $dsn = "mysql:host=$host;dbname=$db_name;charset=utf8mb4";

    // Opciones de PDO
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lanzar excepciones en errores
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Devolver filas como arrays asociativos
        PDO::ATTR_EMULATE_PREPARES   => false,                  // Desactivar emulación de preparaciones para mayor seguridad y rendimiento
    ];

    // Crear una nueva instancia de PDO (establecer la conexión)
    $pdo = new PDO($dsn, $username, $password, $options);

    // echo "Conexión a la base de datos exitosa."; // Descomenta para probar la conexión

} catch (PDOException $e) {
    // Si la conexión falla, se captura la excepción
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
?>