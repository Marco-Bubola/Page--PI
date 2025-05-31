<?php
session_start();
include 'conexao.php';

// Exibe mensagem de sucesso se usuário foi adicionado
$mensagem = '';
if (isset($_GET['msg']) && $_GET['msg'] === 'usuario_adicionado') {
    $mensagem = 'Usuário adicionado com sucesso!';
}

$erro = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    // Consulta segura usando prepared statement
    $stmt = $conn->prepare("SELECT id, nome, tipo, senha FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $nome, $tipo, $senha_hash);
        $stmt->fetch();

        // Aqui, se usar hash: if (password_verify($senha, $senha_hash))
        if ($senha === $senha_hash) { // Troque por password_verify se usar hash
            $_SESSION['usuario_id'] = $id;
            $_SESSION['usuario_nome'] = $nome;
            $_SESSION['usuario_tipo'] = $tipo;
            if ($tipo === 'professor') {
                header("Location: home_professor.php");
            } else {
                header("Location: home_coordenador.php");
            }
            exit();
        } else {
            $erro = "Senha incorreta!";
        }
    } else {
        $erro = "Usuário não encontrado!";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login - PI Page</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .login-container { background: #fff; padding: 30px; max-width: 350px; margin: 80px auto; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; }
        label { font-weight: bold; }
        input[type="email"], input[type="password"] { width: 100%; padding: 8px; margin: 8px 0 16px 0; border: 1px solid #ccc; border-radius: 4px; }
        button { width: 100%; padding: 10px; background: #007bff; color: #fff; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .erro { color: red; text-align: center; }
        .sucesso { color: green; text-align: center; font-weight: bold; }
    </style>
</head>
<body>
    <div class="login-container">
        <?php if ($mensagem) echo "<p class='sucesso'>$mensagem</p>"; ?>
        <h2>Login do Sistema</h2>
        <?php if ($erro) echo "<p class='erro'>$erro</p>"; ?>
        <form method="post">
            <label>Email:</label><br>
            <input type="email" name="email" ><br>
            <label>Senha:</label><br>
            <input type="password" name="senha" ><br>
            <button type="submit">Entrar</button>
            </form>
        <p style="text-align:center; margin-top:10px;">
            Ainda não tem conta?
            <a href="registro.php"><button type="button" style="margin-top:5px; background:#28a745; color:#fff; border:none; border-radius:4px; padding:8px 16px; font-size:15px; cursor:pointer;">Cadastrar</button></a>
        </p> 
    </div>
</body>
</html> 