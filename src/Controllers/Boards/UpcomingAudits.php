<?php

namespace Hubleto\App\External\WaiBlue\OhsAudits\Controllers\Boards;

use Hubleto\App\External\WaiBlue\OhsAudits\Models\Audit;

/**
 * Home dashboard panel "Najbližšie audity (max 1 mesiac)" (legacy home of hygienists): audits of own branches.
 */
class UpcomingAudits extends \Hubleto\Erp\Controller
{
  public bool $hideDefaultDesktop = true;

  public function prepareView(): void
  {
    parent::prepareView();

    $this->viewParams['audits'] = $this->getModel(Audit::class)->record->prepareReadQuery()
      ->with(['BRANCH'])
      ->where('ohs_audits.date_executed', '>=', date('Y-m-d'))
      ->where('ohs_audits.date_executed', '<=', date('Y-m-d', strtotime('+1 month')))
      ->orderBy('ohs_audits.date_executed', 'asc')
      ->get()
      ->toArray()
    ;
    $this->setView('@Hubleto:App:External:WaiBlue:OhsAudits/Boards/UpcomingAudits.twig');
  }
}
