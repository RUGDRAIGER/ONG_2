<?php
// Iniciar la sesión PHP para manejar datos de forma persistente durante la sesión del usuario.
session_start();

// --- Medida 1 y 2: Configurar el tiempo de vida de la sesión ---
$session_lifetime = 86400; // 1 día en segundos (24 * 60 * 60)

// Configurar la cookie de sesión (Medida 1: session.cookie_lifetime)
session_set_cookie_params([
    'lifetime' => $session_lifetime, // La cookie durará 1 día
    'path' => '/',                   // Disponible en todo el dominio
    'domain' => '',                  // Vacío para el dominio actual
    'secure' => true,                // Solo enviar cookie si es HTTPS (¡Muy importante para seguridad!)
    'httponly' => true,              // Previene acceso JS a la cookie (¡Muy importante para seguridad!)
    'samesite' => 'Lax'              // Protege contra CSRF
]);

// Configurar el tiempo de vida de los datos de sesión en el servidor (Medida 2: session.gc_maxlifetime)
ini_set('session.gc_maxlifetime', $session_lifetime);

// --- Inicialización de variables de sesión (simulación de base de datos) ---
// Si las variables de sesión no existen, las inicializamos con valores predeterminados.
if (!isset($_SESSION['totalDonated'])) {
    $_SESSION['totalDonated'] = 0;
}
if (!isset($_SESSION['donations'])) {
    $_SESSION['donations'] = [];
}
// MODIFICADO: Inicializar el carrito de donaciones
if (!isset($_SESSION['donation_cart'])) {
    $_SESSION['donation_cart'] = [];
}

// Medida 3 (Parte 1): Actualizar la marca de tiempo de última actividad
// Esto ayuda a que el contador de gc_maxlifetime se reinicie con cada actividad.
$_SESSION['last_activity'] = time();


if (!isset($_SESSION['events'])) {
    // Datos de ejemplo para eventos. Estas son las propiedades que manejará la clase Event.
    $_SESSION['events'] = [
        [
            'id' => 1,
            'name' => "Jornada de Limpieza de Playas",
            'date' => "2025-07-15",
            'time' => "09:00",
            'location' => "Playa del Sol",
            'description' => "Únete a nosotros para una gran jornada de limpieza de nuestras costas, contribuyendo a la conservación del medio ambiente marino.",
            'category' => "Voluntariado"
        ],
        [
            'id' => 2,
            'name' => "Cena Benéfica Anual 'Corazones Unidos'",
            'date' => "2025-08-20",
            'time' => "19:30",
            'location' => "Salón Grand Plaza",
            'description' => "Nuestra gala anual para recaudar fondos vitales para todos nuestros proyectos. Disfruta de una noche de solidaridad y buena compañía.",
            'category' => "Recaudación de Fondos"
        ],
        [
            'id' => 3,
            'name' => "Taller Intensivo de Primeros Auxilios",
            'date' => "2025-09-10",
            'time' => "10:00",
            'location' => "Centro Comunitario 'El Progreso'",
            'description' => "Aprende habilidades esenciales de primeros auxilios que pueden salvar vidas. Impartido por profesionales de la salud.",
            'category' => "Taller"
        ],
        [
            'id' => 4,
            'name' => "Conferencia Internacional de Desarrollo Sostenible",
            'date' => "2025-10-05",
            'time' => "09:00",
            'location' => "Universidad Central",
            'description' => "Un foro para explorar soluciones innovadoras y estrategias para un futuro más sostenible y equitativo en nuestra sociedad.",
            'category' => "Conferencia"
        ],
        [
            'id' => 5,
            'name' => "Voluntariado en Refugio de Animales",
            'date' => "2025-07-25",
            'time' => "11:00",
            'location' => "Refugio 'Patitas Felices'",
            'description' => "Dedica un día a ayudar a cuidar y dar cariño a los animales que esperan un hogar. ¡Su amor te lo agradecerá!",
            'category' => "Voluntariado"
        ]
    ];
}

// --- CLASE PHP: Clase Event ---
class Event {
    public $id;
    public $name;
    public $date;
    public $time;
    public $location;
    public $description;
    public $category;

