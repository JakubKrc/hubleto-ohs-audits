<?php

namespace Hubleto\App\External\WaiBlue\OhsAudits\Models\Migrations;

use Hubleto\Framework\Migration;

// The audit state is a workflow step (workflow review Q1); the audit workflow is created in Loader::installApp().
class Audit_0001 extends Migration
{
  public function upgradeSchema(): void
  {
    $this->db->execute("CREATE TABLE IF NOT EXISTS `ohs_audits` (
      `id` int(8) primary key auto_increment,
      `id_branch` int(8) NULL DEFAULT NULL,
      `date_executed` date NULL DEFAULT NULL,
      `date_valid_to` date NULL DEFAULT NULL,
      `note` text NULL DEFAULT NULL,
      `id_hygienist` int(8) NULL DEFAULT NULL,
      `date_created` datetime NULL DEFAULT NULL,
      `id_workflow` int(8) NULL DEFAULT NULL,
      `id_workflow_step` int(8) NULL DEFAULT NULL,
      INDEX `id_branch` (`id_branch`),
      INDEX `date_executed` (`date_executed`),
      INDEX `date_valid_to` (`date_valid_to`),
      INDEX `id_workflow` (`id_workflow`),
      INDEX `id_workflow_step` (`id_workflow_step`)
    ) ENGINE=InnoDB");
  }

  public function downgradeSchema(): void
  {
    $this->db->execute("DROP TABLE IF EXISTS `ohs_audits`");
  }

  public function upgradeForeignKeys(): void
  {
    // A branch with audits cannot be deleted (both legacy apps); branches are deactivated instead.
    $this->db->execute("ALTER TABLE `ohs_audits` ADD CONSTRAINT `fk__ohs_audits__id_branch` FOREIGN KEY (`id_branch`) REFERENCES `ohs_branches` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT");
    // Foreign key of the audit's hygienist (models and lookups review B6).
    $this->db->execute("ALTER TABLE `ohs_audits` ADD CONSTRAINT `fk__ohs_audits__id_hygienist` FOREIGN KEY (`id_hygienist`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT");
    $this->db->execute("ALTER TABLE `ohs_audits` ADD CONSTRAINT `fk__ohs_audits__id_workflow` FOREIGN KEY (`id_workflow`) REFERENCES `workflows` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT");
    $this->db->execute("ALTER TABLE `ohs_audits` ADD CONSTRAINT `fk__ohs_audits__id_workflow_step` FOREIGN KEY (`id_workflow_step`) REFERENCES `workflow_steps` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT");
  }

  public function downgradeForeignKeys(): void
  {
    $this->db->execute("ALTER TABLE `ohs_audits` DROP FOREIGN KEY `fk__ohs_audits__id_branch`");
    $this->db->execute("ALTER TABLE `ohs_audits` DROP FOREIGN KEY `fk__ohs_audits__id_hygienist`");
    $this->db->execute("ALTER TABLE `ohs_audits` DROP FOREIGN KEY `fk__ohs_audits__id_workflow`");
    $this->db->execute("ALTER TABLE `ohs_audits` DROP FOREIGN KEY `fk__ohs_audits__id_workflow_step`");
  }
}
