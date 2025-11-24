-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 24/11/2025 às 20:22
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `neuroblogs`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `comentarios`
--

CREATE TABLE `comentarios` (
  `id` int(11) NOT NULL,
  `id_postagem` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `conteudo` text NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `comunidades`
--

CREATE TABLE `comunidades` (
  `id` int(11) NOT NULL,
  `nome_comunidade` varchar(100) NOT NULL COMMENT 'Nome único da comunidade/grupo',
  `tema_principal` varchar(100) NOT NULL COMMENT 'Categoria ou tema principal do grupo',
  `descricao` text DEFAULT NULL COMMENT 'Descrição detalhada da comunidade',
  `id_criador` int(11) NOT NULL COMMENT 'ID do usuário que criou a comunidade',
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `curtidas`
--

CREATE TABLE `curtidas` (
  `id` int(11) NOT NULL,
  `id_postagem` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `membros_comunidade`
--

CREATE TABLE `membros_comunidade` (
  `id_comunidade` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `data_entrada` timestamp NOT NULL DEFAULT current_timestamp(),
  `cargo` varchar(50) NOT NULL DEFAULT 'Membro' COMMENT 'Ex: Membro, Moderador, Administrador'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `perfil_usuario`
--

CREATE TABLE `perfil_usuario` (
  `id` int(11) NOT NULL,
  `pronoun` varchar(50) DEFAULT NULL,
  `neurotipos` varchar(100) DEFAULT NULL,
  `bio_pessoal` text DEFAULT NULL,
  `foto_perfil` varchar(255) NOT NULL DEFAULT 'imagens/default.png',
  `cor_fundo_pref` varchar(7) NOT NULL DEFAULT '#FFFFFF',
  `cor_texto_pref` varchar(7) NOT NULL DEFAULT '#374151',
  `tamanho_fonte_pref` varchar(10) NOT NULL DEFAULT 'medium',
  `fonte_preferida` varchar(50) NOT NULL DEFAULT 'sans-serif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `perfil_usuario`
--

INSERT INTO `perfil_usuario` (`id`, `pronoun`, `neurotipos`, `bio_pessoal`, `foto_perfil`, `cor_fundo_pref`, `cor_texto_pref`, `tamanho_fonte_pref`, `fonte_preferida`) VALUES
(35, NULL, NULL, NULL, 'imagens/default.png', '#f5f5f5', '#2c3e50', 'medium', 'sans-serif'),
(37, NULL, NULL, NULL, 'imagens/default.png', '#f5f5f5', '#2c3e50', 'medium', 'sans-serif'),
(41, NULL, NULL, NULL, 'imagens/default.png', '#f5f5f5', '#000000', 'medium', 'sans-serif'),
(46, NULL, NULL, NULL, 'imagens/default.png', '#f5f5f5', '#2c3e50', 'medium', 'sans-serif');

-- --------------------------------------------------------

--
-- Estrutura para tabela `postagens`
--

CREATE TABLE `postagens` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `id_comunidade` int(11) DEFAULT NULL,
  `conteudo` text NOT NULL,
  `imagem` varchar(255) DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `formato` varchar(50) NOT NULL DEFAULT 'somente-texto',
  `tipo_analise` varchar(50) NOT NULL DEFAULT 'analise-aprofundada',
  `aviso_sensibilidade` varchar(50) NOT NULL DEFAULT 'sem-spoiler'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(10) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(100) NOT NULL,
  `apelido` varchar(50) NOT NULL,
  `nivel` int(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `email`, `senha`, `apelido`, `nivel`) VALUES
(35, 'admin@neuroblog.com', '$2y$10$quryYl9SYJEfrd4vy82DfeJ3ihFywfVl7NAe0hMig.G7zcySGhGQa', 'Akin', 3),
(37, 'akin@gmail.com', '$2y$10$aJyEENTXmQoSVu9QjTtg6ebu2RoVM2jAZjmwa5LUM8WsWS3HmLFaG', 'akin', 0),
(41, 'ak@gmail.com', '$2y$10$t/dK3/zIz..x.bdvAg1AGeLshusQzpP8MjMpkMtXVyHRLrv1mMh8e', 'akiP', 0),
(46, '9@gmail', '$2y$10$JcpXtOgOTJoYEYT64rnG3./x3FRTGaJgNple639WVH9gCVY9msB2i', 'Batata', 0);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `comentarios`
--
ALTER TABLE `comentarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_postagem` (`id_postagem`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Índices de tabela `comunidades`
--
ALTER TABLE `comunidades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome_comunidade` (`nome_comunidade`),
  ADD KEY `id_criador` (`id_criador`);

--
-- Índices de tabela `curtidas`
--
ALTER TABLE `curtidas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_postagem` (`id_postagem`,`id_usuario`),
  ADD KEY `curtidas_ibfk_2` (`id_usuario`);

--
-- Índices de tabela `membros_comunidade`
--
ALTER TABLE `membros_comunidade`
  ADD PRIMARY KEY (`id_comunidade`,`id_usuario`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Índices de tabela `perfil_usuario`
--
ALTER TABLE `perfil_usuario`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `postagens`
--
ALTER TABLE `postagens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `fk_comunidade` (`id_comunidade`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `comentarios`
--
ALTER TABLE `comentarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT de tabela `comunidades`
--
ALTER TABLE `comunidades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `curtidas`
--
ALTER TABLE `curtidas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=183;

--
-- AUTO_INCREMENT de tabela `postagens`
--
ALTER TABLE `postagens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=127;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `comentarios`
--
ALTER TABLE `comentarios`
  ADD CONSTRAINT `comentarios_ibfk_1` FOREIGN KEY (`id_postagem`) REFERENCES `postagens` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comentarios_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `comunidades`
--
ALTER TABLE `comunidades`
  ADD CONSTRAINT `comunidades_ibfk_1` FOREIGN KEY (`id_criador`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `curtidas`
--
ALTER TABLE `curtidas`
  ADD CONSTRAINT `curtidas_ibfk_1` FOREIGN KEY (`id_postagem`) REFERENCES `postagens` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `curtidas_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `membros_comunidade`
--
ALTER TABLE `membros_comunidade`
  ADD CONSTRAINT `membros_comunidade_ibfk_1` FOREIGN KEY (`id_comunidade`) REFERENCES `comunidades` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `membros_comunidade_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `perfil_usuario`
--
ALTER TABLE `perfil_usuario`
  ADD CONSTRAINT `perfil_usuario_ibfk_1` FOREIGN KEY (`id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `postagens`
--
ALTER TABLE `postagens`
  ADD CONSTRAINT `fk_comunidade` FOREIGN KEY (`id_comunidade`) REFERENCES `comunidades` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
