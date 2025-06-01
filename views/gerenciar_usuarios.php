<?php
// Processamento AJAX deve vir antes de qualquer include ou HTML!
// Exclusão via AJAX
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['excluir_usuario_id']) &&
    isset($_SERVER['HTTP_X_REQUESTED_WITH'])
) {
    include '../config/conexao.php';
    $id = intval($_POST['excluir_usuario_id']);
    $stmt = $conn->prepare('DELETE FROM usuarios WHERE id = ?');
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'mensagem' => 'Usuário excluído com sucesso!']);
    } else {
        echo json_encode(['status' => 'danger', 'mensagem' => 'Erro ao excluir usuário.']);
    }
    $stmt->close();
    exit();
}
// Criação via AJAX
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['novo_nome'], $_POST['novo_sobrenome'], $_POST['novo_email'], $_POST['novo_tipo']) &&
    isset($_SERVER['HTTP_X_REQUESTED_WITH'])
) {
    include '../config/conexao.php';
    $nome = trim($_POST['novo_nome']);
    $sobrenome = trim($_POST['novo_sobrenome']);
    $email = trim($_POST['novo_email']);
    $senha = password_hash('12345', PASSWORD_DEFAULT);
    $tipo = $_POST['novo_tipo'];
    $cpf = $_POST['novo_cpf'] ?? '';
    $telefone = $_POST['novo_telefone'] ?? '';
    $data_nascimento = $_POST['novo_data_nascimento'] ?? null;
    $matricula = $_POST['novo_matricula'] ?? '';
    $data_admissao = $_POST['novo_data_admissao'] ?? null;
    $status = $_POST['novo_status'] ?? 'ativo';
    $genero = $_POST['novo_genero'] ?? '';
    $cep = $_POST['novo_cep'] ?? '';
    $rua = $_POST['novo_rua'] ?? '';
    $numero = $_POST['novo_numero'] ?? '';
    $bairro = $_POST['novo_bairro'] ?? '';
    $cidade = $_POST['novo_cidade'] ?? '';
    $estado = $_POST['novo_estado'] ?? '';
    // Validação de campos obrigatórios (exceto complemento, observacoes e foto)
    if (
        empty($nome) || empty($sobrenome) || empty($email) || empty($tipo) ||
        empty($cpf) || empty($telefone) || empty($data_nascimento) || empty($matricula) ||
        empty($data_admissao) || empty($status) || empty($genero) ||
        empty($cep) || empty($rua) || empty($numero) || empty($bairro) || empty($cidade) || empty($estado)
    ) {
        echo json_encode(['status' => 'danger', 'mensagem' => 'Preencha todos os campos obrigatórios!']);
        exit();
    }
    $complemento = $_POST['novo_complemento'] ?? '';
    $observacoes = $_POST['novo_observacoes'] ?? '';
    $foto_perfil = null;
    if (isset($_FILES['novo_foto_perfil']) && $_FILES['novo_foto_perfil']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['novo_foto_perfil']['name'], PATHINFO_EXTENSION);
        $foto_nome = uniqid('foto_', true) . '.' . $ext;
        $destino = '../assets/img/' . $foto_nome;
        if (move_uploaded_file($_FILES['novo_foto_perfil']['tmp_name'], $destino)) {
            $foto_perfil = $destino;
        }
    }
    $stmt = $conn->prepare('SELECT id FROM usuarios WHERE email = ? OR cpf = ?');
    $stmt->bind_param('ss', $email, $cpf);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(['status' => 'danger', 'mensagem' => 'Já existe um usuário com este email ou CPF!']);
    } else {
        $stmt->close();
        $stmt = $conn->prepare('INSERT INTO usuarios (nome, sobrenome, email, senha, tipo, cpf, telefone, data_nascimento, foto_perfil, matricula, data_admissao, status, genero, observacoes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('ssssssssssssss', $nome, $sobrenome, $email, $senha, $tipo, $cpf, $telefone, $data_nascimento, $foto_perfil, $matricula, $data_admissao, $status, $genero, $observacoes);
        if ($stmt->execute()) {
            $usuario_id = $stmt->insert_id;
            $stmt_end = $conn->prepare('INSERT INTO enderecos (usuario_id, cep, rua, numero, complemento, bairro, cidade, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt_end->bind_param('isssssss', $usuario_id, $cep, $rua, $numero, $complemento, $bairro, $cidade, $estado);
            $stmt_end->execute();
            $stmt_end->close();
            echo json_encode(['status' => 'success', 'mensagem' => 'Usuário criado com sucesso!']);
        } else {
            echo json_encode(['status' => 'danger', 'mensagem' => 'Erro ao criar usuário.']);
        }
        $stmt->close();
    }
    exit();
}
// Edição via AJAX
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['editar_usuario_id']) &&
    isset($_SERVER['HTTP_X_REQUESTED_WITH'])
) {
    include '../config/conexao.php';
    $editar_id = intval($_POST['editar_usuario_id']);
    $editar_nome = trim($_POST['editar_nome']);
    $editar_sobrenome = trim($_POST['editar_sobrenome']);
    $editar_email = trim($_POST['editar_email']);
    $editar_tipo = $_POST['editar_tipo'];
    $editar_cpf = $_POST['editar_cpf'] ?? '';
    $editar_telefone = $_POST['editar_telefone'] ?? '';
    $editar_data_nascimento = $_POST['editar_data_nascimento'] ?? null;
    $editar_matricula = $_POST['editar_matricula'] ?? '';
    $editar_data_admissao = $_POST['editar_data_admissao'] ?? null;
    $editar_status = $_POST['editar_status'] ?? 'ativo';
    $editar_genero = $_POST['editar_genero'] ?? '';
    $editar_cep = $_POST['editar_cep'] ?? '';
    $editar_rua = $_POST['editar_rua'] ?? '';
    $editar_numero = $_POST['editar_numero'] ?? '';
    $editar_bairro = $_POST['editar_bairro'] ?? '';
    $editar_cidade = $_POST['editar_cidade'] ?? '';
    $editar_estado = $_POST['editar_estado'] ?? '';
    // Validação de campos obrigatórios (exceto complemento, observacoes e foto)
    if (
        empty($editar_nome) || empty($editar_sobrenome) || empty($editar_email) || empty($editar_tipo) ||
        empty($editar_cpf) || empty($editar_telefone) || empty($editar_data_nascimento) || empty($editar_matricula) ||
        empty($editar_data_admissao) || empty($editar_status) || empty($editar_genero) ||
        empty($editar_cep) || empty($editar_rua) || empty($editar_numero) || empty($editar_bairro) || empty($editar_cidade) || empty($editar_estado)
    ) {
        echo json_encode(['status' => 'danger', 'mensagem' => 'Preencha todos os campos obrigatórios!']);
        exit();
    }
    $editar_complemento = $_POST['editar_complemento'] ?? '';
    $editar_observacoes = $_POST['editar_observacoes'] ?? '';
    $foto_perfil = null;
    if (isset($_FILES['editar_foto_perfil']) && $_FILES['editar_foto_perfil']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['editar_foto_perfil']['name'], PATHINFO_EXTENSION);
        $foto_nome = uniqid('foto_', true) . '.' . $ext;
        $destino = '../assets/img/' . $foto_nome;
        if (move_uploaded_file($_FILES['editar_foto_perfil']['tmp_name'], $destino)) {
            $foto_perfil = $destino;
        }
    }
    $editar_senha = $_POST['editar_senha'] ?? '';
    // Verifica se já existe outro usuário com o mesmo email ou CPF
    $stmt_check = $conn->prepare('SELECT id FROM usuarios WHERE (email = ? OR cpf = ?) AND id != ?');
    $stmt_check->bind_param('ssi', $editar_email, $editar_cpf, $editar_id);
    $stmt_check->execute();
    $stmt_check->store_result();
    if ($stmt_check->num_rows > 0) {
        echo json_encode(['status' => 'danger', 'mensagem' => 'Já existe outro usuário com este email ou CPF!']);
        $stmt_check->close();
        exit();
    }
    $stmt_check->close();
    if (!empty($editar_senha)) {
        $senha_hash = password_hash($editar_senha, PASSWORD_DEFAULT);
        $sql = 'UPDATE usuarios SET nome=?, sobrenome=?, email=?, senha=?, tipo=?, cpf=?, telefone=?, data_nascimento=?, foto_perfil=?, matricula=?, data_admissao=?, status=?, genero=?, observacoes=? WHERE id=?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssssssssssssi', $editar_nome, $editar_sobrenome, $editar_email, $senha_hash, $editar_tipo, $editar_cpf, $editar_telefone, $editar_data_nascimento, $foto_perfil, $editar_matricula, $editar_data_admissao, $editar_status, $editar_genero, $editar_observacoes, $editar_id);
    } else {
        $sql = 'UPDATE usuarios SET nome=?, sobrenome=?, email=?, tipo=?, cpf=?, telefone=?, data_nascimento=?, foto_perfil=?, matricula=?, data_admissao=?, status=?, genero=?, observacoes=? WHERE id=?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssssssssssssi', $editar_nome, $editar_sobrenome, $editar_email, $editar_tipo, $editar_cpf, $editar_telefone, $editar_data_nascimento, $foto_perfil, $editar_matricula, $editar_data_admissao, $editar_status, $editar_genero, $editar_observacoes, $editar_id);
    }
    if ($stmt->execute()) {
        $stmt_end = $conn->prepare('UPDATE enderecos SET cep=?, rua=?, numero=?, complemento=?, bairro=?, cidade=?, estado=? WHERE usuario_id=?');
        $stmt_end->bind_param('sssssssi', $editar_cep, $editar_rua, $editar_numero, $editar_complemento, $editar_bairro, $editar_cidade, $editar_estado, $editar_id);
        $stmt_end->execute();
        $stmt_end->close();
        echo json_encode(['status' => 'success', 'mensagem' => 'Usuário atualizado com sucesso!']);
    } else {
        echo json_encode(['status' => 'danger', 'mensagem' => 'Erro ao atualizar usuário.']);
    }
    $stmt->close();
    exit();
}

