<?php
class Database {
    private $host = "localhost";  // Cambia esto si es necesario
    private $db_name = "DB";  // Reemplaza con tu DB
    private $username = "root";  // Usuario de la DB
    private $password = "";  // Contraseña de la DB
    public $conn;

    public function getConnection() {
        date_default_timezone_set('America/Monterrey');
        $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db_name);

        if ($this->conn->connect_error) {
            die("Error de conexión: " . $this->conn->connect_error);
        }
        return $this->conn;
    }
}
?>

