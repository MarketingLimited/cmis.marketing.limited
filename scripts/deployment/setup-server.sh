#!/bin/bash

###############################################################################
# CMIS Server Setup Script
#
# Usage: ./setup-server.sh
#
# This script prepares a fresh Ubuntu/Debian server for CMIS deployment
###############################################################################

set -e

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Functions
log() { echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"; }
success() { echo -e "${GREEN}✓${NC} $1"; }
error() { echo -e "${RED}✗${NC} $1"; }
warning() { echo -e "${YELLOW}⚠${NC} $1"; }

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   error "This script must be run as root"
   exit 1
fi

log "Starting CMIS server setup..."

# Update system
log "Updating system packages..."
apt-get update
apt-get upgrade -y
success "System updated"

# Install required packages
log "Installing required packages..."
apt-get install -y \
    curl \
    wget \
    git \
    unzip \
    apt-transport-https \
    ca-certificates \
    software-properties-common \
    gnupg \
    lsb-release
success "Base packages installed"

# Install Docker
log "Installing Docker..."
if ! command -v docker &> /dev/null; then
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg | gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | tee /etc/apt/sources.list.d/docker.list > /dev/null
    apt-get update
    apt-get install -y docker-ce docker-ce-cli containerd.io
    success "Docker installed"
else
    success "Docker already installed"
fi

# Install Docker Compose
log "Installing Docker Compose..."
if ! command -v docker-compose &> /dev/null; then
    curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
    chmod +x /usr/local/bin/docker-compose
    success "Docker Compose installed"
else
    success "Docker Compose already installed"
fi

# Create application user
log "Creating application user..."
if ! id -u cmis &> /dev/null; then
    useradd -m -s /bin/bash -G docker cmis
    success "User 'cmis' created"
else
    success "User 'cmis' already exists"
fi

# Create application directory
log "Creating application directory..."
APP_DIR="/var/www/cmis"
mkdir -p "$APP_DIR"
chown cmis:cmis "$APP_DIR"
success "Application directory created: $APP_DIR"

# Create backup directory
log "Creating backup directory..."
BACKUP_DIR="/var/backups/cmis"
mkdir -p "$BACKUP_DIR"
chown cmis:cmis "$BACKUP_DIR"
success "Backup directory created: $BACKUP_DIR"

# Create log directory
log "Creating log directory..."
LOG_DIR="/var/log/cmis"
mkdir -p "$LOG_DIR"
chown cmis:cmis "$LOG_DIR"
success "Log directory created: $LOG_DIR"

# Configure Docker daemon
log "Configuring Docker daemon..."
cat > /etc/docker/daemon.json <<EOF
{
  "log-driver": "json-file",
  "log-opts": {
    "max-size": "10m",
    "max-file": "3"
  },
  "default-address-pools": [
    {
      "base": "172.18.0.0/16",
      "size": 24
    }
  ]
}
EOF
systemctl restart docker
success "Docker daemon configured"

# Enable Docker service
log "Enabling Docker service..."
systemctl enable docker
systemctl enable containerd
success "Docker service enabled"

# Configure firewall (UFW)
log "Configuring firewall..."
if command -v ufw &> /dev/null; then
    ufw allow 22/tcp    # SSH
    ufw allow 80/tcp    # HTTP
    ufw allow 443/tcp   # HTTPS
    ufw --force enable
    success "Firewall configured"
else
    warning "UFW not installed, skipping firewall configuration"
fi

# Configure fail2ban
log "Installing fail2ban..."
apt-get install -y fail2ban
systemctl enable fail2ban
systemctl start fail2ban
success "fail2ban installed and configured"

# Set up log rotation
log "Configuring log rotation..."
cat > /etc/logrotate.d/cmis <<EOF
/var/log/cmis/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 cmis cmis
    sharedscripts
}
EOF
success "Log rotation configured"

# Install monitoring tools
log "Installing monitoring tools..."
apt-get install -y htop iotop nethogs
success "Monitoring tools installed"

# Set up automatic security updates
log "Enabling automatic security updates..."
apt-get install -y unattended-upgrades
dpkg-reconfigure -plow unattended-upgrades
success "Automatic security updates enabled"

# Display system information
log "========================================"
log "Server Setup Summary:"
log "  Hostname: $(hostname)"
log "  OS: $(lsb_release -ds)"
log "  Docker: $(docker --version)"
log "  Docker Compose: $(docker-compose --version)"
log "  App Directory: $APP_DIR"
log "  Backup Directory: $BACKUP_DIR"
log "  Log Directory: $LOG_DIR"
log "========================================"

success "Server setup completed!"

log "Next steps:"
log "  1. Switch to cmis user: su - cmis"
log "  2. Clone repository: git clone <repo_url> $APP_DIR"
log "  3. Configure .env file"
log "  4. Run deployment: cd $APP_DIR && ./scripts/deployment/deploy.sh"

exit 0
