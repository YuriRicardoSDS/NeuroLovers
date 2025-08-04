-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 04/08/2025 às 20:19
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
(1, 19, 'oi', NULL, '2025-08-04 16:06:04'),
(2, 19, '', 'uploads/post_6890da9f759c6.gif', '2025-08-04 16:06:55');

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
(19, 'bye', '$2y$10$4unhwoA.nUfdfvVCvczPOeRXvr/UoCVaauoO.MPRmR2UfBisVgH7S', 'bye', 0);

--
-- Índices para tabelas despejadas
--

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
-- AUTO_INCREMENT de tabela `postagens`
--
ALTER TABLE `postagens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `postagens`
--
ALTER TABLE `postagens`
  ADD CONSTRAINT `fk_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
