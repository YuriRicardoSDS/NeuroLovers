-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 03/10/2025 às 21:24
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
-- Estrutura para tabela `curtidas`
--

CREATE TABLE `curtidas` (
  `id` int(11) NOT NULL,
  `id_postagem` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `postagens`
--

CREATE TABLE `postagens` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `conteudo` text NOT NULL,
  `imagem` varchar(255) DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `formato` varchar(50) NOT NULL DEFAULT 'somente-texto',
  `tipo_analise` varchar(50) NOT NULL DEFAULT 'analise-aprofundada',
  `aviso_sensibilidade` varchar(50) NOT NULL DEFAULT 'sem-spoiler'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `postagens`
--

INSERT INTO `postagens` (`id`, `usuario_id`, `conteudo`, `imagem`, `data_criacao`, `formato`, `tipo_analise`, `aviso_sensibilidade`) VALUES
(106, 32, 'dsafdsa', NULL, '2025-10-03 18:50:15', 'somente-texto', 'resumo-rapido', 'alerta-luzes'),
(107, 32, 'fdhgdf', 'uploads/post_68e01b072dd15.png', '2025-10-03 18:50:47', 'texto-imagem', 'resumo-rapido', 'sem-spoiler'),
(108, 32, 'cxgvgsd', 'uploads/post_68e01db4e292a.png', '2025-10-03 19:02:13', 'texto-imagem', 'resumo-rapido', 'sem-spoiler');

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
(26, 'ae', '$2y$10$kS8c6iBKRmmXZ38CKGuVuu9Vkb8mvjaH7TW3TsAx5ztIcdHOr8Eq2', 'ae', 1),
(27, 'e', '$2y$10$DQqUHb4Bsu6LuyUtkPbQnecX1Zwfs2m5o6jYh8EzCibprK4AeeJS2', 'e', 0),
(28, 'y', '$2y$10$LiLTGiXqyfSc70yZ./nFSe6LoGsIOjnc2DvEnlQNDskMS1udvu5Dq', 'y', 0),
(32, 'k', '$2y$10$RWBvAWRHPTV0WOexKDgr4.jOntZjen9leOUuNg/p9uM4.C3ivBucO', 'k', 0),
(33, 'j', '$2y$10$b9hS3xUHERbChdLQUS.QwOUlRPpkzvGNEJxCpXCx0qOUqYy68qe1G', 'j', 0);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT de tabela `curtidas`
--
ALTER TABLE `curtidas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=173;

--
-- AUTO_INCREMENT de tabela `postagens`
--
ALTER TABLE `postagens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=110;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

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
