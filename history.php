/* Main content styles */
.main {
    position: relative;
    min-height: 100vh;
    margin-left: 260px;
    padding: 20px;
    transition: all 0.3s ease;
    width: calc(100% - 260px);
    z-index: 1;
}

/* When sidebar is closed */
.sidebar.close ~ .main {
    margin-left: 78px;
    width: calc(100% - 78px);
}

/* Logs container styles */
.logs-container {
    background-color: rgba(255, 255, 255, 0.8);
    border-radius: 20px;
    padding: 25px;
    margin: 20px;
    box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
    width: calc(100% - 40px);
    transition: all 0.3s ease;
}

.main.active .logs-container {
    width: calc(100% - 40px);
    margin: 20px;
}

/* Responsive styles */
@media (max-width: 768px) {
    .sidebar {
        width: 260px;
        transform: translateX(0);
    }
    
    .sidebar.close {
        transform: translateX(-100%) !important;
        width: 260px !important;
    }
    
    .main {
        margin-left: 0 !important;
        width: 100% !important;
    }
    
    .main.collapsed {
        margin-left: 0 !important;
        width: 100% !important;
    }
}