<?php
// Requerimos la conexión usando la ruta correcta desde la raíz de la ejecución
require_once 'config/Conexion.php';

class ProductoModel extends Conexion {

    public function __construct() {
        parent::__construct();
    }

    // Obtener todos los productos para la tienda
    public function listarProductos() {
        $sql = "SELECT * FROM productos";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>