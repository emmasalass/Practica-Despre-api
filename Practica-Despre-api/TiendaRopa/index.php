<?php
session_start();

// 1. Importamos correctamente los archivos de configuración y modelos
require_once 'config/Conexion.php';
require_once 'models/ProductoModel.php';

$mensajeRegistro = "";
$mensajeLogin = "";

// 2. Para no alterar el "protected $db" de tu clase Conexion, creamos la instancia local usando tus parámetros con el puerto 3305
try {
    $dbConn = new PDO("mysql:host=localhost;port=3306;dbname=tienda_ropa;charset=utf8mb4", 'root', '');
    $dbConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión en index: " . $e->getMessage());
}

// 3. PROCESAR INICIO DE SESIÓN (LOGIN)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_cliente'])) {
    $email = trim($_POST['log_email']);
    $password = trim($_POST['log_password']);

    try {
        $sqlLog = "SELECT * FROM clientes WHERE email = :email";
        $stmtLog = $dbConn->prepare($sqlLog);
        $stmtLog->execute(['email' => $email]);
        $usuario = $stmtLog->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            // Comprobación doble: Verifica hashes seguros Y contraseñas de prueba en texto plano (como '12345')
            if (password_verify($password, $usuario['password']) || $password === $usuario['password']) {
                
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nombre'] = $usuario['nombre'];
                $_SESSION['usuario_email'] = $usuario['email'];
                
                // Redirección limpia para actualizar la barra de navegación y quitar los datos del POST
                header("Location: index.php");
                exit();
            } else {
                $mensajeLogin = "<p style='color: red; text-align:center; font-weight:bold; margin-bottom:15px;'>Contraseña incorrecta.</p>";
            }
        } else {
            $mensajeLogin = "<p style='color: red; text-align:center; font-weight:bold; margin-bottom:15px;'>El correo electrónico no está registrado.</p>";
        }
    } catch (PDOException $e) {
        $mensajeLogin = "<p style='color: red; text-align:center; font-weight:bold; margin-bottom:15px;'>Error: " . $e->getMessage() . "</p>";
    }
}

// 4. PROCESAR REGISTRO DE CLIENTE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registro_cliente'])) {
    $nombre = trim($_POST['reg_nombre']);
    $email = trim($_POST['reg_email']);
    $password = password_hash($_POST['reg_password'], PASSWORD_BCRYPT); 
    $telefono = trim($_POST['reg_telefono']);

    try {
        $sqlReg = "INSERT INTO clientes (nombre, email, password, telefono) VALUES (:nom, :email, :pass, :tel)";
        $stmtReg = $dbConn->prepare($sqlReg);
        $stmtReg->execute([
            'nom' => $nombre,
            'email' => $email,
            'pass' => $password,
            'tel' => $telefono
        ]);
        $mensajeRegistro = "<p style='color: green; text-align:center; font-weight:bold; margin-bottom:15px;'>¡Cliente registrado con éxito! Ya puedes iniciar sesión.</p>";
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { 
            $mensajeRegistro = "<p style='color: red; text-align:center; font-weight:bold; margin-bottom:15px;'>El correo ya está registrado.</p>";
        } else {
            $mensajeRegistro = "<p style='color: red; text-align:center; font-weight:bold; margin-bottom:15px;'>Error: " . $e->getMessage() . "</p>";
        }
    }
}

// 5. PROCESAR CIERRE DE SESIÓN (LOGOUT)
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// 6. CARGAR PRODUCTOS DESDE EL MODELO CORRECTO
$productoModel = new ProductoModel();
$productos = $productoModel->listarProductos();

