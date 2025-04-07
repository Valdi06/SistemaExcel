/*
 Navicat Premium Data Transfer

 Source Server         : Valdi
 Source Server Type    : MySQL
 Source Server Version : 80017
 Source Host           : localhost:3306
 Source Schema         : pidecredito

 Target Server Type    : MySQL
 Target Server Version : 80017
 File Encoding         : 65001

 Date: 07/04/2025 11:12:14
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for batches
-- ----------------------------
DROP TABLE IF EXISTS `batches`;
CREATE TABLE `batches`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8mb4 COLLATE = utf8mb4 ROW_FORMAT = Dynamic;

SET FOREIGN_KEY_CHECKS = 1;