    public function __construct($id, $name, $date, $time, $location, $description, $category) {
        $this->id = $id;
        $this->name = $name;
        $this->date = $date;
        $this->time = $time;
        $this->location = $location;
        $this->description = $description;
        $this->category = $category;
    }

    public function renderEventHtml() {
        return "
        <div class='event-item'>
            <h3>" . htmlspecialchars($this->name) . "</h3>
            <p><strong>Fecha:</strong> " . htmlspecialchars($this->date) . " " . htmlspecialchars($this->time) . "</p>
            <p><strong>Lugar:</strong> " . htmlspecialchars($this->location) . "</p>
            <p><strong>Categoría:</strong> " . htmlspecialchars($this->category) . "</p>
            <p>" . htmlspecialchars($this->description) . "</p>
        </div>";
    }
}

// --- FUNCIÓN PHP: Simulación de donación (ahora para cada item del carrito) ---
function recordDonation($donorName, $amount) {
    if (!isset($_SESSION['donations'])) {
        $_SESSION['donations'] = [];
    }
    if (!isset($_SESSION['totalDonated'])) {
        $_SESSION['totalDonated'] = 0;
    }

    $newDonation = [
        'donorName' => htmlspecialchars($donorName),
        'amount' => $amount,
        'date' => date('Y-m-d H:i:s')
    ];
    $_SESSION['donations'][] = $newDonation;
    $_SESSION['totalDonated'] += $amount;
}


// --- Manejo de Solicitudes POST ---

// Procesar formulario para añadir al carrito de donaciones
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_to_cart'])) {
    $donorName = trim($_POST['donor_name'] ?? '');
    $donationAmount = floatval($_POST['donation_amount'] ?? 0);

    if (!empty($donorName) && $donationAmount > 0) {
        // Almacenar en el carrito de donaciones
        $_SESSION['donation_cart'][] = ['donorName' => $donorName, 'amount' => $donationAmount];
        header("Location: index.php#donaciones");
        exit();
    } else {
        $_SESSION['error_message'] = "Por favor, ingresa tu nombre y un monto válido para añadir al carrito.";
        header("Location: index.php#donaciones");
        exit();
    }
}

// NUEVO: Procesar la eliminación de un producto del carrito
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['remove_from_cart'])) {
    $itemIndex = intval($_POST['remove_item_index'] ?? -1);

    if (isset($_SESSION['donation_cart'][$itemIndex])) {
        unset($_SESSION['donation_cart'][$itemIndex]);
        // Reindexar el array para evitar huecos en las claves
        $_SESSION['donation_cart'] = array_values($_SESSION['donation_cart']);
        $_SESSION['notification'] = "Producto eliminado del carrito.";
        header("Location: index.php#donaciones");
        exit();
    } else {
        $_SESSION['error_message'] = "Error al intentar eliminar el producto del carrito.";
        header("Location: index.php#donaciones");
        exit();
    }
}


// Procesar el carrito de donaciones
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['process_cart'])) {
    if (!empty($_SESSION['donation_cart'])) {
        $totalCartAmount = 0;
        $donorNames = array_unique(array_column($_SESSION['donation_cart'], 'donorName'));
        $mainDonorName = count($donorNames) > 0 ? implode(' y ', $donorNames) : 'Donante Anónimo';

        foreach ($_SESSION['donation_cart'] as $item) {
            recordDonation($item['donorName'], $item['amount']); // Registrar cada donación del carrito
            $totalCartAmount += $item['amount'];
        }
        $_SESSION['notification'] = "¡Gracias, " . htmlspecialchars($mainDonorName) . ", por tu generosa donación total de CLP$" . number_format($totalCartAmount, 0, ',', '.') . "!";
        $_SESSION['donation_cart'] = []; // Vaciar el carrito después de procesar
        header("Location: index.php#donaciones");
        exit();
    } else {
        $_SESSION['error_message'] = "Tu carrito de donaciones está vacío.";
        header("Location: index.php#donaciones");
        exit();
    }
}

// Vaciar el carrito de donaciones
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['clear_cart'])) {
    $_SESSION['donation_cart'] = [];
    $_SESSION['notification'] = "Carrito de donaciones vaciado."; // Esta notificación sí podría ser útil para el usuario
    header("Location: index.php#donaciones");
    exit();
}


