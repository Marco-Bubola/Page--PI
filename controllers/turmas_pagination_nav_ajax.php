<?php
session_start();
if (
    !isset($_SESSION['usuario_nome']) ||
    !in_array($_SESSION['usuario_tipo'], ['professor', 'coordenador', 'admin'])
) {
    http_response_code(403);
    exit('Acesso negado');
}
require_once '../config/conexao.php';

$pagina = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$cards_por_pagina = 4;

// Buscar turmas ativas
$turmas_ativas_professor = [];
$sql_todas_ativas = $conn->query("SELECT * FROM turmas WHERE status = 'ativa' ORDER BY ano_letivo DESC, nome");
if ($sql_todas_ativas && $sql_todas_ativas->num_rows > 0) {
    while ($row = $sql_todas_ativas->fetch_assoc()) {
        $turmas_ativas_professor[$row['id']] = $row;
    }
}
$total_turmas_ativas = count($turmas_ativas_professor);
$total_paginas = ceil($total_turmas_ativas / $cards_por_pagina);

$html = '';
if ($total_paginas > 1) {
    $html .= '<nav aria-label="Navegação de páginas" class="ms-auto">';
    $html .= '<ul class="pagination justify-content-end mb-0">';
    // Botão primeiro <<
    $html .= '<li class="page-item'.($pagina==1?' disabled':'').'">';
    $html .= '<a class="page-link" href="?page=1" title="Primeira">&laquo;</a></li>';
    for ($i = 1; $i <= $total_paginas; $i++) {
        $active = $i == $pagina ? ' active' : '';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="?page=' . $i . '">' . $i . '</a></li>';
    }
    // Botão último >>
    $html .= '<li class="page-item'.($pagina==$total_paginas?' disabled':'').'">';
    $html .= '<a class="page-link" href="?page=' . $total_paginas . '" title="Última">&raquo;</a></li>';
    $html .= '</ul></nav>';
}
echo $html;
