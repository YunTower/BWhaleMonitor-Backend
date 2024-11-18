-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- 主机： localhost
-- 生成日期： 2023-08-16 19:27:14
-- 服务器版本： 5.7.26
-- PHP 版本： 8.0.2
SET
    SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

START TRANSACTION;

SET
    time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT = @@CHARACTER_SET_CLIENT */
;

/*!40101 SET @OLD_CHARACTER_SET_RESULTS = @@CHARACTER_SET_RESULTS */
;

/*!40101 SET @OLD_COLLATION_CONNECTION = @@COLLATION_CONNECTION */
;

/*!40101 SET NAMES utf8mb4 */
;

--
-- 数据库： `yt_monitor`
--
-- --------------------------------------------------------
--
-- 表的结构 `yt_monitor_server`
--
CREATE TABLE `yt_monitor_server`
(
    `id`       int UNSIGNED NOT NULL COMMENT 'ID',
    `name`     text         NOT NULL COMMENT '名称',
    `os`       text                  DEFAULT NULL COMMENT '系统',
    `ip`       text         NOT NULL COMMENT 'IP',
    `location` text                  DEFAULT NULL COMMENT '位置',
    `cpu`      json                  DEFAULT NULL COMMENT 'CPU',
    `memory`   int                   DEFAULT NULL COMMENT '内存',
    `dick`     json                  DEFAULT NULL COMMENT '存储',
    `status`   int          NOT NULL COMMENT '状态 0 离线 1 在线 2 异常',
    `uptime`   int                   DEFAULT NULL COMMENT '运行时间',
    `created`  timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '创建时间'
) ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4;

--
-- 表的结构 `yt_monitor_setting`
--
CREATE TABLE `yt_monitor_setting`
(
    `id`    int UNSIGNED NOT NULL COMMENT 'ID',
    `name`  text         NOT NULL COMMENT '名称',
    `value` text         NOT NULL COMMENT '值'
) ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4;

--
-- 表的结构 `yt_monitor_log`
--
CREATE TABLE `yt_monitor_log`
(
    `id`      int UNSIGNED NOT NULL COMMENT 'ID',
    `server_id` int UNSIGNED NOT NULL COMMENT '服务器ID',
    `name`    varchar(30)  NOT NULL COMMENT '名称',
    `value`   varchar(64)  NOT NULL COMMENT '值',
    `created` timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '创建时间'
) ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4;

--
-- 转储表的索引
--
--
-- 表的索引 `yt_monitor_server`
--
ALTER TABLE
    `yt_monitor_server`
    ADD
        PRIMARY KEY (`id`);

--
-- 表的索引 `yt_monitor_setting`
--
ALTER TABLE
    `yt_monitor_setting`
    ADD
        PRIMARY KEY (`id`);

--
-- 表的索引 `yt_monitor_log`
--
ALTER TABLE
    `yt_monitor_log`
    ADD
        PRIMARY KEY (`id`);

--
-- 在导出的表使用AUTO_INCREMENT
--
-- 使用表AUTO_INCREMENT `yt_monitor_server`
--
ALTER TABLE
    `yt_monitor_server`
    MODIFY
        `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 1;

--
-- 使用表AUTO_INCREMENT `yt_monitor_setting`
--
ALTER TABLE
    `yt_monitor_setting`
    MODIFY
        `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 1;

--
-- 使用表AUTO_INCREMENT `yt_monitor_log`
--
ALTER TABLE
    `yt_monitor_log`
    MODIFY
        `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 1;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT = @OLD_CHARACTER_SET_CLIENT */
;

/*!40101 SET CHARACTER_SET_RESULTS = @OLD_CHARACTER_SET_RESULTS */
;

/*!40101 SET COLLATION_CONNECTION = @OLD_COLLATION_CONNECTION */
;