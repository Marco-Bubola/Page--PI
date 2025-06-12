<?php
$servername = "localhost"; // Endereço do servidor MySQL
$username = "root"; // Usuário do banco de dados
$password = ""; // Senha do banco de dados
$dbname = "PI_Page"; // Nome do banco de dados

// Criar conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Checar conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}
// echo "Conectado com sucesso!";
?> 