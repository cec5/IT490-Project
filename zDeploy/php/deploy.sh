#!/bin/bash
DEPLOY_SERVER="172.23.193.68"
DEPLOY_USER="luke"                 
FRONTEND_PATH="../../frontend"           
BACKEND_PATH="../../backend"   
DMZ_PATH="../../dmz"   
DEPLOY_PATH="/home/luke/tomTest" 
TEST_PATH="/test"
PACKAGE_NAME="code_package_$(date +%Y%m%d_%H%M%S).tar.gz"
#Function to check the success of a command
check_success() {
  if [ $? -ne 0 ]; then
    echo "Error: $1"
    exit 1
  fi
}

#Bundle the code into a tar.gz package. Testing with only frontend first.
echo "Bundling code from $FRONTEND_PATH into $PACKAGE_NAME..."
tar -czf $PACKAGE_NAME -C "$FRONTEND_PATH" .
check_success "Failed to create package."   

#SCP the package to the deployment server
echo "Transferring package to $DEPLOY_SERVER:$DEPLOY_PATH..."
sshpass -p admin scp -r $TEST_PATH $DEPLOY_USER@$DEPLOY_SERVER:$DEPLOY_PATH
check_success "Failed to transfer package to the deployment server."
