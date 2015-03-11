-- phpMyAdmin SQL Dump
-- version 3.5.6
-- http://www.phpmyadmin.net
--
-- 主机: 127.0.0.1
-- 生成日期: 2015 年 03 月 11 日 09:50
-- 服务器版本: 5.6.10
-- PHP 版本: 5.5.14

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- 数据库: `flytestdb`
--

-- --------------------------------------------------------

--
-- 表的结构 `fly_session`
--

CREATE TABLE IF NOT EXISTS `fly_session` (
  `id` char(32) NOT NULL,
  `expire` int(11) unsigned NOT NULL DEFAULT '0',
  `data` blob NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `fly_session`
--

INSERT INTO `fly_session` (`id`, `expire`, `data`) VALUES
('bm610v3k8pkdl3vpr43jn77f40', 1425983218, 0x617574687c733a31313a227a686f7568616e676a756e223b);

-- --------------------------------------------------------

--
-- 表的结构 `fly_user`
--

CREATE TABLE IF NOT EXISTS `fly_user` (
  `uid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` char(20) NOT NULL,
  `password` char(32) NOT NULL,
  `phone` char(20) NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=9 ;

--
-- 转存表中的数据 `fly_user`
--

INSERT INTO `fly_user` (`uid`, `username`, `password`, `phone`) VALUES
(1, 'zhj222', '123456', '18667115882'),
(2, 'hizz', '123456789', '18667115863'),
(3, 'flyzz', '123456', '18667115884'),
(4, 'zhouhangjun', '123456', '1365701235'),
(6, 'zhouhangjun', '123456', '1365701235'),
(7, 'zhouhangjun', '123456', '1365701235'),
(8, 'zhouhangjun', '123456', '1365701235');

-- --------------------------------------------------------

--
-- 表的结构 `fly_user_info`
--

CREATE TABLE IF NOT EXISTS `fly_user_info` (
  `uid` int(11) NOT NULL,
  `qq` char(20) DEFAULT '',
  `email` char(50) NOT NULL DEFAULT '',
  `sex` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `fly_user_info`
--

INSERT INTO `fly_user_info` (`uid`, `qq`, `email`, `sex`) VALUES
(1, '505171269', 'hiegoer@gmail.com', 1),
(2, '5051712692', 'zz@flyzz.net', 0),
(3, '50517126923', 'sdr@gmail.com', 0);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
