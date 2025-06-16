-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 16/06/2025 às 17:36
-- Versão do servidor: 9.2.0
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `pi_page`
--
CREATE DATABASE IF NOT EXISTS `pi_page` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `pi_page`;

-- --------------------------------------------------------

--
-- Estrutura para tabela `aulas`
--

CREATE TABLE `aulas` (
  `id` int NOT NULL,
  `professor_id` int NOT NULL,
  `disciplina_id` int NOT NULL,
  `turma_id` int NOT NULL,
  `data` date NOT NULL,
  `comentario` text,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `aulas`
--

INSERT INTO `aulas` (`id`, `professor_id`, `disciplina_id`, `turma_id`, `data`, `comentario`, `criado_em`) VALUES
(1, 3, 23, 26, '2025-02-03', 'O Período da Pedra Lascada e Neolítico: A Revolução Agrícola', '2025-06-16 11:47:16'),
(2, 3, 23, 26, '2025-02-10', 'Idade dos Metais: Bronze e Ferro e  A Arte na Pré-História', '2025-06-16 11:48:13'),
(3, 3, 23, 26, '2025-02-17', 'Motivações das Grandes Navegações e As Rotas Marítimas e os Descobrimentos', '2025-06-16 11:49:27'),
(4, 3, 23, 26, '2025-02-24', 'Impacto das Navegações no Mundo e Tratado de Tordesilhas e suas Implicações', '2025-06-16 11:50:32'),
(5, 3, 23, 26, '2025-03-03', 'O Período Pré-Colonial no Brasil e  Ciclos Econômicos Coloniais', '2025-06-16 11:51:26'),
(6, 3, 23, 26, '2025-03-10', 'Sociedade Açucareira e Escravidão e  Invasões Estrangeiras no Brasil Colônia', '2025-06-16 11:52:03'),
(7, 3, 24, 26, '2025-03-17', 'Introdução aos Grupos Funcionais e  A Importância do Carbono na Química Orgânica', '2025-06-16 11:52:59'),
(8, 3, 24, 26, '2025-03-24', 'Série Homóloga e Isomeria de Cadeia e  Forças Intermoleculares em Compostos Orgânicos', '2025-06-16 11:53:40'),
(9, 3, 24, 26, '2025-03-31', 'Álcoois: Classificação e Nomenclatura e  Éteres e suas Aplicações', '2025-06-16 11:54:23'),
(10, 3, 24, 26, '2025-03-31', 'Fenóis e sua Acidez e  Aldeídos e Cetonas: Nomenclatura e Propriedades', '2025-06-16 11:55:44'),
(11, 3, 24, 26, '2025-04-07', 'Definição e Classificação de Polímeros e   Reações de Polimerização', '2025-06-16 11:58:13'),
(12, 3, 24, 26, '2025-04-14', 'Plásticos e Reciclagem e  Borracas e Elastômeros', '2025-06-16 11:59:23'),
(13, 3, 25, 26, '2025-04-28', 'Bloqueio de Imagens: Desenhando o Que Você Vê e  Desenho de Memória e Imaginação', '2025-06-16 12:04:57'),
(14, 3, 25, 26, '2025-05-05', 'Explorando Diferentes Estilos de Esboço e  Criação de Personagens e Ambientes Simples', '2025-06-16 12:06:00'),
(15, 3, 25, 26, '2025-04-21', 'Exagerando Características Faciais e  Expressões Faciais em Caricatura', '2025-06-16 12:09:58'),
(16, 3, 25, 26, '2025-05-05', 'Desenho de Corpos em Caricatura e  Desenho de Acessórios e Cenários para Caricaturas', '2025-06-16 12:11:16'),
(17, 3, 25, 26, '2025-05-12', 'Perspectiva Aérea e Linear em Cenários e  Desenho de Arquitetura e Edifícios', '2025-06-16 12:11:57'),
(18, 3, 25, 26, '2025-06-02', 'Criação de Ambientes Naturais e  Composição de Cenas Completas', '2025-06-16 12:12:57');

-- --------------------------------------------------------

--
-- Estrutura para tabela `capitulos`
--

CREATE TABLE `capitulos` (
  `id` int NOT NULL,
  `plano_id` int NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `ordem` int DEFAULT NULL,
  `status` enum('em_andamento','concluido','cancelado') DEFAULT 'em_andamento',
  `descricao` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `capitulos`
--

INSERT INTO `capitulos` (`id`, `plano_id`, `titulo`, `ordem`, `status`, `descricao`) VALUES
(148, 38, 'Introdução à Biologia Celular', 1, 'cancelado', 'Conceitos fundamentais da célula como unidade da vida.'),
(149, 38, 'Organelas e Funções', 2, 'cancelado', 'Estudo das organelas celulares e seus papéis no metabolismo.'),
(150, 38, 'Divisão Celular', 3, 'cancelado', 'Processos de mitose e meiose e sua importância.'),
(151, 38, 'Ciclo Celular e Controle', 4, 'cancelado', 'Etapas do ciclo celular e sua regulação.'),
(152, 39, 'Cinemática', 1, 'cancelado', 'Análise do movimento de corpos sem considerar as causas.'),
(153, 39, 'Dinâmica', 2, 'cancelado', 'Estudo das forças e das leis que regem o movimento.'),
(154, 39, 'Trabalho e Energia', 3, 'cancelado', 'Conceitos de trabalho, energia cinética e potencial.'),
(155, 40, 'Romantismo', 1, 'cancelado', 'Análise do movimento romântico e seus autores.'),
(156, 40, 'Realismo e Naturalismo', 2, 'cancelado', 'Características e principais escritores.'),
(157, 40, 'Modernismo', 3, 'cancelado', 'Semana de Arte Moderna e seus desdobramentos.'),
(158, 41, 'Resumo das Organelas', 1, 'em_andamento', 'Panorama rápido das principais organelas e suas funções.'),
(159, 41, 'Resumo de Divisão Celular', 2, 'em_andamento', 'Diferenciação entre mitose e meiose com foco em vestibulares.'),
(160, 42, 'Resumo Cinemática', 1, 'em_andamento', 'Síntese dos principais movimentos e fórmulas.'),
(161, 42, 'Resumo das Leis de Newton', 2, 'em_andamento', 'Aplicações práticas das três leis da dinâmica.'),
(162, 41, 'Membrana Celular e Transporte', 3, 'em_andamento', 'Estudo da estrutura da membrana plasmática e os mecanismos de transporte.'),
(163, 41, 'Genética Molecular Básica', 4, 'em_andamento', 'Conceitos introdutórios sobre o material genético e síntese proteica.'),
(164, 42, 'Gravitação Universal', 3, 'em_andamento', 'Estudo das leis de Newton para gravitação e aplicações em órbitas.'),
(165, 42, 'Quantidade de Movimento e Impulso', 4, 'em_andamento', 'Conceitos fundamentais para choques e conservação de movimento linear.'),
(166, 43, 'Antiguidade Clássica', 1, 'cancelado', 'Estudo das civilizações grega e romana, suas contribuições para a política, filosofia e arte.'),
(167, 43, 'Idade Média e Feudalismo', 2, 'cancelado', 'Análise do período medieval, com foco na estrutura feudal, Igreja e Cruzadas.'),
(168, 43, 'Revoluções e Transformações', 3, 'cancelado', 'Impacto das revoluções (Industrial, Francesa) e o surgimento do mundo moderno.'),
(169, 44, 'Fundamentos da Química Orgânica', 1, 'cancelado', 'Conceitos básicos, ligações químicas e hibridização do carbono.'),
(170, 44, 'Hidrocarbonetos e Funções Orgânicas', 2, 'cancelado', 'Classificação, nomenclatura e propriedades dos hidrocarbonetos, álcoois, éteres, etc.'),
(171, 44, 'Reações Orgânicas', 3, 'cancelado', 'Principais tipos de reações como adição, substituição e eliminação.'),
(172, 45, 'Introdução ao Desenho', 1, 'cancelado', 'Materiais, tipos de traço, formas básicas e observação.'),
(173, 45, 'Perspectiva e Composição', 2, 'cancelado', 'Regras de perspectiva (um, dois e três pontos) e princípios de composição visual.'),
(174, 45, 'Luz, Sombra e Volume', 3, 'cancelado', 'Técnicas de sombreamento, hachuras e representação de volume em objetos.'),
(175, 46, 'Grandes Civilizações Antigas', 1, 'em_andamento', 'Estudo das civilizações do Egito, Mesopotâmia e Vale do Indo.'),
(176, 46, 'Impérios e Expansão', 2, 'em_andamento', 'Ascensão e queda de grandes impérios, como o Persa e o Mongol.'),
(177, 46, 'Guerras Mundiais e Pós-Guerra', 3, 'em_andamento', 'Análise das causas, eventos e consequências das duas Grandes Guerras e a Guerra Fria.'),
(178, 47, 'Grupos Funcionais Essenciais', 1, 'em_andamento', 'Revisão dos principais grupos funcionais (álcoois, aldeídos, cetonas, ácidos carboxílicos) e suas características.'),
(179, 47, 'Reações de Nomenclatura', 2, 'em_andamento', 'Exercícios práticos de nomenclatura IUPAC para diversos compostos orgânicos.'),
(180, 47, 'Isomeria', 3, 'em_andamento', 'Conceitos e tipos de isomeria (estrutural, geométrica, óptica) com exemplos práticos.'),
(181, 48, 'Desenho de Observação Rápido', 1, 'em_andamento', 'Técnicas para capturar a essência de objetos e figuras em desenhos rápidos.'),
(182, 48, 'Estudos de Proporção', 2, 'em_andamento', 'Exercícios para aprimorar a percepção de proporções em diferentes contextos de desenho.'),
(183, 48, 'Uso da Cor no Desenho', 3, 'em_andamento', 'Introdução ao uso de cores (lápis de cor, marcadores) para realçar e dar vida aos desenhos.'),
(184, 49, 'Períodos da Pré-História', 1, 'concluido', 'Paleolítico, Neolítico e Idade dos Metais: características e desenvolvimentos.'),
(185, 49, 'Grandes Navegações', 2, 'concluido', 'As explorações marítimas europeias, descobertas e o início da globalização.'),
(186, 49, 'Brasil Colônia', 3, 'concluido', 'Chegada dos portugueses, exploração de recursos e formação da sociedade colonial.'),
(187, 50, 'Química do Carbono', 1, 'concluido', 'Propriedades únicas do carbono e a diversidade de compostos orgânicos.'),
(188, 50, 'Funções Oxigenadas', 2, 'concluido', 'Introdução e exemplos de álcoois, fenóis, éteres, aldeídos e cetonas.'),
(189, 50, 'Polímeros Naturais e Sintéticos', 3, 'concluido', 'Conceitos básicos de polimerização e exemplos de polímeros do dia a dia.'),
(190, 51, 'Esboço Criativo', 1, 'concluido', 'Técnicas de esboço para desenvolver ideias e composições originais.'),
(191, 51, 'Desenho de Caricaturas', 2, 'concluido', 'Fundamentos do desenho de caricaturas, exagero e expressividade.'),
(192, 51, 'Desenho de Cenários', 3, 'concluido', 'Criação de fundos e ambientes para personagens ou objetos.'),
(193, 52, 'Mecânica: Cinemática e Dinâmica', 1, 'em_andamento', 'Estudo do movimento dos corpos, forças, leis de Newton e trabalho e energia.'),
(194, 52, 'Termologia e Óptica', 2, 'em_andamento', 'Análise dos fenômenos térmicos (temperatura, calor, termodinâmica) e óticos (luz, espelhos, lentes).'),
(195, 52, 'Eletricidade e Magnetismo', 3, 'em_andamento', 'Conceitos de carga elétrica, corrente, circuitos, campos elétricos e magnéticos.'),
(196, 53, 'Quinhentismo e Barroco', 1, 'em_andamento', 'Literatura do período colonial brasileiro, com destaque para a produção jesuíta e o estilo barroco.'),
(197, 53, 'Romantismo e Realismo/Naturalismo', 2, 'em_andamento', 'Análise das características do Romantismo no Brasil (poesia e prosa) e a transição para o Realismo e Naturalismo.'),
(198, 53, 'Modernismo e Contemporâneo', 3, 'em_andamento', 'As fases do Modernismo brasileiro, as vanguardas e a literatura pós-moderna e contemporânea.'),
(199, 54, 'Álgebra e Funções', 1, 'em_andamento', 'Revisão de equações, inequações, sistemas lineares e estudo aprofundado de funções (1º e 2º grau, exponencial, logarítmica).'),
(200, 54, 'Geometria Analítica e Plana', 2, 'em_andamento', 'Cálculo de distâncias, retas, circunferências no plano cartesiano e estudo de áreas e volumes de figuras planas.'),
(201, 54, 'Análise Combinatória e Probabilidade', 3, 'em_andamento', 'Princípios de contagem, arranjos, permutações, combinações e cálculo de probabilidades.'),
(202, 55, 'Introdução à Física e Grandezas', 1, 'em_andamento', 'Definição de física, grandezas físicas, sistemas de unidades e análise dimensional.'),
(203, 55, 'Movimento Retilíneo e Leis de Newton', 2, 'em_andamento', 'Estudo do movimento em linha reta, aceleração, velocidade e as três leis de Newton.'),
(204, 55, 'Ondulatória e Acústica', 3, 'em_andamento', 'Fenômenos ondulatórios (reflexão, refração, difração) e as características do som.'),
(205, 56, 'Classicismo e Arcadismo', 1, 'em_andamento', 'Estudo das características do Classicismo em Portugal e do Arcadismo no Brasil e em Portugal.'),
(206, 56, 'Simbolismo e Parnasianismo', 2, 'em_andamento', 'A poesia simbolista, com sua musicalidade e misticismo, e o rigor formal do Parnasianismo.'),
(207, 56, 'Gerações Modernistas', 3, 'em_andamento', 'As três gerações do Modernismo brasileiro (poesia, prosa de 30 e 45) e seus principais autores.'),
(208, 57, 'Teoria dos Conjuntos e Números', 1, 'em_andamento', 'Revisão de conjuntos numéricos, operações, intervalos e diagramas de Venn.'),
(209, 57, 'Matrizes e Determinantes', 2, 'em_andamento', 'Cálculo com matrizes, determinantes e sua aplicação na resolução de sistemas lineares.'),
(210, 57, 'Geometria Espacial', 3, 'em_andamento', 'Áreas e volumes de sólidos geométricos: prisma, pirâmide, cilindro, cone e esfera.'),
(211, 58, 'Revisão Rápida de Mecânica', 1, 'em_andamento', 'Tópicos essenciais de mecânica para o ENEM: movimento uniforme, energia e força-peso.'),
(212, 58, 'Calor e Energia no ENEM', 2, 'em_andamento', 'Termometria, calorimetria e transformações de energia em máquinas térmicas.'),
(213, 58, 'Circuitos Elétricos e Eletrodinâmica', 3, 'em_andamento', 'Leis de Ohm, potência elétrica e associação de resistores para o ENEM.'),
(214, 59, 'Linguagem e Funções da Linguagem no ENEM', 1, 'em_andamento', 'Análise das funções da linguagem e sua aplicação em textos variados do ENEM.'),
(215, 59, 'Interpretação Textual Avançada', 2, 'em_andamento', 'Estratégias para compreender textos complexos, inferir informações e identificar teses.'),
(216, 59, 'Variação Linguística e Gêneros Textuais', 3, 'em_andamento', 'Estudo da diversidade linguística e os diferentes gêneros textuais cobrados no ENEM.'),
(217, 60, 'Estatística Básica para o ENEM', 1, 'em_andamento', 'Média, moda, mediana, gráficos e tabelas para interpretar dados do ENEM.'),
(218, 60, 'Porcentagem e Razão e Proporção', 2, 'em_andamento', 'Cálculos de porcentagem, juros simples e compostos, e problemas de razão e proporção.'),
(219, 60, 'Geometria Plana Essencial para o ENEM', 3, 'em_andamento', 'Cálculo de áreas de figuras planas (triângulos, quadriláteros, círculos) e conceitos básicos de ângulos.');

-- --------------------------------------------------------

--
-- Estrutura para tabela `disciplinas`
--

CREATE TABLE `disciplinas` (
  `id` int NOT NULL,
  `nome` varchar(100) NOT NULL,
  `codigo` varchar(20) DEFAULT NULL,
  `descricao` text,
  `status` enum('ativa','concluída','cancelada') DEFAULT 'ativa'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `disciplinas`
--

INSERT INTO `disciplinas` (`id`, `nome`, `codigo`, `descricao`, `status`) VALUES
(20, 'Biologia Celular', 'BIO2025', 'Estudo das estruturas e funções das células, seus componentes e mecanismos celulares fundamentais.', 'ativa'),
(21, 'Física Mecânica', 'FIS2025', 'Análise dos movimentos, forças e leis que regem os corpos em interação.', 'ativa'),
(22, 'Literatura Brasileira', 'LIT2025', 'Leitura e interpretação de obras e autores clássicos da literatura nacional.', 'ativa'),
(23, 'História Mundial', 'HIS2025', 'Estudo aprofundado dos principais eventos e civilizações da história mundial, desde a antiguidade até a contemporaneidade.', 'ativa'),
(24, 'Química Orgânica', 'QUO2025', 'Análise da estrutura, propriedades, reações e síntese de compostos orgânicos.', 'ativa'),
(25, 'Desenho Artístico', 'DES2025', 'Desenvolvimento de técnicas de desenho à mão livre, proporção, perspectiva e sombreamento.', 'ativa'),
(26, 'Física para Vestibulares', 'FISVEST25', 'Estudo aprofundado dos principais tópicos de física cobrados nos exames vestibulares e ENEM, com resolução de exercícios.', 'ativa'),
(27, 'Literatura Portuguesa e Brasileira', 'LITPREV25', 'Análise das escolas literárias, autores e obras essenciais para os exames de vestibular, com foco em interpretação textual.', 'ativa'),
(28, 'Matemática Avançada para Vestibulares', 'MATAVAN25', 'Conteúdo de matemática avançada para vestibulares, incluindo cálculo, geometria analítica e análise combinatória.', 'ativa');

-- --------------------------------------------------------

--
-- Estrutura para tabela `enderecos`
--

CREATE TABLE `enderecos` (
  `id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `cep` varchar(9) DEFAULT NULL,
  `rua` varchar(255) DEFAULT NULL,
  `numero` varchar(10) DEFAULT NULL,
  `complemento` varchar(50) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` varchar(2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `enderecos`
--

INSERT INTO `enderecos` (`id`, `usuario_id`, `cep`, `rua`, `numero`, `complemento`, `bairro`, `cidade`, `estado`) VALUES
(4, 8, '13973043', 'Rua Nhambiquara de Tupã', '444', '222', 'Jardim Macucos', 'Itapira', 'SP'),
(6, 3, '13973043', 'Rua Nhambiquara de Tupã', '444', '', 'Jardim Macucos', 'Itapira', 'SP');

-- --------------------------------------------------------

-- --------------------------------------------------------

--
-- Estrutura para tabela `planos`
--

CREATE TABLE `planos` (
  `id` int NOT NULL,
  `turma_id` int NOT NULL,
  `disciplina_id` int NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descricao` text,
  `status` enum('em_andamento','concluido','cancelado') DEFAULT 'em_andamento',
  `criado_por` int NOT NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `data_inicio` date DEFAULT NULL,
  `data_fim` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `planos`
--

INSERT INTO `planos` (`id`, `turma_id`, `disciplina_id`, `titulo`, `descricao`, `status`, `criado_por`, `criado_em`, `data_inicio`, `data_fim`) VALUES
(38, 22, 20, 'Plano Biologia Celular', 'Plano completo da disciplina de Biologia Celular com foco em vestibulares.', 'cancelado', 1, '2025-06-16 14:00:36', '2025-07-01', '2025-12-01'),
(39, 22, 21, 'Plano Física Mecânica', 'Plano com foco em cinemática, dinâmica e leis de Newton.', 'cancelado', 1, '2025-06-16 14:00:36', '2025-07-01', '2025-12-01'),
(40, 22, 22, 'Plano Literatura Brasileira', 'Exploração de períodos literários, autores e obras selecionadas.', 'cancelado', 1, '2025-06-16 14:00:36', '2025-07-01', '2025-12-01'),
(41, 23, 20, 'Plano Biologia Celular Intensivo', 'Versão compacta do curso de Biologia Celular com foco em revisão rápida.', 'em_andamento', 1, '2025-06-16 14:00:36', '2025-08-01', '2025-11-30'),
(42, 23, 21, 'Plano Física Mecânica Intensivo', 'Curso concentrado de revisão de cinemática e leis de Newton.', 'em_andamento', 1, '2025-06-16 14:00:36', '2025-08-01', '2025-11-30'),
(43, 24, 23, 'Plano Anual História Mundial', 'Currículo completo de história mundial para o ano letivo de 2025, abordando os principais períodos e civilizações.', 'cancelado', 1, '2025-06-16 14:12:03', '2025-07-15', '2025-12-15'),
(44, 24, 24, 'Plano Anual Química Orgânica', 'Estudo aprofundado dos fundamentos da química orgânica, incluindo nomenclatura, reações e mecanismos.', 'cancelado', 1, '2025-06-16 14:12:03', '2025-07-15', '2025-12-15'),
(45, 24, 25, 'Plano Anual Desenho Artístico', 'Desenvolvimento de habilidades em desenho, desde conceitos básicos até técnicas avançadas de representação.', 'cancelado', 1, '2025-06-16 14:12:03', '2025-07-15', '2025-12-15'),
(46, 25, 23, 'Plano Intensivo História Mundial', 'Curso intensivo de revisão dos principais tópicos de história mundial, com foco em exames.', 'em_andamento', 1, '2025-06-16 14:12:03', '2025-08-01', '2025-11-30'),
(47, 25, 24, 'Plano Intensivo Química Orgânica', 'Estudo condensado dos conceitos essenciais da química orgânica para uma revisão rápida e eficaz.', 'em_andamento', 1, '2025-06-16 14:12:03', '2025-08-01', '2025-11-30'),
(48, 25, 25, 'Plano Intensivo Desenho Artístico', 'Workshop intensivo para aprimoramento rápido de técnicas de desenho e composição.', 'em_andamento', 1, '2025-06-16 14:12:03', '2025-08-01', '2025-11-30'),
(49, 26, 23, 'Plano de Férias História Mundial', 'Panorama geral da história mundial em um formato condensado para o período de férias.', 'concluido', 1, '2025-06-16 14:12:03', '2025-07-01', '2025-07-31'),
(50, 26, 24, 'Plano de Férias Química Orgânica', 'Introdução à química orgânica, focando nos grupos funcionais e reações básicas.', 'concluido', 1, '2025-06-16 14:12:03', '2025-07-01', '2025-07-31'),
(51, 26, 25, 'Plano de Férias Desenho Artístico', 'Atividades práticas de desenho para iniciantes e entusiastas durante as férias.', 'concluido', 1, '2025-06-16 14:12:03', '2025-07-01', '2025-07-31'),
(52, 27, 26, 'Plano de Física Intensivo para Vestibulares', 'Preparação intensiva para os principais exames vestibulares e ENEM, com foco em resolução de problemas e tópicos de alta incidência.', 'em_andamento', 1, '2025-06-16 14:14:43', '2025-08-01', '2025-12-20'),
(53, 27, 27, 'Plano de Literatura Intensivo para Vestibulares', 'Abordagem aprofundada das escolas literárias e obras obrigatórias, com análise crítica e interpretação de textos literários.', 'em_andamento', 1, '2025-06-16 14:14:43', '2025-08-01', '2025-12-20'),
(54, 27, 28, 'Plano de Matemática Intensiva para Vestibulares', 'Revisão e aprofundamento dos conteúdos de matemática para os vestibulares mais exigentes, com exercícios complexos e dicas de prova.', 'em_andamento', 1, '2025-06-16 14:14:43', '2025-08-01', '2025-12-20'),
(55, 28, 26, 'Plano de Física Semiextensivo para Vestibulares', 'Estudo gradual e aprofundado dos conceitos de física, com tempo para assimilação e exercícios variados.', 'em_andamento', 1, '2025-06-16 14:14:43', '2025-07-01', '2025-11-30'),
(56, 28, 27, 'Plano de Literatura Semiextensivo para Vestibulares', 'Construção da base literária com análise de textos e compreensão de estilos, ideal para quem precisa de mais tempo.', 'em_andamento', 1, '2025-06-16 14:14:43', '2025-07-01', '2025-11-30'),
(57, 28, 28, 'Plano de Matemática Semiextensiva para Vestibulares', 'Desenvolvimento gradual do raciocínio lógico e matemático, abrangendo todos os tópicos de forma consistente.', 'em_andamento', 1, '2025-06-16 14:14:43', '2025-07-01', '2025-11-30'),
(58, 29, 26, 'Plano de Física ENEM Turbo', 'Aulas rápidas e focadas nos tópicos de física mais relevantes para o ENEM, com resolução de questões estilo prova.', 'em_andamento', 1, '2025-06-16 14:14:43', '2025-09-01', '2025-10-30'),
(59, 29, 27, 'Plano de Literatura ENEM Turbo', 'Estratégias de leitura e interpretação para as questões de literatura do ENEM, com foco em obras contemporâneas e regionalismo.', 'em_andamento', 1, '2025-06-16 14:14:43', '2025-09-01', '2025-10-30'),
(60, 29, 28, 'Plano de Matemática ENEM Turbo', 'Resolução de problemas de matemática do ENEM em formato de desafio, com foco em estatística, porcentagem e geometria básica.', 'em_andamento', 1, '2025-06-16 14:14:43', '2025-09-01', '2025-10-30');

-- --------------------------------------------------------

--
-- Estrutura para tabela `topicos`
--

CREATE TABLE `topicos` (
  `id` int NOT NULL,
  `capitulo_id` int NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descricao` text NOT NULL,
  `ordem` int DEFAULT NULL,
  `status` enum('em_andamento','concluido','cancelado') DEFAULT 'em_andamento',
  `observacoes` text,
  `data_criacao` datetime DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `topicos`
--

INSERT INTO `topicos` (`id`, `capitulo_id`, `titulo`, `descricao`, `ordem`, `status`, `observacoes`, `data_criacao`, `data_atualizacao`) VALUES
(171, 148, 'Teoria Celular', 'História da teoria celular e seus postulados fundamentais.', 1, 'cancelado', '', '2025-06-16 14:00:36', '2025-06-16 12:34:21'),
(172, 148, 'Tipos de Células', 'Diferenças entre células procariontes e eucariontes com exemplos.', 2, 'cancelado', '', '2025-06-16 14:00:36', '2025-06-16 12:34:21'),
(173, 149, 'Núcleo Celular', 'Funções do núcleo, cromatina, nucléolo e carioteca.', 1, 'cancelado', '', '2025-06-16 14:00:36', '2025-06-16 12:34:21'),
(174, 149, 'Mitocôndrias e Cloroplastos', 'Funções energéticas e o papel na respiração e fotossíntese.', 2, 'cancelado', '', '2025-06-16 14:00:36', '2025-06-16 12:34:21'),
(175, 149, 'Retículo Endoplasmático', 'Diferenças entre RER e REL, e suas funções.', 3, 'cancelado', '', '2025-06-16 14:00:36', '2025-06-16 12:34:21'),
(176, 150, 'Mitose', 'Fases da mitose e sua relevância na multiplicação celular.', 1, 'cancelado', '', '2025-06-16 14:00:36', '2025-06-16 12:34:21'),
(177, 150, 'Meiose', 'Fases e importância na variabilidade genética.', 2, 'cancelado', '', '2025-06-16 14:00:36', '2025-06-16 12:34:21'),
(178, 151, 'Fases do Ciclo Celular', 'G1, S, G2 e mitose: controle e checkpoints.', 1, 'cancelado', '', '2025-06-16 14:00:36', '2025-06-16 12:34:21'),
(179, 152, 'Movimento Uniforme', 'Definição, equações e gráficos do MU.', 1, 'cancelado', '', '2025-06-16 14:00:36', '2025-06-16 12:34:21'),
(180, 152, 'Movimento Uniformemente Variado', 'Equações do MUV, aceleração e gráficos.', 2, 'cancelado', '', '2025-06-16 14:00:36', '2025-06-16 12:34:21'),
(181, 153, '1ª Lei de Newton', 'Princípio da inércia e exemplos do cotidiano.', 1, 'cancelado', '', '2025-06-16 14:00:36', '2025-06-16 12:34:21'),
(182, 153, '2ª Lei de Newton', 'Relação entre força, massa e aceleração: F = ma.', 2, 'cancelado', '', '2025-06-16 14:00:36', '2025-06-16 12:34:21'),
(183, 154, 'Trabalho de uma Força', 'Cálculo do trabalho e suas unidades.', 1, 'cancelado', '', '2025-06-16 14:00:36', '2025-06-16 12:34:21'),
(184, 154, 'Energia Mecânica', 'Energia cinética, potencial e conservação.', 2, 'cancelado', '', '2025-06-16 14:00:36', '2025-06-16 12:34:21'),
(185, 155, 'José de Alencar', 'Obras e contribuições ao romance indianista.', 1, 'cancelado', '', '2025-06-16 14:00:36', '2025-06-16 12:34:21'),
(186, 155, 'Gonçalves Dias', 'Poesia lírica e indianismo romântico.', 2, 'cancelado', '', '2025-06-16 14:00:36', '2025-06-16 12:34:21'),
(187, 156, 'Machado de Assis', 'Ironia e crítica social em Dom Casmurro.', 1, 'cancelado', '', '2025-06-16 14:00:36', '2025-06-16 12:34:21'),
(188, 156, 'Aluísio Azevedo', 'Naturalismo em O Cortiço.', 2, 'cancelado', '', '2025-06-16 14:00:36', '2025-06-16 12:34:21'),
(189, 157, 'Oswald de Andrade', 'Manifestos e ruptura com o passado.', 1, 'cancelado', '', '2025-06-16 14:00:36', '2025-06-16 12:34:21'),
(190, 157, 'Mário de Andrade', 'Macunaíma e a identidade nacional.', 2, 'cancelado', '', '2025-06-16 14:00:36', '2025-06-16 12:34:21'),
(191, 150, 'Importância da Meiose na Reprodução', 'Como a meiose garante variabilidade genética e hereditariedade.', 3, 'cancelado', '', '2025-06-16 14:00:36', '2025-06-16 12:34:21'),
(192, 151, 'Checkpoints Celulares', 'Etapas de verificação que evitam erros na divisão celular.', 2, 'cancelado', '', '2025-06-16 14:00:36', '2025-06-16 12:34:21'),
(193, 152, 'Lançamento Oblíquo', 'Estudo do movimento bidimensional em ângulos.', 3, 'cancelado', '', '2025-06-16 14:00:36', '2025-06-16 12:34:21'),
(194, 153, '3ª Lei de Newton', 'Ação e reação e suas implicações práticas.', 3, 'cancelado', '', '2025-06-16 14:00:36', '2025-06-16 12:34:21'),
(195, 157, 'Poesia Modernista', 'Linguagem livre, cotidiano e ruptura estética.', 3, 'cancelado', '', '2025-06-16 14:00:36', '2025-06-16 12:34:21'),
(196, 158, 'Função das Organelas', 'Revisão rápida de ribossomos, mitocôndrias, complexo golgiense, etc.', 1, 'em_andamento', '', '2025-06-16 14:00:36', '2025-06-16 14:00:36'),
(197, 159, 'Comparativo Mitose vs Meiose', 'Principais diferenças estruturais e funcionais.', 1, 'em_andamento', '', '2025-06-16 14:00:36', '2025-06-16 14:00:36'),
(198, 160, 'Tabela de Fórmulas', 'Tabela com equações dos principais movimentos.', 1, 'em_andamento', '', '2025-06-16 14:00:36', '2025-06-16 14:00:36'),
(199, 161, 'Exercícios de Força Resultante', 'Problemas resolvidos com ênfase em F=ma.', 1, 'em_andamento', '', '2025-06-16 14:00:36', '2025-06-16 14:00:36'),
(200, 162, 'Estrutura da Membrana Plasmática', 'Modelo do mosaico fluido e os principais componentes lipídicos e proteicos.', 1, 'em_andamento', '', '2025-06-16 14:00:36', '2025-06-16 14:00:36'),
(201, 162, 'Transporte Passivo', 'Difusão simples, facilitada e osmose.', 2, 'em_andamento', '', '2025-06-16 14:00:36', '2025-06-16 14:00:36'),
(202, 162, 'Transporte Ativo', 'Bomba de sódio e potássio, transporte em massa.', 3, 'em_andamento', '', '2025-06-16 14:00:36', '2025-06-16 14:00:36'),
(203, 163, 'DNA e RNA', 'Estrutura e funções das moléculas de ácidos nucleicos.', 1, 'em_andamento', '', '2025-06-16 14:00:36', '2025-06-16 14:00:36'),
(204, 163, 'Síntese de Proteínas', 'Processo de transcrição e tradução nas células.', 2, 'em_andamento', '', '2025-06-16 14:00:36', '2025-06-16 14:00:36'),
(205, 164, 'Lei da Gravitação Universal', 'Formulação da força gravitacional entre corpos com massa.', 1, 'em_andamento', '', '2025-06-16 14:00:36', '2025-06-16 14:00:36'),
(206, 164, 'Satélites e Órbitas', 'Conceitos de velocidade orbital e energia em órbita.', 2, 'em_andamento', '', '2025-06-16 14:00:36', '2025-06-16 14:00:36'),
(207, 165, 'Definição de Quantidade de Movimento', 'Cálculo do momento linear e sua importância física.', 1, 'em_andamento', '', '2025-06-16 14:00:36', '2025-06-16 14:00:36'),
(208, 165, 'Princípio da Conservação do Momento Linear', 'Análise de colisões elásticas e inelásticas.', 2, 'em_andamento', '', '2025-06-16 14:00:36', '2025-06-16 14:00:36'),
(209, 165, 'Impulso de uma Força', 'Definição de impulso e sua relação com a variação de momento.', 3, 'em_andamento', '', '2025-06-16 14:00:36', '2025-06-16 14:00:36'),
(210, 166, 'Democracia Ateniense', 'Funcionamento da democracia direta em Atenas, o papel dos cidadãos e a exclusão de grupos sociais.', 1, 'cancelado', '', '2025-06-16 14:12:03', '2025-06-16 12:35:05'),
(211, 166, 'Império Romano e Legado', 'Expansão do Império Romano, suas instituições, direito, engenharia e a influência cultural duradoura.', 2, 'cancelado', '', '2025-06-16 14:12:03', '2025-06-16 12:35:05'),
(212, 166, 'Arte e Arquitetura Grega', 'Estilos arquitetônicos (dórico, jônico, coríntio), esculturas e a busca pela perfeição estética na Grécia Antiga.', 3, 'cancelado', '', '2025-06-16 14:12:03', '2025-06-16 12:35:05'),
(213, 166, 'Filosofia Clássica', 'Pensadores como Sócrates, Platão e Aristóteles, suas ideias e o impacto no pensamento ocidental.', 4, 'cancelado', '', '2025-06-16 14:12:03', '2025-06-16 12:35:05'),
(214, 167, 'Estrutura Social Feudal', 'A hierarquia feudal, o papel do rei, nobreza, clero e camponeses.', 1, 'cancelado', '', '2025-06-16 14:12:03', '2025-06-16 12:35:05'),
(215, 167, 'O Poder da Igreja Medieval', 'A influência da Igreja Católica na sociedade, cultura e política da Idade Média.', 2, 'cancelado', '', '2025-06-16 14:12:03', '2025-06-16 12:35:05'),
(216, 167, 'As Cruzadas', 'Motivações, eventos e consequências das expedições militares religiosas à Terra Santa.', 3, 'cancelado', '', '2025-06-16 14:12:03', '2025-06-16 12:35:05'),
(217, 167, 'Renascimento Urbano e Comercial', 'O ressurgimento das cidades, o crescimento do comércio e a formação da burguesia.', 4, 'cancelado', '', '2025-06-16 14:12:03', '2025-06-16 12:35:05'),
(218, 168, 'Revolução Industrial', 'Inovações tecnológicas, mudanças sociais e econômicas do século XVIII e XIX.', 1, 'cancelado', '', '2025-06-16 14:12:03', '2025-06-16 12:35:05'),
(219, 168, 'Revolução Francesa', 'Causas, fases e consequências da Revolução Francesa, e seus ideais de liberdade, igualdade e fraternidade.', 2, 'cancelado', '', '2025-06-16 14:12:03', '2025-06-16 12:35:05'),
(220, 168, 'Imperialismo e Colonialismo', 'A expansão das potências europeias no século XIX e a partilha da África e Ásia.', 3, 'cancelado', '', '2025-06-16 14:12:03', '2025-06-16 12:35:05'),
(221, 168, 'Primeira Guerra Mundial', 'As causas da Grande Guerra, os blocos de alianças e o impacto global do conflito.', 4, 'cancelado', '', '2025-06-16 14:12:03', '2025-06-16 12:35:05'),
(222, 169, 'Carbono: Características e Ligações', 'Propriedades do carbono que permitem a formação de uma vasta gama de compostos orgânicos, incluindo a tetravalência e a capacidade de formar cadeias.', 1, 'cancelado', '', '2025-06-16 14:12:03', '2025-06-16 12:35:05'),
(223, 169, 'Hibridização e Geometria Molecular', 'Como a hibridização de orbitais (sp3, sp2, sp) influencia a geometria das moléculas orgânicas, como cadeias abertas, ramificadas e cíclicas.', 2, 'cancelado', '', '2025-06-16 14:12:03', '2025-06-16 12:35:05'),
(224, 169, 'Classificação das Cadeias Carbônicas', 'Os diferentes tipos de classificação de cadeias carbônicas: aberta, fechada, mista, saturada, insaturada, homogênea e heterogênea.', 3, 'cancelado', '', '2025-06-16 14:12:03', '2025-06-16 12:35:05'),
(225, 169, 'Polaridade de Ligações e Moléculas', 'Determinação da polaridade de ligações covalentes e de moléculas orgânicas, e sua relação com propriedades físicas.', 4, 'cancelado', '', '2025-06-16 14:12:03', '2025-06-16 12:35:05'),
(226, 170, 'Nomenclatura de Hidrocarbonetos', 'Regras IUPAC para dar nome a alcanos, alcenos, alcinos, ciclanos, ciclenos e aromáticos.', 1, 'cancelado', '', '2025-06-16 14:12:03', '2025-06-16 12:35:05'),
(227, 170, 'Álcoois e Fenóis', 'Estrutura, nomenclatura, propriedades físicas e usos de álcoois e fenóis.', 2, 'cancelado', '', '2025-06-16 14:12:03', '2025-06-16 12:35:05'),
(228, 170, 'Aldeídos e Cetonas', 'Características dos grupos carbonila em aldeídos e cetonas, e suas principais aplicações.', 3, 'cancelado', '', '2025-06-16 14:12:03', '2025-06-16 12:35:05'),
(229, 170, 'Ácidos Carboxílicos e Ésteres', 'Estrutura e propriedades de ácidos carboxílicos e a formação de ésteres, importantes em fragrâncias e sabores.', 4, 'cancelado', '', '2025-06-16 14:12:03', '2025-06-16 12:35:05'),
(230, 171, 'Reações de Adição', 'Mecanismos de adição em alcenos e alcinos, como hidrogenação, halogenação e hidratação.', 1, 'cancelado', '', '2025-06-16 14:12:03', '2025-06-16 12:35:05'),
(231, 171, 'Reações de Substituição', 'Substituição de hidrogênios por outros grupos em alcanos e anéis aromáticos.', 2, 'cancelado', '', '2025-06-16 14:12:03', '2025-06-16 12:35:05'),
(232, 171, 'Reações de Eliminação', 'Formação de duplas ou triplas ligações pela remoção de átomos, como desidratação de álcoois.', 3, 'cancelado', '', '2025-06-16 14:12:03', '2025-06-16 12:35:05'),
(233, 171, 'Reações de Oxidação e Redução', 'Oxidação branda e enérgica, e reações de redução em compostos orgânicos.', 4, 'cancelado', '', '2025-06-16 14:12:03', '2025-06-16 12:35:05'),
(234, 172, 'Explorando Materiais de Desenho', 'Tipos de lápis (H, HB, B), carvão, grafite, borrachas e papéis, e como escolher o mais adequado para cada técnica.', 1, 'cancelado', '', '2025-06-16 14:12:03', '2025-06-16 12:35:05'),
(235, 172, 'Exercícios de Aquecimento e Controle de Traço', 'Séries de exercícios para soltar a mão, melhorar a firmeza do traço e a coordenação motora fina.', 2, 'cancelado', '', '2025-06-16 14:12:03', '2025-06-16 12:35:05'),
(236, 172, 'Desenhando Formas Geométricas Simples', 'Prática na construção de cubos, esferas, cilindros e cones em diferentes ângulos para entender a estrutura dos objetos.', 3, 'cancelado', '', '2025-06-16 14:12:03', '2025-06-16 12:35:05'),
(237, 172, 'Introdução à Observação e Esboço', 'Técnicas para observar e simplificar objetos complexos em formas básicas para o esboço inicial.', 4, 'cancelado', '', '2025-06-16 14:12:03', '2025-06-16 12:35:05'),
(238, 173, 'Perspectiva com Um Ponto de Fuga', 'Criação de profundidade em desenhos utilizando um único ponto de fuga no horizonte para objetos e cenários.', 1, 'cancelado', '', '2025-06-16 14:12:03', '2025-06-16 12:35:05'),
(239, 173, 'Perspectiva com Dois Pontos de Fuga', 'Aplicação de dois pontos de fuga para desenhar objetos e arquiteturas com mais realismo e volume.', 2, 'cancelado', '', '2025-06-16 14:12:03', '2025-06-16 12:35:05'),
(240, 173, 'Regra dos Terços e Ponto Focal', 'Utilização da regra dos terços para compor desenhos de forma equilibrada e direcionar o olhar para o ponto focal.', 3, 'cancelado', '', '2025-06-16 14:12:03', '2025-06-16 12:35:05'),
(241, 173, 'Enquadramento e Plano de Fundo', 'Como escolher o melhor enquadramento para a cena e integrar o plano de fundo com o objeto principal.', 4, 'cancelado', '', '2025-06-16 14:12:03', '2025-06-16 12:35:05'),
(242, 174, 'Círculo Cromático e Valores de Cinza', 'Estudo do círculo cromático para entender as relações entre cores e a escala de valores de cinza para sombreamento.', 1, 'cancelado', '', '2025-06-16 14:12:03', '2025-06-16 12:35:05'),
(243, 174, 'Técnicas de Sombreamento com Lápis', 'Hachuras, esfumado, pontilhismo e outras técnicas para criar tons e volumes com lápis.', 2, 'cancelado', '', '2025-06-16 14:12:03', '2025-06-16 12:35:05'),
(244, 174, 'Luz e Sombra em Objetos Simples', 'Aplicação de luz e sombra em formas básicas para simular volume e profundidade.', 3, 'cancelado', '', '2025-06-16 14:12:03', '2025-06-16 12:35:05'),
(245, 174, 'Texturas e Superfícies', 'Representação de diferentes texturas (madeira, tecido, metal) usando técnicas de traço e sombreamento.', 4, 'cancelado', '', '2025-06-16 14:12:03', '2025-06-16 12:35:05'),
(246, 175, 'Egito Antigo: Sociedade e Cultura', 'Organização social, religião, rituais funerários e as principais realizações arquitetônicas do Antigo Egito.', 1, 'em_andamento', '', '2025-06-16 14:12:03', '2025-06-16 14:12:03'),
(247, 175, 'Mesopotâmia: Berço das Civilizações', 'O desenvolvimento da escrita cuneiforme, cidades-estado, códigos de leis (Código de Hamurabi) na Mesopotâmia.', 2, 'em_andamento', '', '2025-06-16 14:12:03', '2025-06-16 14:12:03'),
(248, 175, 'Civilização do Vale do Indo', 'Organização urbana, sistemas de saneamento e o mistério do colapso da civilização Harappana.', 3, 'em_andamento', '', '2025-06-16 14:12:03', '2025-06-16 14:12:03'),
(249, 175, 'Primeiros Impérios Orientais', 'Formação e características dos primeiros impérios como o Acádio, Babilônico e Assírio.', 4, 'em_andamento', '', '2025-06-16 14:12:03', '2025-06-16 14:12:03'),
(250, 176, 'O Império Persa e sua Organização', 'Expansão e a administração centralizada do Império Persa, incluindo a rede de estradas e o correio.', 1, 'em_andamento', '', '2025-06-16 14:12:03', '2025-06-16 14:12:03'),
(251, 176, 'O Império Mongol: Gengis Khan e a Conquista', 'A formação do vasto Império Mongol sob Gengis Khan e seu impacto na Ásia e Europa.', 2, 'em_andamento', '', '2025-06-16 14:12:03', '2025-06-16 14:12:03'),
(252, 176, 'O Império Bizantino e seu Legado', 'A continuidade do Império Romano no Oriente, sua cultura, arte e a importância de Constantinopla.', 3, 'em_andamento', '', '2025-06-16 14:12:03', '2025-06-16 14:12:03'),
(253, 176, 'Califados Islâmicos e a Idade de Ouro', 'A expansão do Islã e o florescimento cultural e científico dos califados medievais.', 4, 'em_andamento', '', '2025-06-16 14:12:03', '2025-06-16 14:12:03'),
(254, 177, 'Causas da Primeira Guerra Mundial', 'Nacionalismos, imperialismo, corrida armamentista e a política de alianças que levaram ao conflito.', 1, 'em_andamento', '', '2025-06-16 14:12:03', '2025-06-16 14:12:03'),
(255, 177, 'O Fascismo e Nazismo', 'O surgimento e as características dos regimes totalitários na Itália e Alemanha no entreguerras.', 2, 'em_andamento', '', '2025-06-16 14:12:03', '2025-06-16 14:12:03'),
(256, 177, 'Segunda Guerra Mundial: Eixos e Aliados', 'Os principais eventos, batalhas e o desdobramento da Segunda Guerra Mundial.', 3, 'em_andamento', '', '2025-06-16 14:12:03', '2025-06-16 14:12:03'),
(257, 177, 'Guerra Fria e a Bipolaridade', 'A polarização mundial entre EUA e URSS, a corrida armamentista e espacial, e os conflitos indiretos.', 4, 'em_andamento', '', '2025-06-16 14:12:03', '2025-06-16 14:12:03'),
(258, 178, 'Álcoois: Propriedades e Reações', 'Revisão das características dos álcoois, suas propriedades físicas e químicas, e reações típicas como oxidação e desidratação.', 1, 'em_andamento', '', '2025-06-16 14:12:03', '2025-06-16 14:12:03'),
(259, 178, 'Aldeídos e Cetonas: Reações de Carbonila', 'Foco nas reações de adição nucleofílica em grupos carbonila e suas aplicações sintéticas.', 2, 'em_andamento', '', '2025-06-16 14:12:03', '2025-06-16 14:12:03'),
(260, 178, 'Ácidos Carboxílicos e Derivados', 'Revisão da acidez dos ácidos carboxílicos e a formação de seus derivados: ésteres, amidas, anidridos e cloretos de acila.', 3, 'em_andamento', '', '2025-06-16 14:12:03', '2025-06-16 14:12:03'),
(261, 178, 'Aminas e Amidas: Bases Orgânicas', 'Estudo das propriedades básicas das aminas e a formação de amidas, com ênfase em sua importância biológica e industrial.', 4, 'em_andamento', '', '2025-06-16 14:12:03', '2025-06-16 14:12:03'),
(262, 179, 'Nomenclatura de Compostos Aromáticos', 'Regras para nomear benzenos substituídos e compostos policíclicos aromáticos.', 1, 'em_andamento', '', '2025-06-16 14:12:03', '2025-06-16 14:12:03'),
(263, 179, 'Regras Prioritárias para Nomenclatura', 'Como determinar a função principal em compostos com múltiplos grupos funcionais para a nomenclatura correta.', 2, 'em_andamento', '', '2025-06-16 14:12:03', '2025-06-16 14:12:03'),
(264, 179, 'Nomenclatura de Compostos Heterocíclicos', 'Introdução à nomenclatura de anéis contendo átomos diferentes de carbono (N, O, S).', 3, 'em_andamento', '', '2025-06-16 14:12:03', '2025-06-16 14:12:03'),
(265, 179, 'Exercícios Resolvidos de Nomenclatura', 'Aplicação das regras de nomenclatura em uma série de exemplos e problemas, com foco nos erros comuns.', 4, 'em_andamento', '', '2025-06-16 14:12:03', '2025-06-16 14:12:03'),
(266, 180, 'Isomeria Plana', 'Tipos de isomeria plana: de cadeia, de posição, de função, metameria e tautomeria, com exemplos detalhados.', 1, 'em_andamento', '', '2025-06-16 14:12:03', '2025-06-16 14:12:03'),
(267, 180, 'Isomeria Geométrica (Cis-Trans)', 'Critérios para a ocorrência da isomeria geométrica em alcenos e compostos cíclicos, e a designação cis/trans.', 2, 'em_andamento', '', '2025-06-16 14:12:03', '2025-06-16 14:12:03'),
(268, 180, 'Quiralidade e Enantiômeros', 'Conceito de quiralidade, centros quirais, enantiômeros e a notação R/S para estereocentros.', 3, 'em_andamento', '', '2025-06-16 14:12:03', '2025-06-16 14:12:03'),
(269, 180, 'Misturas Racêmicas e Atividade Óptica', 'Propriedades de misturas racêmicas e a influência da quiralidade na atividade óptica de compostos.', 4, 'em_andamento', '', '2025-06-16 14:12:03', '2025-06-16 14:12:03'),
(270, 181, 'Desenho Gestual e Linha de Ação', 'Técnicas de desenho gestual para capturar o movimento e a energia de figuras de forma rápida e expressiva.', 1, 'em_andamento', '', '2025-06-16 14:12:03', '2025-06-16 14:12:03'),
(271, 181, 'Desenho de Contorno Cego', 'Exercícios para aprimorar a coordenação olho-mão e a observação detalhada através do desenho sem olhar para o papel.', 2, 'em_andamento', '', '2025-06-16 14:12:03', '2025-06-16 14:12:03'),
(272, 181, 'Esboços Rápidos de Cenários Urbanos', 'Prática em desenhar cenas urbanas rapidamente, capturando a atmosfera e os elementos principais.', 3, 'em_andamento', '', '2025-06-16 14:12:03', '2025-06-16 14:12:03'),
(273, 181, 'Doodles e Desenho Livre', 'Estimular a criatividade e a espontaneidade através de desenhos livres e doodles.', 4, 'em_andamento', '', '2025-06-16 14:12:03', '2025-06-16 14:12:03'),
(274, 182, 'Proporções Humanas Básicas', 'Estudo das proporções do corpo humano para desenhar figuras mais realistas, utilizando o \"cabeça\" como unidade de medida.', 1, 'em_andamento', '', '2025-06-16 14:12:03', '2025-06-16 14:12:03'),
(275, 182, 'Proporções de Animais e Objetos', 'Análise das proporções em diferentes animais e objetos para desenhá-los de forma precisa.', 2, 'em_andamento', '', '2025-06-16 14:12:03', '2025-06-16 14:12:03'),
(276, 182, 'Utilização de Grades para Proporção', 'Técnicas de gradeamento para transferir e escalar desenhos mantendo as proporções corretas.', 3, 'em_andamento', '', '2025-06-16 14:12:03', '2025-06-16 14:12:03'),
(277, 182, 'Desenho de Referência e Estudo', 'Como usar fotos e outros desenhos como referência para estudo e aprimoramento das proporções.', 4, 'em_andamento', '', '2025-06-16 14:12:03', '2025-06-16 14:12:03'),
(278, 183, 'Noções de Teoria das Cores Aplicada ao Desenho', 'Introdução às cores primárias, secundárias, terciárias, cores quentes e frias, e como elas interagem no desenho.', 1, 'em_andamento', '', '2025-06-16 14:12:03', '2025-06-16 14:12:03'),
(279, 183, 'Técnicas de Lápis de Cor', 'Aplicação de camadas, mistura de cores, pressão do lápis e uso de diferentes papéis para lápis de cor.', 2, 'em_andamento', '', '2025-06-16 14:12:03', '2025-06-16 14:12:03'),
(280, 183, 'Marcadores e suas Aplicações', 'Como usar marcadores (base álcool, base água) para criar áreas sólidas de cor, gradientes e texturas.', 3, 'em_andamento', '', '2025-06-16 14:12:03', '2025-06-16 14:12:03'),
(281, 183, 'Desenho com Meios Mistos', 'Experimentação com a combinação de diferentes materiais (lápis, marcadores, canetas) em um mesmo desenho para efeitos únicos.', 4, 'em_andamento', '', '2025-06-16 14:12:03', '2025-06-16 14:12:03'),
(282, 184, 'Paleolítico: O Período da Pedra Lascada', 'As primeiras ferramentas, o nomadismo, a caça e coleta, e as pinturas rupestres.', 1, 'concluido', '', '2025-06-16 14:12:03', '2025-06-16 11:47:16'),
(283, 184, 'Neolítico: A Revolução Agrícola', 'O surgimento da agricultura, sedentarismo, domesticação de animais e o início das primeiras aldeias.', 2, 'concluido', '', '2025-06-16 14:12:03', '2025-06-16 11:47:16'),
(284, 184, 'Idade dos Metais: Bronze e Ferro', 'A descoberta e uso dos metais, o desenvolvimento de novas ferramentas e a formação das primeiras cidades e Estados.', 3, 'concluido', '', '2025-06-16 14:12:03', '2025-06-16 11:48:13'),
(285, 184, 'A Arte na Pré-História', 'Análise das manifestações artísticas do período, como as esculturas femininas (Vênus) e as pinturas de cavernas.', 4, 'concluido', '', '2025-06-16 14:12:03', '2025-06-16 11:48:13'),
(286, 185, 'Motivações das Grandes Navegações', 'Fatores econômicos (busca por especiarias), tecnológicos (melhoria de navios e instrumentos de navegação) e religiosos.', 1, 'concluido', '', '2025-06-16 14:12:03', '2025-06-16 11:49:27'),
(287, 185, 'As Rotas Marítimas e os Descobrimentos', 'As principais rotas exploradas por portugueses e espanhóis, e a descoberta de novas terras e povos.', 2, 'concluido', '', '2025-06-16 14:12:03', '2025-06-16 11:49:27'),
(288, 185, 'Tratado de Tordesilhas e suas Implicações', 'A divisão do \"Novo Mundo\" entre Portugal e Espanha e as disputas territoriais.', 3, 'concluido', '', '2025-06-16 14:12:03', '2025-06-16 11:50:32'),
(289, 185, 'Impacto das Navegações no Mundo', 'As consequências culturais, econômicas e sociais das Grandes Navegações para Europa, América, África e Ásia.', 4, 'concluido', '', '2025-06-16 14:12:03', '2025-06-16 11:50:32'),
(290, 186, 'O Período Pré-Colonial no Brasil', 'Os primeiros contatos entre europeus e povos indígenas antes da colonização efetiva.', 1, 'concluido', '', '2025-06-16 14:12:03', '2025-06-16 11:51:26'),
(291, 186, 'Ciclos Econômicos Coloniais', 'O pau-brasil, a cana-de-açúcar, a mineração: seus impactos e a organização da economia colonial.', 2, 'concluido', '', '2025-06-16 14:12:03', '2025-06-16 11:51:26'),
(292, 186, 'Sociedade Açucareira e Escravidão', 'A estrutura social do Brasil colonial, a monocultura e o sistema de escravidão africana.', 3, 'concluido', '', '2025-06-16 14:12:03', '2025-06-16 11:52:03'),
(293, 186, 'Invasões Estrangeiras no Brasil Colônia', 'As tentativas de invasão holandesa e francesa e suas resistências.', 4, 'concluido', '', '2025-06-16 14:12:03', '2025-06-16 11:52:03'),
(294, 187, 'Introdução aos Grupos Funcionais', 'Visão geral dos principais grupos funcionais em química orgânica e como identificá-los.', 1, 'concluido', '', '2025-06-16 14:12:03', '2025-06-16 11:52:59'),
(295, 187, 'A Importância do Carbono na Química Orgânica', 'Porque o carbono é o elemento central da química orgânica e a diversidade de compostos que ele forma.', 2, 'concluido', '', '2025-06-16 14:12:03', '2025-06-16 11:52:59'),
(296, 187, 'Série Homóloga e Isomeria de Cadeia', 'Conceitos de série homóloga e como a isomeria de cadeia afeta as propriedades dos compostos.', 3, 'concluido', '', '2025-06-16 14:12:03', '2025-06-16 11:53:40'),
(297, 187, 'Forças Intermoleculares em Compostos Orgânicos', 'Os tipos de forças intermoleculares (dipolo-dipolo, pontes de hidrogênio, forças de London) e seu efeito em pontos de fusão e ebulição.', 4, 'concluido', '', '2025-06-16 14:12:03', '2025-06-16 11:53:40'),
(298, 188, 'Álcoois: Classificação e Nomenclatura', 'Diferenças entre álcoois primários, secundários e terciários, e as regras IUPAC para sua nomeação.', 1, 'concluido', '', '2025-06-16 14:12:03', '2025-06-16 11:54:23'),
(299, 188, 'Éteres e suas Aplicações', 'Estrutura dos éteres, nomenclatura e suas utilizações como solventes e anestésicos.', 2, 'concluido', '', '2025-06-16 14:12:03', '2025-06-16 11:54:23'),
(300, 188, 'Fenóis e sua Acidez', 'Características dos fenóis e como a presença do grupo hidroxila ligado diretamente ao anel aromático afeta sua acidez.', 3, 'concluido', '', '2025-06-16 14:12:03', '2025-06-16 11:55:44'),
(301, 188, 'Aldeídos e Cetonas: Nomenclatura e Propriedades', 'Como nomear aldeídos e cetonas e as propriedades decorrentes do grupo carbonila.', 4, 'concluido', '', '2025-06-16 14:12:03', '2025-06-16 11:55:44'),
(302, 189, 'Definição e Classificação de Polímeros', 'O que são polímeros, monômeros, e a classificação em polímeros naturais e sintéticos.', 1, 'concluido', '', '2025-06-16 14:12:03', '2025-06-16 11:58:13'),
(303, 189, 'Reações de Polimerização', 'Mecanismos de polimerização por adição e por condensação, com exemplos práticos.', 2, 'concluido', '', '2025-06-16 14:12:03', '2025-06-16 11:58:13'),
(304, 189, 'Plásticos e Reciclagem', 'Os tipos de plásticos mais comuns (PET, PE, PVC, PP, PS) e a importância da reciclagem.', 3, 'concluido', '', '2025-06-16 14:12:03', '2025-06-16 11:59:23'),
(305, 189, 'Borracas e Elastômeros', 'Propriedades das borrachas naturais e sintéticas, e as aplicações dos elastômeros.', 4, 'concluido', '', '2025-06-16 14:12:03', '2025-06-16 11:59:23'),
(306, 190, 'Bloqueio de Imagens: Desenhando o Que Você Vê', 'Técnicas para simplificar e esboçar rapidamente a forma geral de objetos e cenas, sem se prender aos detalhes iniciais.', 1, 'concluido', '', '2025-06-16 14:12:03', '2025-06-16 12:04:57'),
(307, 190, 'Desenho de Memória e Imaginação', 'Exercícios para desenvolver a capacidade de desenhar a partir da memória ou da imaginação, sem referências diretas.', 2, 'concluido', '', '2025-06-16 14:12:03', '2025-06-16 12:04:57'),
(308, 190, 'Explorando Diferentes Estilos de Esboço', 'Variedade de abordagens para o esboço, desde linhas soltas e fluidas até traços mais definidos e angulares.', 3, 'concluido', '', '2025-06-16 14:12:03', '2025-06-16 12:06:00'),
(309, 190, 'Criação de Personagens e Ambientes Simples', 'Técnicas básicas para esboçar personagens e seus ambientes, explorando proporções e poses.', 4, 'concluido', '', '2025-06-16 14:12:03', '2025-06-16 12:06:00'),
(310, 191, 'Exagerando Características Faciais', 'Como identificar e exagerar traços específicos do rosto para criar caricaturas reconhecíveis e divertidas.', 1, 'concluido', '', '2025-06-16 14:12:03', '2025-06-16 12:09:58'),
(311, 191, 'Expressões Faciais em Caricatura', 'Desenho de diferentes expressões (alegria, raiva, surpresa) de forma exagerada para transmitir emoção nas caricaturas.', 2, 'concluido', '', '2025-06-16 14:12:03', '2025-06-16 12:09:58'),
(312, 191, 'Desenho de Corpos em Caricatura', 'Simplificação e exagero das proporções corporais para criar silhuetas cômicas e dinâmicas.', 3, 'concluido', '', '2025-06-16 14:12:03', '2025-06-16 12:11:16'),
(313, 191, 'Desenho de Acessórios e Cenários para Caricaturas', 'Inclusão de elementos que complementam a caricatura, como roupas, objetos e fundos simples.', 4, 'concluido', '', '2025-06-16 14:12:03', '2025-06-16 12:11:16'),
(314, 192, 'Perspectiva Aérea e Linear em Cenários', 'Aplicação da perspectiva para criar a ilusão de profundidade em cenários, utilizando pontos de fuga e diminuição de detalhes com a distância.', 1, 'concluido', '', '2025-06-16 14:12:03', '2025-06-16 12:11:57'),
(315, 192, 'Desenho de Arquitetura e Edifícios', 'Técnicas para desenhar edifícios, ruas e elementos urbanos com precisão e realismo.', 2, 'concluido', '', '2025-06-16 14:12:03', '2025-06-16 12:11:57'),
(316, 192, 'Criação de Ambientes Naturais', 'Desenho de paisagens, árvores, rochas e água, focando na representação de texturas e formas orgânicas.', 3, 'concluido', '', '2025-06-16 14:12:03', '2025-06-16 12:12:57'),
(317, 192, 'Composição de Cenas Completas', 'Integração de personagens, objetos e cenários para criar narrativas visuais complexas e envolventes.', 4, 'concluido', '', '2025-06-16 14:12:03', '2025-06-16 12:12:57'),
(318, 193, 'Cinemática Escalar e Vetorial', 'Diferenciação entre grandezas escalares e vetoriais, estudo de movimento uniforme e uniformemente variado (MUV) e vetores deslocamento, velocidade e aceleração.', 1, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(319, 193, 'Leis de Newton e Forças Fundamentais', 'Aplicação das três leis de Newton, identificação e cálculo das forças de atrito, normal, tração, peso e elástica.', 2, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(320, 193, 'Trabalho, Energia e Potência', 'Cálculo de trabalho realizado por forças, energia cinética, potencial (gravitacional e elástica), mecânica e o conceito de potência.', 3, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(321, 193, 'Impulso e Quantidade de Movimento', 'Princípio do impulso, conservação da quantidade de movimento e análise de colisões.', 4, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(322, 194, 'Termometria e Dilatação Térmica', 'Escalas termométricas (Celsius, Fahrenheit, Kelvin), conversões e o fenômeno da dilatação térmica em sólidos e líquidos.', 1, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(323, 194, 'Calorimetria e Mudanças de Fase', 'Calor sensível e latente, capacidade térmica, calor específico e os processos de fusão, vaporização, solidificação e condensação.', 2, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(324, 194, 'Reflexão e Refração da Luz', 'Leis da reflexão e refração, formação de imagens em espelhos planos e esféricos, e o fenômeno da dispersão da luz.', 3, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(325, 194, 'Lentes Esféricas e Instrumentos Ópticos', 'Tipos de lentes (convergentes e divergentes), formação de imagens e funcionamento de lupa, microscópio e telescópio.', 4, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(326, 195, 'Eletrostática: Carga Elétrica e Força', 'Carga elétrica, princípios de conservação e quantização, processo de eletrização e Lei de Coulomb.', 1, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(327, 195, 'Campo Elétrico e Potencial Elétrico', 'Conceitos de campo e potencial elétrico, linhas de força, superfícies equipotenciais e energia potencial elétrica.', 2, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(328, 195, 'Corrente Elétrica e Leis de Ohm', 'Definição de corrente elétrica, resistência elétrica, resistividade e as Leis de Ohm em circuitos.', 3, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(329, 195, 'Magnetismo e Eletromagnetismo', 'Campos magnéticos gerados por ímãs e correntes elétricas, força magnética sobre cargas e condutores e indução eletromagnética.', 4, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(330, 196, 'A Literatura Jesuíta e o Caráter Pedagógico', 'As cartas de Pero Vaz de Caminha e a produção dos jesuítas, com a catequese indígena e a descrição da terra.', 1, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(331, 196, 'Gregório de Matos e o Barroco Baiano', 'A poesia lírica, satírica e religiosa de Gregório de Matos, principal expoente do Barroco no Brasil.', 2, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(332, 196, 'Padre Antônio Vieira e o Sermão', 'A prosa barroca de Padre Antônio Vieira, seus sermões e a retórica persuasiva.', 3, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(333, 196, 'Características do Barroco Luso-Brasileiro', 'Dualismo, cultismo, conceptismo, e o contexto histórico do Barroco na Península Ibérica e no Brasil.', 4, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(334, 197, 'Romantismo no Brasil: Poesia e Prosa', 'Características gerais do Romantismo, indianismo, ultrarromantismo e condoreirismo na poesia, e o romance urbano e regionalista na prosa.', 1, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(335, 197, 'José de Alencar e o Nacionalismo Romântico', 'A obra de José de Alencar, com foco em Iracema, O Guarani e a idealização da paisagem e do índio.', 2, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(336, 197, 'Machado de Assis: Transição e Realismo', 'A fase inicial de Machado de Assis e o desenvolvimento do Realismo em obras como Memórias Póstumas de Brás Cubas e Dom Casmurro.', 3, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(337, 197, 'Aluísio Azevedo e o Naturalismo', 'O Naturalismo em O Cortiço, com a influência do determinismo e a análise de ambientes e personagens marginalizados.', 4, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(338, 198, 'Semana de Arte Moderna e a Primeira Geração Modernista', 'O marco de 1922, as propostas das vanguardas europeias e os autores da Geração de 22 (Oswald de Andrade, Mário de Andrade, Manuel Bandeira).', 1, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(339, 198, 'Segunda Geração Modernista: Poesia e Prosa de 30', 'Aprofundamento temático e estilístico, com destaque para a poesia de Carlos Drummond de Andrade e Cecília Meireles, e a prosa regionalista (Graciliano Ramos, Jorge Amado).', 2, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(340, 198, 'Terceira Geração Modernista (Geração de 45)', 'O retorno a formas mais tradicionais, com Guimarães Rosa e Clarice Lispector.', 3, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(341, 198, 'Literatura Contemporânea e Tendências Atuais', 'A literatura pós-moderna, poesia concreta, marginal e a produção contemporânea brasileira.', 4, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(342, 199, 'Equações e Inequações Algébricas', 'Resolução de equações de 1º e 2º grau, fracionárias, irracionais e sistemas de inequações.', 1, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(343, 199, 'Funções de Primeiro e Segundo Grau', 'Definição, gráfico, domínio, contradomínio, imagem e estudo de sinais de funções afins e quadráticas.', 2, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(344, 199, 'Funções Exponenciais e Logarítmicas', 'Propriedades, gráficos, equações e inequações envolvendo logaritmos e exponenciais.', 3, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(345, 199, 'Sequências Numéricas: Progressão Aritmética e Geométrica', 'Termo geral, soma dos termos e aplicações de P.A. e P.G. em problemas.', 4, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(346, 200, 'Pontos e Retas no Plano Cartesiano', 'Cálculo de distância entre pontos, ponto médio, declive da reta, equações da reta (geral, reduzida, segmentária).', 1, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(347, 200, 'Circunferências e suas Equações', 'Equação reduzida e geral da circunferência, e relações entre reta e circunferência.', 2, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(348, 200, 'Polígonos: Áreas e Propriedades', 'Cálculo de áreas de triângulos, quadriláteros (quadrado, retângulo, trapézio, losango), polígonos regulares.', 3, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(349, 200, 'Teorema de Tales e Semelhança de Triângulos', 'Aplicação do Teorema de Tales e critérios de semelhança de triângulos em problemas geométricos.', 4, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(350, 201, 'Princípio Fundamental da Contagem (PFC)', 'Cálculo de possibilidades para eventos com múltiplas etapas, utilizando o PFC.', 1, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(351, 201, 'Arranjos e Permutações Simples e com Repetição', 'Diferença entre arranjo e permutação, e cálculo para casos com e sem repetição de elementos.', 2, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(352, 201, 'Combinações Simples e com Repetição', 'Cálculo de combinações, onde a ordem dos elementos não importa.', 3, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(353, 201, 'Probabilidade Condicional e Eventos Independentes', 'Cálculo de probabilidade para eventos dependentes e independentes, e o conceito de probabilidade condicional.', 4, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(354, 202, 'Grandezas Físicas e Unidades', 'Definição de grandezas fundamentais e derivadas, e o uso correto do Sistema Internacional de Unidades (SI).', 1, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(355, 202, 'Notação Científica e Algarismos Significativos', 'Representação de números muito grandes ou pequenos e a importância dos algarismos significativos em medições.', 2, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(356, 202, 'Vetores: Soma e Subtração', 'Representação gráfica de vetores, métodos para soma e subtração (regra do paralelogramo, poligonal) e decomposição de vetores.', 3, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(357, 202, 'Análise Dimensional e Consistência', 'Verificação da consistência dimensional de equações físicas para identificar erros ou grandezas.', 4, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(358, 203, 'Movimento Retilíneo Uniforme (MRU)', 'Conceito de velocidade constante, função horária da posição e gráfico v-t e s-t do MRU.', 1, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(359, 203, 'Movimento Retilíneo Uniformemente Variado (MRUV)', 'Aceleração constante, equações do MRUV (Sorvetão, Torricelli) e gráficos.', 2, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(360, 203, 'Queda Livre e Lançamento Vertical', 'Movimentos na vertical sob ação da gravidade, com e sem resistência do ar.', 3, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(361, 203, 'Força de Atrito e Plano Inclinado', 'Cálculo e aplicação da força de atrito estático e cinético, e análise de forças em corpos em planos inclinados.', 4, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(362, 204, 'Ondas: Classificação e Elementos', 'Ondas mecânicas e eletromagnéticas, comprimento de onda, frequência, período e amplitude.', 1, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(363, 204, 'Fenômenos Ondulatórios', 'Reflexão, refração, difração, interferência e polarização de ondas.', 2, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(364, 204, 'Qualidades Fisiológicas do Som', 'Altura, intensidade e timbre do som, e sua relação com frequência, amplitude e forma da onda.', 3, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(365, 204, 'Efeito Doppler e Ressonância', 'Alteração aparente da frequência devido ao movimento relativo entre fonte e observador, e o fenômeno da ressonância.', 4, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(366, 205, 'O Classicismo Português: Camões', 'A epopeia de Os Lusíadas e a poesia lírica de Luís Vaz de Camões, como expoente do Classicismo.', 1, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(367, 205, 'Arcadismo Brasileiro: Cláudio Manuel da Costa e Tomás Antônio Gonzaga', 'O bucolismo, o \"carpe diem\" e a idealização da vida no campo na poesia árcade brasileira.', 2, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(368, 205, 'Arcadismo Português: Bocage', 'A poesia de Bocage, com suas características árcades e a transição para o pré-romantismo.', 3, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(369, 205, 'Temas e Estilo do Neoclassicismo', 'A volta aos ideais clássicos de equilíbrio, razão e simplicidade na arte e literatura.', 4, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(370, 206, 'Parnasianismo: Olavo Bilac e o Culto à Forma', 'A preocupação com a forma, a rima e a métrica perfeita na poesia parnasiana, com destaque para Olavo Bilac.', 1, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(371, 206, 'Simbolismo: Cruz e Sousa e Alphonsus de Guimaraens', 'A musicalidade, o misticismo, a sinestesia e a subjetividade na poesia simbolista brasileira.', 2, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(372, 206, 'Poesia Simbolista em Portugal', 'Características do Simbolismo em Portugal e seus principais representantes.', 3, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(373, 206, 'Pré-Modernismo no Brasil', 'Autores e obras que antecederam a Semana de Arte Moderna, como Euclides da Cunha e Lima Barreto.', 4, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(374, 207, 'Poesia da Primeira Geração Modernista', 'A quebra com o passado, o verso livre, a linguagem coloquial e os temas do cotidiano.', 1, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(375, 207, 'Prosa da Segunda Geração Modernista (Romance de 30)', 'O regionalismo, a crítica social e psicológica em autores como Graciliano Ramos, Jorge Amado e Rachel de Queiroz.', 2, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(376, 207, 'Poesia da Segunda Geração Modernista', 'Aprofundamento existencial e social na poesia de Carlos Drummond de Andrade, Vinicius de Moraes e Cecília Meireles.', 3, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(377, 207, 'Guimarães Rosa e Clarice Lispector: Inovação e Psicanálise', 'A renovação da linguagem e a exploração do universo psicológico e existencial na prosa da Terceira Geração.', 4, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(378, 208, 'Operações com Conjuntos', 'União, interseção, diferença e complementar de conjuntos, e suas representações em diagramas de Venn.', 1, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(379, 208, 'Intervalos Reais', 'Representação de intervalos em notação de conjuntos e na reta numérica.', 2, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(380, 208, 'Números Naturais, Inteiros e Racionais', 'Propriedades e operações com os conjuntos N, Z e Q.', 3, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(381, 208, 'Números Irracionais e Reais', 'Conceito de números irracionais (ex: $\\sqrt{2}$, $\\pi$) e a constituição do conjunto dos números reais.', 4, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(382, 209, 'Operações com Matrizes', 'Adição, subtração, multiplicação de matrizes por escalar e entre matrizes, e matriz transposta.', 1, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(383, 209, 'Determinantes de Matrizes (2x2 e 3x3)', 'Cálculo de determinantes para matrizes de ordem 2 e 3 (Regra de Sarrus).', 2, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(384, 209, 'Sistemas Lineares e Regra de Cramer', 'Resolução de sistemas de equações lineares utilizando o método de substituição, adição e a Regra de Cramer.', 3, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(385, 209, 'Matriz Inversa e Equações Matriciais', 'Cálculo da matriz inversa e resolução de equações matriciais.', 4, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(386, 210, 'Prismas e Pirâmides: Áreas e Volumes', 'Cálculo de área da base, área lateral, área total e volume de prismas e pirâmides (retos e oblíquos).', 1, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(387, 210, 'Cilindros e Cones: Áreas e Volumes', 'Fórmulas para área da base, área lateral, área total e volume de cilindros e cones de revolução.', 2, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(388, 210, 'Esferas e Partes da Esfera', 'Cálculo da área da superfície e volume da esfera, e estudo de fuso e cunha esféricos.', 3, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(389, 210, 'Troncos de Pirâmide e Cone', 'Cálculo de áreas e volumes para troncos de pirâmide e cone.', 4, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(390, 211, 'Movimento Uniforme e Aceleração Média', 'Revisão dos conceitos de velocidade média e aceleração média, aplicados a problemas do ENEM.', 1, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(391, 211, 'Energia: Potencial, Cinética e Conservação', 'Identificação dos tipos de energia em sistemas físicos e o princípio de conservação da energia mecânica.', 2, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(392, 211, 'Força Peso e Leis de Newton no Cotidiano', 'Aplicação das leis de Newton em situações cotidianas, como elevadores e planos inclinados simples.', 3, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(393, 211, 'Gráficos de Movimento para o ENEM', 'Interpretação e análise de gráficos de posição-tempo, velocidade-tempo e aceleração-tempo.', 4, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(394, 212, 'Conceitos de Calor e Temperatura', 'Diferença entre calor e temperatura, e fenômenos de transmissão de calor (condução, convecção, irradiação).', 1, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(395, 212, 'Calorimetria: Curvas de Aquecimento e Resfriamento', 'Interpretação de gráficos de calorimetria e cálculo de calor sensível e latente.', 2, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(396, 212, 'Termodinâmica: Leis e Aplicações', 'As leis da termodinâmica, máquinas térmicas e o conceito de rendimento.', 3, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(397, 212, 'Dilatação Térmica em Materiais', 'Impacto da dilatação em estruturas e materiais, com exemplos práticos.', 4, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(398, 213, 'Leis de Ohm e Associações de Resistores', 'Cálculo de resistores em série e paralelo, e aplicação da primeira e segunda Lei de Ohm.', 1, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(399, 213, 'Potência Elétrica e Consumo de Energia', 'Cálculo da potência dissipada em resistores e o consumo de energia elétrica em residências.', 2, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(400, 213, 'Circuitos Simples com Pilhas e Lâmpadas', 'Análise de circuitos elétricos básicos e o funcionamento de componentes como pilhas e lâmpadas.', 3, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(401, 213, 'Geradores e Receptores Elétricos', 'Funcionamento de geradores (pilhas, baterias) e receptores elétricos, e seus rendimentos.', 4, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(402, 214, 'Funções da Linguagem: Conativa, Referencial, Emotiva, Fática, Poética, Metalinguística', 'Identificação das seis funções da linguagem em diferentes tipos de texto.', 1, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(403, 214, 'Gêneros Textuais e Tipologias para o ENEM', 'Diferença entre gênero e tipologia textual, e as características dos principais gêneros cobrados.', 2, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(404, 214, 'Coesão e Coerência Textual', 'Mecanismos de coesão (referencial, sequencial) e coerência (temática, lógica) para construir textos bem articulados.', 3, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(405, 214, 'Conectivos e Articuladores Textuais', 'Função dos conectivos na construção do sentido e na ligação entre orações e parágrafos.', 4, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(406, 215, 'Estratégias de Leitura Rápida e Dinâmica', 'Técnicas para otimizar a leitura e identificar informações-chave em textos longos.', 1, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(407, 215, 'Inferência e Reconhecimento de Implícitos', 'Desenvolvimento da capacidade de inferir informações e reconhecer o que está implícito no texto.', 2, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(408, 215, 'Tese, Argumentos e Contra-argumentos', 'Identificação da tese principal do texto e dos argumentos que a sustentam, bem como possíveis contra-argumentos.', 3, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(409, 215, 'Análise Crítica e Ideologias no Texto', 'Desenvolvimento do senso crítico para analisar textos e identificar possíveis ideologias presentes.', 4, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(410, 216, 'Variação Linguística: Histórica, Geográfica e Social', 'O estudo das diferentes formas de uso da língua de acordo com o tempo, região e grupos sociais.', 1, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43');
INSERT INTO `topicos` (`id`, `capitulo_id`, `titulo`, `descricao`, `ordem`, `status`, `observacoes`, `data_criacao`, `data_atualizacao`) VALUES
(411, 216, 'Níveis de Linguagem: Formal e Informal', 'A adequação da linguagem ao contexto comunicativo, alternando entre a norma culta e a coloquial.', 2, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(412, 216, 'Intertextualidade e Interdiscursividade', 'A relação entre diferentes textos e discursos, e como um texto pode fazer referência a outro.', 3, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(413, 216, 'Linguagem Corporal e Comunicação Não Verbal', 'A importância dos gestos, expressões e posturas na comunicação humana.', 4, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(414, 217, 'Medidas de Tendência Central: Média, Moda e Mediana', 'Cálculo e interpretação das medidas de tendência central para diferentes conjuntos de dados.', 1, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(415, 217, 'Gráficos Estatísticos: Barras, Setores, Linhas', 'Interpretação e construção de diferentes tipos de gráficos para representar dados estatísticos.', 2, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(416, 217, 'Tabelas e Distribuição de Frequências', 'Organização de dados em tabelas de frequência e a leitura de informações estatísticas.', 3, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(417, 217, 'Análise de Informações em Textos e Gráficos', 'Habilidades para extrair informações relevantes de textos e gráficos estatísticos para resolver problemas do ENEM.', 4, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(418, 218, 'Cálculo de Porcentagens e Aplicações', 'Aplicações de porcentagem em situações do dia a dia, como descontos, acréscimos e juros.', 1, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(419, 218, 'Juros Simples e Compostos', 'Diferença entre juros simples e compostos, e cálculo de montante e capital inicial.', 2, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(420, 218, 'Razão e Proporção', 'Definição de razão e proporção, e sua aplicação em problemas de escala e divisão proporcional.', 3, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(421, 218, 'Regra de Três Simples e Composta', 'Resolução de problemas utilizando a regra de três simples e composta em situações diretas e inversas.', 4, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(422, 219, 'Área de Figuras Planas: Quadrado, Retângulo, Triângulo', 'Fórmulas para cálculo de áreas de figuras básicas, e problemas que envolvem composição de áreas.', 1, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(423, 219, 'Área e Perímetro de Círculos', 'Cálculo de área e perímetro de círculos e setores circulares.', 2, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(424, 219, 'Ângulos e Suas Relações', 'Classificação de ângulos, ângulos complementares e suplementares, e ângulos formados por retas paralelas cortadas por transversal.', 3, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43'),
(425, 219, 'Teorema de Pitágoras e Triângulos Retângulos', 'Aplicação do Teorema de Pitágoras e relações métricas no triângulo retângulo para resolver problemas.', 4, 'em_andamento', '', '2025-06-16 14:14:43', '2025-06-16 14:14:43');

-- --------------------------------------------------------

--
-- Estrutura para tabela `topicos_ministrados`
--

CREATE TABLE `topicos_ministrados` (
  `id` int NOT NULL,
  `aula_id` int NOT NULL,
  `topico_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `topicos_ministrados`
--

INSERT INTO `topicos_ministrados` (`id`, `aula_id`, `topico_id`) VALUES
(1, 1, 282),
(2, 1, 283),
(3, 2, 284),
(4, 2, 285),
(5, 3, 286),
(6, 3, 287),
(7, 4, 288),
(8, 4, 289),
(9, 5, 290),
(10, 5, 291),
(11, 6, 292),
(12, 6, 293),
(13, 7, 294),
(14, 7, 295),
(15, 8, 296),
(16, 8, 297),
(17, 9, 298),
(18, 9, 299),
(19, 10, 300),
(20, 10, 301),
(21, 11, 302),
(22, 11, 303),
(23, 12, 304),
(24, 12, 305),
(25, 13, 306),
(26, 13, 307),
(27, 14, 308),
(28, 14, 309),
(29, 15, 310),
(30, 15, 311),
(31, 16, 312),
(32, 16, 313),
(33, 17, 314),
(34, 17, 315),
(35, 18, 316),
(36, 18, 317);

-- --------------------------------------------------------

--
-- Estrutura para tabela `topicos_personalizados`
--

CREATE TABLE `topicos_personalizados` (
  `id` int NOT NULL,
  `aula_id` int NOT NULL,
  `descricao` text NOT NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `turmas`
--

CREATE TABLE `turmas` (
  `id` int NOT NULL,
  `nome` varchar(100) NOT NULL,
  `inicio` date DEFAULT NULL,
  `fim` date DEFAULT NULL,
  `status` enum('ativa','concluída','cancelada') DEFAULT 'ativa',
  `ano_letivo` year NOT NULL,
  `turno` enum('manha','tarde','noite') DEFAULT 'manha'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `turmas`
--

INSERT INTO `turmas` (`id`, `nome`, `inicio`, `fim`, `status`, `ano_letivo`, `turno`) VALUES
(22, 'Turma Extensivo Manhã', '2025-07-01', '2025-12-10', 'cancelada', '2025', 'manha'),
(23, 'Turma Intensivo Tarde', '2025-08-01', '2025-11-30', 'ativa', '2025', 'tarde'),
(24, 'Turma Integral 2025', '2025-07-15', '2025-12-15', 'cancelada', '2025', 'manha'),
(25, 'Turma Noturna Intensiva', '2025-08-01', '2025-11-30', 'ativa', '2025', 'noite'),
(26, 'Turma de Férias 2025', '2025-07-01', '2025-07-31', 'concluída', '2025', 'tarde'),
(27, 'Cursinho Intensivo Manhã', '2025-08-01', '2025-12-20', 'ativa', '2025', 'manha'),
(28, 'Cursinho Semiextensivo Noite', '2025-07-01', '2025-11-30', 'ativa', '2025', 'noite'),
(29, 'Cursinho Online ENEM Turbo', '2025-09-01', '2025-10-30', 'ativa', '2025', '');

-- --------------------------------------------------------

--
-- Estrutura para tabela `turma_disciplinas`
--

CREATE TABLE `turma_disciplinas` (
  `id` int NOT NULL,
  `turma_id` int NOT NULL,
  `disciplina_id` int NOT NULL,
  `professor_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `turma_disciplinas`
--

INSERT INTO `turma_disciplinas` (`id`, `turma_id`, `disciplina_id`, `professor_id`) VALUES
(192, 23, 20, 3),
(193, 23, 21, 3),
(194, 24, 23, 3),
(195, 24, 24, 3),
(196, 24, 25, 3),
(197, 25, 23, 3),
(198, 25, 24, 3),
(199, 25, 25, 3),
(200, 26, 23, 3),
(201, 26, 24, 3),
(202, 26, 25, 3),
(203, 27, 26, 3),
(204, 27, 27, 3),
(205, 27, 28, 3),
(206, 28, 26, 3),
(207, 28, 27, 3),
(208, 28, 28, 3),
(209, 29, 26, 3),
(210, 29, 27, 3),
(211, 29, 28, 3),
(212, 22, 20, NULL),
(213, 22, 21, NULL),
(214, 22, 22, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `nome` varchar(100) NOT NULL,
  `sobrenome` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `tipo` enum('professor','admin','coordenador') NOT NULL,
  `cpf` varchar(14) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `matricula` varchar(20) DEFAULT NULL,
  `data_admissao` date DEFAULT NULL,
  `status` enum('ativo','inativo') DEFAULT 'ativo',
  `data_criacao` datetime DEFAULT CURRENT_TIMESTAMP,
  `data_ultimo_login` datetime DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `genero` varchar(20) DEFAULT NULL,
  `observacoes` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `usuarios` sena 12345
--

INSERT INTO `usuarios` (`id`, `nome`, `sobrenome`, `email`, `senha`, `tipo`, `cpf`, `telefone`, `data_nascimento`, `foto_perfil`, `matricula`, `data_admissao`, `status`, `data_criacao`, `data_ultimo_login`, `endereco`, `genero`, `observacoes`) VALUES
(1, 'marco', NULL, 'admin@hotmail.com', '$2y$10$w/SlRhAFyvSPEN9q8.qE9.Fvt1kqH/xxeGsXIogPbx7LM95sBfGFa', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, 'ativo', '2025-06-01 15:11:01', NULL, NULL, NULL, NULL),
(3, 'professor', 'teste', 'professor@gmail.com', '$2y$10$w/SlRhAFyvSPEN9q8.qE9.Fvt1kqH/xxeGsXIogPbx7LM95sBfGFa', 'professor', '444444', '199841221111', '2025-06-02', '../assets/img/foto_683cc7f0aa7bd7.41819667.png', '111111', '2025-06-03', 'ativo', '2025-06-01 15:11:01', NULL, NULL, 'Masculino', ''),
(8, 'coordenador', 'tes', 'coordenador@hotmail.com', '$2y$10$w/SlRhAFyvSPEN9q8.qE9.Fvt1kqH/xxeGsXIogPbx7LM95sBfGFa', 'coordenador', '44444444444', '19984122111', '2025-06-01', 'https://via.placeholder.com/70x70?text=Usuário', '4444444444', '2025-06-01', 'ativo', '2025-06-01 16:09:16', NULL, NULL, 'Masculino', '');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `aulas`
--
ALTER TABLE `aulas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `professor_id` (`professor_id`),
  ADD KEY `disciplina_id` (`disciplina_id`),
  ADD KEY `turma_id` (`turma_id`);

--
-- Índices de tabela `capitulos`
--
ALTER TABLE `capitulos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `capitulos_ibfk_1` (`plano_id`);

--
-- Índices de tabela `disciplinas`
--
ALTER TABLE `disciplinas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`);

--
-- Índices de tabela `enderecos`
--
ALTER TABLE `enderecos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `planos`
--
ALTER TABLE `planos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `disciplina_id` (`disciplina_id`),
  ADD KEY `criado_por` (`criado_por`),
  ADD KEY `planos_ibfk_3_idx` (`turma_id`);

--
-- Índices de tabela `topicos`
--
ALTER TABLE `topicos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `topicos_ibfk_1` (`capitulo_id`);

--
-- Índices de tabela `topicos_ministrados`
--
ALTER TABLE `topicos_ministrados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aula_id` (`aula_id`),
  ADD KEY `topico_id` (`topico_id`);

--
-- Índices de tabela `topicos_personalizados`
--
ALTER TABLE `topicos_personalizados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aula_id` (`aula_id`);

--
-- Índices de tabela `turmas`
--
ALTER TABLE `turmas`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `turma_disciplinas`
--
ALTER TABLE `turma_disciplinas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `turma_id` (`turma_id`),
  ADD KEY `disciplina_id` (`disciplina_id`),
  ADD KEY `professor_id` (`professor_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `cpf` (`cpf`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `aulas`
--
ALTER TABLE `aulas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de tabela `capitulos`
--
ALTER TABLE `capitulos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=220;

--
-- AUTO_INCREMENT de tabela `disciplinas`
--
ALTER TABLE `disciplinas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de tabela `enderecos`
--
ALTER TABLE `enderecos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `planos`
--
ALTER TABLE `planos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT de tabela `topicos`
--
ALTER TABLE `topicos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=426;

--
-- AUTO_INCREMENT de tabela `topicos_ministrados`
--
ALTER TABLE `topicos_ministrados`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT de tabela `topicos_personalizados`
--
ALTER TABLE `topicos_personalizados`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `turmas`
--
ALTER TABLE `turmas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de tabela `turma_disciplinas`
--
ALTER TABLE `turma_disciplinas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=215;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `aulas`
--
ALTER TABLE `aulas`
  ADD CONSTRAINT `aulas_ibfk_1` FOREIGN KEY (`professor_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `aulas_ibfk_2` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`id`),
  ADD CONSTRAINT `aulas_ibfk_3` FOREIGN KEY (`turma_id`) REFERENCES `turmas` (`id`);

--
-- Restrições para tabelas `capitulos`
--
ALTER TABLE `capitulos`
  ADD CONSTRAINT `capitulos_ibfk_1` FOREIGN KEY (`plano_id`) REFERENCES `planos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `enderecos`
--
ALTER TABLE `enderecos`
  ADD CONSTRAINT `enderecos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `planos`
--
ALTER TABLE `planos`
  ADD CONSTRAINT `planos_ibfk_1` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`id`),
  ADD CONSTRAINT `planos_ibfk_2` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `topicos`
--
ALTER TABLE `topicos`
  ADD CONSTRAINT `topicos_ibfk_1` FOREIGN KEY (`capitulo_id`) REFERENCES `capitulos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `topicos_ministrados`
--
ALTER TABLE `topicos_ministrados`
  ADD CONSTRAINT `topicos_ministrados_ibfk_1` FOREIGN KEY (`aula_id`) REFERENCES `aulas` (`id`),
  ADD CONSTRAINT `topicos_ministrados_ibfk_2` FOREIGN KEY (`topico_id`) REFERENCES `topicos` (`id`);

--
-- Restrições para tabelas `topicos_personalizados`
--
ALTER TABLE `topicos_personalizados`
  ADD CONSTRAINT `topicos_personalizados_ibfk_1` FOREIGN KEY (`aula_id`) REFERENCES `aulas` (`id`);

--
-- Restrições para tabelas `turma_disciplinas`
--
ALTER TABLE `turma_disciplinas`
  ADD CONSTRAINT `turma_disciplinas_ibfk_1` FOREIGN KEY (`turma_id`) REFERENCES `turmas` (`id`),
  ADD CONSTRAINT `turma_disciplinas_ibfk_2` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`id`),
  ADD CONSTRAINT `turma_disciplinas_ibfk_3` FOREIGN KEY (`professor_id`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
