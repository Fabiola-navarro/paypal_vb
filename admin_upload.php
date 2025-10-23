<?php
// admin_upload.php

// Incluir la configuración de la base de datos
include 'db_config.php'; // Asegúrate de que esta conexión esté abierta con $conn

// ===============================================
// SOLUCIÓN DE RUTA: DEFINICIÓN DE RUTAS ABSOLUTAS Y RELATIVAS
// ===============================================

// Ruta del directorio donde está ESTE archivo (ej: C:\wamp64\www\VB_VintageBeats\admin)
$script_path = dirname(__FILE__); 

// Directorios RELATIVOS para la URL/Base de Datos
$relative_dir_img = "generos/img/"; 
$relative_dir_audio = "generos/music/";

// Rutas ABSOLUTAS para el servidor (usadas por move_uploaded_file)
// Asegúrate de que $script_path te lleva a la raíz. Si admin_upload.php está en la raíz, usa $script_path.
// Si está en una subcarpeta (ej: admin/admin_upload.php), debes usar $script_path . '/../'
// ASUMIMOS que 'generos' está al mismo nivel que este script o en la raíz.

// Ejemplo ABSOLUTA (Asumiendo que 'generos' está al mismo nivel que este script):
$target_dir_img = $script_path . "/" . $relative_dir_img; 
$target_dir_audio = $script_path . "/" . $relative_dir_audio;


