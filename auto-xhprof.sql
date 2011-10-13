--
-- 表的结构 `ax_error`
--

CREATE TABLE IF NOT EXISTS `ax_error` (
  `id` varchar(64) NOT NULL,
  `type` int(11) NOT NULL COMMENT '错误类型',
  `message` varchar(512) NOT NULL COMMENT '错误信息详细',
  `file` varchar(256) NOT NULL COMMENT '错误文件名',
  `line` int(11) NOT NULL COMMENT '错误文件的行数',
  PRIMARY KEY (`id`)
);

--
-- 表的结构 `ax_log`
--

CREATE TABLE IF NOT EXISTS `ax_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `host` varchar(128) NOT NULL COMMENT 'host地址',
  `uri` varchar(256) NOT NULL COMMENT 'uri信息',
  `resp_time` float NOT NULL COMMENT '响应时间',
  `error_id` varchar(64) NOT NULL COMMENT '是否有错误信息',
  `xhprof_id` varchar(64) NOT NULL COMMENT '是否有xhprof数据',
  `client_time` datetime NOT NULL COMMENT '客户端提交时间',
  `log_time` datetime NOT NULL COMMENT '数据写入时间',
  PRIMARY KEY (`id`)
);

--
-- 表的结构 `ax_xhprof`
--

CREATE TABLE IF NOT EXISTS `ax_xhprof` (
  `id` varchar(64) NOT NULL,
  `type` varchar(512) NOT NULL,
  `data` longtext NOT NULL,
  PRIMARY KEY (`id`)
);
