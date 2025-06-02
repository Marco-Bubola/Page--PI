<?php
include '../config/conexao.php';

$sucesso = '';
$erro = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    $tipo = 'professor'; // Força o tipo para professor

    // Criptografa a senha
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    // Verifica se o email já está cadastrado
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $erro = "Este email já está cadastrado.";
    } else {
        $stmt->close();
        // Insere novo usuário
        $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nome, $email, $senha_hash, $tipo);
        if ($stmt->execute()) {
            header('Location: index.php?msg=usuario_adicionado');
            exit();
        } else {
            $erro = "Erro ao registrar usuário.";
        }
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Registro - Page</title>
    <link rel="icon" type="image/png" href="../assets/img/LOGO_PAGE.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .registro-container { background: #fff; padding: 38px 32px 28px 32px; max-width: 370px; margin: 80px auto 30px auto; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.10); }
        .app-title { text-align: center; font-size: 2.1rem; font-weight: bold; color: #007bff; letter-spacing: 1px; margin-bottom: 0.2em; }
        .app-subtitle { text-align: center; color: #555; font-size: 1.08rem; margin-bottom: 1.2em; }
        h2 { text-align: center; color: #333; font-size: 1.3rem; margin-bottom: 1.2em; }
        label { font-weight: 500; color: #333; }
        input[type="text"], input[type="email"], input[type="password"] { width: 100%; padding: 10px 38px 10px 12px; margin: 8px 0 18px 0; border: 1px solid #ccc; border-radius: 5px; font-size: 1.05em; background: #f8f9fa; }
        .input-group { position: relative; }
        .input-group .toggle-eye { position: absolute; top: 50%; right: 12px; transform: translateY(-50%); background: none; border: none; outline: none; cursor: pointer; color: #888; font-size: 1.2em; }
        button[type="submit"] { width: 100%; padding: 11px; background: #28a745; color: #fff; border: none; border-radius: 5px; font-size: 1.08em; font-weight: 500; cursor: pointer; margin-top: 8px; transition: background 0.2s; }
        button[type="submit"]:hover { background: #218838; }
        .erro { color: #d9534f; text-align: center; margin-bottom: 10px; }
        .sucesso { color: #28a745; text-align: center; font-weight: bold; margin-bottom: 10px; }
        .login-link { text-align:center; margin-top:18px; }
        .login-link a { text-decoration: none; color: #007bff; }
        .footer-registro { text-align:center; color:#aaa; font-size:0.98em; margin-top:30px; letter-spacing:0.5px; }
        @media (max-width: 500px) { .registro-container { padding: 22px 6vw 18px 6vw; max-width: 98vw; } }
    </style>
</head>
<body>
    <div class="registro-container">
        <div class="app-title">Page</div>
        <div class="app-subtitle">Sistema de Gestão Escolar</div>
        <h2>Registro de Usuário</h2>
        <?php if ($erro) echo "<p class='erro'>$erro</p>"; ?>
        <?php if ($sucesso) echo "<p class='sucesso'>$sucesso</p>"; ?>
        <form method="post" autocomplete="off">
            <label>Nome:</label>
            <input type="text" name="nome" required autocomplete="name">
            <label>Email:</label>
            <input type="email" name="email" required autocomplete="username">
            <label>Senha:</label>
            <div class="input-group mb-2">
                <input type="password" name="senha" id="campoSenha" required autocomplete="new-password">
                <button type="button" class="toggle-eye" onclick="toggleSenha()" tabindex="-1"><i id="iconeSenha" class="bi bi-eye"></i></button>
            </div>
            <input type="hidden" name="tipo" value="professor">
            <button type="submit">Registrar</button>
        </form>
        <div class="login-link">
            Já tem conta? <a href="login.php">Faça login</a>
        </div>
    </div>
    <div class="footer-registro">&copy; <?php echo date('Y'); ?> Page - Todos os direitos reservados.</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function toggleSenha() {
        var campo = document.getElementById('campoSenha');
        var icone = document.getElementById('iconeSenha');
        if (campo.type === 'password') {
            campo.type = 'text';
            icone.classList.remove('bi-eye');
            icone.classList.add('bi-eye-slash');
        } else {
            campo.type = 'password';
            icone.classList.remove('bi-eye-slash');
            icone.classList.add('bi-eye');
        }
    }
    </script>
</body>
</html> 