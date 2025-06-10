CREATE TABLE `aulas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `professor_id` int NOT NULL,
  `disciplina_id` int NOT NULL,
  `turma_id` int NOT NULL,
  `data` date NOT NULL,
  `comentario` text,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `professor_id` (`professor_id`),
  KEY `disciplina_id` (`disciplina_id`),
  KEY `turma_id` (`turma_id`),
  CONSTRAINT `aulas_ibfk_1` FOREIGN KEY (`professor_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `aulas_ibfk_2` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`id`),
  CONSTRAINT `aulas_ibfk_3` FOREIGN KEY (`turma_id`) REFERENCES `turmas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


