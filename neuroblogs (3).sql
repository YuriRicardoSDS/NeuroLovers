-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 01/12/2025 às 18:28
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
-- Estrutura para tabela `comentarios_comunidade`
--

CREATE TABLE `comentarios_comunidade` (
  `id` int(11) NOT NULL,
  `id_postagem` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `conteudo` text NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `comentarios_comunidade`
--

INSERT INTO `comentarios_comunidade` (`id`, `id_postagem`, `id_usuario`, `conteudo`, `data_criacao`) VALUES
(53, 94, 9, 'dsds', '2025-12-01 13:53:29');

-- --------------------------------------------------------

--
-- Estrutura para tabela `comentarios_pessoais`
--

CREATE TABLE `comentarios_pessoais` (
  `id` int(11) NOT NULL,
  `id_postagem` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `conteudo` text NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `comentarios_pessoais`
--

INSERT INTO `comentarios_pessoais` (`id`, `id_postagem`, `id_usuario`, `conteudo`, `data_criacao`) VALUES
(1, 1, 1, 'dsadsad', '2025-11-28 14:48:58'),
(2, 3, 1, 'KKK', '2025-11-28 15:41:25'),
(3, 4, 1, 'adsa', '2025-11-28 17:04:06'),
(4, 2, 1, 'dsad', '2025-11-28 18:10:24'),
(5, 5, 1, 'cxcx', '2025-11-28 18:15:11'),
(6, 8, 7, '123', '2025-11-29 22:16:11'),
(7, 9, 7, '123', '2025-11-29 22:16:46'),
(8, 10, 5, '1', '2025-11-29 22:30:13'),
(9, 23, 5, '1', '2025-11-29 23:49:44'),
(10, 22, 5, '1', '2025-11-29 23:49:46'),
(11, 20, 7, 'top', '2025-11-29 23:51:26'),
(12, 20, 5, 'top', '2025-11-29 23:51:46'),
(22, 66, 9, 'sadas', '2025-12-01 13:52:32'),
(23, 65, 9, 'sadas', '2025-12-01 13:52:38');

-- --------------------------------------------------------

--
-- Estrutura para tabela `comunidades`
--

CREATE TABLE `comunidades` (
  `id` int(11) NOT NULL,
  `nome_comunidade` varchar(100) NOT NULL,
  `tema_principal` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `imagem` varchar(255) DEFAULT 'uploads/comunidade/default.png',
  `id_criador` int(11) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `comunidades`
--

INSERT INTO `comunidades` (`id`, `nome_comunidade`, `tema_principal`, `descricao`, `imagem`, `id_criador`, `data_criacao`) VALUES
(7, 'Tecnologia', '', 'tecnologia', 'uploads/comunidade/community_7_1764458572.png', 7, '2025-11-29 17:18:06'),
(8, 'Teste Final', '', 'final', 'uploads/comunidade/community_8_1764458461.webp', 10, '2025-11-29 22:24:33'),
(9, 'Tenologia', '', 'sds', 'uploads/comunidade/default.png', 9, '2025-12-01 13:47:42'),
(10, 'Logistica', '', 'ssf', 'uploads/comunidade/community_10_1764596932.avif', 9, '2025-12-01 13:48:14'),
(19, 'e', '', 'e', 'uploads/comunidade/default.png', 5, '2025-12-01 17:14:11'),
(20, 'dds', '', 'ddd', 'uploads/comunidade/default.png', 1, '2025-12-01 17:27:11');

-- --------------------------------------------------------

--
-- Estrutura para tabela `curtidas_comunidade`
--

CREATE TABLE `curtidas_comunidade` (
  `id` int(11) NOT NULL,
  `id_postagem` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `curtidas_comunidade`
--

INSERT INTO `curtidas_comunidade` (`id`, `id_postagem`, `id_usuario`) VALUES
(132, 94, 9);

-- --------------------------------------------------------

--
-- Estrutura para tabela `curtidas_pessoais`
--

CREATE TABLE `curtidas_pessoais` (
  `id` int(11) NOT NULL,
  `id_postagem` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `curtidas_pessoais`
--

INSERT INTO `curtidas_pessoais` (`id`, `id_postagem`, `id_usuario`) VALUES
(1, 1, 1),
(3, 2, 1),
(2, 3, 1),
(5, 4, 1),
(6, 5, 1),
(7, 8, 7),
(9, 9, 7),
(11, 10, 5),
(12, 11, 5),
(14, 12, 5),
(15, 14, 5),
(17, 20, 5),
(21, 20, 7),
(16, 21, 5),
(18, 22, 5),
(19, 23, 5),
(20, 23, 7),
(27, 63, 5),
(26, 64, 5),
(29, 65, 9),
(28, 66, 9);

-- --------------------------------------------------------

--
-- Estrutura para tabela `membros_comunidade`
--

CREATE TABLE `membros_comunidade` (
  `id_comunidade` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `data_entrada` timestamp NOT NULL DEFAULT current_timestamp(),
  `cargo` varchar(50) NOT NULL DEFAULT 'Membro'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `membros_comunidade`
--

INSERT INTO `membros_comunidade` (`id_comunidade`, `id_usuario`, `is_admin`, `data_entrada`, `cargo`) VALUES
(7, 7, 0, '2025-11-29 17:19:06', 'Membro'),
(7, 10, 0, '2025-12-01 15:18:30', 'Membro'),
(8, 10, 0, '2025-12-01 15:18:28', 'Membro'),
(9, 10, 0, '2025-12-01 15:18:26', 'Membro'),
(10, 9, 1, '2025-12-01 13:48:14', 'Membro'),
(10, 10, 0, '2025-12-01 15:18:24', 'Membro'),
(19, 5, 1, '2025-12-01 17:14:11', 'Membro'),
(20, 1, 0, '2025-12-01 17:27:49', 'Membro');

-- --------------------------------------------------------

--
-- Estrutura para tabela `perfil_usuario`
--

CREATE TABLE `perfil_usuario` (
  `id` int(11) NOT NULL COMMENT 'Chave estrangeira para usuarios.id',
  `pronoun` varchar(50) DEFAULT NULL,
  `neurotipos` varchar(100) DEFAULT NULL,
  `bio` text DEFAULT NULL COMMENT 'Biografia consolidada do usuário',
  `foto_perfil` varchar(255) NOT NULL DEFAULT 'imagens/default.png',
  `cor_fundo_pref` varchar(7) NOT NULL DEFAULT '#FFFFFF',
  `cor_texto_pref` varchar(7) NOT NULL DEFAULT '#374151',
  `tamanho_fonte_pref` varchar(10) NOT NULL DEFAULT 'medium',
  `fonte_preferida` varchar(50) NOT NULL DEFAULT 'sans-serif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `perfil_usuario`
--

INSERT INTO `perfil_usuario` (`id`, `pronoun`, `neurotipos`, `bio`, `foto_perfil`, `cor_fundo_pref`, `cor_texto_pref`, `tamanho_fonte_pref`, `fonte_preferida`) VALUES
(1, NULL, NULL, '', 'uploads/perfil/1_1764425778.png', '#FFFFFF', '#374151', 'medium', 'sans-serif'),
(2, NULL, NULL, '', 'uploads/perfil/2_1764356200.png', '#FFFFFF', '#374151', 'medium', 'sans-serif'),
(5, NULL, NULL, 'Oi 2', 'uploads/perfil/5_1764454204.png', '#FFFFFF', '#374151', 'medium', 'sans-serif'),
(7, NULL, NULL, 'Ola', 'uploads/perfil/7_1764454546.png', '#FFFFFF', '#374151', 'medium', 'sans-serif'),
(9, NULL, NULL, 'Pode pá', 'uploads/perfil/9_1764597038.png', '#FFFFFF', '#374151', 'medium', 'sans-serif'),
(10, NULL, NULL, 'cc', 'uploads/perfil/10_1764602420.png', '#FFFFFF', '#374151', 'medium', 'sans-serif');

-- --------------------------------------------------------

--
-- Estrutura para tabela `posts_comunidade`
--

CREATE TABLE `posts_comunidade` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL COMMENT 'Quem postou',
  `id_comunidade` int(11) NOT NULL COMMENT 'Obrigatorio - Garante que está no feed principal',
  `titulo` varchar(255) NOT NULL,
  `conteudo` text NOT NULL,
  `imagem` varchar(255) DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `formato` varchar(50) NOT NULL DEFAULT 'somente-texto',
  `tipo_analise` varchar(50) NOT NULL DEFAULT 'analise-aprofundada',
  `aviso_sensibilidade` varchar(50) NOT NULL DEFAULT 'sem-spoiler'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `posts_comunidade`
--

INSERT INTO `posts_comunidade` (`id`, `usuario_id`, `id_comunidade`, `titulo`, `conteudo`, `imagem`, `data_criacao`, `formato`, `tipo_analise`, `aviso_sensibilidade`) VALUES
(94, 9, 10, '', 'dafa', 'uploads/posts_comunidade/post_692d9dcfed011.avif', '2025-12-01 13:53:19', 'somente-texto', 'analise-aprofundada', 'sem-spoiler');

-- --------------------------------------------------------

--
-- Estrutura para tabela `posts_pessoais`
--

CREATE TABLE `posts_pessoais` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL COMMENT 'Obrigatorio - O dono do post',
  `titulo` varchar(255) DEFAULT NULL,
  `conteudo` text NOT NULL,
  `imagem` varchar(255) DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `visibilidade` enum('publico','privado') NOT NULL DEFAULT 'publico'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `posts_pessoais`
--

INSERT INTO `posts_pessoais` (`id`, `usuario_id`, `titulo`, `conteudo`, `imagem`, `data_criacao`, `visibilidade`) VALUES
(1, 1, NULL, 'dsad', NULL, '2025-11-28 14:35:14', 'publico'),
(2, 1, NULL, 'asdsdsad', NULL, '2025-11-28 14:49:01', 'publico'),
(3, 1, NULL, 'sdsadsad', NULL, '2025-11-28 14:49:08', 'publico'),
(4, 1, NULL, 'KKK', NULL, '2025-11-28 15:41:40', 'publico'),
(5, 1, NULL, 'cxzczx', NULL, '2025-11-28 18:15:06', 'publico'),
(6, 1, NULL, 'cxzczx', NULL, '2025-11-28 18:15:20', 'publico'),
(7, 1, NULL, 'cxzczx', NULL, '2025-11-28 18:15:38', 'publico'),
(8, 7, NULL, 'OI', NULL, '2025-11-29 22:16:06', 'publico'),
(9, 7, NULL, 'Seila', NULL, '2025-11-29 22:16:30', 'publico'),
(10, 5, NULL, 'Ola', NULL, '2025-11-29 22:30:10', 'publico'),
(11, 5, NULL, 'oi', NULL, '2025-11-29 22:30:43', 'publico'),
(12, 5, NULL, 'add', NULL, '2025-11-29 23:27:37', 'publico'),
(13, 5, NULL, 'oi', NULL, '2025-11-29 23:34:31', 'publico'),
(14, 5, NULL, 'Rize', NULL, '2025-11-29 23:37:59', 'publico'),
(16, 5, NULL, 'ola', NULL, '2025-11-29 23:40:55', 'publico'),
(19, 5, NULL, '123', NULL, '2025-11-29 23:46:27', 'publico'),
(20, 5, NULL, '421', 'uploads/posts_pessoais/5_1764459998.jpg', '2025-11-29 23:46:38', 'publico'),
(21, 5, NULL, '411', 'uploads/posts_pessoais/5_1764460010.png', '2025-11-29 23:46:50', 'publico'),
(22, 5, NULL, '4123', 'uploads/posts_pessoais/5_1764460022.png', '2025-11-29 23:47:02', 'publico'),
(23, 5, NULL, '123', NULL, '2025-11-29 23:48:37', 'publico'),
(37, 5, NULL, '1', NULL, '2025-11-30 00:10:15', 'publico'),
(38, 5, NULL, '1', NULL, '2025-11-30 00:10:16', 'publico'),
(39, 5, NULL, '1', NULL, '2025-11-30 00:10:17', 'publico'),
(40, 5, NULL, '1', NULL, '2025-11-30 00:10:19', 'publico'),
(41, 5, NULL, '1', NULL, '2025-11-30 00:10:19', 'publico'),
(42, 5, NULL, '1', NULL, '2025-11-30 00:10:20', 'publico'),
(43, 5, NULL, '1', NULL, '2025-11-30 00:10:21', 'publico'),
(44, 5, NULL, '1', NULL, '2025-11-30 00:10:22', 'publico'),
(45, 5, NULL, '1', NULL, '2025-11-30 00:10:23', 'publico'),
(46, 5, NULL, '1', NULL, '2025-11-30 00:10:23', 'publico'),
(47, 5, NULL, '11', NULL, '2025-11-30 00:10:25', 'publico'),
(48, 5, NULL, 'a', NULL, '2025-11-30 00:10:57', 'publico'),
(49, 5, NULL, 'a', NULL, '2025-11-30 00:11:01', 'publico'),
(50, 5, NULL, 'a', NULL, '2025-11-30 00:11:02', 'publico'),
(51, 5, NULL, 'a', NULL, '2025-11-30 00:11:03', 'publico'),
(52, 5, NULL, 'a', NULL, '2025-11-30 00:11:03', 'publico'),
(55, 5, NULL, '123', 'uploads/posts_pessoais/5_1764463309.jpg', '2025-11-30 00:41:49', 'privado'),
(63, 5, NULL, '123', NULL, '2025-11-30 01:28:23', 'publico'),
(64, 5, NULL, '123', 'uploads/posts_pessoais/5_1764466611.webp', '2025-11-30 01:36:51', 'publico'),
(65, 9, NULL, 'Oi', NULL, '2025-12-01 13:51:13', 'publico'),
(66, 9, NULL, 'oi', NULL, '2025-12-01 13:52:29', 'privado'),
(68, 1, NULL, 'kk', NULL, '2025-12-01 16:00:27', 'publico'),
(69, 1, NULL, 'l', NULL, '2025-12-01 16:00:37', 'publico'),
(70, 1, NULL, 'i', NULL, '2025-12-01 16:00:47', 'publico'),
(71, 1, NULL, 't', NULL, '2025-12-01 16:00:52', 'publico');

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
(1, 'ak@gmail.com', '$2y$10$mJT.XRnXSg2DbgSLIfBhWeYfNwL3emH7.qTzyCwiT8v/VTw/NyY/O', 'akiP', 0),
(2, 'a@gmail.com', '$2y$10$yUjyCyt1VXzZAk/StjtGGO4fiq5xLa3Z0z1WKFohNVf10ZPOkN5Iy', 'a', 1),
(3, 'k@gmail.com', '$2y$10$fai1Xh247nTEjeJOg9xJgebxzhm881BdD/MsKEFHCLNm3yf.5VP5K', 'k', 0),
(4, 's@gmail', '$2y$10$06JPfyeiQiKcVNoTTGNLW.o.WQg92g9VMxD9cJ3O76cdWzBNFWqYu', 's', 0),
(5, 'luan@gmail.com', '$2y$10$sI1.uVCbTX/vya4GTHF6t.UxKROVhNMLniRxnQgt7KqJzp.KG/uRC', 'luan', 0),
(6, 'oi1@gmail.com', '$2y$10$29NrN7IUkxyIQFKgBTXDCut9.RMZyX8xMh1vmsfcUrCO5E8NLrcK2', 'oi1', 0),
(7, 'oi2@gmail.com', '$2y$10$T1mYvwR8QRmJq6LqOv0ceuXY.GMiMG8.Ot/NtdJDuM9mLLDtUyDK.', 'oi2', 0),
(8, 't@gmail.com', '$2y$10$RBMiiG7cR0QIiLpYJjGjLeB4NXcpW0InKs93erU9z01ka2j7IVs3i', 'teste1', 0),
(9, 'sarahr@prof.educacao.sp.gov.br', '$2y$10$JCkuXBVn1BADuAVvjZ8THus2Qd1rQEgCAhwydP68qF6/fZ0g4fmom', 'Sarah', 0),
(10, 'Jozeanesmoreira@gmail.com', '$2y$10$u3y0dIhqlIiyjRj5vgWE4.y36V0kbSA5y0K.Sc9rAuk5D8VJxvq/e', 'ANNE', 0);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `comentarios_comunidade`
--
ALTER TABLE `comentarios_comunidade`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_comentarios_post_com` (`id_postagem`),
  ADD KEY `fk_comentarios_user_com` (`id_usuario`);

--
-- Índices de tabela `comentarios_pessoais`
--
ALTER TABLE `comentarios_pessoais`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_comentarios_post_pes` (`id_postagem`),
  ADD KEY `fk_comentarios_user_pes` (`id_usuario`);

--
-- Índices de tabela `comunidades`
--
ALTER TABLE `comunidades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome_comunidade` (`nome_comunidade`),
  ADD KEY `id_criador` (`id_criador`);

--
-- Índices de tabela `curtidas_comunidade`
--
ALTER TABLE `curtidas_comunidade`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uc_post_user_com` (`id_postagem`,`id_usuario`),
  ADD KEY `fk_curtidas_user_com` (`id_usuario`);

--
-- Índices de tabela `curtidas_pessoais`
--
ALTER TABLE `curtidas_pessoais`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uc_post_user_pes` (`id_postagem`,`id_usuario`),
  ADD KEY `fk_curtidas_user_pes` (`id_usuario`);

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
-- Índices de tabela `posts_comunidade`
--
ALTER TABLE `posts_comunidade`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `fk_comunidade` (`id_comunidade`);

--
-- Índices de tabela `posts_pessoais`
--
ALTER TABLE `posts_pessoais`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `comentarios_comunidade`
--
ALTER TABLE `comentarios_comunidade`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT de tabela `comentarios_pessoais`
--
ALTER TABLE `comentarios_pessoais`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de tabela `comunidades`
--
ALTER TABLE `comunidades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de tabela `curtidas_comunidade`
--
ALTER TABLE `curtidas_comunidade`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=140;

--
-- AUTO_INCREMENT de tabela `curtidas_pessoais`
--
ALTER TABLE `curtidas_pessoais`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT de tabela `posts_comunidade`
--
ALTER TABLE `posts_comunidade`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98;

--
-- AUTO_INCREMENT de tabela `posts_pessoais`
--
ALTER TABLE `posts_pessoais`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `comentarios_comunidade`
--
ALTER TABLE `comentarios_comunidade`
  ADD CONSTRAINT `fk_comentarios_post_com` FOREIGN KEY (`id_postagem`) REFERENCES `posts_comunidade` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_comentarios_user_com` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `comentarios_pessoais`
--
ALTER TABLE `comentarios_pessoais`
  ADD CONSTRAINT `fk_comentarios_post_pes` FOREIGN KEY (`id_postagem`) REFERENCES `posts_pessoais` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_comentarios_user_pes` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `comunidades`
--
ALTER TABLE `comunidades`
  ADD CONSTRAINT `fk_comunidades_criador` FOREIGN KEY (`id_criador`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `curtidas_comunidade`
--
ALTER TABLE `curtidas_comunidade`
  ADD CONSTRAINT `fk_curtidas_post_com` FOREIGN KEY (`id_postagem`) REFERENCES `posts_comunidade` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_curtidas_user_com` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `curtidas_pessoais`
--
ALTER TABLE `curtidas_pessoais`
  ADD CONSTRAINT `fk_curtidas_post_pes` FOREIGN KEY (`id_postagem`) REFERENCES `posts_pessoais` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_curtidas_user_pes` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `membros_comunidade`
--
ALTER TABLE `membros_comunidade`
  ADD CONSTRAINT `fk_membros_comunidade` FOREIGN KEY (`id_comunidade`) REFERENCES `comunidades` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_membros_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `perfil_usuario`
--
ALTER TABLE `perfil_usuario`
  ADD CONSTRAINT `fk_perfil_usuario` FOREIGN KEY (`id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `posts_comunidade`
--
ALTER TABLE `posts_comunidade`
  ADD CONSTRAINT `fk_posts_comunidade_comunidade` FOREIGN KEY (`id_comunidade`) REFERENCES `comunidades` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_posts_comunidade_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `posts_pessoais`
--
ALTER TABLE `posts_pessoais`
  ADD CONSTRAINT `fk_posts_pessoais_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
