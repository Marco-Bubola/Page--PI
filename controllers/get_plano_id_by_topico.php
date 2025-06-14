<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['usuario_nome'])) exit(json_encode(['plano_id'=>null]));
require_once '../config/conexao.php';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$plano_id = null;
if ($id) {
    $res = $conn->query("SELECT c.plano_id FROM topicos t JOIN capitulos c ON t.capitulo_id = c.id WHERE t.id = $id");
    if ($row = $res->fetch_assoc()) $plano_id = $row['plano_id'];
}
echo json_encode(['plano_id'=>$plano_id]);
