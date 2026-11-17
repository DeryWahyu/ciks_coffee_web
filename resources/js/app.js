// Owner and login pages do not download the employee realtime/audio bundle.
if (window.CiksEmployeeNotifications?.userId || document.querySelector('[data-order-queue-revision]')) {
    import('./employee.js');
}
