import { initializeDashboard } from './features/dashboard.js';
import { initializeLogin } from './features/login.js';
import { initializeProductCreate } from './features/product-create.js';
import { initializeProductEdit } from './features/product-edit.js';
import { initializeChangePassword } from './features/change-password.js';
import { initializeForgotPassword, initializeResetPassword } from './features/password-recovery.js';
import { initializeProfileEdit } from './features/profile-edit.js';
import { initializeProfile } from './features/profile.js';

initializeLogin();
initializeProductCreate();
initializeProductEdit();
initializeDashboard();
initializeChangePassword();
initializeForgotPassword();
initializeResetPassword();
initializeProfileEdit();
initializeProfile();
