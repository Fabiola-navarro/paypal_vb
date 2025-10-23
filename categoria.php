<?php
// categoria.php

// Incluir el archivo de configuración y conexión
// ⚠️ IMPORTANTE: Asegúrate de que 'db_config.php' contenga la conexión a MySQLi ($conn)
include 'db_config.php'; 

/**
 * Obtiene los productos disponibles (stock > 0) de la base de datos.
 * @param mysqli $conn La conexión a la base de datos.
 * @return array Un array de productos o un array vacío en caso de error.
 */
function getProducts($conn) {
    // Consulta para obtener todos los campos necesarios solo si hay stock
    $sql = "SELECT id, nombre, artista_marca, tipo, genero, precio, stock, imagen_url, audio_url FROM productos WHERE stock > 0 ORDER BY id DESC";
    
    // Usamos $conn->query() si $conn es un objeto mysqli
    if (!$result = $conn->query($sql)) {
        // En un entorno de desarrollo, puedes usar: die("Error en la consulta: " . $conn->error);
        return [];
    }
    
    $products = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    return $products;
}

// Obtener los productos disponibles
$products = getProducts($conn);

// Cierre la conexión a la base de datos (Buena práctica)
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos | Vintage Beats</title>
    
    <link rel="stylesheet" href="styles.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400..900&family=Press+Start+2P&display=swap" rel="stylesheet">
    
    <style>
        /* ================================================= */
        /* ESTILOS ESPECÍFICOS PARA FILTROS Y TARJETAS NEÓN */
        /* (Tus estilos se mantienen intactos aquí) */
        /* ================================================= */
        .dropdown-filter { position: relative; display: inline-block; }
        .dropdown-menu { 
            display: none; position: absolute; z-index: 10; 
            background-color: #1a0329; border: 1px solid #ff00c8; 
            min-width: 160px; box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.7);
            padding: 8px 0; margin-top: 5px; border-radius: 4px;
        }
        .dropdown-menu.show { display: block; }
        .dropdown-menu .filter-btn {
            display: block; width: 100%; text-align: left; padding: 10px 15px; 
            color: #fff; background: none; border: none; cursor: pointer;
        }
        .dropdown-menu .filter-btn:hover, .dropdown-menu .filter-btn.active {
            background-color: #ff00c8; color: #1a0329;
        }
        
        .filter-buttons .filter-btn {
            background-color: #3d035c; color: #fff; border: 1px solid #ff00c8;
            padding: 10px 15px; margin-right: 10px; border-radius: 4px;
            cursor: pointer; transition: all 0.2s;
        }
        .filter-buttons .filter-btn.active:not(.dropdown-toggle) {
            background-color: #ff00c8; color: #1a0329; font-weight: bold;
            box-shadow: 0 0 8px #ff00c8;
        }
        .filter-buttons .filter-btn.dropdown-toggle.active {
            background-color: #ff00c8; color: #1a0329;
            box-shadow: 0 0 8px #ff00c8;
        }

        /* Estilos para el overlay de Play/Pause */
        .product-image-container { position: relative; cursor: pointer; }
        .play-icon-overlay {
            position: absolute; top: 50%; left: 50%; 
            transform: translate(-50%, -50%); color: #ff00c8; 
            font-size: 3em; opacity: 0; transition: opacity 0.3s;
            background: rgba(0, 0, 0, 0.6); border-radius: 50%; padding: 10px;
            pointer-events: none; 
        }
        .product-image-container:hover .play-icon-overlay { opacity: 1; }

        /* Estilos base de Synthwave */
        body { background-color: #1a0329; color: #fff; margin: 0; padding: 0; }
        .product-grid { display: flex; flex-wrap: wrap; gap: 30px; justify-content: center; padding: 40px 0; }
        .product-card {
            width: 300px; padding: 20px; background-color: #11001a; text-align: center;
            border: 2px solid #a800ff; /* Morado neón */
            box-shadow: 0 0 10px #a800ff, 0 0 20px #a800ff50, inset 0 0 10px #a800ff;
            transition: transform 0.3s, box-shadow 0.3s;
            display: block;
        }
        .product-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 0 15px #ff00c8, 0 0 30px #ff00c850; border-color: #ff00c8;
        }
        .product-image-container { height: 250px; display: flex; align-items: center; justify-content: center; margin-bottom: 15px; }
        .product-card-img { max-width: 100%; max-height: 100%; display: block; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3); }
        .product-title { font-size: 1.3em; color: #e0ccff; margin: 5px 0 2px 0; text-transform: uppercase; }
        .product-artist { font-size: 1.1em; color: #ff00c8; margin: 0 0 10px 0; }
        .product-price { font-size: 1.6em; color: #ff00c8; margin: 15px 0 20px 0; }
        .add-to-cart-btn {
            width: 100%; padding: 12px; background-color: #ff00c8; color: #fff;
            border: none; border-radius: 4px; font-size: 1.1em; font-weight: bold; 
            text-transform: uppercase; cursor: pointer;
            box-shadow: 0 0 5px #ff00c8, 0 0 10px #ff00c8, inset 0 0 5px #ff00c8;
            transition: background-color 0.3s, box-shadow 0.3s;
        }
        .add-to-cart-btn:hover { background-color: #e000b0; box-shadow: 0 0 10px #ff00c8, 0 0 20px #ff00c8; }
        .add-to-cart-btn.disabled {
            background-color: #444; cursor: not-allowed; 
            box-shadow: none; border: 1px dashed #777;
        }
        .cart-count {
            position: absolute; top: -5px; right: -5px;
            background-color: #ff00c8; color: #1a0329;
            border-radius: 50%; padding: 2px 6px;
            font-size: 0.8em; font-weight: bold;
            display: none; /* Se muestra con JS si count > 0 */
        }
        .cart-icon {
            position: relative;
        }
    </style>
</head>
<body>

    <header class="main-header">
        <div class="banner-top">
            <img src="img/banner.png" alt="Banner superior" class="header-banner glowing-banner">
        </div>

        <nav class="main-nav">
            <div class="container">
                <div class="brand-area">
                    <a href="index.html" class="logo-link">
                        <img src="img/logo.png" alt="Logo" class="header-logo-circle"> 
                        <span class="brand-name">Vintage Beats</span>
                    </a>
                </div>
                <ul class="nav-links">
                    <li><a href="index.html">INICIO</a></li>
                    <li><a href="categoria.php">PRODUCTOS</a></li> 
                    <li><a href="#galeria">GALERÍA</a></li>
                    <li><a href="#sentibot">SENTIBOT</a></li>
                    <li><a href="#novedades">NOVEDADES</a></li>
                    <li><a href="#contacto">CONTACTO</a></li>
                </ul>
                <div class="icon-group">
                    <a href="user_profile.html" class="user-icon"><i class="fa fa-user"></i></a>
                    <a href="carrito.html" class="cart-icon">
                        <i class="fa fa-shopping-cart"></i>
                        <span id="cart-count" class="cart-count">0</span>
                    </a>
                </div>
            </div>
        </nav>
    </header>

    <div class="filter-bar-wrapper">
        <div class="container">
            <div class="filter-buttons">
                <button class="filter-btn active" data-filter-type="category" data-filter-value="all">Todos los Productos</button>
                <button class="filter-btn" data-filter-type="category" data-filter-value="vinilo">Vinilos</button>
                <button class="filter-btn" data-filter-type="category" data-filter-value="cd">CD's</button>
                <button class="filter-btn" data-filter-type="category" data-filter-value="reproductor">Reproductores</button>
                
                <div class="dropdown-filter">
                    <button class="filter-btn dropdown-toggle" id="genre-dropdown-btn">
                        Género <i class="fa fa-caret-down"></i>
                    </button>
                    
                    <div class="dropdown-menu" id="genre-dropdown-menu">
                        <button class="filter-btn" data-filter-type="genre" data-filter-value="pop">Pop</button>
                        <button class="filter-btn" data-filter-type="genre" data-filter-value="rock">Rock</button>
                        <button class="filter-btn" data-filter-type="genre" data-filter-value="electronica">Electrónica</button>
                        <button class="filter-btn" data-filter-type="genre" data-filter-value="latina">Latina</button>
                        <button class="filter-btn" data-filter-type="genre" data-filter-value="kpop">K-Pop</button>
                        <button class="filter-btn" data-filter-type="genre" data-filter-value="rap">Rap</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

<main>
    <section class="section-padding" id="productos">
        <div class="container">
            
            <div id="product-list-container" class="product-grid"> 
                <?php
                if (!empty($products)) {
                    // Bucle para imprimir cada producto obtenido de la base de datos
                    foreach ($products as $product) {
                        
                        $is_music = ($product['tipo'] === 'vinilo' || $product['tipo'] === 'cd');
                        
                        // 1. Atributos de Filtro y datos del producto
                        $category_attr = htmlspecialchars($product['tipo']);
                        $genre_attr = !empty($product['genero']) ? htmlspecialchars($product['genero']) : 'none'; 
                        $in_stock = $product['stock'] > 0;

                        // 2. Información del Producto para JS (CRÍTICO: Usar json_encode)
                        // Preparamos los datos del producto
                        $product_data_array = [
                            'id' => htmlspecialchars($product['id']),
                            'name' => htmlspecialchars($product['nombre']),
                            'price' => (float)$product['precio'], // Precio como flotante
                            'image' => htmlspecialchars($product['imagen_url']),
                            'artist' => htmlspecialchars($product['artista_marca']),
                            'type' => htmlspecialchars($product['tipo']),
                        ];
                        
                        // ✅ CLAVE: Escapar el JSON para que sea seguro dentro de la comilla simple del onclick
                        $product_json_data = json_encode($product_data_array, JSON_HEX_QUOT | JSON_HEX_TAG);

                        $price_formatted = number_format($product['precio'], 2, '.', ',');
                        $image = htmlspecialchars($product['imagen_url']);

                        // 3. Lógica para Audio y Botón "Añadir"
                        $audio_tag = '';
                        $audio_trigger_class = '';
                        $audio_data_id = '';
                        $button_content = $in_stock ? "<i class='fa fa-shopping-cart'></i> Añadir" : "<i class='fa fa-ban'></i> Agotado";
                        $button_class = $in_stock ? 'add-to-cart-btn' : 'add-to-cart-btn disabled';
                        $button_disabled = $in_stock ? '' : 'disabled';
                        
                        // ✅ CORRECCIÓN FINAL: Pasar el objeto JSON escapado
                        $buy_args = $in_stock ? "onclick='addToCart({$product_json_data})'" : '';

                        if ($is_music && !empty($product['audio_url'])) {
                            $audio_id = "audio-{$product['id']}";
                            $audio_trigger_class = 'audio-trigger';
                            $audio_data_id = "data-audio-id='{$audio_id}'";
                            
                            $audio_tag = "<audio id='{$audio_id}' src='{$product['audio_url']}' preload='auto'></audio>";
                        }

                        // 4. IMPRIMIR LA TARJETA DEL PRODUCTO
                        echo "
                        <div class='product-card' data-category='{$category_attr}' data-genre='{$genre_attr}' data-stock='{$product['stock']}'>
                            <div class='product-image-container'>
                                <img src='{$image}' alt='Portada de " . htmlspecialchars($product['nombre']) . "' class='product-card-img {$audio_trigger_class}' {$audio_data_id}>
                                <div class='play-icon-overlay' id='overlay-{$product['id']}'>
                                    <i class='fa fa-play'></i>
                                </div>
                            </div>
                            <div class='product-info'>
                                <h3 class='product-title'>" . htmlspecialchars($product['nombre']) . "</h3>
                                <p class='product-artist'>" . htmlspecialchars($product['artista_marca']) . "</p>
                                <p class='product-price'>$ {$price_formatted}</p>
                                <button class='{$button_class}' {$buy_args} {$button_disabled}>
                                    {$button_content}
                                </button>
                            </div>
                            {$audio_tag}
                        </div>
                        ";
                    }
                } else {
                    echo "<p class='no-products-msg'>Lo sentimos, no hay productos disponibles en este momento.</p>";
                }
                ?>
            </div>
            
        </div>
    </section>
    
    <footer>
        <div class="footer-bottom">
            &copy; 2025 V-Beats. Todos los derechos reservados.
        </div>
    </footer>
</main>
    
<script>
    // ===============================================
    // 🔑 1. LÓGICA DEL CARRITO DE COMPRAS (Funcional)
    // ===============================================
    
    // ⚠️ CLAVE: La clave de almacenamiento debe ser la misma que usaste en carrito.html
    const CART_STORAGE_KEY = 'vintageBeatsCart';

    function getCartItems() {
        const cart = sessionStorage.getItem(CART_STORAGE_KEY);
        return cart ? JSON.parse(cart) : [];
    }

    function saveCartItems(items) {
        sessionStorage.setItem(CART_STORAGE_KEY, JSON.stringify(items));
        updateCartCount(); 
    }

    function updateCartCount() {
        const cartItems = getCartItems();
        // Suma la cantidad de todos los productos
        const count = cartItems.reduce((sum, item) => sum + item.quantity, 0);
        const cartCountElement = document.getElementById('cart-count');
        if (cartCountElement) {
            cartCountElement.textContent = count;
            // Muestra el contador solo si hay ítems
            cartCountElement.style.display = count > 0 ? 'inline-block' : 'none';
        }
    }

    /**
     * Añade un producto al carrito. Se llama desde el atributo onclick del botón.
     * @param {object} productData Objeto que contiene id, name, price, image, artist, type.
     */
    function addToCart(productData) {
        const cartItems = getCartItems();
        // CRÍTICO: Buscar ítem por ID. Usamos String() para asegurar la comparación.
        const existingItem = cartItems.find(item => String(item.id) === String(productData.id));
        
        const { id, name, price, image, artist, type } = productData;

        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            // Se añade el producto completo
            cartItems.push({
                id: id,
                name: name,
                price: price, 
                image: image,
                artist: artist,
                type: type,
                quantity: 1
            });
        }

        saveCartItems(cartItems);
        alert(`"${name}" añadido al carrito!`);
    }


    // ===============================================
    // 🔑 2. LÓGICA DE AUDIO (Mantenida de tu código)
    // ===============================================
    
    let currentlyPlayingAudio = null; 

    function updateOverlay(audioElement, isPlaying) {
        const productId = audioElement.id.replace('audio-', '');
        const overlay = document.getElementById(`overlay-${productId}`);
        
        if (overlay) {
            const icon = overlay.querySelector('i');
            icon.className = isPlaying ? 'fa fa-pause' : 'fa fa-play';
        }
    }

    function toggleAudio(audioElement) {
        
        if (currentlyPlayingAudio && currentlyPlayingAudio !== audioElement) {
            currentlyPlayingAudio.pause(); 
            currentlyPlayingAudio.currentTime = 0; 
            updateOverlay(currentlyPlayingAudio, false); 
        }

        if (audioElement.paused) {
            audioElement.play().catch(error => {
                console.error("Error al intentar reproducir el audio:", error);
                updateOverlay(audioElement, false); 
            });
            currentlyPlayingAudio = audioElement; 
            updateOverlay(audioElement, true);
        } else {
            audioElement.pause(); 
            audioElement.currentTime = 0; 
            currentlyPlayingAudio = null; 
            updateOverlay(audioElement, false);
        }
    }

    // ===============================================
    // 🔑 3. LÓGICA DE FILTRADO Y MEZCLA (Mantenida de tu código)
    // ===============================================
    
    let currentCategory = 'all'; 
    let currentGenre = 'all'; 

    // Se inicializa en DOMContentLoaded
    let productCards = []; 

    function updateProductDisplay() {
        if (productCards.length === 0) {
             productCards = document.querySelectorAll('#product-list-container .product-card');
        }
        
        let foundProducts = false;
        productCards.forEach(card => {
            const cardCategory = card.getAttribute('data-category');
            const cardGenre = card.getAttribute('data-genre') || 'all'; 
            
            let matchesCategory = (currentCategory === 'all' || currentCategory === cardCategory);
            let matchesGenre = (currentGenre === 'all' || currentGenre === cardGenre); 
            
            // Si se selecciona Reproductor, el filtro de Género se ignora.
            if (currentCategory === 'reproductor' && currentGenre !== 'all') {
                matchesGenre = false; 
            }
            
            const shouldDisplay = matchesCategory && matchesGenre;
            
            card.style.display = shouldDisplay ? 'block' : 'none';
            if (shouldDisplay) foundProducts = true;
        });
    }

    function updateActiveClasses() {
        // Limpiar clases activas en todos los botones
        document.querySelectorAll('.filter-buttons .filter-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        // Activar el botón de categoría actual
        const activeCategoryBtn = document.querySelector(`.filter-buttons .filter-btn[data-filter-type="category"][data-filter-value="${currentCategory}"]`);
        if (activeCategoryBtn) activeCategoryBtn.classList.add('active');

        // Activar el botón desplegable y el género si está activo
        const dropdownToggle = document.getElementById('genre-dropdown-btn');
        if (currentGenre !== 'all') {
            dropdownToggle.classList.add('active');
            const genreBtn = document.querySelector(`.dropdown-menu .filter-btn[data-filter-type="genre"][data-filter-value="${currentGenre}"]`);
            if (genreBtn) genreBtn.classList.add('active');
        } else {
            dropdownToggle.classList.remove('active');
        }
    }
    
    function shuffleArray(container) {
        let cards = Array.from(container.children);
        for (let i = cards.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [cards[i], cards[j]] = [cards[j], cards[i]];
        }
        container.innerHTML = ''; 
        cards.forEach(card => container.appendChild(card));
    }


    // ------------------- INICIALIZACIÓN Y LISTENERS -------------------
    document.addEventListener('DOMContentLoaded', () => {
        const productContainer = document.getElementById('product-list-container');
        shuffleArray(productContainer); 

        updateCartCount(); 
        updateActiveClasses(); // Inicializar clases

        const filterButtons = document.querySelectorAll('.filter-buttons .filter-btn');
        const dropdownToggle = document.getElementById('genre-dropdown-btn');
        const dropdownMenu = document.getElementById('genre-dropdown-menu');
        
        // Manejo del Dropdown
        dropdownToggle.addEventListener('click', (e) => {
            e.stopPropagation(); 
            dropdownMenu.classList.toggle('show');
        });
        window.addEventListener('click', (event) => {
            if (!dropdownToggle.contains(event.target) && !dropdownMenu.contains(event.target)) {
                dropdownMenu.classList.remove('show');
            }
        });

        // Lógica de Manejo de Clics en Filtros
        filterButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                const filterType = button.getAttribute('data-filter-type');
                const filterValue = button.getAttribute('data-filter-value');

                if (!filterType || !filterValue || button.classList.contains('dropdown-toggle')) return;

                // 1. ACTUALIZAR LOS ESTADOS DE FILTRO
                if (filterType === 'category') {
                    // Alternar la categoría (excepto 'all')
                    currentCategory = (currentCategory === filterValue && filterValue !== 'all') ? 'all' : filterValue;
                    if (currentCategory === 'reproductor' || currentCategory === 'all') {
                        currentGenre = 'all';
                    }
                } else if (filterType === 'genre') { 
                    currentGenre = (currentGenre === filterValue) ? 'all' : filterValue;
                    dropdownMenu.classList.remove('show'); 
                    
                    if (currentGenre !== 'all') {
                        // Si aplicamos un género, forzamos la categoría a 'all' si era 'reproductor'
                        if (currentCategory === 'reproductor') {
                            currentCategory = 'all';
                        }
                    }
                }

                // 2. ACTUALIZAR CLASES Y PANTALLA
                updateActiveClasses();
                updateProductDisplay();
            });
        });
        
        // Lógica de Audio (Hacemos clic en el contenedor de la imagen)
        document.querySelectorAll('.product-image-container').forEach(container => {
            const img = container.querySelector('.product-card-img.audio-trigger');
            if (img) {
                container.addEventListener('click', () => {
                    const audioId = img.getAttribute('data-audio-id');
                    const audioElement = document.getElementById(audioId);
                    if (audioElement) {
                        toggleAudio(audioElement);
                    }
                });
            }
        });
        
        // Resetear la reproducción al finalizar
        document.querySelectorAll('audio').forEach(audio => {
            audio.addEventListener('ended', () => {
                audio.currentTime = 0;
                updateOverlay(audio, false);
                if (currentlyPlayingAudio === audio) {
                    currentlyPlayingAudio = null;
                }
            });
        });

        // Inicializar la vista
        updateProductDisplay();
    });
</script>
</body>
</html>