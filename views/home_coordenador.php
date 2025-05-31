<?php
session_start();
if (!isset($_SESSION['usuario_nome']) || ($_SESSION['usuario_tipo'] !== 'coordenador' && $_SESSION['usuario_tipo'] !== 'admin')) {
    header('Location: index.php');
    exit();
}
$nome = $_SESSION['usuario_nome'];
include 'navbar.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Home Coordenador - PI Page</title>
    <link rel="icon" type="image/png" href="../assets/img/LOGO_PAGE.png">
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .home-container { background: #fff; padding: 30px; max-width: 400px; margin: 80px auto; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center; }
        h2 { color: #333; }
        .user { font-size: 18px; color: #007bff; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="home-container">
        <h2>Bem-vindo Coordenador/Admin!</h2>
        <div class="user">Olá, <strong><?php echo htmlspecialchars($nome); ?></strong>!</div>
    </div>
</body>
</html> 