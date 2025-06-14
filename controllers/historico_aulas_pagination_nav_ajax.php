<?php
session_start();
if (!isset($_SESSION['usuario_nome']) || $_SESSION['usuario_tipo'] !== 'professor') {
    http_response_code(403);
    exit('Acesso negado');
}
require_once '../config/conexao.php';

$pagina = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$aulas_por_pagina = 5;

$res_total = $conn->query("SELECT COUNT(*) as total FROM aulas");
$total_aulas = $res_total ? intval($res_total->fetch_assoc()['total']) : 0;
$total_paginas = ceil($total_aulas / $aulas_por_pagina);

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
