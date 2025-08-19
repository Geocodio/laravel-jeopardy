#!/bin/bash

# Laravel Jeopardy Buzzer Server Setup Script
# This script sets up the buzzer server as a systemd service on Raspberry Pi

set -e

WORK_DIR="/home/pi/laravel-jeopardy"
SERVICE_NAME="laravel-buzzer"
SERVICE_FILE="/etc/systemd/system/${SERVICE_NAME}.service"
LOG_DIR="/var/log/laravel-buzzer"
PHP_BIN="/usr/bin/php"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

log() {
    echo -e "${GREEN}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1" >&2
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   error "This script must be run as root (use sudo)"
   exit 1
fi

log "Starting Laravel Jeopardy Buzzer Server setup..."

# Check if work directory exists
if [ ! -d "$WORK_DIR" ]; then
    error "Directory $WORK_DIR does not exist!"
    exit 1
fi

# Check if PHP is installed
if [ ! -f "$PHP_BIN" ]; then
    error "PHP not found at $PHP_BIN. Please install PHP first."
    exit 1
fi

# Create log directory if it doesn't exist
if [ ! -d "$LOG_DIR" ]; then
    log "Creating log directory at $LOG_DIR..."
    mkdir -p "$LOG_DIR"
    chown pi:pi "$LOG_DIR"
fi

# Create wrapper script for the buzzer server
WRAPPER_SCRIPT="/usr/local/bin/buzzer-server-wrapper.sh"
log "Creating wrapper script at $WRAPPER_SCRIPT..."

cat > "$WRAPPER_SCRIPT" << 'EOF'
#!/bin/bash

WORK_DIR="/home/pi/laravel-jeopardy"
LOG_DIR="/var/log/laravel-buzzer"
LOG_FILE="$LOG_DIR/buzzer-server.log"
ERROR_LOG="$LOG_DIR/buzzer-server-error.log"
RESTART_LOG="$LOG_DIR/restart.log"

# Ensure log files exist and are writable
touch "$LOG_FILE" "$ERROR_LOG" "$RESTART_LOG"
chown pi:pi "$LOG_FILE" "$ERROR_LOG" "$RESTART_LOG"

log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

log_restart() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$RESTART_LOG"
}

cd "$WORK_DIR" || exit 1

log_message "Starting Laravel Buzzer Server..."
log_message "Working directory: $WORK_DIR"
log_message "PHP version: $(php -v | head -n1)"

RESTART_COUNT=0
MAX_RESTART_DELAY=60

while true; do
    log_message "Starting buzzer server (attempt #$((RESTART_COUNT + 1)))..."
    
    # Run the buzzer server and capture its exit code
    php artisan buzzer-server >> "$LOG_FILE" 2>> "$ERROR_LOG"
    EXIT_CODE=$?
    
    RESTART_COUNT=$((RESTART_COUNT + 1))
    
    if [ $EXIT_CODE -eq 0 ]; then
        log_message "Buzzer server exited normally with code 0"
        log_restart "Normal exit after $RESTART_COUNT restart(s)"
    else
        log_message "ERROR: Buzzer server crashed with exit code $EXIT_CODE"
        log_restart "CRASH: Exit code $EXIT_CODE after running. Restart #$RESTART_COUNT"
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Process crashed with exit code $EXIT_CODE" >> "$ERROR_LOG"
    fi
    
    # Calculate restart delay (exponential backoff with max limit)
    if [ $RESTART_COUNT -lt 5 ]; then
        DELAY=$((RESTART_COUNT * 2))
    else
        DELAY=$MAX_RESTART_DELAY
    fi
    
    log_message "Waiting $DELAY seconds before restart..."
    log_restart "Waiting $DELAY seconds before restart #$((RESTART_COUNT + 1))"
    sleep $DELAY
    
    log_message "Restarting buzzer server..."
done
EOF

chmod +x "$WRAPPER_SCRIPT"
log "Wrapper script created successfully"

# Create systemd service file
log "Creating systemd service file at $SERVICE_FILE..."

