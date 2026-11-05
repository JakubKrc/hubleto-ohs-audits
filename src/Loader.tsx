import React from 'react';
import App from '@hubleto/react-ui/core/App'
import Translator from '@hubleto/react-ui/core/Translator';
import FormCustomizer from '@hubleto/react-ui/core/FormCustomizer';
import { type FormMeta } from '@hubleto/react-ui/components/fc/FormInterfaces';
import TableAudits from './Components/FC/TableAudits'

const parentApp = 'Hubleto/App/External/WaiBlue/OhsAudits';
const T = new Translator(parentApp + '/Loader', 'Loader');

// "Audity" tab in the branch form (OhsBranches).
function mountBranchAuditsTab() {
  return {
    title: T.translate('Audits'),
    content: (form: FormMeta) => {
      if (form.id <= 0) return <div className='alert alert-info'>{T.translate('Save the branch first.')}</div>;
      return <TableAudits uid={form.uid + '_table_audits'} parentForm={form} idBranch={form.id} />;
    },
  };
}

class OhsAuditsApp extends App {
  init() {
    super.init();
    globalThis.hubleto.registerReactComponent('OhsAuditsTableAudits', TableAudits);
    FormCustomizer.addTab('FormBranch', 'ohs-audits', mountBranchAuditsTab);
  }
}

globalThis.hubleto.registerApp(parentApp, new OhsAuditsApp());
