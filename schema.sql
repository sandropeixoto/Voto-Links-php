-- Criação do Banco de Dados (caso não exista)
CREATE DATABASE IF NOT EXISTS `linktree_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `linktree_db`;

-- =========================================================================
-- Tabela: USUARIOS
-- Armazena os dados de login e o perfil público (slug)
-- =========================================================================
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `usuario` varchar(50) NOT NULL,     -- Ex: 'joao' para link.com/joao
  `senha` varchar(255) NOT NULL,      -- Hash da senha (bcrypt)
  `bio` varchar(255) DEFAULT NULL,    -- Pequena descrição
  `foto` varchar(255) DEFAULT NULL,   -- Caminho da imagem
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `usuario` (`usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================================
-- Tabela: LINKS
-- Armazena os botões que aparecem na página pública
-- =========================================================================
CREATE TABLE `links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,      -- Texto do botão
  `url` varchar(255) NOT NULL,         -- Link de destino
  `ordem` int(11) DEFAULT 0,           -- Para ordenar (0, 1, 2...)
  `ativo` int(1) DEFAULT 1,            -- 1 = Ativo, 0 = Oculto
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_usuario_link` (`usuario_id`),
  CONSTRAINT `fk_usuario_link` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;