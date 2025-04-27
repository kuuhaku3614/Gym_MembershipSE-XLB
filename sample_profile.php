<style>/* Profile Section - fixed alignment */
.profile-section {
    padding: 50px 0;
    background-color: var(--background-light);
    width: 100%;
}

.profile-content {
    display: grid;
    grid-template-columns: 1fr 3fr;
    gap: 30px;
    width: 100%;
}

.profile-sidebar {
    background-color: var(--background-white);
    padding: 30px;
    border-radius: 10px;
    box-shadow: var(--shadow);
    text-align: center;
}

.profile-picture {
    position: relative;
    width: 120px;
    height: 120px;
    margin: 0 auto 20px;
}

.profile-picture img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid var(--primary-color);
}

.change-photo-btn {
    position: absolute;
    bottom: 0;
    right: 0;
    background-color: var(--primary-color);
    color: white;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    cursor: pointer;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
}

.profile-sidebar h3 {
    margin: 0;
    color: var(--text-dark);
    font-size: 1.3rem;
    font-weight: 600;
}

.profile-sidebar p {
    color: var(--text-light);
    margin-top: 5px;
    margin-bottom: 25px;
}

.profile-nav {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.profile-nav-btn {
    background: none;
    border: none;
    text-align: left;
    padding: 10px 15px;
    border-radius: 5px;
    cursor: pointer;
    color: var(--text-light);
    font-weight: 500;
    transition: var(--transition);
}

.profile-nav-btn:hover {
    background-color: var(--background-light);
    color: var(--primary-color);
}

.profile-nav-btn.active {
    background-color: var(--primary-color);
    color: white;
}

.profile-details {
    background-color: var(--background-white);
    padding: 30px;
    border-radius: 10px;
    box-shadow: var(--shadow);
}

.profile-tab {
    display: none;
}

.profile-tab.active {
    display: block;
}

/* Form Styles that apply to profile section */
.form-row {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 20px;
    flex: 1;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: var(--text-dark);
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="tel"],
.form-group input[type="password"],
.form-group select {
    width: 100%;
    padding: 12px;
    border: 1px solid var(--border-color);
    border-radius: 5px;
    font-size: 1rem;
}

.form-group input:focus,
.form-group select:focus {
    border-color: var(--primary-color);
    outline: none;
}

/* Toggle Switch */
.toggle-switch {
    display: flex;
    align-items: center;
}

.toggle-switch input[type="checkbox"] {
    height: 0;
    width: 0;
    visibility: hidden;
    position: absolute;
}

.toggle-switch label {
    cursor: pointer;
    width: 50px;
    height: 25px;
    background: #bdc3c7;
    display: block;
    border-radius: 25px;
    position: relative;
    margin-right: 10px;
}

.toggle-switch label:after {
    content: '';
    position: absolute;
    top: 3px;
    left: 3px;
    width: 19px;
    height: 19px;
    background: #fff;
    border-radius: 50%;
    transition: 0.3s;
}

.toggle-switch input:checked + label {
    background: var(--primary-color);
}

.toggle-switch input:checked + label:after {
    left: calc(100% - 3px);
    transform: translateX(-100%);
}

.toggle-label {
    color: var(--primary-color);
    font-weight: 600;
}

/* Notification Settings */
.notification-settings {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.notification-option {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 15px;
    border-bottom: 1px solid #f0f0f0;
}

.notification-info h4 {
    margin: 0 0 5px;
    color: var(--text-dark);
    font-weight: 600;
}

.notification-info p {
    margin: 0;
    color: var(--text-light);
    font-size: 0.9rem;
}

/* Wallet Tab */
.wallet-info {
    padding: 15px 0;
}

.wallet-info h3 {
    margin-top: 0;
    color: var(--text-dark);
    font-weight: 600;
}

.wallet-info p {
    color: var(--text-light);
    margin-bottom: 25px;
}

.wallet-address {
    margin-bottom: 30px;
}

.wallet-address label {
    display: block;
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 10px;
}

.address-display {
    display: flex;
}

.address-display input {
    flex: 1;
    padding: 12px;
    border: 1px solid var(--border-color);
    border-radius: 5px 0 0 5px;
    font-family: monospace;
    background-color: var(--background-light);
    font-size: 0.9rem;
}

.address-display button {
    border-radius: 0 5px 5px 0;
    padding: 0 15px;
}

.wallet-connection {
    margin-bottom: 30px;
}

.wallet-connection h4 {
    color: var(--text-dark);
    margin-bottom: 15px;
    font-weight: 600;
}

.connected-service {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background-color: var(--background-light);
    border-radius: 8px;
}

.service-info {
    display: flex;
    align-items: center;
}

.service-info img {
    width: 30px;
    height: 30px;
    margin-right: 15px;
}

.service-info h5 {
    margin: 0;
    color: var(--text-dark);
    font-weight: 600;
}

.service-info p {
    margin: 5px 0 0;
    color: var(--text-light);
    font-size: 0.8rem;
}

.wallet-actions {
    display: flex;
    gap: 15px;
    margin-top: 20px;
}

.btn-sm {
    padding: 5px 10px;
    font-size: 0.8rem;
}

/* Responsive Styles for profile section */
@media (max-width: 1200px) {
    .profile-content {
        grid-template-columns: 1fr;
    }
    
    .profile-nav {
        flex-direction: row;
        flex-wrap: wrap;
    }
    
    .profile-nav-btn {
        flex: 1;
        min-width: 120px;
        text-align: center;
    }
}

@media (max-width: 768px) {
    .form-row {
        flex-direction: column;
        gap: 0;
    }
    
    .wallet-actions {
        flex-direction: column;
    }
}</style>
<!-- Profile Section -->
<section id="profile" class="profile-section">
            <div class="container">
                <div class="section-header">
                    <h2>Profile Settings</h2>
                    <p>Manage your account information</p>
                </div>
                <div class="profile-content">
                    <div class="profile-sidebar">
                        <div class="profile-picture">
                            <img src="../svg/profile-placeholder.svg" alt="Profile Picture">
                            <button class="change-photo-btn"><i class="fas fa-camera"></i></button>
                        </div>
                        <h3 id="profile-name">Gerby Hallasgo</h3>
                        <p id="profile-email">hallasgogerby@email.com</p>
                        <div class="profile-nav">
                            <button class="profile-nav-btn active" data-tab="personal">Personal Information</button>
                            <button class="profile-nav-btn" data-tab="security">Security</button>
                            <button class="profile-nav-btn" data-tab="notifications">Notifications</button>
                            <button class="profile-nav-btn" data-tab="wallet">Blockchain Wallet</button>
                        </div>
                    </div>
                    <div class="profile-details">
                        <div class="profile-tab active" id="personal-tab">
                            <form id="personal-form">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="first-name">First Name</label>
                                        <input type="text" id="first-name" value="Gerby">
                                    </div>
                                    <div class="form-group">
                                        <label for="last-name">Last Name</label>
                                        <input type="text" id="last-name" value="Hallasgo">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" value="hallasgogerby@email.com">
                                </div>
                                <div class="form-group">
                                    <label for="phone">Phone</label>
                                    <input type="tel" id="phone" value="+63 956 230 7646">
                                </div>
                                <div class="form-group">
                                    <label for="institution">Institution</label>
                                    <input type="text" id="institution" value="Western Mindanao State University">
                                </div>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </form>
                        </div>
                        <div class="profile-tab" id="security-tab">
                            <form id="security-form">
                                <div class="form-group">
                                    <label for="current-password">Current Password</label>
                                    <input type="password" id="current-password">
                                </div>
                                <div class="form-group">
                                    <label for="new-password">New Password</label>
                                    <input type="password" id="new-password">
                                </div>
                                <div class="form-group">
                                    <label for="confirm-password">Confirm New Password</label>
                                    <input type="password" id="confirm-password">
                                </div>
                                <div class="form-group">
                                    <label>Two-Factor Authentication</label>
                                    <div class="toggle-switch">
                                        <input type="checkbox" id="tfa-toggle">
                                        <label for="tfa-toggle"></label>
                                        <span class="toggle-label">Enabled</span>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">Update Security Settings</button>
                            </form>
                        </div>
                        <div class="profile-tab" id="notifications-tab">
                            <div class="notification-settings">
                                <div class="notification-option">
                                    <div class="notification-info">
                                        <h4>Credential Updates</h4>
                                        <p>Receive notifications when your credentials are issued or updated</p>
                                    </div>
                                    <div class="toggle-switch">
                                        <input type="checkbox" id="credential-notif" checked>
                                        <label for="credential-notif"></label>
                                    </div>
                                </div>
                                <div class="notification-option">
                                    <div class="notification-info">
                                        <h4>Verification Alerts</h4>
                                        <p>Get notified when someone verifies your shared credentials</p>
                                    </div>
                                    <div class="toggle-switch">
                                        <input type="checkbox" id="verification-notif" checked>
                                        <label for="verification-notif"></label>
                                    </div>
                                </div>
                                <div class="notification-option">
                                    <div class="notification-info">
                                        <h4>Security Alerts</h4>
                                        <p>Important security notifications about your account</p>
                                    </div>
                                    <div class="toggle-switch">
                                        <input type="checkbox" id="security-notif" checked>
                                        <label for="security-notif"></label>
                                    </div>
                                </div>
                                <div class="notification-option">
                                    <div class="notification-info">
                                        <h4>Marketing Updates</h4>
                                        <p>News and updates about VerifiED platform</p>
                                    </div>
                                    <div class="toggle-switch">
                                        <input type="checkbox" id="marketing-notif">
                                        <label for="marketing-notif"></label>
                                    </div>
                                </div>
                                <button class="btn btn-primary">Save Notification Preferences</button>
                            </div>
                        </div>
                        <div class="profile-tab" id="wallet-tab">
                            <div class="wallet-info">
                                <h3>Blockchain Wallet</h3>
                                <p>Your credentials are securely stored using blockchain technology</p>
                                
                                <div class="wallet-address">
                                    <label>Your Wallet Address</label>
                                    <div class="address-display">
                                        <input type="text" value="0x7a3b9e2f8c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f" readonly>
                                        <button class="btn-icon"><i class="fas fa-copy"></i></button>
                                    </div>
                                </div>
                                
                                <div class="wallet-connection">
                                    <h4>Connected Services</h4>
                                    <div class="connected-service">
                                        <div class="service-info">
                                            <img src="../svg/metamask.svg" alt="Metamask">
                                            <div>
                                                <h5>MetaMask</h5>
                                                <p>Connected on Apr 5, 2025</p>
                                            </div>
                                        </div>
                                        <button class="btn btn-outline btn-sm">Disconnect</button>
                                    </div>
                                </div>
                                
                                <div class="wallet-actions">
                                    <button class="btn btn-secondary"><i class="fas fa-plug"></i> Connect Different Wallet</button>
                                    <button class="btn btn-outline"><i class="fas fa-download"></i> Export Private Key</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>