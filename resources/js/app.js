import './bootstrap';
import './geo-address-picker';
import { companyLogoField } from './company-logo-field';
import { employeeFichaForm } from './employee-ficha-form';
import { createCompanyForm } from './create-company-form';
import { firstAdminAccess } from './first-admin-access';
import { installationAreaFields } from './colombian-area';
import { installationSiteForm } from './installation-site-form';
import { observatoryBoard } from './observatory-board';
import { observatoryIntake } from './observatory-intake';
import { observatoryMap } from './observatory-map';
import { postEmployeePicker } from './post-employee-picker';
import { employeeReassignForm } from './employee-reassign-form';
import { panelSidebar } from './panel-sidebar';
import './employee-document-indexer';

import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.data('panelSidebar', panelSidebar);
Alpine.data('companyLogoField', companyLogoField);
Alpine.data('employeeFichaForm', employeeFichaForm);
Alpine.data('createCompanyForm', createCompanyForm);
Alpine.data('firstAdminAccess', firstAdminAccess);
Alpine.data('installationAreaFields', installationAreaFields);
Alpine.data('installationSiteForm', installationSiteForm);
Alpine.data('observatoryBoard', observatoryBoard);
Alpine.data('observatoryIntake', observatoryIntake);
Alpine.data('observatoryMap', observatoryMap);
Alpine.data('postEmployeePicker', postEmployeePicker);
Alpine.data('employeeReassignForm', employeeReassignForm);

Alpine.start();
