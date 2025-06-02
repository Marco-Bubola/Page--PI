-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 02/06/2025 às 03:29
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

DROP TABLE IF EXISTS `aulas`;
CREATE TABLE `aulas` (
  `id` int NOT NULL,
  `professor_id` int NOT NULL,
  `disciplina_id` int NOT NULL,
  `turma_id` int NOT NULL,
  `data` date NOT NULL,
  `comentario` text,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `capitulos`
--

DROP TABLE IF EXISTS `capitulos`;
CREATE TABLE `capitulos` (
  `id` int NOT NULL,
  `plano_id` int NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `ordem` int DEFAULT NULL,
  `status` enum('em_andamento','concluido') DEFAULT 'em_andamento'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `disciplinas`
--

DROP TABLE IF EXISTS `disciplinas`;
CREATE TABLE `disciplinas` (
  `id` int NOT NULL,
  `nome` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `enderecos`
--

DROP TABLE IF EXISTS `enderecos`;
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

-- --------------------------------------------------------

--
-- Estrutura para tabela `planos`
--

DROP TABLE IF EXISTS `planos`;
CREATE TABLE `planos` (
  `id` int NOT NULL,
  `turma_id` int NOT NULL,
  `disciplina_id` int NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descricao` text,
  `status` enum('em_andamento','concluido') DEFAULT 'em_andamento',
  `criado_por` int NOT NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `topicos`
--

DROP TABLE IF EXISTS `topicos`;
CREATE TABLE `topicos` (
  `id` int NOT NULL,
  `capitulo_id` int NOT NULL,
  `descricao` text NOT NULL,
  `ordem` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `topicos_ministrados`
--

DROP TABLE IF EXISTS `topicos_ministrados`;
CREATE TABLE `topicos_ministrados` (
  `id` int NOT NULL,
  `aula_id` int NOT NULL,
  `topico_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `topicos_personalizados`
--

DROP TABLE IF EXISTS `topicos_personalizados`;
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

DROP TABLE IF EXISTS `turmas`;
CREATE TABLE `turmas` (
  `id` int NOT NULL,
  `nome` varchar(100) NOT NULL,
  `ano_letivo` year NOT NULL,
  `turno` enum('manha','tarde','noite') DEFAULT 'manha'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `turma_disciplinas`
--

DROP TABLE IF EXISTS `turma_disciplinas`;
CREATE TABLE `turma_disciplinas` (
  `id` int NOT NULL,
  `turma_id` int NOT NULL,
  `disciplina_id` int NOT NULL,
  `professor_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
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
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `sobrenome`, `email`, `senha`, `tipo`, `cpf`, `telefone`, `data_nascimento`, `foto_perfil`, `matricula`, `data_admissao`, `status`, `data_criacao`, `data_ultimo_login`, `endereco`, `genero`, `observacoes`) VALUES
(1, 'marco', NULL, 'marcobubola@hotmail.com', '12345', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, 'ativo', '2025-06-01 15:11:01', NULL, NULL, NULL, NULL),
(3, 'professor', 'teste', 'professor@gmail.com', '12345', 'professor', '444444', '199841221111', '2025-06-02', '../assets/img/foto_683cc7f0aa7bd7.41819667.png', '111111', '2025-06-03', 'ativo', '2025-06-01 15:11:01', NULL, NULL, 'Masculino', ''),
(8, 'coordenador', 'tes', 'coordenador@hotmail.com', '$2y$10$wmoU0Uro1.nIIBUukXJph.jfcx.uG/URYc9FUY45ONPveXrd.tEdm', 'coordenador', '44444444444', '19984122111', '2025-06-01', 'https://via.placeholder.com/70x70?text=Usuário', '4444444444', '2025-06-01', 'ativo', '2025-06-01 16:09:16', NULL, NULL, 'Masculino', '');

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `capitulos`
--
ALTER TABLE `capitulos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `disciplinas`
--
ALTER TABLE `disciplinas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `enderecos`
--
ALTER TABLE `enderecos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `planos`
--
ALTER TABLE `planos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `topicos`
--
ALTER TABLE `topicos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `topicos_ministrados`
--
ALTER TABLE `topicos_ministrados`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `topicos_personalizados`
--
ALTER TABLE `topicos_personalizados`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `turmas`
--
ALTER TABLE `turmas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `turma_disciplinas`
--
ALTER TABLE `turma_disciplinas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

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
