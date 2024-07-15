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