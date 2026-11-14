<?php
class Conexion {
    private $host = 'localhost';
    private $port = '3306'; // Agregamos tu puerto personalizado
    private $username = 'root';
    private $password = ''; 
    private $dbname = 'tienda_ropa';
    private $charset = 'utf8mb4';
    protected $db;

    public function __construct() {
        // Agregamos port={$this->port} dentro de la cadena DSN
        $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->dbname};charset={$this->charset}";
        try {
            $this->db = new PDO($dsn, $this->username, $this->password);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }
}
?>