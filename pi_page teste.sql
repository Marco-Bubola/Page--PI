-- Criação do banco
CREATE DATABASE IF NOT EXISTS `pi_page` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `pi_page`;

-- -------------------------
-- Tabela: usuarios
-- -------------------------
CREATE TABLE usuarios (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  senha VARCHAR(255) NOT NULL,
  tipo ENUM('professor', 'admin', 'coordenador') NOT NULL
);

-- -------------------------
-- Tabela: disciplinas
-- -------------------------
CREATE TABLE disciplinas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL UNIQUE
);

-- -------------------------
-- Tabela: turmas
-- -------------------------
CREATE TABLE turmas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  ano_letivo YEAR NOT NULL,
  turno ENUM('manha', 'tarde', 'noite') DEFAULT 'manha'
);

-- -------------------------
-- Tabela: turma_disciplinas (ligação entre turmas e disciplinas)
-- -------------------------
CREATE TABLE turma_disciplinas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  turma_id INT NOT NULL,
  disciplina_id INT NOT NULL,
  professor_id INT,
  FOREIGN KEY (turma_id) REFERENCES turmas(id),
  FOREIGN KEY (disciplina_id) REFERENCES disciplinas(id),
  FOREIGN KEY (professor_id) REFERENCES usuarios(id)
);

-- -------------------------
-- Tabela: planos (planos de aula por disciplina)
-- -------------------------
CREATE TABLE planos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  disciplina_id INT NOT NULL,
  titulo VARCHAR(255) NOT NULL,
  descricao TEXT,
  status ENUM('em_andamento', 'concluido') DEFAULT 'em_andamento',
  criado_por INT NOT NULL,
  criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (disciplina_id) REFERENCES disciplinas(id),
  FOREIGN KEY (criado_por) REFERENCES usuarios(id)
);

-- -------------------------
-- Tabela: capitulos (capítulos dos planos)
-- -------------------------
CREATE TABLE capitulos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  plano_id INT NOT NULL,
  titulo VARCHAR(255) NOT NULL,
  ordem INT,
  status ENUM('em_andamento', 'concluido') DEFAULT 'em_andamento',
  FOREIGN KEY (plano_id) REFERENCES planos(id)
);

-- -------------------------
-- Tabela: topicos (tópicos/checkboxes de cada capítulo)
-- -------------------------
CREATE TABLE topicos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  capitulo_id INT NOT NULL,
  descricao TEXT NOT NULL,
  ordem INT,
  FOREIGN KEY (capitulo_id) REFERENCES capitulos(id)
);

-- -------------------------
-- Tabela: aulas (registro de aula por turma e disciplina)
-- -------------------------
CREATE TABLE aulas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  professor_id INT NOT NULL,
  disciplina_id INT NOT NULL,
  turma_id INT NOT NULL,
  data DATE NOT NULL,
  comentario TEXT,
  criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (professor_id) REFERENCES usuarios(id),
  FOREIGN KEY (disciplina_id) REFERENCES disciplinas(id),
  FOREIGN KEY (turma_id) REFERENCES turmas(id)
);

-- -------------------------
-- Tabela: topicos_ministrados (o que foi dado em aula)
-- -------------------------
CREATE TABLE topicos_ministrados (
  id INT AUTO_INCREMENT PRIMARY KEY,
  aula_id INT NOT NULL,
  topico_id INT NOT NULL,
  FOREIGN KEY (aula_id) REFERENCES aulas(id),
  FOREIGN KEY (topico_id) REFERENCES topicos(id)
);

CREATE TABLE topicos_personalizados (
  id INT AUTO_INCREMENT PRIMARY KEY,
  aula_id INT NOT NULL,
  descricao TEXT NOT NULL,
  criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (aula_id) REFERENCES aulas(id)
);
-- Usuários
INSERT INTO usuarios (nome, email, senha, tipo) VALUES
('marco', 'marcobubola@hotmail.com', '12345', 'admin'),
('professor', 'professor@gmail.com', '12345', 'professor'),
('coordenador', 'coordenador@gmail.com', '12345', 'coordenador');