cat > "$SERVICE_FILE" << EOF
[Unit]
Description=Laravel Jeopardy Buzzer Server
After=network.target
StartLimitIntervalSec=0

[Service]
Type=simple
User=pi
Group=pi
WorkingDirectory=$WORK_DIR
ExecStart=$WRAPPER_SCRIPT
Restart=always
RestartSec=5

# Environment variables
Environment="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"
Environment="HOME=/home/pi"

# Logging
StandardOutput=journal
StandardError=journal
SyslogIdentifier=laravel-buzzer

# Resource limits
LimitNOFILE=1024
TimeoutStopSec=10

[Install]
WantedBy=multi-user.target
EOF

log "Service file created successfully"

# Reload systemd to recognize the new service
log "Reloading systemd daemon..."
systemctl daemon-reload

# Check if service is already running
if systemctl is-active --quiet "$SERVICE_NAME"; then
    warning "Service $SERVICE_NAME is already running"
    log "Restarting service to apply any changes..."
    systemctl restart "$SERVICE_NAME"
else
    log "Enabling service to start on boot..."
    systemctl enable "$SERVICE_NAME"
    
    log "Starting service..."
    systemctl start "$SERVICE_NAME"
fi

# Wait a moment for service to start
sleep 2

# Check service status
if systemctl is-active --quiet "$SERVICE_NAME"; then
    log "✓ Service is running successfully!"
else
    error "Service failed to start. Check logs with: journalctl -u $SERVICE_NAME -n 50"
    exit 1
fi

# Create convenience scripts
log "Creating convenience scripts..."

# Status check script
cat > "/usr/local/bin/buzzer-status" << 'EOF'
#!/bin/bash
echo "=== Laravel Buzzer Server Status ==="
systemctl status laravel-buzzer --no-pager
echo ""
echo "=== Recent Logs ==="
tail -n 20 /var/log/laravel-buzzer/buzzer-server.log
echo ""
echo "=== Recent Restarts ==="
tail -n 10 /var/log/laravel-buzzer/restart.log
EOF
chmod +x /usr/local/bin/buzzer-status

# Log viewer script
cat > "/usr/local/bin/buzzer-logs" << 'EOF'
#!/bin/bash
if [ "$1" == "-f" ]; then
    tail -f /var/log/laravel-buzzer/buzzer-server.log
else
    tail -n 50 /var/log/laravel-buzzer/buzzer-server.log
fi
EOF
chmod +x /usr/local/bin/buzzer-logs

# Error log viewer script
cat > "/usr/local/bin/buzzer-errors" << 'EOF'
#!/bin/bash
if [ "$1" == "-f" ]; then
    tail -f /var/log/laravel-buzzer/buzzer-server-error.log
else
    tail -n 50 /var/log/laravel-buzzer/buzzer-server-error.log
fi
EOF
chmod +x /usr/local/bin/buzzer-errors

log "Setup completed successfully!"
echo ""
echo "========================================="
echo "Laravel Buzzer Server has been installed!"
echo "========================================="
echo ""
echo "Service Management Commands:"
echo "  sudo systemctl status $SERVICE_NAME   - Check service status"
echo "  sudo systemctl start $SERVICE_NAME    - Start the service"
echo "  sudo systemctl stop $SERVICE_NAME     - Stop the service"
echo "  sudo systemctl restart $SERVICE_NAME  - Restart the service"
echo "  sudo journalctl -u $SERVICE_NAME -f   - View systemd logs"
echo ""
echo "Convenience Commands:"
echo "  buzzer-status          - Quick status and recent logs"
echo "  buzzer-logs            - View recent buzzer server logs"
echo "  buzzer-logs -f         - Follow buzzer server logs (live)"
echo "  buzzer-errors          - View recent error logs"
echo "  buzzer-errors -f       - Follow error logs (live)"
echo ""
echo "Log Files:"
echo "  $LOG_DIR/buzzer-server.log      - Main application log"
echo "  $LOG_DIR/buzzer-server-error.log - Error log"
echo "  $LOG_DIR/restart.log             - Restart history"
echo ""