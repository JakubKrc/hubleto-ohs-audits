<?php

namespace Hubleto\App\External\WaiBlue\OhsAudits\Models;

use Hubleto\App\External\WaiBlue\OhsCore\Models\Traits\RefusesUnknownUser;
use Hubleto\App\External\WaiBlue\OhsCore\Services\Roles;
use Hubleto\App\External\WaiBlue\OhsCore\Controllers\Api\UserLookup;
use Hubleto\App\Community\Auth\Models\User;
use Hubleto\App\External\WaiBlue\OhsBranches\Models\Branch;
use Hubleto\App\External\WaiBlue\OhsCore\Services\MonthDates;
use Hubleto\Framework\Db\Column\Boolean;
use Hubleto\Framework\Db\Column\Date;
use Hubleto\Framework\Db\Column\DateTime;
use Hubleto\Framework\Exceptions\RecordSaveException;
use Hubleto\Framework\Db\Column\Lookup;
use Hubleto\Framework\Db\Column\Text;
use Hubleto\App\Community\Customers\Models\Customer;
use Hubleto\App\External\WaiBlue\OhsClients\Services\CustomerColumns;
use Hubleto\App\External\WaiBlue\OhsCore\Models\Traits\EmptyDatesAsNull;
use Hubleto\App\External\WaiBlue\OhsCore\Models\Traits\StaysInOwnWorkflow;
use Hubleto\App\Community\Workflow\Models\Workflow;
use Hubleto\App\Community\Workflow\Models\WorkflowStep;

/**
 * Audit (visit) of a branch by its hygienist. Valid until the next audit is due:
 * the audit date plus the client's audit interval, unless typed. Its state is a workflow step: open or closed,
 * what both legacy apps share (MioMed closes an audit, Medison sets it "Ukončený").
 */
class Audit extends \Hubleto\Erp\Model
{

  const WORKFLOW_GROUP = 'ohs_audits';