$totalItemsCarrito = 0;
if (isset($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
        $totalItemsCarrito += $item['cantidad'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moda Urbana | Tienda de Ropa</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        header { background-color: #8b0b0b; color: #fff; padding: 20px 0; position: sticky; top: 0; z-index: 100; }
        .nav-container { width: 90%; max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 24px; font-weight: bold; letter-spacing: 2px; text-decoration: none; color: white;}
        nav { display: flex; align-items: center; }
        nav a, nav span { color: #fff; text-decoration: none; margin-left: 20px; transition: color 0.3s; }
        nav a:hover { color: #ff5a5f; }
        .user-welcome { font-weight: bold; color: #ffeb3b; }

        .hero { background-color: #c5c1c1; height: 350px; display: flex; align-items: center; justify-content: center; text-align: center; flex-direction: column; padding: 0 20px; }
        .hero h1 { font-size: 44px; margin-bottom: 10px; text-transform: uppercase; }
        .hero p { font-size: 18px; margin-bottom: 20px; color: #555; }
        .btn-shop { background-color: #111; color: #fff; padding: 12px 30px; text-decoration: none; font-weight: bold; text-transform: uppercase; transition: 0.3s; }

        .products-section { width: 90%; max-width: 1200px; margin: 50px auto; }
        .section-title { text-align: center; margin-bottom: 40px; font-size: 32px; }
        .product-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; }
        .product-card { background-color: #b8efb6d8; border: 1px solid #b8efb6d8; padding: 15px; text-align: center; border-radius: 5px; }
        .product-image { width: 100%; height: 300px; object-fit: cover; margin-bottom: 15px; border-radius: 3px; }
        .product-title { font-size: 18px; margin-bottom: 10px; }
        .product-price { color: #ff5a5f; font-weight: bold; font-size: 20px; margin-bottom: 15px; }
        .btn-add { display: inline-block; width: 100%; background-color: #111; color: #fff; padding: 10px 0; text-decoration: none; font-weight: bold; border-radius: 3px; border:none; cursor:pointer;}

        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 14px; color: #444; text-align: left; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 15px; outline: none; }
        .btn-submit { background-color: #111; color: #fff; border: none; padding: 12px; font-size: 16px; font-weight: bold; text-transform: uppercase; width: 100%; cursor: pointer; border-radius: 4px; transition: 0.3s; }
        .btn-submit:hover { background-color: #ff5a5f; }

        .cart-sidebar { position: fixed; top: 0; right: 0; width: 380px; height: 100%; background-color: #fff; box-shadow: -5px 0 15px rgba(0,0,0,0.1); z-index: 200; padding: 30px 20px; display: flex; flex-direction: column; transition: transform 0.3s ease; transform: translateX(100%); }
        .cart-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #eee; padding-bottom: 15px; margin-bottom: 20px; }
        .close-cart { font-size: 24px; cursor: pointer; color: #888; }
        .cart-items { flex-grow: 1; overflow-y: auto; }
        .cart-item { display: flex; align-items: center; gap: 12px; margin-bottom: 15px; border-bottom: 1px solid #f5f5f5; padding-bottom: 10px; }
        .cart-item img { width: 65px; height: 65px; object-fit: cover; border-radius: 4px; }
        .cart-item-info { flex-grow: 1; }
        .cart-item-controls { display: flex; align-items: center; gap: 10px; }
        .input-cantidad { width: 55px; padding: 4px; text-align: center; border: 1px solid #ccc; border-radius: 4px; }
        .cart-item-price { color: #ff5a5f; font-weight: bold; }
        .btn-eliminar-item { font-size: 20px; color: #aaa; cursor: pointer; }
        .btn-eliminar-item:hover { color: #d9534f; }
        .cart-footer { border-top: 2px solid #eee; padding-top: 20px; }
        .cart-total { display: flex; justify-content: space-between; font-size: 18px; font-weight: bold; margin-bottom: 15px; }
        .btn-checkout { display: block; text-align: center; background-color: #0e0c0c; color: #fff; padding: 12px; text-decoration: none; font-weight: bold; border-radius: 4px; margin-bottom: 10px; }
        .btn-clear { background-color: #d9534f; color: white; display: block; text-align: center; padding: 10px; text-decoration: none; font-weight: bold; border-radius: 4px; }
    </style>
</head>
<body>

    <header>
        <div class="nav-container">
            <a href="index.php" class="logo">Poli shop online</a>
            <nav>
                <a href="#">Inicio</a>
                <a href="#productos">Productos</a>
                
                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <span class="user-welcome">👤 ¡Hola, <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?>!</span>
                    <a href="index.php?logout=1" style="color: #ffeb3b;">Cerrar Sesión</a>
                <?php else: ?>
                    <a href="#zona-usuarios" onclick="cambiarPestaña('login')">Iniciar Sesión</a>
                    <a href="#zona-usuarios" onclick="cambiarPestaña('registro')">Registrarse</a>
                <?php endif; ?>

                <a href="#" id="open-cart-btn">Carrito (<span id="cart-count"><?php echo $totalItemsCarrito; ?></span>)</a>
            </nav>
        </div>
    </header>

    <section class="hero">
        <h1>Tenemos Esta Promocion Nueva</h1>
        <p>Encuentra tu estilo único con las mejores tendencias de la temporada.</p>
        <a href="#productos" class="btn-shop">Ver Catálogo</a>
    </section>

    <main class="products-section" id="productos">
        <h2 class="section-title">Productos</h2>
        <div class="product-grid">
            <?php if (!empty($productos)): ?>
                <?php foreach ($productos as $prod): ?>
                    <div class="product-card">
                        <img src="<?php echo htmlspecialchars($prod['imagen']); ?>" alt="<?php echo htmlspecialchars($prod['nombre']); ?>" class="product-image">
                        <h3 class="product-title"><?php echo htmlspecialchars($prod['nombre']); ?></h3>
                        <p class="product-price">$<?php echo number_format($prod['precio'], 2); ?></p>
                        <button class="btn-add btn-agregar-carrito" data-id="<?php echo $prod['id']; ?>">Añadir al Carrito</button>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; grid-column: 1/-1;">No hay productos disponibles por el momento.</p>
            <?php endif; ?>
        </div>
    </main>

    <div style="width: 90%; max-width: 600px; margin: 60px auto; background-color: #fff; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); overflow: hidden; border: 1px solid #eee;" id="zona-usuarios">
        
        <div style="display: flex; background-color: #f5f5f5; border-bottom: 1px solid #eee;">
            <button id="tab-login" onclick="cambiarPestaña('login')" style="flex: 1; padding: 15px; border: none; background: none; font-size: 15px; font-weight: bold; cursor: pointer; transition: 0.3s; color: #8b0b0b; border-bottom: 3px solid #8b0b0b;">
                🔑 Iniciar Sesión
            </button>
            <button id="tab-registro" onclick="cambiarPestaña('registro')" style="flex: 1; padding: 15px; border: none; background: none; font-size: 15px; font-weight: bold; cursor: pointer; transition: 0.3s; color: #666; border-bottom: 3px solid transparent;">
                👤 Crear Cuenta
            </button>
            <button id="tab-contacto" onclick="cambiarPestaña('contacto')" style="flex: 1; padding: 15px; border: none; background: none; font-size: 15px; font-weight: bold; cursor: pointer; transition: 0.3s; color: #666; border-bottom: 3px solid transparent;">
                📩 Contacto
            </button>
        </div>

        <div style="padding: 30px;">
            
            <div id="contenido-login">
                <h3 style="margin-bottom: 20px; text-align: center; color: #333;">Ingresa a tu Cuenta</h3>
                <?php echo $mensajeLogin; ?>
                <form action="index.php#zona-usuarios" method="POST">
                    <input type="hidden" name="login_cliente" value="1">
                    <div class="form-group">
                        <label for="log_email">Correo Electrónico</label>
                        <input type="email" id="log_email" name="log_email" placeholder="tu-correo@mail.com" required>
                    </div>
                    <div class="form-group">
                        <label for="log_password">Contraseña</label>
                        <input type="password" id="log_password" name="log_password" placeholder="Tu contraseña" required>
                    </div>
                    <button type="submit" class="btn-submit" style="background-color: #8b0b0b;">Entrar</button>
                </form>
            </div>

            <div id="contenido-registro" style="display: none;">
                <h3 style="margin-bottom: 20px; text-align: center; color: #333;">Regístrate como Cliente</h3>
                <?php echo $mensajeRegistro; ?>
                <form action="index.php#zona-usuarios" method="POST">
                    <input type="hidden" name="registro_cliente" value="1">
                    <div class="form-group">
                        <label for="reg_nombre">Nombre Completo</label>
                        <input type="text" id="reg_nombre" name="reg_nombre" placeholder="Tu nombre" required>
                    </div>
                    <div class="form-group">
                        <label for="reg_email">Correo Electrónico</label>
                        <input type="email" id="reg_email" name="reg_email" placeholder="usuario@correo.com" required>
                    </div>
                    <div class="form-group">
                        <label for="reg_password">Contraseña</label>
                        <input type="password" id="reg_password" name="reg_password" placeholder="Mínimo 6 caracteres" required>
                    </div>
                    <div class="form-group">
                        <label for="reg_telefono">Teléfono (Opcional)</label>
                        <input type="text" id="reg_telefono" name="reg_telefono" placeholder="Ej. 5512345678">
                    </div>
                    <button type="submit" class="btn-submit" style="background-color: #8b0b0b;">Registrarme</button>
                </form>
            </div>

            <div id="contenido-contacto" style="display: none;">
                <h3 style="margin-bottom: 20px; text-align: center; color: #333;">Envíanos un Mensaje</h3>
                <form action="#" method="POST" onsubmit="alert('¡Mensaje enviado con éxito!'); return false;">
                    <div class="form-group">
                        <label>Nombre Completo</label>
                        <input type="text" placeholder="Ej. Juan Pérez" required>
                    </div>
                    <div class="form-group">
                        <label>Correo Electrónico</label>
                        <input type="email" placeholder="correo@ejemplo.com" required>
                    </div>
                    <div class="form-group">
                        <label>Mensaje</label>
                        <textarea rows="4" placeholder="Escribe tu duda..." style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px; font-size:15px; outline:none; resize:none;" required></textarea>
                    </div>
                    <button type="submit" class="btn-submit">Enviar Mensaje</button>
                </form>
            </div>

        </div>
    </div>

    <aside class="cart-sidebar" id="cart">
        <div class="cart-header">
            <h3>Tu Carrito</h3>
            <span class="close-cart" id="close-cart-btn">&times;</span>
        </div>
        <div class="cart-items" id="cart-items-container">
            <?php 
            $totalPagar = 0;
            if (isset($_SESSION['carrito']) && !empty($_SESSION['carrito'])): 
                foreach ($_SESSION['carrito'] as $prodId => $item): 
                    $subtotal = $item['precio'] * $item['cantidad'];
                    $totalPagar += $subtotal;
            ?>
                <div class="cart-item" data-id="<?php echo $prodId; ?>">
                    <img src="<?php echo htmlspecialchars($item['imagen']); ?>" alt="Producto">
                    <div class="cart-item-info">
                        <div class="cart-item-title"><?php echo htmlspecialchars($item['nombre']); ?></div>
                        <div class="cart-item-controls">
                            <input type="number" class="input-cantidad" min="1" value="<?php echo $item['cantidad']; ?>" data-id="<?php echo $prodId; ?>">
                            <span class="cart-item-price">$<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                    </div>
                    <span class="btn-eliminar-item" data-id="<?php echo $prodId; ?>">&times;</span>
                </div>
            <?php 
                endforeach; 
            endif; 
            ?>
        </div>
        <div class="cart-footer">
            <div class="cart-total">
                <span>Total:</span>
                <span id="cart-total-val">$<?php echo number_format($totalPagar, 2); ?></span>
            </div>
            <a href="#" class="btn-checkout" onclick="alert('Pedido simulado con éxito en esta práctica.')">Proceder al Pago</a>
            <a href="#" class="btn-clear" id="btn-vaciar">Vaciar carrito</a>
        </div>
    </aside>

    <script>
        const cart = document.getElementById('cart');
        const closeCartBtn = document.getElementById('close-cart-btn');
        const openCartBtn = document.getElementById('open-cart-btn');
        const container = document.getElementById('cart-items-container');

        closeCartBtn.addEventListener('click', () => { cart.style.transform = 'translateX(100%)'; });
        openCartBtn.addEventListener('click', (e) => { e.preventDefault(); cart.style.transform = 'translateX(0)'; });

        // LÓGICA DE CONTROL PARA EL MENÚ DE 3 PESTAÑAS (TABS)
        function cambiarPestaña(tipo) {
            const tabLogin = document.getElementById('tab-login');
            const tabRegistro = document.getElementById('tab-registro');
            const tabContacto = document.getElementById('tab-contacto');
            
            const contenidoLogin = document.getElementById('contenido-login');
            const contenidoRegistro = document.getElementById('contenido-registro');
            const contenidoContacto = document.getElementById('contenido-contacto');

            // Resetear todos los estilos de las pestañas
            tabLogin.style.color = '#666'; tabLogin.style.borderBottom = '3px solid transparent'; contenidoLogin.style.display = 'none';
            tabRegistro.style.color = '#666'; tabRegistro.style.borderBottom = '3px solid transparent'; contenidoRegistro.style.display = 'none';
            tabContacto.style.color = '#666'; tabContacto.style.borderBottom = '3px solid transparent'; contenidoContacto.style.display = 'none';

            // Activar la pestaña seleccionada
            if (tipo === 'login') {
                tabLogin.style.color = '#8b0b0b';
                tabLogin.style.borderBottom = '3px solid #8b0b0b';
                contenidoLogin.style.display = 'block';
            } else if (tipo === 'registro') {
                tabRegistro.style.color = '#8b0b0b';
                tabRegistro.style.borderBottom = '3px solid #8b0b0b';
                contenidoRegistro.style.display = 'block';
            } else if (tipo === 'contacto') {
                tabContacto.style.color = '#111';
                tabContacto.style.borderBottom = '3px solid #111';
                contenidoContacto.style.display = 'block';
            }
        }

        // Mantener activa la pestaña de registro o login si PHP envió un mensaje
        <?php if (!empty($mensajeRegistro)): ?>
            cambiarPestaña('registro');
        <?php elseif (!empty($mensajeLogin)): ?>
            cambiarPestaña('login');
        <?php endif; ?>

        function actualizarInterfazCarrito(data) {
            if(data.success) {
                document.getElementById('cart-count').innerText = data.totalItems;
                document.getElementById('cart-total-val').innerText = '$' + data.totalPagar;
                container.innerHTML = data.htmlItems;
                asignarEventosCarrito();
            }
        }

        function enviarPeticionCarrito(cuerpo) {
            fetch('agregar_al_carrito.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: cuerpo
            })
            .then(res => res.json())
            .then(data => actualizarInterfazCarrito(data));
        }

        function asignarEventosCarrito() {
            container.querySelectorAll('.input-cantidad').forEach(input => {
                input.addEventListener('change', function() {
                    enviarPeticionCarrito(`accion=actualizar&id=${this.getAttribute('data-id')}&cantidad=${this.value}`);
                });
            });
            container.querySelectorAll('.btn-eliminar-item').forEach(btn => {
                btn.addEventListener('click', function() {
                    enviarPeticionCarrito(`accion=eliminar&id=${this.getAttribute('data-id')}`);
                });
            });
        }

        document.querySelectorAll('.btn-agregar-carrito').forEach(button => {
            button.addEventListener('click', function() {
                enviarPeticionCarrito(`id=${this.getAttribute('data-id')}&accion=agregar`);
                cart.style.transform = 'translateX(0)'; 
            });
        });

        document.getElementById('btn-vaciar').addEventListener('click', function(e) {
            e.preventDefault();
            enviarPeticionCarrito('accion=vaciar');
        });

        asignarEventosCarrito();
    </script>
</body>
</html>