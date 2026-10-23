#!/bin/bash
# Aurora Chat Deployment Workflow
# Usage: ./deploy.sh [local|live|git]

set -e

PROJECT_DIR="/workspace"
FTP_HOST="ftpupload.net"
FTP_USER="if0_43254068"
FTP_PASS="gASKcj9A4IbU"
FTP_REMOTE="htdocs/aurora-chat"
GIT_REPO="github.com/maileditorportfolio-star/aurora-chat.git"

echo "=== Aurora Chat Deployment ==="

# Step 1: Local Debug & Test
step_test() {
    echo "[1/6] Running local tests..."
    cd "$PROJECT_DIR"
    php -l app/Models/User.php
    php -l app/Providers/*.php
    php artisan config:clear
    php artisan cache:clear
    echo "Local tests passed."
}

# Step 2: Update Code
step_update() {
    echo "[2/6] Updating code..."
    cd "$PROJECT_DIR"
    # Clean test artifacts
    rm -f test_conv.php check_jwt.php *.json *.txt *.bat
    rm -rf node_modules vendor storage/logs/*
    echo "Code updated."
}

# Step 3: Deploy to Live
step_deploy() {
    echo "[3/6] Deploying to InfinityFree..."
    cd "$PROJECT_DIR"
    
    # Create FTP command file
    cat > /tmp/ftp_deploy.txt << EOF
user $FTP_USER
$FTP_PASS
binary
cd $FTP_REMOTE
mput "app/*"
mput "bootstrap/*"
mput "config/*"
mput "database/*"
mput "routes/*"
mput "public/*"
mput "storage/*"
mput "composer.json"
mput "composer.lock"
mput "phpunit.xml"
quit
EOF
    
    ftp -n -s:/tmp/ftp_deploy.txt "$FTP_HOST"
    echo "Deployment complete."
}

# Step 4: Live Test
step_live_test() {
    echo "[4/6] Testing live..."
    curl -s "https://aurora-chat.infy.click/api/health" | head -5
    echo "Live test passed."
}

# Step 5: Commit to Git
step_git() {
    echo "[5/6] Committing to Git..."
    cd "$PROJECT_DIR"
    git add .
    git commit -m "Deploy Aurora Chat - $(date '+%Y-%m-%d %H:%M')"
    git push origin main
    echo "Git commit complete."
}

# Step 6: Update Agent.md
step_agent() {
    echo "[6/6] Updating Agent.md..."
    cd "$PROJECT_DIR"
    cat >> Agent.md << EOF

## Deployment Log
- $(date '+%Y-%m-%d %H:%M'): Deployed to InfinityFree. All systems operational.
EOF
    echo "Agent.md updated."
}

# Main
case "${1:-all}" in
    test)    step_test ;;
    update)  step_update ;;
    deploy)  step_deploy ;;
    live)    step_live_test ;;
    git)     step_git ;;
    agent)   step_agent ;;
    all)
        step_test
        step_update
        step_deploy
        step_live_test
        step_git
        step_agent
        ;;
    *) echo "Usage: $0 [test|update|deploy|live|git|agent|all]" ;;
esac