session_start();
if (!isset($_SESSION['usuario_nome']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header('Location: index.php');
    exit();
}

include 'navbar.php';
include '../config/conexao.php';
// Atualização do tipo de usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usuario_id'], $_POST['novo_tipo'])) {
    $usuario_id = intval($_POST['usuario_id']);
    $novo_tipo = $_POST['novo_tipo'];
    $tipos_validos = ['professor', 'coordenador', 'admin'];
    if (in_array($novo_tipo, $tipos_validos)) {
        $stmt = $conn->prepare('UPDATE usuarios SET tipo = ? WHERE id = ?');
        $stmt->bind_param('si', $novo_tipo, $usuario_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Buscar todos os usuários com filtro de CPF
$filtro_cpf = isset($_GET['filtro_cpf']) ? trim($_GET['filtro_cpf']) : '';
if ($filtro_cpf !== '') {
    $stmt = $conn->prepare('SELECT * FROM usuarios WHERE cpf LIKE ?');
    $cpf_like = "%$filtro_cpf%";
    $stmt->bind_param('s', $cpf_like);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    $result = $conn->query('SELECT * FROM usuarios');
}

$mensagem = '';
$erro = '';
// Mensagens via GET
$mensagem = isset($_GET['msg']) ? $_GET['msg'] : '';
$erro = isset($_GET['erro']) ? $_GET['erro'] : '';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Usuários</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../assets/img/LOGO_PAGE.png">
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .container { background: #fff; padding: 30px; max-width: 900px; margin: 40px auto; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h2 { color: #333; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: center; }
        th { background: #007bff; color: #fff; }
        tr:nth-child(even) { background: #f9f9f9; }
    </style>
</head>
<body>
    <?php include 'notificacao.php'; ?>
    <div class="container-fluid">
        <h2 class="mt-4 mb-4 text-center">Gerenciamento de Usuários</h2>
        <div class="mb-3 text-end">
            <form class="row g-2 align-items-center" method="get" style="justify-content: flex-end;">
                <div class="col-auto">
                    <input type="text" class="form-control" name="filtro_cpf" placeholder="Filtrar por CPF" value="<?php echo isset($_GET['filtro_cpf']) ? htmlspecialchars($_GET['filtro_cpf']) : ''; ?>">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                </div>
            </form>
            <button class="btn btn-success ms-2" data-bs-toggle="modal" data-bs-target="#criarUsuarioModal">Criar Novo Usuário</button>
        </div>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Foto</th>
                <th>Nome</th>
                <th>Email</th>
                    <th>CPF</th>
                <th>Tipo</th>
                    <th>Status</th>
                    <th>Telefone</th>
                <th>Ação</th>
            </tr>
            </thead>
            <tbody>
            <?php while ($usuario = $result->fetch_assoc()): ?>
            <?php
            // Buscar endereço do usuário
            $endereco = [
                'cep' => '',
                'rua' => '',
                'numero' => '',
                'complemento' => '',
                'bairro' => '',
                'cidade' => '',
                'estado' => ''
            ];
            $stmt_end = $conn->prepare('SELECT * FROM enderecos WHERE usuario_id = ? LIMIT 1');
            $stmt_end->bind_param('i', $usuario['id']);
            $stmt_end->execute();
            $res_end = $stmt_end->get_result();
            if ($res_end && $res_end->num_rows > 0) {
                $endereco = $res_end->fetch_assoc();
            }
            $stmt_end->close();
            ?>
            <tr>
                <td>
                    <?php if (!empty($usuario['foto_perfil'])): ?>
                        <img src="<?php echo $usuario['foto_perfil']; ?>" alt="Foto de Perfil" style="max-width:50px;max-height:50px;border-radius:50%;">
                    <?php else: ?>
                        <img src="https://via.placeholder.com/50x50?text=Usuário" alt="Sem Foto" style="max-width:50px;max-height:50px;border-radius:50%;">
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($usuario['nome'] . ' ' . ($usuario['sobrenome'] ?? '')); ?></td>
                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                <td><?php echo htmlspecialchars($usuario['cpf'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($usuario['tipo']); ?></td>
                <td><?php echo isset($usuario['status']) ? htmlspecialchars($usuario['status']) : ''; ?></td>
                <td><?php echo htmlspecialchars($usuario['telefone'] ?? ''); ?></td>
                <td>
                    <!-- Botão Editar -->
                    <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editarModal<?php echo $usuario['id']; ?>">Editar</button>
                    <!-- Botão Excluir -->
                    <button type="button" class="btn btn-danger btn-sm js-excluir-usuario" data-id="<?php echo $usuario['id']; ?>" data-bs-toggle="modal" data-bs-target="#excluirModal<?php echo $usuario['id']; ?>">Excluir</button>
                </td>
            </tr>

            <!-- Modal de Exclusão -->
            <div class="modal fade" id="excluirModal<?php echo $usuario['id']; ?>" tabindex="-1" aria-labelledby="excluirModalLabel<?php echo $usuario['id']; ?>" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="excluirModalLabel<?php echo $usuario['id']; ?>">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                  </div>
                  <div class="modal-body">
                    Tem certeza que deseja excluir o usuário <strong><?php echo htmlspecialchars($usuario['nome']); ?></strong>?
                  </div>
                  <div class="modal-footer">
                    <form class="form-excluir-usuario" data-id="<?php echo $usuario['id']; ?>" style="display:inline;">
                        <input type="hidden" name="excluir_usuario_id" value="<?php echo $usuario['id']; ?>">
                        <button type="submit" class="btn btn-danger">Excluir</button>
                    </form>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Modal de Edição XL -->
            <div class="modal fade" id="editarModal<?php echo $usuario['id']; ?>" tabindex="-1" aria-labelledby="editarModalLabel<?php echo $usuario['id']; ?>" aria-hidden="true">
              <div class="modal-dialog modal-xl">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="editarModalLabel<?php echo $usuario['id']; ?>">Editar Usuário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                  </div>
                  <form class="form-editar-usuario" data-id="<?php echo $usuario['id']; ?>" method="post" enctype="multipart/form-data">
                  <div class="modal-body">
                        <input type="hidden" name="editar_usuario_id" value="<?php echo $usuario['id']; ?>">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Nome</label>
                                <input type="text" class="form-control" name="editar_nome" value="<?php echo htmlspecialchars($usuario['nome']); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Sobrenome</label>
                                <input type="text" class="form-control" name="editar_sobrenome" value="<?php echo htmlspecialchars($usuario['sobrenome'] ?? ''); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="editar_email" value="<?php echo htmlspecialchars($usuario['email']); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Nova Senha (deixe em branco para não alterar)</label>
                                <input type="password" class="form-control" name="editar_senha">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tipo</label>
                                <select name="editar_tipo" class="form-select">
                                    <option value="" <?php if (empty($usuario['tipo'])) echo 'selected'; ?>>Selecione</option>
                                    <option value="professor" <?php if ($usuario['tipo'] === 'professor') echo 'selected'; ?>>Professor</option>
                                    <option value="coordenador" <?php if ($usuario['tipo'] === 'coordenador') echo 'selected'; ?>>Coordenador</option>
                                    <option value="admin" <?php if ($usuario['tipo'] === 'admin') echo 'selected'; ?>>Admin</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">CPF</label>
                                <input type="text" class="form-control" name="editar_cpf" value="<?php echo htmlspecialchars($usuario['cpf'] ?? ''); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Telefone</label>
                                <input type="text" class="form-control" name="editar_telefone" value="<?php echo htmlspecialchars($usuario['telefone'] ?? ''); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Data de Nascimento</label>
                                <input type="date" class="form-control" name="editar_data_nascimento" value="<?php echo htmlspecialchars($usuario['data_nascimento'] ?? ''); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Foto de Perfil</label>
                                <input type="file" class="form-control" name="editar_foto_perfil" accept="image/*">
                                <?php if (!empty($usuario['foto_perfil'])): ?>
                                    <img src="<?php echo $usuario['foto_perfil']; ?>" alt="Foto de Perfil" style="max-width:60px;max-height:60px;margin-top:5px;">
                                <?php endif; ?>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Matrícula</label>
                                <input type="text" class="form-control" name="editar_matricula" value="<?php echo htmlspecialchars($usuario['matricula'] ?? ''); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Data de Admissão</label>
                                <input type="date" class="form-control" name="editar_data_admissao" value="<?php echo htmlspecialchars($usuario['data_admissao'] ?? ''); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select name="editar_status" class="form-select">
                                    <option value="" <?php if (empty($usuario['status'])) echo 'selected'; ?>>Selecione</option>
                                    <option value="ativo" <?php if (($usuario['status'] ?? '') === 'ativo') echo 'selected'; ?>>Ativo</option>
                                    <option value="inativo" <?php if (($usuario['status'] ?? '') === 'inativo') echo 'selected'; ?>>Inativo</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Gênero</label>
                                <select name="editar_genero" class="form-select">
                                    <option value="" <?php if (empty($usuario['genero'])) echo 'selected'; ?>>Selecione</option>
                                    <option value="Masculino" <?php if (($usuario['genero'] ?? '') === 'Masculino') echo 'selected'; ?>>Masculino</option>
                                    <option value="Feminino" <?php if (($usuario['genero'] ?? '') === 'Feminino') echo 'selected'; ?>>Feminino</option>
                                    <option value="Outro" <?php if (($usuario['genero'] ?? '') === 'Outro') echo 'selected'; ?>>Outro</option>
                                    <option value="Prefiro não informar" <?php if (($usuario['genero'] ?? '') === 'Prefiro não informar') echo 'selected'; ?>>Prefiro não informar</option>
                                </select>
                            </div>
                            <!-- Endereço detalhado -->
                            <div class="col-md-2">
                                <label class="form-label">CEP</label>
                                <input type="text" class="form-control" name="editar_cep" value="<?php echo htmlspecialchars($endereco['cep']); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Rua</label>
                                <input type="text" class="form-control" name="editar_rua" value="<?php echo htmlspecialchars($endereco['rua']); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Número</label>
                                <input type="text" class="form-control" name="editar_numero" value="<?php echo htmlspecialchars($endereco['numero']); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Complemento</label>
                                <input type="text" class="form-control" name="editar_complemento" value="<?php echo htmlspecialchars($endereco['complemento']); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Bairro</label>
                                <input type="text" class="form-control" name="editar_bairro" value="<?php echo htmlspecialchars($endereco['bairro']); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Cidade</label>
                                <input type="text" class="form-control" name="editar_cidade" value="<?php echo htmlspecialchars($endereco['cidade']); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Estado</label>
                                <input type="text" class="form-control" name="editar_estado" value="<?php echo htmlspecialchars($endereco['estado']); ?>">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Observações</label>
                                <textarea class="form-control" name="editar_observacoes"><?php echo htmlspecialchars($usuario['observacoes'] ?? ''); ?></textarea>
                            </div>
                        </div>
                  </div>
                  <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Salvar Alterações</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                  </div>
                </form>
                </div>
              </div>
            </div>

            <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal Criar Novo Usuário XL -->
    <div class="modal fade" id="criarUsuarioModal" tabindex="-1" aria-labelledby="criarUsuarioModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="criarUsuarioModalLabel">Criar Novo Usuário</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
          </div>
          <form id="form-criar-usuario" method="post" enctype="multipart/form-data">
          <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Nome</label>
                        <input type="text" class="form-control" name="novo_nome" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Sobrenome</label>
                        <input type="text" class="form-control" name="novo_sobrenome" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="novo_email" required>
                    </div>
                    <!-- Senha padrão, campo oculto -->
                    <input type="hidden" name="novo_senha" value="12345">
                    <div class="col-md-3">
                        <label class="form-label">Tipo</label>
                        <select name="novo_tipo" class="form-select">
                            <option value="professor">Professor</option>
                            <option value="coordenador">Coordenador</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">CPF</label>
                        <input type="text" class="form-control" name="novo_cpf">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Telefone</label>
                        <input type="text" class="form-control" name="novo_telefone">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Data de Nascimento</label>
                        <input type="date" class="form-control" name="novo_data_nascimento">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Foto de Perfil</label>
                        <input type="file" class="form-control" name="novo_foto_perfil" accept="image/*">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Matrícula</label>
                        <input type="text" class="form-control" name="novo_matricula">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Data de Admissão</label>
                        <input type="date" class="form-control" name="novo_data_admissao">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="novo_status" class="form-select">
                            <option value="ativo">Ativo</option>
                            <option value="inativo">Inativo</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Gênero</label>
                        <select name="novo_genero" class="form-select">
                            <option value="">Selecione</option>
                            <option value="Masculino">Masculino</option>
                            <option value="Feminino">Feminino</option>
                            <option value="Outro">Outro</option>
                            <option value="Prefiro não informar">Prefiro não informar</option>
                        </select>
                    </div>
                    <!-- Endereço via CEP -->
                    <div class="col-md-2">
                        <label class="form-label">CEP</label>
                        <input type="text" class="form-control" name="novo_cep" id="novo_cep" maxlength="9" onblur="buscarEnderecoPorCEP(this.value)">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Rua</label>
                        <input type="text" class="form-control" name="novo_rua" id="novo_rua">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Número</label>
                        <input type="text" class="form-control" name="novo_numero" id="novo_numero">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Complemento</label>
                        <input type="text" class="form-control" name="novo_complemento" id="novo_complemento">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Bairro</label>
                        <input type="text" class="form-control" name="novo_bairro" id="novo_bairro">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Cidade</label>
                        <input type="text" class="form-control" name="novo_cidade" id="novo_cidade">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Estado</label>
                        <input type="text" class="form-control" name="novo_estado" id="novo_estado">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" name="novo_observacoes"></textarea>
                    </div>
                </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-success">Criar Usuário</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          </div>
          </form>
        </div>
      </div>
    </div>

 

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function buscarEnderecoPorCEP(cep) {
        cep = cep.replace(/\D/g, '');
        if (cep.length === 8) {
            fetch('https://viacep.com.br/ws/' + cep + '/json/')
                .then(response => response.json())
                .then(data => {
                    if (!data.erro) {
                        document.getElementById('novo_rua').value = data.logradouro || '';
                        document.getElementById('novo_bairro').value = data.bairro || '';
                        document.getElementById('novo_cidade').value = data.localidade || '';
                        document.getElementById('novo_estado').value = data.uf || '';
                    }
                });
        }
    }

    document.querySelectorAll('.form-excluir-usuario').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const usuarioId = this.getAttribute('data-id');
            const modal = this.closest('.modal');
            const formData = new FormData();
            formData.append('excluir_usuario_id', usuarioId);
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                mostrarNotificacao(data.mensagem, data.status);
                if (data.status === 'success') {
                    if (modal) {
                        const bsModal = bootstrap.Modal.getInstance(modal);
                        bsModal.hide();
                    }
                    setTimeout(() => { window.location.reload(); }, 1200);
                }
            });
        });
    });

    // Função para validar campos obrigatórios de qualquer formulário
    function validarCamposObrigatorios(form, prefixo) {
        let valido = true;
        const obrigatorios = [
            prefixo + 'nome',
            prefixo + 'sobrenome',
            prefixo + 'email',
            prefixo + 'tipo',
            prefixo + 'cpf',
            prefixo + 'telefone',
            prefixo + 'data_nascimento',
            prefixo + 'matricula',
            prefixo + 'data_admissao',
            prefixo + 'status',
            prefixo + 'genero',
            prefixo + 'cep',
            prefixo + 'rua',
            prefixo + 'numero',
            prefixo + 'bairro',
            prefixo + 'cidade',
            prefixo + 'estado'
        ];
        obrigatorios.forEach(nome => {
            const campo = form.querySelector(`[name="${nome}"]`);
            if (campo) {
                campo.classList.remove('is-invalid');
                // Remove todas as mensagens antigas do container
                let feedbacks = campo.parentNode.querySelectorAll('.invalid-feedback');
                feedbacks.forEach(fb => fb.remove());
                if (!campo.value.trim()) {
                    campo.classList.add('is-invalid');
                    // Adiciona mensagem de erro logo após o campo, dentro do mesmo container
                    const div = document.createElement('div');
                    div.className = 'invalid-feedback';
                    div.innerText = 'Campo obrigatório!';
                    if (campo.nextSibling) {
                        campo.parentNode.insertBefore(div, campo.nextSibling);
                    } else {
                        campo.parentNode.appendChild(div);
                    }
                    valido = false;
                }
            }
        });
        return valido;
    }

    const formCriar = document.getElementById('form-criar-usuario');
    if (formCriar) {
        formCriar.addEventListener('submit', function(e) {
            e.preventDefault();
            // Validação dos campos obrigatórios
            if (!validarCamposObrigatorios(formCriar, 'novo_')) {
                return;
            }
            const formData = new FormData(this);
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                mostrarNotificacao(data.mensagem, data.status);
                if (data.status === 'success') {
                    const modal = document.getElementById('criarUsuarioModal');
                    if (modal) {
                        const bsModal = bootstrap.Modal.getInstance(modal);
                        bsModal.hide();
                    }
                    setTimeout(() => { window.location.reload(); }, 1200);
                }
            });
        });
    }

    document.querySelectorAll('.form-editar-usuario').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            form.querySelectorAll('.is-invalid').forEach(campo => campo.classList.remove('is-invalid'));
            form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
            if (!validarCamposObrigatorios(form, 'editar_')) {
                return;
            }
            const formData = new FormData(this);
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                mostrarNotificacao(data.mensagem, data.status);
                if (data.status === 'success') {
                    const modal = this.closest('.modal');
                    if (modal) {
                        const bsModal = bootstrap.Modal.getInstance(modal);
                        bsModal.hide();
                    }
                    setTimeout(() => { window.location.reload(); }, 1200);
                }
            });
        });
    });
    </script>
</body>
</html> 