// Procesar formulario de registro de eventos (sin cambios)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register_event'])) {
    $eventName = trim($_POST['event_name'] ?? '');
    $eventDate = trim($_POST['event_date'] ?? '');
    $eventTime = trim($_POST['event_time'] ?? '');
    $eventLocation = trim($_POST['event_location'] ?? '');
    $eventDescription = trim($_POST['event_description'] ?? '');
    $eventCategory = trim($_POST['event_category'] ?? '');

    if (!empty($eventName) && !empty($eventDate) && !empty($eventLocation)) {
        $newId = count($_SESSION['events']) > 0 ? max(array_column($_SESSION['events'], 'id')) + 1 : 1;

        $newEvent = new Event($newId, $eventName, $eventDate, $eventTime, $eventLocation, $eventDescription, $eventCategory);
        $_SESSION['events'][] = (array) $newEvent;

        $_SESSION['notification'] = "¡Evento '" . htmlspecialchars($eventName) . "' registrado con éxito!";
        header("Location: index.php#eventos");
        exit();
    } else {
        $_SESSION['error_message'] = "Por favor, completa todos los campos obligatorios del evento.";
        header("Location: index.php#eventos");
        exit();
    }
}

// --- Lógica para Filtrado de Eventos (manejo de GET requests) ---
$filteredEvents = $_SESSION['events'];

if ($_SERVER["REQUEST_METHOD"] == "GET" && (isset($_GET['search_event']) || isset($_GET['filter_category']) || isset($_GET['sort_date']))) {
    $searchTerm = strtolower(trim($_GET['event_search_input'] ?? ''));
    $filterCategory = $_GET['event_category_select'] ?? '';
    $sortByDate = isset($_GET['sort_date']);

    if (!empty($searchTerm)) {
        $filteredEvents = array_filter($filteredEvents, function($event) use ($searchTerm) {
            return strpos(strtolower($event['name']), $searchTerm) !== false ||
                   strpos(strtolower($event['description']), $searchTerm) !== false ||
                   strpos(strtolower($event['location']), $searchTerm) !== false;
        });
    }

    if (!empty($filterCategory) && $filterCategory !== 'all') {
        $filteredEvents = array_filter($filteredEvents, function($event) use ($filterCategory) {
            return $event['category'] === $filterCategory;
        });
    }

    if ($sortByDate) {
        usort($filteredEvents, function($a, $b) {
            $dateA = strtotime($a['date'] . ' ' . $a['time']);
            $dateB = strtotime($b['date'] . ' ' . $b['time']);
            return $dateA - $dateB;
        });
    }
}

$eventsToDisplay = [];
foreach ($filteredEvents as $eventData) {
    $eventsToDisplay[] = new Event(
        $eventData['id'],
        $eventData['name'],
        $eventData['date'],
        $eventData['time'],
        $eventData['location'],
        $eventData['description'],
        $eventData['category']
    );
}

