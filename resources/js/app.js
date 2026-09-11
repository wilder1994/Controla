import './bootstrap';
import './geo-address-picker';
import { companyLogoField } from './company-logo-field';
import { employeeFichaForm } from './employee-ficha-form';
import { createCompanyForm } from './create-company-form';
import { firstAdminAccess } from './first-admin-access';
import { installationAreaFields } from './colombian-area';
import { installationSiteForm } from './installation-site-form';
import { observatoryIntake } from './observatory-intake';
import { observatoryMap } from './observatory-map';
import { postEmployeePicker } from './post-employee-picker';
import { employeeReassignForm } from './employee-reassign-form';
import './employee-document-indexer';

import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.data('companyLogoField', companyLogoField);
Alpine.data('employeeFichaForm', employeeFichaForm);
Alpine.data('createCompanyForm', createCompanyForm);
Alpine.data('firstAdminAccess', firstAdminAccess);
Alpine.data('installationAreaFields', installationAreaFields);
Alpine.data('installationSiteForm', installationSiteForm);
Alpine.data('observatoryIntake', observatoryIntake);
Alpine.data('observatoryMap', observatoryMap);
Alpine.data('postEmployeePicker', postEmployeePicker);
Alpine.data('employeeReassignForm', employeeReassignForm);

Alpine.start();
