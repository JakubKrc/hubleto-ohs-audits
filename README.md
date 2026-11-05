# Ohs Audits

Audits of the branches with validity, documents, calendar and the home panel of upcoming audits.

Hubleto app of the Ohs set (occupational health service, Slovakia). Namespace: `Hubleto\App\External\WaiBlue\OhsAudits`.
Needs: `wai-blue/hubleto-ohs-core`, `wai-blue/hubleto-ohs-branches`, `wai-blue/hubleto-ohs-clients`.

## Install into a Hubleto project

```bash
composer config repositories.wai-blue/hubleto-ohs-audits vcs <repository-url>
composer require wai-blue/hubleto-ohs-audits:dev-main
php hubleto app install "Hubleto\App\External\WaiBlue\OhsAudits"
npm run build
```
