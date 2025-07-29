class OfflineManager {
    constructor() {
        this.dbName = 'qrAttendanceDB';
        this.dbVersion = 1;
        this.db = null;
        this.isOnline = navigator.onLine;
        this.syncInProgress = false;
        this.pendingSync = false;

        // Initialize IndexedDB
        this.initDB().then(() => {
            this.setupEventListeners();
            if (this.isOnline) {
                this.syncData();
            }
        });
    }

    async initDB() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, this.dbVersion);

            request.onerror = (event) => {
                console.error('Error opening IndexedDB:', event.target.error);
                reject(event.target.error);
            };

            request.onsuccess = (event) => {
                this.db = event.target.result;
                resolve();
            };

            request.onupgradeneeded = (event) => {
                const db = event.target.result;

                // Create object stores for different types of data
                if (!db.objectStoreNames.contains('attendance')) {
                    db.createObjectStore('attendance', { keyPath: 'id', autoIncrement: true });
                }
                if (!db.objectStoreNames.contains('syncQueue')) {
                    db.createObjectStore('syncQueue', { keyPath: 'id', autoIncrement: true });
                }
            };
        });
    }

    setupEventListeners() {
        // Listen for online/offline events
        window.addEventListener('online', () => {
            this.isOnline = true;
            this.showNotification('System is back online');
            this.syncData();
        });

        window.addEventListener('offline', () => {
            this.isOnline = false;
            this.showNotification('System is offline - data will be cached locally');
        });
    }

    async cacheAttendanceData(data) {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['attendance'], 'readwrite');
            const store = transaction.objectStore('attendance');

            const request = store.add({
                ...data,
                timestamp: new Date().toISOString(),
                synced: false
            });

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async getPendingAttendanceData() {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['attendance'], 'readonly');
            const store = transaction.objectStore('attendance');
            const request = store.getAll();

            request.onsuccess = () => {
                const data = request.result.filter(item => !item.synced);
                resolve(data);
            };
            request.onerror = () => reject(request.error);
        });
    }

    async markAttendanceAsSynced(ids) {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['attendance'], 'readwrite');
            const store = transaction.objectStore('attendance');

            ids.forEach(id => {
                const request = store.get(id);
                request.onsuccess = () => {
                    const data = request.result;
                    data.synced = true;
                    store.put(data);
                };
            });

            transaction.oncomplete = () => resolve();
            transaction.onerror = () => reject(transaction.error);
        });
    }

    async syncData() {
        if (this.syncInProgress || !this.isOnline) {
            this.pendingSync = true;
            return;
        }

        this.syncInProgress = true;

        try {
            const pendingData = await this.getPendingAttendanceData();
            if (pendingData.length === 0) {
                this.syncInProgress = false;
                if (this.pendingSync) {
                    this.pendingSync = false;
                    this.syncData();
                }
                return;
            }

            // Send data to server
            const response = await fetch('api/sync-attendance.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(pendingData)
            });

            if (!response.ok) {
                throw new Error('Sync failed');
            }

            const result = await response.json();
            
            // Mark synced data
            if (result.success) {
                await this.markAttendanceAsSynced(pendingData.map(item => item.id));
                this.showNotification('Data synced successfully');
            }

        } catch (error) {
            console.error('Sync error:', error);
            this.showNotification('Error syncing data', 'error');
        } finally {
            this.syncInProgress = false;
            if (this.pendingSync) {
                this.pendingSync = false;
                this.syncData();
            }
        }
    }

    showNotification(message, type = 'success') {
        // Check if notification container exists
        let container = document.getElementById('notification-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'notification-container';
            container.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
            `;
            document.body.appendChild(container);
        }

        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show`;
        notification.style.cssText = `
            margin-bottom: 10px;
            min-width: 300px;
        `;
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        // Add to container
        container.appendChild(notification);

        // Remove after 5 seconds
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }

    // Helper method to check if system is online
    checkOnlineStatus() {
        return this.isOnline;
    }
}

// Initialize offline manager
const offlineManager = new OfflineManager();

// Export for use in other files
window.offlineManager = offlineManager; 