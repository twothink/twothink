/*
Navicat MySQL Data Transfer

Source Server         : 127.0.0.1
Source Server Version : 50516
Source Host           : 127.0.0.1:3306
Source Database       : thinkphp

Target Server Type    : MYSQL
Target Server Version : 50516
File Encoding         : 65001

Date: 2014-1-23 16:57:34
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `onethink_login`
-- ----------------------------
DROP TABLE IF EXISTS `twothink_login`;
CREATE TABLE `twothink_login` (
  `login_id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL COMMENT '用户UID',
  `type_uid` varchar(255) NOT NULL COMMENT '授权登陆用户名 第三方分配的appid',
  `type` char(80) NOT NULL COMMENT '登陆类型 qq|sina',
  `oauth_token` varchar(150) DEFAULT NULL COMMENT '授权账号',
  `oauth_token_secret` varchar(150) DEFAULT NULL COMMENT '授权密码',
  PRIMARY KEY (`login_id`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ts_login
-- ----------------------------