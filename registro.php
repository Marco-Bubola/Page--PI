<?php
include 'conexao.php';

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
    <title>Registro - PI Page</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .registro-container { background: #fff; padding: 30px; max-width: 400px; margin: 80px auto; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; }
        label { font-weight: bold; }
        input, select { width: 100%; padding: 8px; margin: 8px 0 16px 0; border: 1px solid #ccc; border-radius: 4px; }
        button { width: 100%; padding: 10px; background: #28a745; color: #fff; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; }
        button:hover { background: #218838; }
        .erro { color: red; text-align: center; }
        .sucesso { color: green; text-align: center; }
    </style>
</head>
<body>
    <div class="registro-container">
        <h2>Registro de Usuário</h2>
        <?php if ($erro) echo "<p class='erro'>$erro</p>"; ?>
        <?php if ($sucesso) echo "<p class='sucesso'>$sucesso</p>"; ?>
        <form method="post">
            <label>Nome:</label><br>
            <input type="text" name="nome" required><br>
            <label>Email:</label><br>
            <input type="email" name="email" required><br>
            <label>Senha:</label><br>
            <input type="password" name="senha" required><br>
            <input type="hidden" name="tipo" value="professor">
            <button type="submit">Registrar</button>
        </form>
        <p style="text-align:center; margin-top:10px;">
            <a href="login.php">Já tem conta? Faça login</a>
        </p>
    </div>
</body>
</html> 