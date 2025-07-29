<?php
// view_data.php
require_once 'db_connect.php'; // Incluye el archivo de conexión a la base de datos

$proyectos = [];
$donantes = [];

try {
    // Obtener todos los proyectos
    $stmt_proyectos = $pdo->query("SELECT * FROM PROYECTO ORDER BY nombre ASC");
    $proyectos = $stmt_proyectos->fetchAll();

    // Obtener todos los donantes
    $stmt_donantes = $pdo->query("SELECT * FROM DONANTE ORDER BY nombre ASC");
    $donantes = $stmt_donantes->fetchAll();

} catch (PDOException $e) {
    echo "Error al cargar datos: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Proyectos y Donantes</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 20px; color: #333; }
        .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); max-width: 900px; margin: auto; }
        h2 { text-align: center; margin-bottom: 20px; }
        h3 { color: #4CAF50; border-bottom: 2px solid #eee; padding-bottom: 5px; margin-top: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; color: #555; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        tr:hover { background-color: #f1f1f1; }
        .no-records { text-align: center; color: #888; padding: 20px; }
        .links { text-align: center; margin-top: 30px; }
        .links a { background-color: #007bff; color: white; padding: 10px 15px; border-radius: 5px; text-decoration: none; margin: 0 10px; display: inline-block; }
        .links a:hover { background-color: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Listado de Proyectos y Donantes</h2>

        <h3>Proyectos Registrados</h3>
        <?php if (!empty($proyectos)): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Presupuesto (CLP$)</th>
                    <th>F. Inicio</th>
                    <th>F. Fin</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($proyectos as $proyecto): ?>
                <tr>
                    <td><?php echo htmlspecialchars($proyecto['id_proyecto']); ?></td>
                    <td><?php echo htmlspecialchars($proyecto['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($proyecto['descripcion']); ?></td>
                    <td><?php echo number_format($proyecto['presupuesto'], 2, ',', '.'); ?></td>
                    <td><?php echo htmlspecialchars($proyecto['fecha_inicio']); ?></td>
                    <td><?php echo htmlspecialchars($proyecto['fecha_fin'] ?? 'N/A'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p class="no-records">No hay proyectos registrados aún.</p>
        <?php endif; ?>

        <h3 style="color: #007bff;">Donantes Registrados</h3>
        <?php if (!empty($donantes)): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Dirección</th>
                    <th>Teléfono</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($donantes as $donante): ?>
                <tr>
                    <td><?php echo htmlspecialchars($donante['id_donante']); ?></td>
                    <td><?php echo htmlspecialchars($donante['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($donante['email']); ?></td>
                    <td><?php echo htmlspecialchars($donante['direccion'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($donante['telefono'] ?? 'N/A'); ?></td>
                </tr>
                <?php endante; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p class="no-records">No hay donantes registrados aún.</p>
        <?php endif; ?>

        <div class="links">
            <a href="form_proyecto.html">Registrar Nuevo Proyecto</a>
            <a href="form_donante.html">Registrar Nuevo Donante</a>
        </div>
    </div>
</body>
</html>