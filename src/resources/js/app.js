import { initializeDashboard } from './features/dashboard.js';
import { initializeLogin } from './features/login.js';
import { initializeProductCreate } from './features/product-create.js';
import { initializeProductEdit } from './features/product-edit.js';

initializeLogin();
initializeProductCreate();
initializeProductEdit();
initializeDashboard();
