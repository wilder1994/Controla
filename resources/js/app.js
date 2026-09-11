import './bootstrap';
import './geo-address-picker';
import { companyLogoField } from './company-logo-field';
import { employeeFichaForm } from './employee-ficha-form';
import { createCompanyForm } from './create-company-form';
import { firstAdminAccess } from './first-admin-access';
import { installationSiteForm } from './installation-site-form';
import { postEmployeePicker } from './post-employee-picker';
import { employeeReassignForm } from './employee-reassign-form';

import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.data('companyLogoField', companyLogoField);
Alpine.data('employeeFichaForm', employeeFichaForm);
Alpine.data('createCompanyForm', createCompanyForm);
Alpine.data('firstAdminAccess', firstAdminAccess);
Alpine.data('installationSiteForm', installationSiteForm);
Alpine.data('postEmployeePicker', postEmployeePicker);
Alpine.data('employeeReassignForm', employeeReassignForm);

Alpine.start();
