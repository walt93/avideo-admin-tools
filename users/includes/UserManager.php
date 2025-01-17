<?php
class UserManager {
    private $configPath;
    private $users;
    
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
    }
    
    public function getUserId($username) {
        return $this->users[$username]['id'] ?? null;
    }
    
    public function verifyAccess($username) {
        // Check if user exists in config
        if (!isset($this->users[$username])) {
            $this->logAccessAttempt($username, 'User not configured');
            return false;
        }
        
        // Get authenticated user from Apache
        $auth_user = $_SERVER['PHP_AUTH_USER'] ?? null;
        
        // Verify the authenticated user matches the requested user or is admin
        if ($auth_user !== $username && $auth_user !== 'walt') {
            $this->logAccessAttempt($username, 'Authentication mismatch');
            return false;
        }
        
        $this->logAccessAttempt($username, 'Success via Apache auth');
        return true;
    }
    
    private function logAccessAttempt($username, $status) {
        $logEntry = sprintf(
            "[%s] Access attempt for %s from %s by %s: %s\n",
            date('Y-m-d H:i:s'),
            $username,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['PHP_AUTH_USER'] ?? 'unknown',
            $status
        );
        
        error_log($logEntry, 3, __DIR__ . '/../logs/access.log');
    }
}