  public string $table = 'ohs_audits';
  public string $recordManagerClass = RecordManagers\Audit::class;
  // Date and branch, as in both legacy apps; the newest audits first.
  public ?string $lookupSqlValue = 'concat(date_format({%TABLE%}.date_executed, "%d.%m.%Y"),
    ifnull(concat(" - ", (select name from ohs_branches where ohs_branches.id = {%TABLE%}.id_branch)), ""))';
  public string $lookupOrderBy = '{%TABLE%}.date_executed desc';
  public ?string $lookupUrlDetail = 'ohs/audits/audits/{%ID%}';

  public array $relations = [
    'BRANCH' => [ self::BELONGS_TO, Branch::class, 'id_branch', 'id' ],
    'HYGIENIST' => [ self::BELONGS_TO, User::class, 'id_hygienist', 'id' ],
    'WORKFLOW' => [ self::BELONGS_TO, Workflow::class, 'id_workflow', 'id' ],
    'WORKFLOW_STEP' => [ self::BELONGS_TO, WorkflowStep::class, 'id_workflow_step', 'id' ],
  ];

  public function describeColumns(): array
  {
    return array_merge(parent::describeColumns(), [
      'id_branch' => (new Lookup($this, $this->translate('Branch'), Branch::class, 'RESTRICT'))
        ->setRequired()
        ->setDefaultVisible()
      ,
      'date_executed' => (new Date($this, $this->translate('Audit date')))
        ->setRequired()
        ->setDefaultVisible()
        ->setDefaultValue(date("Y-m-d"))
      ,
      'date_valid_to' => (new Date($this, $this->translate('Valid to')))
        ->setDefaultVisible()
        ->setHint($this->translate('Left empty, it is the audit date plus the audit interval of the client.'))
      ,
      'note' => (new Text($this, $this->translate('Note / purpose of the visit')))->setDefaultVisible(),
      'id_hygienist' => (new Lookup($this, $this->translate('Hygienist'), User::class))
        ->setEndpoint(UserLookup::endpointFor(Roles::HYGIENIST, 'id_hygienist'))
        ->setDefaultVisible()
        ->setDefaultValue($this->authProvider()->getUserId())
      ,
      'date_created' => (new DateTime($this, $this->translate('Created')))->setReadonly()->setDefaultValue(date("Y-m-d H:i:s")),
      'id_workflow' => (new Lookup($this, $this->translate('Workflow'), Workflow::class))->setReadonly(),
      'id_workflow_step' => (new Lookup($this, $this->translate('State'), WorkflowStep::class))
        ->setDefaultVisible()
        ->setReadonly()
      ,
    ]);
  }

  public function describeTable(): \Hubleto\Framework\Description\Table
  {
    $description = parent::describeTable();
    $description->ui['addButtonText'] = $this->translate('Add audit');
    $description->ui['orderBy'] = ['field' => 'date_executed', 'direction' => 'desc'];
    $description->show(['header', 'fulltextSearch', 'columnSearch', 'moreActionsButton']);
    $description->hide(['footer']);
    // In the branch's tab the branch is known, so its column is left out (both legacy apps).
    if ($this->router()->urlParamAsInteger('idBranch') > 0) $description->hideColumns(['id_branch']);

    $description->addFilter(
      RecordManagers\Audit::STATE_FILTER,
      Workflow::buildTableFilterForWorkflowSteps($this, $this->translate('State'))
    );

    return $description;
  }

  public function describeForm(): \Hubleto\Framework\Description\Form
  {
    $description = parent::describeForm();
    $description->defaultValues['date_executed'] = date('Y-m-d');
    $description->defaultValues['id_hygienist'] = $this->authProvider()->getUserId();
    return $description;
  }

  public function getRelationsIncludedInLoadTableData(): array|null
  {
    return ['BRANCH', 'HYGIENIST', 'WORKFLOW_STEP'];
  }

  public function getMaxReadLevelForLoadTableData(): int
  {
    return 1;
  }

  public function onBeforeCreate(array $record): array
  {
    $record = parent::onBeforeCreate($record);
    // $record = $this->dropSentWorkflow($record);
    // New audits start in the first step of the audit workflow ("open").
    /** @var Workflow */
    $mWorkflow = $this->getModel(Workflow::class);
    $record = $mWorkflow->applyDefaultWorkflow($record, self::WORKFLOW_GROUP);

    return $this->fillValidTo($record);
  }

  // The branch stays as created.
  public function onBeforeUpdate(array $record): array
  {
    $record = parent::onBeforeUpdate($record);
    // $this->refuseWorkflowOfOtherGroup($record, self::WORKFLOW_GROUP);

    $originalAudit = $this->record->where('id', (int) $record['id'])->first();
    if ($originalAudit) {
      $record['id_branch'] = $originalAudit->id_branch;
    }

    return $this->fillValidTo($record);
  }

  public function onAfterCreate(array $savedRecord): array
  {
    return parent::onAfterCreate($savedRecord);
  }

  // Left empty, "valid to" is the audit date plus the audit interval of the branch's client.
  private function fillValidTo(array $record): array
  {
    $hasAuditDate = !empty($record['date_executed']);
    $emptyValidTo = empty($record["date_valid_to"]);

    if ($hasAuditDate && $emptyValidTo) {
      $intervalMonths = $this->getAuditIntervalMonths((int) ($record['id_branch'] ?? 0));
      $record['date_valid_to'] = MonthDates::addMonths((string) $record['date_executed'], $intervalMonths);
    }

    $isValidBeforeAudit = $hasAuditDate && !empty($record['date_valid_to']) && $record['date_valid_to'] < $record['date_executed'];
    if ($isValidBeforeAudit) {
      throw new RecordSaveException($this->translate('The audit cannot be valid to a date before the audit.'));
    }
    return $record;
  }

  public function getAuditIntervalMonths(int $idBranch): int
  {
    /** @var CustomerColumns */
    $customerColumns = $this->getService(CustomerColumns::class);
    $defaultMonths = $customerColumns->getDefaultAuditIntervalMonths();

    /** @var Branch */
    $branch = $this->getModel(Branch::class)->record->where('id', $idBranch)->first();
    if (!$branch) return $defaultMonths;

    /** @var Customer */
    $customer = $this->getModel(Customer::class)->record->where('id', $branch->id_customer)->first();
    $clientMonths = $customer ? (int) $customer->ohs_audit_interval_months : 0;
    if ($clientMonths > 0) return $clientMonths;

    return $defaultMonths;
  }
}
