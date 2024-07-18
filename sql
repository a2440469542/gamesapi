CREATE TABLE `cp_black`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `pix` varchar(50) NOT NULL COMMENT 'pix',
  `add_time` datetime NOT NULL COMMENT '拉黑时间',
  PRIMARY KEY (`id`),
  UNIQUE INDEX(`id`)
);
ALTER TABLE `cp_user`
ADD COLUMN `lock_money` float(10, 2) NOT NULL DEFAULT 0 COMMENT '冻结额度' AFTER `money`;
ALTER TABLE `cp_bank_black`
ADD COLUMN `type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '类型：1=pix;2=ip' AFTER `id`;

ALTER TABLE `cp_channel`
ADD COLUMN `service_path` varchar(255) NULL COMMENT '客服链接' AFTER `tema`,
ADD COLUMN `tg_path` varchar(255) NULL COMMENT 'tg链接' AFTER `service_path`;
CREATE TABLE `cp_agent`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '代理ID',
  `pid` json NULL COMMENT '平台ID，多个数组',
  `mobile` varchar(30) NOT NULL COMMENT '手机号',
  `pwd` varchar(32) NOT NULL COMMENT '密码',
  `reg_time` datetime NOT NULL COMMENT '注册时间',
  `reg_ip` varchar(35) NOT NULL COMMENT '注册IP',
  `last_login_time` datetime NULL COMMENT '最后登录时间',
  `last_login_ip` varchar(30) NULL COMMENT '最后登录ip',
  PRIMARY KEY (`id`),
  UNIQUE INDEX(`id`)
) COMMENT = '代理数据表';