// Calcular el total actual en el carrito
$totalCartAmount = 0;
foreach ($_SESSION['donation_cart'] as $item) {
    $totalCartAmount += $item['amount'];
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organización Sin Fines de Lucro</title>
    <style>
        /* Estilos CSS (integrados directamente para facilitar el copy-paste) */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            color: #333;
        }

        header {
            background-color: #4CAF50;
            color: white;
            padding: 1em 0;
            text-align: center;
        }

        nav ul {
            list-style: none;
            padding: 0;
            text-align: center;
            background-color: #333;
            margin: 0;
        }

        nav ul li {
            display: inline;
            margin: 0 15px;
        }

        nav ul li a {
            color: white;
            text-decoration: none;
            padding: 10px 0;
            display: inline-block;
        }

        .hero {
            background-color: #e0e0e0;
            text-align: center;
            padding: 50px 20px;
        }

        section {
            padding: 20px;
            margin: 20px auto;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 960px;
        }

        h2 {
            color: #4CAF50;
            text-align: center;
            margin-bottom: 20px;
        }

        .projects-container, .event-list, .donations-list {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }

        .project-item, .event-item {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            background-color: #f9f9f9;
            flex: 1 1 280px;
            max-width: 300px;
            box-sizing: border-box;
        }

        .project-item img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
            margin-bottom: 10px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group input[type="date"],
        .form-group input[type="time"],
        .form-group textarea,
        .form-group select {
            width: calc(100% - 20px);
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .form-group button, .event-filter-form button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
        }

        .form-group button:hover, .event-filter-form button:hover {
            background-color: #45a049;
        }

        #total-donations {
            text-align: center;
            margin-top: 20px;
            font-size: 1.2em;
            font-weight: bold;
            color: #0d6efd; /* Un color azul para destacar */
        }

        #recent-donations-list {
            list-style: none;
            padding: 0;
            margin-top: 20px;
        }

        #recent-donations-list li {
            background-color: #e9ecef;
            border: 1px solid #dee2e6;
            padding: 10px;
            margin-bottom: 8px;
            border-radius: 4px;
        }

        .event-filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f0f8ff;
            border-radius: 8px;
            border: 1px solid #cfe2ff;
            align-items: flex-end;
        }

        .event-filter-form div {
            flex: 1;
            min-width: 180px;
        }
        .event-filter-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        .event-filter-form input[type="text"],
        .event-filter-form select {
            width: calc(100% - 22px); /* Adjusting for padding */
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 1em;
        }
        .event-filter-form button {
            flex-grow: 0;
            min-width: 100px;
        }

        .event-registration-form {
            margin-top: 30px;
            padding: 20px;
            background-color: #f7f7f7;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
        }

        .event-registration-form h3 {
            color: #28a745;
            text-align: center;
            margin-bottom: 20px;
        }

        .event-registration-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        .event-registration-form input[type="text"],
        .event-registration-form input[type="date"],
        .event-registration-form input[type="time"],
        .event-registration-form textarea,
        .event-registration-form select {
            width: calc(100% - 22px);
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .event-registration-form button {
            background-color: #007bff;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1em;
            display: block;
            width: 100%;
            margin-top: 10px;
        }

        .event-registration-form button:hover {
            background-color: #0056b3;
        }

        /* Notificaciones */
        .notifications-container {
            margin-top: 20px;
        }

        .notification-item {
            background-color: #f0f0f0;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            border-left: 5px solid;
        }

        .notification-logro { border-color: #28a745; background-color: #d4edda; color: #155724; }
        .notification-campaña { border-color: #ffc107; background-color: #fff3cd; color: #856404; }
        .notification-progreso_donacion { border-color: #007bff; background-color: #cce5ff; color: #004085; }

        /* Estilos Carrito de Donaciones */
        .donation-cart-section {
            background-color: #e6f7ff;
            border: 1px solid #91d5ff;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
        }

        .donation-cart-section h3 {
            color: #1890ff;
            text-align: center;
            margin-bottom: 15px;
        }

        .donation-cart-list {
            list-style: none;
            padding: 0;
            margin-bottom: 15px;
        }

        .donation-cart-list li {
            background-color: #bae7ff;
            padding: 8px 10px;
            margin-bottom: 5px;
            border-radius: 3px;
            display: flex; /* Usar flexbox para alinear elementos */
            justify-content: space-between; /* Espaciar contenido y botón */
            align-items: center; /* Alinear verticalmente */
        }
        .donation-cart-list li span:first-child {
            font-weight: bold;
        }

        .cart-total {
            text-align: right;
            font-size: 1.1em;
            font-weight: bold;
            color: #1890ff;
            margin-top: 10px;
        }

        .cart-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }

        .cart-actions button {
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.95em;
            width: auto;
            display: inline-block; /* Para que los botones estén en línea */
        }

        .cart-actions .process-button {
            background-color: #28a745;
            color: white;
            border: none;
        }
        .cart-actions .process-button:hover {
            background-color: #218838;
        }

        .cart-actions .clear-button {
            background-color: #dc3545;
            color: white;
            border: none;
        }
        .cart-actions .clear-button:hover {
            background-color: #c82333;
        }

        /* Estilo para el botón de eliminar producto individual */
        .donation-cart-list li form button {
            background-color: #ff4d4f; /* Rojo */
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 0.8em;
            margin-left: 10px; /* Espacio entre el texto y el botón */
        }
        .donation-cart-list li form button:hover {
            background-color: #fa541c; /* Rojo más oscuro al pasar el mouse */
        }


        footer {
            text-align: center;
            padding: 20px;
            background-color: #333;
            color: white;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <header>
        <h1>Organización Sin Fines de Lucro</h1>
        <nav>
            <ul>
                <li><a href="#proyectos">Proyectos</a></li>
                <li><a href="#eventos">Eventos</a></li>
                <li><a href="#donaciones">Donaciones</a></li>
                <li><a href="#notificaciones">Notificaciones</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section id="hero" class="hero">
            <h2>Transformando Vidas a Través de la Solidaridad</h2>
            <p>Juntos, podemos construir un futuro mejor para quienes más lo necesitan.</p>
        </section>

        <section id="proyectos">
            <h2>Nuestros Proyectos</h2>
            <div id="projects-container" class="projects-container">
                </div>
        </section>

        <section id="eventos">
            <h2>Próximos Eventos</h2>

            <form action="index.php" method="GET" class="event-filter-form">
                <div>
                    <label for="event_search_input">Buscar Evento:</label>
                    <input type="text" id="event_search_input" name="event_search_input" placeholder="Nombre, lugar o descripción" value="<?php echo htmlspecialchars($_GET['event_search_input'] ?? ''); ?>">
                </div>
                <div>
                    <label for="event_category_select">Filtrar por Categoría:</label>
                    <select id="event_category_select" name="event_category_select">
                        <option value="all" <?php echo (($_GET['event_category_select'] ?? 'all') == 'all') ? 'selected' : ''; ?>>Todas</option>
                        <option value="Voluntariado" <?php echo (($_GET['event_category_select'] ?? '') == 'Voluntariado') ? 'selected' : ''; ?>>Voluntariado</option>
                        <option value="Recaudación de Fondos" <?php echo (($_GET['event_category_select'] ?? '') == 'Recaudación de Fondos') ? 'selected' : ''; ?>>Recaudación de Fondos</option>
                        <option value="Taller" <?php echo (($_GET['event_category_select'] ?? '') == 'Taller') ? 'selected' : ''; ?>>Taller</option>
                        <option value="Conferencia" <?php echo (($_GET['event_category_select'] ?? '') == 'Conferencia') ? 'selected' : ''; ?>>Conferencia</option>
                        <option value="Otro" <?php echo (($_GET['event_category_select'] ?? '') == 'Otro') ? 'selected' : ''; ?>>Otro</option>
                    </select>
                </div>
                <div>
                    <button type="submit" name="search_event">Buscar / Filtrar</button>
                </div>
                <div>
                    <button type="submit" name="sort_date">Ordenar por Fecha</button>
                </div>
            </form>

            <div id="results-container" class="event-list">
                <?php
                if (!empty($eventsToDisplay)) {
                    foreach ($eventsToDisplay as $event) {
                        echo $event->renderEventHtml();
                    }
                } else {
                    echo '<p>No se encontraron eventos que coincidan con la búsqueda o filtro.</p>';
                }
                ?>
            </div>

            <form action="index.php" method="POST" class="event-registration-form">
                <h3>Registrar Nuevo Evento</h3>
                <div class="form-group">
                    <label for="event_name">Nombre del Evento:</label>
                    <input type="text" id="event_name" name="event_name" required>
                </div>
                <div class="form-group">
                    <label for="event_date">Fecha:</label>
                    <input type="date" id="event_date" name="event_date" required>
                </div>
                <div class="form-group">
                    <label for="event_time">Hora:</label>
                    <input type="time" id="event_time" name="event_time" required>
                </div>
                <div class="form-group">
                    <label for="event_location">Lugar:</label>
                    <input type="text" id="event_location" name="event_location" required>
                </div>
                <div class="form-group">
                    <label for="event_description">Descripción:</label>
                    <textarea id="event_description" name="event_description" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label for="event_category">Categoría:</label>
                    <select id="event_category" name="event_category">
                        <option value="Voluntariado">Voluntariado</option>
                        <option value="Recaudación de Fondos">Recaudación de Fondos</option>
                        <option value="Taller">Taller</option>
                        <option value="Conferencia">Conferencia</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
                <button type="submit" name="register_event">Registrar Evento</button>
            </form>
        </section>

        <section id="donaciones">
            <h2>Haz tu Donación</h2>
            <div id="total-donations">
                <h3>Total Recaudado: CLP$<span id="total-amount"><?php echo number_format($_SESSION['totalDonated'], 0, ',', '.'); ?></span></h3>
            </div>

            <form action="index.php" method="POST" id="add-to-cart-form" class="form-group">
                <h3>Añadir al Carrito de Donaciones</h3>
                <label for="donor-name-cart">Tu Nombre:</label>
                <input type="text" id="donor-name-cart" name="donor_name" required>

                <label for="donation-amount-cart">Monto de Donación (CLP$):</label>
                <input type="number" id="donation-amount-cart" name="donation_amount" min="1" step="1" required>

                <button type="submit" name="add_to_cart">Añadir al Carrito</button>
            </form>

            <div class="donation-cart-section">
                <h3>Carrito de Donaciones</h3>
                <ul id="donation-cart-list" class="donation-cart-list">
                    <?php if (!empty($_SESSION['donation_cart'])): ?>
                        <?php foreach ($_SESSION['donation_cart'] as $index => $item): ?>
                            <li>
                                <span><?php echo htmlspecialchars($item['donorName']); ?></span>
                                <span>CLP$<?php echo number_format($item['amount'], 0, ',', '.'); ?></span>
                                <form action="index.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="remove_item_index" value="<?php echo $index; ?>">
                                    <button type="submit" name="remove_from_cart">Eliminar</button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>Tu carrito está vacío.</li>
                    <?php endif; ?>
                </ul>
                <div class="cart-total">
                    Total en Carrito: CLP$<span id="cart-total-amount"><?php echo number_format($totalCartAmount, 0, ',', '.'); ?></span>
                </div>
                <div class="cart-actions">
                    <form action="index.php" method="POST">
                        <button type="submit" name="process_cart" class="process-button" <?php echo empty($_SESSION['donation_cart']) ? 'disabled' : ''; ?>>Confirmar Donación</button>
                    </form>
                    <form action="index.php" method="POST">
                        <button type="submit" name="clear_cart" class="clear-button" <?php echo empty($_SESSION['donation_cart']) ? 'disabled' : ''; ?>>Vaciar Carrito</button>
                    </form>
                </div>
            </div>

            <h3>Donaciones Recientes</h3>
            <ul id="recent-donations-list" class="donations-list">
                <?php
                if (!empty($_SESSION['donations'])) {
                    $recentDonations = array_slice(array_reverse($_SESSION['donations']), 0, 5);
                    foreach ($recentDonations as $donation) {
                        echo "<li>" . htmlspecialchars($donation['donorName']) . " donó CLP$" . number_format($donation['amount'], 0, ',', '.') . " el " . date('d/m/Y H:i', strtotime($donation['date'])) . "</li>";
                    }
                } else {
                    echo '<li>Aún no hay donaciones. ¡Sé el primero!</li>';
                }
                ?>
            </ul>
        </section>

        <section id="notificaciones">
            <h2>Últimas Noticias y Logros</h2>
            <div id="notifications-container" class="notifications-container">
                </div>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 Organización Sin Fines de Lucro. Todos los derechos reservados.</p>
    </footer>

    <script>
        // --- Clases JavaScript para el frontend (Proyectos y Notificaciones) ---
        class Project {
            constructor(id, name, description, imageUrl) {
                this.id = id;
                this.name = name;
                this.description = description;
                this.imageUrl = imageUrl;
            }

            render() {
                const projectDiv = document.createElement('div');
                projectDiv.classList.add('project-item');
                projectDiv.innerHTML = `
                    <h3>${this.name}</h3>
                    <img src="${this.imageUrl}" alt="${this.name}" style="max-width: 100%; height: auto;">
                    <p>${this.description}</p>
                `;
                return projectDiv;
            }
        }

        class Notification {
            constructor(id, message, type, date) {
                this.id = id;
                this.message = message;
                this.type = type;
                this.date = new Date(date);
            }

            render() {
                const notificationDiv = document.createElement('div');
                notificationDiv.classList.add('notification-item', `notification-${this.type}`);
                let title = '';
                if (this.type === 'logro') {
                    title = 'Logro Alcanzado:';
                } else if (this.type === 'campaña') {
                    title = 'Campaña en Curso:';
                } else if (this.type === 'progreso_donacion') {
                    title = 'Actualización de Donación:';
                }
                notificationDiv.innerHTML = `
                    <p><strong>${title}</strong> ${this.message}</p>
                    <small>${this.date.toLocaleString('es-ES')}</small>
                `;
                return notificationDiv;
            }
        }

        // --- Datos de ejemplo para Proyectos ---
        const projectsData = [
            new Project(1, "Construyendo Futuros", "Un proyecto visionario que busca edificar escuelas y centros comunitarios en las áreas rurales más necesitadas, brindando acceso a educación de calidad y espacios de encuentro para el desarrollo local.", "images/proyecto-escuelas.jpg"),
            new Project(2, "Agua Pura para Todos", "Iniciativa crucial para instalar sistemas de purificación y distribución de agua potable en comunidades desfavorecidas, garantizando la salud y el bienestar de sus habitantes.", "images/proyecto-agua-limpia.jpg"),
            new Project(3, "Sembrando Esperanza", "Programa de apoyo integral a la salud mental, ofreciendo talleres, terapias grupales y recursos educativos para fomentar el bienestar emocional y reducir el estigma asociado.", "images/salud-mental-taller.jpg")
        ];

        // --- Datos de ejemplo para Notificaciones ---
        const notificationsData = [
            new Notification(1, "¡Hemos superado nuestra meta y hemos construido 2 nuevas escuelas este año, impactando a cientos de niños!", "logro", "2025-06-18T10:00:00"),
            new Notification(2, "Nuestra campaña 'Agua Limpia para Todos' ha recaudado CLP$15.000. ¡Estamos a solo CLP$5.000 de nuestro objetivo!", "campaña", "2025-06-20T14:30:00"),
            new Notification(3, "El proyecto 'Sembrando Esperanza' ha brindado apoyo psicológico a más de 500 personas en la comunidad.", "logro", "2025-06-22T09:00:00")
        ];

        // --- Lógica de Interfaz con JavaScript ---
        function loadProjects() {
            const projectsContainer = document.getElementById('projects-container');
            // Asegurarse de que el contenedor existe antes de intentar añadir contenido
            if (projectsContainer) {
                projectsContainer.innerHTML = ''; // Limpiar cualquier contenido previo
                projectsData.forEach(project => {
                    projectsContainer.appendChild(project.render());
                });
            } else {
                console.error("Error: Elemento con ID 'projects-container' no encontrado.");
            }
        }

        function loadNotifications() {
            const notificationsContainer = document.getElementById('notifications-container');
            // Asegurarse de que el contenedor existe antes de intentar añadir contenido
            if (notificationsContainer) {
                notificationsContainer.innerHTML = ''; // Limpiar cualquier contenido previo
                const sortedNotifications = [...notificationsData].sort((a, b) => b.date - a.date);
                sortedNotifications.forEach(notification => {
                    notificationsContainer.appendChild(notification.render());
                });
            } else {
                console.error("Error: Elemento con ID 'notifications-container' no encontrado.");
            }
        }

        // Función para añadir notificaciones dinámicamente (llamada por JS o PHP)
        function addNotification(message, type) {
            const newNotificationId = notificationsData.length > 0 ? Math.max(...notificationsData.map(n => n.id)) + 1 : 1;
            const newNotification = new Notification(newNotificationId, message, type, new Date());
            notificationsData.push(newNotification);
            loadNotifications();
        }

        // --- Event Listeners ---
        document.addEventListener('DOMContentLoaded', () => {
            loadProjects(); // Carga los proyectos al iniciar
            loadNotifications(); // Carga las notificaciones al iniciar

            // Manejo de mensajes de sesión desde PHP
            const phpNotification = "<?php echo isset($_SESSION['notification']) ? $_SESSION['notification'] : ''; unset($_SESSION['notification']); ?>";
            const phpErrorMessage = "<?php echo isset($_SESSION['error_message']) ? $_SESSION['error_message'] : ''; unset($_SESSION['error_message']); ?>";

            if (phpNotification) {
                // Para las notificaciones del carrito, usamos un tipo específico
                addNotification(phpNotification, 'progreso_donacion');
            }
            if (phpErrorMessage) {
                alert(phpErrorMessage);
            }
        });
    </script>
</body>
</html>