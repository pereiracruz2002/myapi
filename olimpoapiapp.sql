-- MySQL dump 10.13  Distrib 5.6.31, for debian-linux-gnu (i686)
--
-- Host: localhost    Database: olimpoapiapp
-- ------------------------------------------------------
-- Server version	5.6.31-0ubuntu0.15.10.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `comentarios`
--

DROP TABLE IF EXISTS `comentarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `comentarios` (
  `comentario_id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_treinos_id` int(11) NOT NULL,
  `usuarios_usuarios_id` int(11) NOT NULL,
  `comentario` varchar(45) DEFAULT NULL,
  `status` enum('disabled','enabled') DEFAULT NULL,
  PRIMARY KEY (`comentario_id`,`usuario_treinos_id`,`usuarios_usuarios_id`),
  KEY `fk_comentarios_usuario_treinos1_idx` (`usuario_treinos_id`),
  KEY `fk_comentarios_usuarios1_idx` (`usuarios_usuarios_id`),
  CONSTRAINT `fk_comentarios_usuario_treinos1` FOREIGN KEY (`usuario_treinos_id`) REFERENCES `usuario_treinos` (`usuario_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_comentarios_usuarios1` FOREIGN KEY (`usuarios_usuarios_id`) REFERENCES `usuarios` (`usuarios_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comentarios`
--

LOCK TABLES `comentarios` WRITE;
/*!40000 ALTER TABLE `comentarios` DISABLE KEYS */;
/*!40000 ALTER TABLE `comentarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `enderecos`
--

DROP TABLE IF EXISTS `enderecos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `enderecos` (
  `endereco_id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `estado` varchar(2) DEFAULT NULL,
  `cidade` varchar(255) DEFAULT NULL,
  `latitude` mediumtext,
  `longitude` mediumtext,
  `local` varchar(255) DEFAULT NULL,
  `status` enum('enable','disabled') DEFAULT NULL,
  `tipo_endereco` int(11) NOT NULL,
  PRIMARY KEY (`endereco_id`,`usuario_id`,`tipo_endereco`),
  KEY `fk_atendimentos_usuarios1_idx` (`usuario_id`),
  KEY `fk_enderecos_tipo_endereco1_idx` (`tipo_endereco`),
  CONSTRAINT `fk_atendimentos_usuarios1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`usuarios_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_enderecos_tipo_endereco1` FOREIGN KEY (`tipo_endereco`) REFERENCES `tipo_endereco` (`tipo_endereco_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='l';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `enderecos`
--

LOCK TABLES `enderecos` WRITE;
/*!40000 ALTER TABLE `enderecos` DISABLE KEYS */;
/*!40000 ALTER TABLE `enderecos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `especialidades`
--

DROP TABLE IF EXISTS `especialidades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `especialidades` (
  `especialidade_id` int(11) NOT NULL AUTO_INCREMENT,
  `especialidade` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`especialidade_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `especialidades`
--

LOCK TABLES `especialidades` WRITE;
/*!40000 ALTER TABLE `especialidades` DISABLE KEYS */;
/*!40000 ALTER TABLE `especialidades` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(40) NOT NULL DEFAULT '0',
  `ip_address` varchar(16) NOT NULL DEFAULT '0',
  `timestamp` int(10) unsigned zerofill NOT NULL,
  `data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('18n4s1aga1n30ga645pc0e71n36dk4i0','127.0.0.1',1514923555,'__ci_last_regenerate|i:1514923555;'),('tccbsa2og98asqjiidm48a1pmc43naon','127.0.0.1',1514924530,'__ci_last_regenerate|i:1514924530;'),('20ftb2ot6qfpgvftktqcikcrqsg7p40s','127.0.0.1',1514928406,'__ci_last_regenerate|i:1514928406;'),('ks31f5jjci0kb2qmu1vu6lmqlepavfm5','127.0.0.1',1514929532,'__ci_last_regenerate|i:1514929528;');
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipo_endereco`
--

DROP TABLE IF EXISTS `tipo_endereco`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tipo_endereco` (
  `tipo_endereco_id` int(11) NOT NULL,
  `tipo_endereco` enum('RESIDENCIAL','COMERCIAL','ATENDIMENTO') DEFAULT NULL,
  PRIMARY KEY (`tipo_endereco_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tipo_endereco`
--

LOCK TABLES `tipo_endereco` WRITE;
/*!40000 ALTER TABLE `tipo_endereco` DISABLE KEYS */;
/*!40000 ALTER TABLE `tipo_endereco` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipos`
--

DROP TABLE IF EXISTS `tipos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tipos` (
  `tipo_id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`tipo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tipos`
--

LOCK TABLES `tipos` WRITE;
/*!40000 ALTER TABLE `tipos` DISABLE KEYS */;
/*!40000 ALTER TABLE `tipos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuario_treinos`
--

DROP TABLE IF EXISTS `usuario_treinos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuario_treinos` (
  `usuario_treinos_id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `especialidade_id` int(11) NOT NULL,
  `status` enum('disabled','enabled') DEFAULT NULL,
  `preco_medio` float(20,2) DEFAULT NULL,
  PRIMARY KEY (`usuario_treinos_id`,`usuario_id`,`especialidade_id`),
  KEY `fk_usuario_treinos_usuarios1_idx` (`usuario_id`),
  KEY `fk_usuario_treinos_especialidades1_idx` (`especialidade_id`),
  CONSTRAINT `fk_usuario_treinos_especialidades1` FOREIGN KEY (`especialidade_id`) REFERENCES `especialidades` (`especialidade_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_usuario_treinos_usuarios1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`tipo_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario_treinos`
--

LOCK TABLES `usuario_treinos` WRITE;
/*!40000 ALTER TABLE `usuario_treinos` DISABLE KEYS */;
/*!40000 ALTER TABLE `usuario_treinos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `usuarios_id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) DEFAULT NULL,
  `sobrenome` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `sexo` enum('masculino','feminino') DEFAULT NULL,
  `nascimento` date DEFAULT NULL,
  `estado` varchar(2) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `status` enum('enable','disabled') DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `latitude` mediumtext,
  `longitude` mediumtext,
  `foto` varchar(255) DEFAULT NULL,
  `tipo_id` int(11) NOT NULL,
  `descricao` text,
  PRIMARY KEY (`usuarios_id`,`tipo_id`),
  KEY `fk_usuarios_tipos_idx` (`tipo_id`),
  CONSTRAINT `fk_usuarios_tipos` FOREIGN KEY (`tipo_id`) REFERENCES `tipos` (`tipo_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `visualizacao`
--

DROP TABLE IF EXISTS `visualizacao`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `visualizacao` (
  `visualizacao_id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`visualizacao_id`,`usuario_id`),
  KEY `fk_visualizacao_usuarios1_idx` (`usuario_id`),
  CONSTRAINT `fk_visualizacao_usuarios1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`usuarios_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `visualizacao`
--

LOCK TABLES `visualizacao` WRITE;
/*!40000 ALTER TABLE `visualizacao` DISABLE KEYS */;
/*!40000 ALTER TABLE `visualizacao` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2018-01-02 21:26:14
