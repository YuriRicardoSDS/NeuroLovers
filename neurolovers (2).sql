-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 05/09/2025 às 18:43
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
-- Banco de dados: `neurolovers`
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

--
-- Despejando dados para a tabela `comentarios`
--

INSERT INTO `comentarios` (`id`, `id_postagem`, `id_usuario`, `conteudo`, `data_criacao`) VALUES
(2, 18, 7, 'ghjgfj', '2025-08-11 18:25:53'),
(3, 19, 7, 'hjkbk', '2025-08-11 18:28:42'),
(9, 12, 19, 'jbnij', '2025-08-12 18:48:49'),
(28, 67, 23, 'sdsd', '2025-09-01 18:32:50'),
(29, 71, 26, 'dsfdasf', '2025-09-01 18:40:56'),
(30, 69, 26, 'jbkhj', '2025-09-02 18:32:43'),
(32, 72, 19, 'vdsvsdvdsv', '2025-09-05 16:34:46'),
(33, 73, 19, '1', '2025-09-05 16:35:20');

-- --------------------------------------------------------

--
-- Estrutura para tabela `curtidas`
--

CREATE TABLE `curtidas` (
  `id` int(11) NOT NULL,
  `id_postagem` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `curtidas`
--

INSERT INTO `curtidas` (`id`, `id_postagem`, `id_usuario`) VALUES
(140, 4, 19),
(139, 12, 19),
(138, 15, 19),
(63, 17, 19),
(2, 18, 7),
(59, 18, 19),
(72, 19, 16),
(42, 19, 19),
(46, 26, 19),
(85, 40, 16),
(137, 40, 19),
(69, 44, 16),
(136, 44, 19),
(67, 45, 16),
(135, 45, 19),
(66, 52, 16),
(134, 52, 19),
(133, 57, 19),
(110, 57, 23),
(55, 58, 19),
(109, 58, 23),
(107, 59, 16),
(131, 59, 19),
(106, 64, 16),
(130, 64, 19),
(128, 67, 19),
(108, 67, 23),
(129, 68, 19),
(127, 69, 19),
(122, 70, 19),
(121, 71, 19),
(117, 71, 26),
(120, 72, 19),
(144, 73, 19);

-- --------------------------------------------------------

--
-- Estrutura para tabela `postagens`
--

CREATE TABLE `postagens` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `conteudo` text NOT NULL,
  `imagem` varchar(255) DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `postagens`
--

INSERT INTO `postagens` (`id`, `usuario_id`, `conteudo`, `imagem`, `data_criacao`) VALUES
(4, 1, 'Blue é mais rápido que o God.', NULL, '2025-08-04 18:31:01'),
(12, 19, '', 'uploads/post_6891018d2b2c1.png', '2025-08-04 18:53:01'),
(15, 19, 'posdsd', 'uploads/post_689104ce7260e.png', '2025-08-04 19:06:54'),
(16, 19, 'To que nem o patinho!!!', 'uploads/post_689106f536079.jpg', '2025-08-04 19:16:05'),
(17, 19, 'sxcdzcxzc', NULL, '2025-08-11 18:24:20'),
(18, 19, 'sdsdsadds', NULL, '2025-08-11 18:24:35'),
(19, 7, 'bjhibib', NULL, '2025-08-11 18:28:33'),
(26, 19, 'cvzvz', 'uploads/post_689a43ea6e179.png', '2025-08-11 19:26:34'),
(40, 19, 'asasaasas', NULL, '2025-08-12 19:11:18'),
(44, 19, 'gfsgsfd', NULL, '2025-08-12 19:17:17'),
(45, 19, 'fdasfdsadf', 'uploads/post_689b95975829c.webp', '2025-08-12 19:27:19'),
(52, 19, 'sadsa', 'uploads/post_689c981e37100.png', '2025-08-13 13:50:22'),
(57, 19, '', 'uploads/post_68acb9d7aa4af.jpg', '2025-08-25 19:30:31'),
(58, 19, 'hgfdh', 'uploads/post_68b5cab6932fe.webp', '2025-09-01 16:32:54'),
(59, 19, 'dsgdghsfr', NULL, '2025-09-01 16:39:48'),
(64, 16, 'dsadsad', NULL, '2025-09-01 18:16:52'),
(67, 23, 'vczv', NULL, '2025-09-01 18:32:44'),
(68, 23, 'Tan tan tan', NULL, '2025-09-01 18:33:37'),
(69, 23, 'sdsadsa', 'uploads/post_68b5e77c829ff.png', '2025-09-01 18:35:40'),
(70, 23, 'cdgfdsgs', NULL, '2025-09-01 18:38:51'),
(71, 26, '', 'uploads/post_68b5e8b261acc.webp', '2025-09-01 18:40:50'),
(72, 26, '', 'uploads/post_68b73ee9585c1.png', '2025-09-02 19:00:57'),
(73, 19, 'batata', NULL, '2025-09-05 16:35:11'),
(74, 19, 'aaa', NULL, '2025-09-05 16:37:16');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(10) NOT NULL,
  `user` varchar(20) NOT NULL,
  `senha` varchar(100) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `nivel` int(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `user`, `senha`, `nome`, `nivel`) VALUES
(1, 'Akin', '$2y$10$.oxo3mshRNWjxmvDCV.OP.8zYFHEJ44C351Wd3N9UvrAEIiq7LlHO', 'Akin', 3),
(2, 'Yuri', '$2y$10$l5Th0aboAO7s26YlSIwjO.OoeqgFgEEiS4TChy.njQGlOV7HuRY8W', 'Yuri', 3),
(3, 'Laysa', '$2y$10$jz0HGdddjTbvj530c1B3UO0xGxnDVsecdN1ma.2ZC7HCLzy8CkylK', 'Laysa', 0),
(5, 'caina@gmail', '$2y$10$gWIxfxR4f/B3nZZwcp0k2ea8t9nRsyC.lmjUrluoMajIvVIJWK8B.', 'caina', 1),
(6, 'yuri', '$2y$10$5lxhR3jMQP3zpWTOXIE4eOjH3tSXb6sS8DgMq3uYZi9KjmFC.szi6', 'yuri', 0),
(7, '123456', '$2y$10$orvl/PEtfSEOKxcxHLbdV.R8aELndWQt1UsnYlAzR9rI7yus6U2xi', '123456', 0),
(8, '123456', '$2y$10$NGtccrUzTl7Y6i1tWQ.sXuxKr2ywLhntd50AiVuG1hJ9gjJAiKr3y', '123456', 0),
(9, '456', '$2y$10$iLcDIlt6NDJ8AlwMZv8wDOPhl0VB4SU0QVzHT0MmEi2k27qc3n7PS', '456', 0),
(10, 'dumal', '$2y$10$AHPAOhEvUYTbNY7/pxVE9O.OLK.vc/oIZwmwTiJT4Qfx1RQE0hDo.', 'dumal', 0),
(11, 'ana', '$2y$10$GdRnEdZ9fEyoigdxEAxmCel3dYVfdzaNhOxoWKBNs1rNP901okUBG', 'ana', 0),
(12, 'Akin', '$2y$10$58EgDilE7a5ia5/kVA0PH.eqfQKOW/wehrVTKdKdJFFgpIW5orzqW', 'Adalberto', 0),
(13, 'Anenha', '$2y$10$TbwfgOIFuFAaYJflMI8MV.MIHQKVB.oOcGtyw08sVsAMhi5Vlq1Lu', 'Ana Luiza', 0),
(14, 'LAY', '$2y$10$usJQ4qbkv/.1brma6dYBrOHK0rdjlI2gqR6FaMTF7f51SwqJ9s.2i', 'LAY', 0),
(15, 'LAYs', '$2y$10$OD97Q6WTOgoDut9LJWYqluq0dhVA2azQMxOzaRAG1h0CpKFdBMaam', 'LAYs', 0),
(16, 'OI', '$2y$10$pRLobuKcQxdu3Ezf/.hg1eSnNJtLchmlVlhUQTvv6SWhllOvjIxKK', 'OI', 0),
(17, 'eae', '$2y$10$o8QbgpjcjZXB/Kn8IciBEei2FUAIsT2D3oARwQQD4AQTrx4wDtkmC', 'eae', 0),
(18, 'sd', '$2y$10$g3g7UnMCkt4FDzqFVO.k.uuI5eVWJPk5EPKdqYb2niEFMSjZS5N2O', 'sd', 0),
(19, 'bye', '$2y$10$4unhwoA.nUfdfvVCvczPOeRXvr/UoCVaauoO.MPRmR2UfBisVgH7S', 'bye', 0),
(23, 'sa', '$2y$10$jOtFCEBjHHD1eDmFLp68peJ3d/cnmXNU.6J54Ww/YaezlsreaB2t2', 'sa', 0),
(24, 'sa', '$2y$10$qBQZldUj8yRujRDNzsdisO4ko0vZR/4fPJwx494rbl5OueCE6jvai', 'sa', 0),
(25, 'sa', '$2y$10$y2byhwLXwXpRKUO9iiP.fOIrtaR23N0b.B.1ONLLcNrPRZnBulet.', 'sa', 0),
(26, 'ae', '$2y$10$kS8c6iBKRmmXZ38CKGuVuu9Vkb8mvjaH7TW3TsAx5ztIcdHOr8Eq2', 'ae', 1);

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
-- Índices de tabela `curtidas`
--
ALTER TABLE `curtidas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_postagem` (`id_postagem`,`id_usuario`),
  ADD KEY `curtidas_ibfk_2` (`id_usuario`);

--
-- Índices de tabela `postagens`
--
ALTER TABLE `postagens`
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
-- AUTO_INCREMENT de tabela `comentarios`
--
ALTER TABLE `comentarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT de tabela `curtidas`
--
ALTER TABLE `curtidas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=145;

--
-- AUTO_INCREMENT de tabela `postagens`
--
ALTER TABLE `postagens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

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
-- Restrições para tabelas `curtidas`
--
ALTER TABLE `curtidas`
  ADD CONSTRAINT `curtidas_ibfk_1` FOREIGN KEY (`id_postagem`) REFERENCES `postagens` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `curtidas_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `postagens`
--
ALTER TABLE `postagens`
  ADD CONSTRAINT `fk_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
