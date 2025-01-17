<?php
class UserManager {
    private $configPath;
    private $users;
    private $settings;
    
    public function __construct() {
        $this->configPath = __DIR__ . '/../config/users.json';
        $this->loadConfig();
    }
    
    private function loadConfig() {
        if (!file_exists($this->configPath)) {
            throw new Exception('User configuration not found');
        }
        
        $config = json_decode(file_get_contents($this->configPath), true);
        if (!$config) {
            throw new Exception('Invalid user configuration');
        }
        
        $this->users = $config['users'] ?? [];
        $this->settings = $config['settings'] ?? [];
    }
    
    public function getUserId($username) {
        return $this->users[$username]['id'] ?? null;
    }
    
    public function verifyAccess($username) {
        // Check if user exists
        if (!isset($this->users[$username])) {
            $this->logAccessAttempt($username, 'User not found');
            return false;
        }
        
        $user = $this->users[$username];
        
        // Check IP restrictions if enabled
        if ($this->settings['require_ip_match'] ?? false) {
            if (!empty($user['allowed_ips'])) {
                $clientIp = $_SERVER['REMOTE_ADDR'];
                if (!in_array($clientIp, $user['allowed_ips'])) {
                    $this->logAccessAttempt($username, 'IP not allowed: ' . $clientIp);
                    return false;
                }
            }
        }
        
        // Verify authentication token if provided
        $providedToken = $_GET['token'] ?? null;
        if ($providedToken) {
            if (!$this->verifyToken($user, $providedToken)) {
                $this->logAccessAttempt($username, 'Invalid token');
                return false;
            }
        }
        
        // Log successful access
        $this->logAccessAttempt($username, 'Success');
        return true;
    }
    
    private function verifyToken($user, $providedToken) {
        // Token verification logic here
        // This is a placeholder - implement proper token verification
        return hash_equals($user['auth_token'], $providedToken);
    }
    
    private function logAccessAttempt($username, $status) {
        if ($this->settings['log_access'] ?? false) {
            $logEntry = sprintf(
                "[%s] Access attempt for %s from %s: %s\n",
                date('Y-m-d H:i:s'),
                $username,
                $_SERVER['REMOTE_ADDR'],
                $status
            );
            
            error_log($logEntry, 3, __DIR__ . '/../logs/access.log');
        }
    }
}
