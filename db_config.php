<?php
// db_config.php - Archivo de Configuración de la Base de Datos

// ⚠️ DEBES REEMPLAZAR ESTOS VALORES CON LOS DE TU BASE DE DATOS
$servername = "localhost";
$username = "root";       
$password = "";           // Usualmente vacío si usas root en WAMP/XAMPP
$dbname = "vb_beats";     // Reemplaza con el nombre real de tu base de datos

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    // Si hay un error, el script muere y muestra el mensaje
    die("Error de conexión a la base de datos: " . $conn->connect_error);
}

// Establecer el conjunto de caracteres a UTF-8 (esencial para acentos)
$conn->set_charset("utf8mb4");

// IMPORTANTE: La función getProducts() NO DEBE ESTAR AQUÍ.
?>