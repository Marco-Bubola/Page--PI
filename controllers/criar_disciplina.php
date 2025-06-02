<?php
session_start();
if (!isset($_SESSION['usuario_nome']) || ($_SESSION['usuario_tipo'] !== 'coordenador' && $_SESSION['usuario_tipo'] !== 'admin')) {
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['nome_disciplina']) && isset($_POST['codigo_disciplina']) && isset($_POST['descricao_disciplina']) && isset($_POST['ativa_disciplina'])) {
    $nome = trim($_POST['nome_disciplina']);
    $codigo = trim($_POST['codigo_disciplina']);
    $descricao = trim($_POST['descricao_disciplina']);
    $ativa = isset($_POST['ativa_disciplina']) && $_POST['ativa_disciplina'] == '1' ? 1 : 0;
    require_once '../config/conexao.php'; // ajuste o caminho se necessário
    
    // Prevenir duplicidade
    $stmt = $conn->prepare('SELECT id FROM disciplinas WHERE nome = ?');
    $stmt->bind_param('s', $nome);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        // Já existe
        $redirect = 'home_coordenador.php';
        if (isset($_POST['redirect']) && preg_match('/^[a-zA-Z0-9_]+\.php$/', $_POST['redirect'])) {
            $redirect = $_POST['redirect'];
        }
        header('Location: ../views/' . $redirect . '?erro=disciplina_existente');
        exit();
    }
    $stmt->close();

    $stmt = $conn->prepare('INSERT INTO disciplinas (nome, codigo, descricao, ativa) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('sssi', $nome, $codigo, $descricao, $ativa);
    if ($stmt->execute()) {
        $redirect = 'home_coordenador.php';
        if (isset($_POST['redirect']) && preg_match('/^[a-zA-Z0-9_]+\.php$/', $_POST['redirect'])) {
            $redirect = $_POST['redirect'];
        }
        header('Location: ../views/' . $redirect . '?sucesso=disciplina_criada');
        exit();
    } else {
        $redirect = 'home_coordenador.php';
        if (isset($_POST['redirect']) && preg_match('/^[a-zA-Z0-9_]+\.php$/', $_POST['redirect'])) {
            $redirect = $_POST['redirect'];
        }
        header('Location: ../views/' . $redirect . '?erro=erro_banco');
        exit();
    }
} else {
    $redirect = 'home_coordenador.php';
    if (isset($_POST['redirect']) && preg_match('/^[a-zA-Z0-9_]+\.php$/', $_POST['redirect'])) {
        $redirect = $_POST['redirect'];
    }
    header('Location: ../views/' . $redirect . '?erro=dados_invalidos');
    exit();
} 