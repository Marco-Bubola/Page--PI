-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 31/05/2025 às 20:00
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
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `tipo` enum('professor','admin','coordenador') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `tipo`) VALUES
(1, 'marco', 'marcobubola@hotmail.com', '12345', 'admin'),
(3, 'professor ', 'professor@gmail.com', '12345', 'professor'),
(4, 'coordenador ', 'coordenador@gmail.com', '12345', 'coordenador');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
ALTER TABLE `usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;
USE `pi_page`;

-- Tabela de Disciplinas (Matérias)
CREATE TABLE disciplinas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL UNIQUE
);

-- Plano de aula por disciplina
CREATE TABLE planos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  disciplina_id INT NOT NULL,
  titulo VARCHAR(255) NOT NULL,
  descricao TEXT,
  criado_por INT NOT NULL,
  criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (disciplina_id) REFERENCES disciplinas(id),
  FOREIGN KEY (criado_por) REFERENCES usuarios(id)
);

-- Capítulos do plano de aula
CREATE TABLE capitulos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  plano_id INT NOT NULL,
  titulo VARCHAR(255) NOT NULL,
  ordem INT,
  FOREIGN KEY (plano_id) REFERENCES planos(id)
);

-- Tópicos do capítulo (os "checkboxes")
CREATE TABLE topicos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  capitulo_id INT NOT NULL,
  descricao TEXT NOT NULL,
  ordem INT,
  FOREIGN KEY (capitulo_id) REFERENCES capitulos(id)
);

-- Registro de aulas realizadas
CREATE TABLE aulas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  professor_id INT NOT NULL,
  disciplina_id INT NOT NULL,
  data DATE NOT NULL,
  comentario TEXT,
  criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (professor_id) REFERENCES usuarios(id),
  FOREIGN KEY (disciplina_id) REFERENCES disciplinas(id)
);

-- Tópicos ministrados em cada aula
CREATE TABLE topicos_ministrados (
  id INT AUTO_INCREMENT PRIMARY KEY,
  aula_id INT NOT NULL,
  topico_id INT NOT NULL,
  FOREIGN KEY (aula_id) REFERENCES aulas(id),
  FOREIGN KEY (topico_id) REFERENCES topicos(id)
);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