$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    /**
     * Sube un archivo al servidor y devuelve la ruta relativa para la base de datos.
     * @param string $file_key La clave del archivo en $_FILES.
     * @param string $target_dir_absolute La ruta ABSOLUTA del directorio de destino (servidor).
     * @param string $target_dir_relative La ruta RELATIVA del directorio de destino (BD/URL).
     * @param bool $required Si el archivo es obligatorio.
     * @return string|bool|null La ruta relativa, false en caso de error, o null si no es requerido.
     */
    function uploadFile($file_key, $target_dir_absolute, $target_dir_relative, $required = true) {
        global $message;
        
        // Manejo de errores de subida
        if ($required && (!isset($_FILES[$file_key]) || $_FILES[$file_key]['error'] != UPLOAD_ERR_OK)) {
            // Maneja el caso de que no se suba ningún archivo requerido
            if (!isset($_FILES[$file_key]) || $_FILES[$file_key]['error'] == UPLOAD_ERR_NO_FILE) {
                $message .= "Error: El archivo de " . ($file_key == 'imagen_url' ? 'imagen' : 'audio') . " es obligatorio.<br>";
            } else {
                $message .= "Error al subir el archivo de " . $file_key . " (Código: " . ($_FILES[$file_key]['error'] ?? 'N/A') . ").<br>";
            }
            return false;
        }

        if (!$required && (!isset($_FILES[$file_key]) || $_FILES[$file_key]['error'] == UPLOAD_ERR_NO_FILE)) {
            return null; // No requerido y no subido, devuelve nulo
        }
        
        // 1. Sanitizar el nombre de archivo (MEJORADO)
        $original_name = basename($_FILES[$file_key]["name"]);
        $extension = pathinfo($original_name, PATHINFO_EXTENSION);
        // Limpia el nombre base (solo deja letras, números, guiones y puntos)
        $cleaned_name = preg_replace('/[^a-zA-Z0-9\.\-_]/', '', pathinfo($original_name, PATHINFO_FILENAME));
        
        // Generar nombre único: [hash]-[nombre_limpio].[ext]
        $filename = uniqid() . '-' . strtolower($cleaned_name) . '.' . $extension;
        
        // 2. CONSTRUCCIÓN DE RUTAS
        // Ruta ABSOLUTA para el servidor (necesaria para move_uploaded_file)
        $target_file_full_path = $target_dir_absolute . $filename; 
        
        // 🔑 RUTA RELATIVA para guardar en la BD (ej: generos/img/nombre.png)
        $target_file_relative = $target_dir_relative . $filename;

        $fileType = strtolower($extension);
        $uploadOk = 1;
        
        // 3. Comprobación de tipo de archivo
        if ($file_key == 'imagen_url' && !in_array($fileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            $message .= "Error: Solo se permiten JPG, JPEG, PNG, GIF para la imagen.<br>";
            $uploadOk = 0;
        } elseif ($file_key == 'audio_url' && $fileType !== 'mp3') {
            $message .= "Error: Solo se permiten archivos MP3 para el audio.<br>";
            $uploadOk = 0;
        }

        if ($uploadOk == 0) {
            return false;
        } else {
            // 4. VERIFICAR Y CREAR DIRECTORIO ABSOLUTO si no existe
            if (!is_dir($target_dir_absolute)) {
                if (!mkdir($target_dir_absolute, 0777, true)) {
                    $message .= "Error: No se pudo crear el directorio '{$target_dir_absolute}'. Revise permisos del servidor.<br>";
                    return false;
                }
            }
            
            // 5. Mover el archivo usando la RUTA ABSOLUTA
            if (move_uploaded_file($_FILES[$file_key]["tmp_name"], $target_file_full_path)) {
                return $target_file_relative; // Devuelve la RUTA RELATIVA para la BD y la URL
            } else {
                $message .= "Error al mover el archivo de " . $file_key . ". Revise permisos de escritura en la carpeta: '{$target_dir_absolute}'.<br>";
                return false;
            }
        }
    }

    // Recoger y sanitizar datos del formulario (omito la validación de POST para no repetirla)
    $nombre = trim($_POST['nombre'] ?? '');
    $artista_marca = trim($_POST['artista_marca'] ?? '');
    $tipo = $_POST['tipo'] ?? '';
    $precio = filter_var($_POST['precio'], FILTER_VALIDATE_FLOAT);
    $stock = filter_var($_POST['stock'], FILTER_VALIDATE_INT);
    
    // Validar campos obligatorios
    if (!$nombre || !$artista_marca || !$tipo || $precio === false || $stock === false) {
        $message .= "Error: Faltan campos obligatorios o los formatos son incorrectos.<br>";
    } else {
        
        $genero = null;
        $audio_path = null;
        $all_uploads_ok = true; 
        $is_music = ($tipo === 'vinilo' || $tipo === 'cd');
        
        // Lógica condicional para Música (vinilo/cd)
        if ($is_music) {
            $genero = $_POST['genero'] ?? '';
            if (!$genero) {
                $message .= "Error: El género es obligatorio para vinilos y CDs.<br>";
            }
            
            // Subir audio (obligatorio para música)
            $audio_upload = uploadFile('audio_url', $target_dir_audio, $relative_dir_audio, true);
            if ($audio_upload === false) {
                $all_uploads_ok = false; 
            } else {
                $audio_path = $audio_upload;
            }

        } else if ($tipo === 'reproductor') {
            // Reproductores: género y audio son NULL
            $genero = null;
            $audio_path = null;
        }

        // Subir Imagen (obligatorio para todos)
        $imagen_path = null;
        $image_upload = uploadFile('imagen_url', $target_dir_img, $relative_dir_img, true);
        if ($image_upload === false) {
            $all_uploads_ok = false; 
        } else {
            // ESTA ES LA RUTA RELATIVA QUE RESUELVE TU PROBLEMA (ej: generos/img/...)
            $imagen_path = $image_upload; 
        }

        // 4. Inserción en la base de datos
        if (empty($message) && $all_uploads_ok) {
            
            // Uso de Prepared Statement (seguridad)
            $sql = "INSERT INTO productos (nombre, artista_marca, tipo, genero, precio, stock, imagen_url, audio_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            
            $stmt->bind_param("ssssdiss", 
                $nombre, 
                $artista_marca, 
                $tipo, 
                $genero, 
                $precio, 
                $stock, 
                $imagen_path, // RUTA RELATIVA EN LA BASE DE DATOS
                $audio_path   // RUTA RELATIVA EN LA BASE DE DATOS
            );
            
            if ($stmt->execute()) {
                $message = "<p class='success'>¡Producto '{$nombre}' añadido con éxito!</p>";
                $_POST = array(); // Limpiar formulario
            } else {
                $message = "<p class='error'>Error al insertar en la base de datos: " . $stmt->error . "</p>";
            }
            $stmt->close();
        }
    }
}
$conn->close(); // Cerrar la conexión al finalizar
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrador de Productos | Vintage Beats</title>
    <link rel="stylesheet" href="styles.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        /* Estilos de administración */
        body { background-color: #1a1a2e; color: #fff; font-family: sans-serif; }
        .admin-container { 
            max-width: 600px; 
            margin: 50px auto; 
            padding: 30px; 
            background: #0f0f1b; 
            border-radius: 8px; 
            box-shadow: 0 0 20px rgba(0, 255, 255, 0.5); 
        }
        .admin-container h1 { 
            color: #00ffcc; 
            text-align: center; 
            margin-bottom: 30px; 
            border-bottom: 2px solid #00ffcc; 
            padding-bottom: 10px; 
        }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #00ffcc; }
        .form-group input[type="text"], 
        .form-group input[type="number"], 
        .form-group input[type="file"], 
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #00ffcc;
            border-radius: 4px;
            background: #23234d;
            color: #fff;
            box-sizing: border-box;
        }
        .btn-submit {
            display: block;
            width: 100%;
            padding: 15px;
            background-color: #00ffcc;
            color: #1a1a2e;
            border: none;
            border-radius: 4px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-submit:hover { background-color: #00e6b8; }
        .success { color: #00ffcc; font-weight: bold; text-align: center; margin-bottom: 20px; padding: 10px; border: 1px solid #00ffcc; }
        .error { color: #ff6347; font-weight: bold; text-align: center; margin-bottom: 20px; padding: 10px; border: 1px solid #ff6347; }
    </style>
</head>
<body>

    <div class="admin-container">
        <h1>Administración de Productos</h1>

        <?php echo $message; // Mostrar mensajes de éxito o error ?>

        <form method="POST" enctype="multipart/form-data">
            
            <div class="form-group">
                <label for="tipo">Tipo de Producto:</label>
                <select name="tipo" id="tipo" required onchange="toggleFields()">
                    <option value="">-- Seleccione Tipo --</option>
                    <option value="vinilo" <?php echo (($_POST['tipo'] ?? '') == 'vinilo' ? 'selected' : ''); ?>>Vinilo</option>
                    <option value="cd" <?php echo (($_POST['tipo'] ?? '') == 'cd' ? 'selected' : ''); ?>>CD</option>
                    <option value="reproductor" <?php echo (($_POST['tipo'] ?? '') == 'reproductor' ? 'selected' : ''); ?>>Reproductor</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="nombre" id="label-nombre">Nombre (Álbum / Reproductor):</label>
                <input type="text" name="nombre" id="nombre" required value="<?php echo $_POST['nombre'] ?? ''; ?>">
            </div>

            <div class="form-group">
                <label for="artista_marca" id="label-artist-brand">Artista / Marca:</label>
                <input type="text" name="artista_marca" id="artista_marca" required value="<?php echo $_POST['artista_marca'] ?? ''; ?>">
            </div>

            <div id="music-fields" style="display:none;">
                <div class="form-group">
                    <label for="genero">Género:</label>
                    <select name="genero" id="genero">
                        <option value="">-- Seleccione Género --</option>
                        <option value="pop" <?php echo (($_POST['genero'] ?? '') == 'pop' ? 'selected' : ''); ?>>Pop</option>
                        <option value="rock" <?php echo (($_POST['genero'] ?? '') == 'rock' ? 'selected' : ''); ?>>Rock</option>
                        <option value="electronica" <?php echo (($_POST['genero'] ?? '') == 'electronica' ? 'selected' : ''); ?>>Electrónica</option>
                        <option value="latina" <?php echo (($_POST['genero'] ?? '') == 'latina' ? 'selected' : ''); ?>>Latina</option>
                        <option value="kpop" <?php echo (($_POST['genero'] ?? '') == 'kpop' ? 'selected' : ''); ?>>K-Pop</option>
                        <option value="rap" <?php echo (($_POST['genero'] ?? '') == 'rap' ? 'selected' : ''); ?>>Rap</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="audio_url">Audio MP3 (Muestra):</label>
                    <input type="file" name="audio_url" id="audio_url" accept=".mp3">
                </div>
            </div>

            <div class="form-group">
                <label for="precio">Precio:</label>
                <input type="number" name="precio" id="precio" step="0.01" min="0.01" required value="<?php echo $_POST['precio'] ?? ''; ?>">
            </div>

            <div class="form-group">
                <label for="stock">Stock:</label>
                <input type="number" name="stock" id="stock" min="0" required value="<?php echo $_POST['stock'] ?? ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="imagen_url">Imagen del Producto (JPG, PNG, GIF):</label>
                <input type="file" name="imagen_url" id="imagen_url" accept=".jpg, .jpeg, .png, .gif" required>
            </div>

            <button type="submit" class="btn-submit"><i class="fa fa-upload"></i> Subir Producto</button>
        </form>
    </div>

    <script>
        function toggleFields() {
            const tipo = document.getElementById('tipo').value;
            const musicFields = document.getElementById('music-fields');
            const audioInput = document.getElementById('audio_url');
            const generoSelect = document.getElementById('genero');
            const artistBrandLabel = document.getElementById('label-artist-brand');
            const nombreLabel = document.getElementById('label-nombre');

            const isMusic = (tipo === 'vinilo' || tipo === 'cd');
            
            if (isMusic) {
                musicFields.style.display = 'block';
                audioInput.required = true;
                generoSelect.required = true;
                artistBrandLabel.textContent = 'Artista:';
                nombreLabel.textContent = 'Nombre del Álbum:';
            } else if (tipo === 'reproductor') {
                musicFields.style.display = 'none';
                audioInput.required = false;
                generoSelect.required = false;
                artistBrandLabel.textContent = 'Marca:';
                nombreLabel.textContent = 'Nombre del Reproductor:';
                generoSelect.value = ''; 
            } else {
                musicFields.style.display = 'none';
                audioInput.required = false;
                generoSelect.required = false;
                artistBrandLabel.textContent = 'Artista / Marca:';
                nombreLabel.textContent = 'Nombre (Álbum / Reproductor):';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
             // Ejecutar toggleFields si hay un valor preseleccionado del POST
             if (document.getElementById('tipo').value !== "") {
                 toggleFields();
             } else {
                 toggleFields();
             }
        });
    </script>
</body>
</html>