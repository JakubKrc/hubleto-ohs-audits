<?php

namespace Hubleto\App\External\WaiBlue\OhsAudits\Models\Migrations;

use Hubleto\Framework\Migration;

class Audit_0001 extends Migration
{
  public function upgradeSchema(): void
  {
    $this->db->execute("CREATE TABLE IF NOT EXISTS `ohs_audits` (
      `id` int(8) primary key auto_increment,
      `id_branch` int(8) NULL DEFAULT NULL,
      `date_executed` date NULL DEFAULT NULL,
      `date_valid_to` date NULL DEFAULT NULL,
      `valid_to_typed` int(1) NOT NULL DEFAULT 0,
      `note` text NULL DEFAULT NULL,
      `id_hygienist` int(8) NULL DEFAULT NULL,
      INDEX `id_branch` (`id_branch`),
      INDEX `date_executed` (`date_executed`),
      INDEX `date_valid_to` (`date_valid_to`)
    ) ENGINE=InnoDB");
  }

  public function downgradeSchema(): void
  {
    $this->db->execute("DROP TABLE IF EXISTS `ohs_audits`");
  }

  public function upgradeForeignKeys(): void
  {
    $this->db->execute("ALTER TABLE `ohs_audits` ADD CONSTRAINT `fk__ohs_audits__id_branch` FOREIGN KEY (`id_branch`) REFERENCES `ohs_branches` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT");
  }

  public function downgradeForeignKeys(): void
  {
    $this->db->execute("ALTER TABLE `ohs_audits` DROP FOREIGN KEY `fk__ohs_audits__id_branch`");
  }
}
