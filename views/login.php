<?php
session_start();
include '../config/conexao.php';

// Exibe mensagem de sucesso se usuário foi adicionado
$mensagem = '';
if (isset($_GET['msg']) && $_GET['msg'] === 'usuario_adicionado') {
    $mensagem = 'Usuário adicionado com sucesso!';
}

$erro = '';
$email_digitado = '';
$senha_digitada = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    $email_digitado = $email;
    $senha_digitada = $senha;

    // Consulta segura usando prepared statement
    $stmt = $conn->prepare("SELECT id, nome, tipo, senha FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $nome, $tipo, $senha_hash);
        $stmt->fetch();

        if (password_verify($senha, $senha_hash)) {
            $_SESSION['usuario_id'] = $id;
            $_SESSION['usuario_nome'] = $nome;
            $_SESSION['usuario_tipo'] = $tipo;
            if ($tipo === 'professor') {
                header("Location: home_professor.php");
            } else {
                header("Location: home_coordenador.php");
            }
            setcookie('ultimo_email', $email, time() + (86400 * 30), "/"); // 30 dias
            exit();
        } else {
            $erro = "Senha incorreta!";
        }
    } else {
        $erro = "Usuário não encontrado!";
    }
    $stmt->close();
} else {
    // Primeiro acesso (GET), preenche com cookie se existir
    if (isset($_COOKIE['ultimo_email'])) {
        $email_digitado = $_COOKIE['ultimo_email'];
    }
    // Nunca preencha senha automaticamente por segurança
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login - Page</title>
    <link rel="icon" type="image/png" href="../assets/img/LOGO_PAGE.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/stylelogin.css">
</head>
<body>
    <div class="login-container">
        <div class="app-title">Page</div>
        <div class="app-subtitle">Sistema de Gestão Escolar</div>
        <?php if ($mensagem) echo "<p class='sucesso'>$mensagem</p>"; ?>
        <h2>Login do Sistema</h2>
        <?php if ($erro) echo "<p class='erro'>$erro</p>"; ?>
        <form method="post" autocomplete="off">
            <label>Email:</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($email_digitado); ?>" required autocomplete="username">
            <label>Senha:</label>
            <div class="input-group mb-2">
                <input type="password" name="senha" id="campoSenha" value="<?php echo htmlspecialchars($senha_digitada); ?>" autocomplete="current-password" style="width:100%;padding:10px 38px 10px 12px;margin:8px 0 18px 0;border:1px solid #ccc;border-radius:5px;font-size:1.05em;background:#f8f9fa;transition:none;">
                <button type="button" class="toggle-eye" onclick="toggleSenha()" tabindex="-1"><i id="iconeSenha" class="bi bi-eye"></i></button>
            </div>
            <button type="submit">Entrar</button>
        </form>
        <div class="register-link">
            Ainda não tem conta?<br>
            <a href="registro.php"><button type="button">Cadastrar</button></a>
        </div>
    </div>
    <div class="footer-login">&copy; <?php echo date('Y'); ?> Page - Todos os direitos reservados.</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function toggleSenha() {
        var campo = document.getElementById('campoSenha');
        var icone = document.getElementById('iconeSenha');
        var valor = campo.value;
        var selectionStart = campo.selectionStart;
        var selectionEnd = campo.selectionEnd;
        if (campo.type === 'password') {
            campo.type = 'text';
            icone.classList.remove('bi-eye');
            icone.classList.add('bi-eye-slash');
        } else {
            campo.type = 'password';
            icone.classList.remove('bi-eye-slash');
            icone.classList.add('bi-eye');
        }
        // Força o estilo a permanecer igual
        campo.style.width = '100%';
        campo.style.padding = '10px 38px 10px 12px';
        campo.style.margin = '8px 0 18px 0';
        campo.style.border = '1px solid #ccc';
        campo.style.borderRadius = '5px';
        campo.style.fontSize = '1.05em';
        campo.style.background = '#f8f9fa';
        campo.style.transition = 'none';
        // Mantém o cursor na posição correta
        campo.value = '';
        campo.value = valor;
        campo.setSelectionRange(selectionStart, selectionEnd);
    }
    </script>
</body>
</html> 