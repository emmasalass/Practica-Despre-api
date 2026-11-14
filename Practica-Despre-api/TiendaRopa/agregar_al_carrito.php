<?php
session_start();

$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $accion = $_POST['accion'];

    // ACCIÓN 1: VACIAR TODO EL CARRITO
    if ($accion === 'vaciar') {
        $_SESSION['carrito'] = [];
        $response = ['success' => true];
    }

    // ACCIÓN 2: ELIMINAR UN SOLO PRODUCTO
    if ($accion === 'eliminar' && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        if (isset($_SESSION['carrito'][$id])) {
            unset($_SESSION['carrito'][$id]);
        }
        $response = ['success' => true];
    }

    // ACCIÓN 3: ACTUALIZAR CANTIDAD (INPUT O FLECHAS)
    if ($accion === 'actualizar' && isset($_POST['id']) && isset($_POST['cantidad'])) {
        $id = intval($_POST['id']);
        $cantidad = intval($_POST['cantidad']);
        
        if (isset($_SESSION['carrito'][$id])) {
            if ($cantidad > 0) {
                $_SESSION['carrito'][$id]['cantidad'] = $cantidad;
            } else {
                unset($_SESSION['carrito'][$id]); // Si baja a 0, se remueve
            }
        }
        $response = ['success' => true];
    }

    // ACCIÓN 4: AGREGAR PRODUCTO (Tu lógica original adaptada al puerto 3306)
    if ($accion === 'agregar' && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        
        try {
            $dbConn = new PDO("mysql:host=localhost;port=3306;dbname=tienda_ropa;charset=utf8mb4", 'root', '');
            $sql = "SELECT * FROM productos WHERE id = :id";
            $stmt = $dbConn->prepare($sql);
            $stmt->execute(['id' => $id]);
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($producto) {
                if (!isset($_SESSION['carrito'])) {
                    $_SESSION['carrito'] = [];
                }

                if (isset($_SESSION['carrito'][$id])) {
                    $_SESSION['carrito'][$id]['cantidad']++;
                } else {
                    $_SESSION['carrito'][$id] = [
                        'nombre' => $producto['nombre'],
                        'precio' => $producto['precio'],
                        'imagen' => $producto['imagen'],
                        'cantidad' => 1
                    ];
                }
                $response = ['success' => true];
            }
        } catch (PDOException $e) {
            $response = ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // SI LA ACCIÓN FUE EXITOSA, RECALCULAMOS EL HTML Y TOTALES PARA DEVOLVER AL FRONT-END
    if ($response['success']) {
        $totalItems = 0;
        $totalPagar = 0;
        $htmlItems = '';

        if (isset($_SESSION['carrito']) && !empty($_SESSION['carrito'])) {
            foreach ($_SESSION['carrito'] as $prodId => $item) {
                $totalItems += $item['cantidad'];
                $subtotal = $item['precio'] * $item['cantidad'];
                $totalPagar += $subtotal;

                $htmlItems .= '
                <div class="cart-item" data-id="'.$prodId.'">
                    <img src="'.htmlspecialchars($item['imagen']).'" alt="Producto">
                    <div class="cart-item-info">
                        <div class="cart-item-title">'.htmlspecialchars($item['nombre']).'</div>
                        <div class="cart-item-controls">
                            <input type="number" class="input-cantidad" min="1" value="'.$item['cantidad'].'" data-id="'.$prodId.'">
                            <span class="cart-item-price">$'.number_format($subtotal, 2).'</span>
                        </div>
                    </div>
                    <span class="btn-eliminar-item" data-id="'.$prodId.'">&times;</span>
                </div>';
            }
        }

        $response = [
            'success' => true,
            'totalItems' => $totalItems,
            'totalPagar' => number_format($totalPagar, 2),
            'htmlItems' => $htmlItems
        ];
    }
}

echo json_encode($response);