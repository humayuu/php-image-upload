/*
Navicat MySQL Data Transfer

Source Server         : Home
Source Server Version : 50505
Source Host           : localhost:3306
Source Database       : store_db

Target Server Type    : MYSQL
Target Server Version : 50505
File Encoding         : 65001

Date: 2025-08-28 14:37:17
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `product_tbl`
-- ----------------------------
DROP TABLE IF EXISTS `product_tbl`;
CREATE TABLE `product_tbl` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_name` varchar(255) DEFAULT NULL,
  `product_image` varchar(255) DEFAULT NULL,
  `product_multi_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of product_tbl
-- ----------------------------
