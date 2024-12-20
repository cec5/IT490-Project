#!/bin/bash

# Check if running as sudo
if [ "$EUID" -ne 0 ]; then
  echo "Please run as root or use sudo."
  exit 1
fi

# Function to check the success of the last command
check_success() {
  if [ $? -ne 0 ]; then
    echo "Error: $1"
    exit 1
  fi
}

# Create a new user "deployer"
USERNAME="deployer"
PASSWORD="I\$Am\$The\$Deployer\$Of\$Worlds"
useradd -m -s /bin/bash $USERNAME
check_success "Failed to create user '$USERNAME'."

echo "$USERNAME:$PASSWORD" | chpasswd
check_success "Failed to set password for user '$USERNAME'."

# Add "deployer" to sudoers
if [ -x "$(command -v visudo)" ]; then
  echo "$USERNAME ALL=(ALL) NOPASSWD:ALL" | EDITOR='tee -a' visudo >/dev/null
  check_success "Failed to add '$USERNAME' to sudoers."
else
  echo "Warning: 'visudo' command not found. Skipping adding to sudoers."
fi

# Install and configure SSH server
if [ -x "$(command -v apt)" ]; then
  apt update && printf 'y2\n105' | apt install -y openssh-server
  check_success "Failed to install OpenSSH server."
elif [ -x "$(command -v yum)" ]; then
  yum install -y openssh-server
  check_success "Failed to install OpenSSH server."
elif [ -x "$(command -v dnf)" ]; then
  dnf install -y openssh-server
  check_success "Failed to install OpenSSH server."
else
  echo "Error: Package manager not recognized. Cannot install OpenSSH server."
  exit 1
fi

# Ensure SSH service is enabled and running
systemctl enable ssh
check_success "Failed to enable SSH service."

systemctl start ssh
check_success "Failed to start SSH service."

# Configure SSH to allow only "deployer"
SSHD_CONFIG="/etc/ssh/sshd_config"
cp $SSHD_CONFIG "$SSHD_CONFIG.bak" # Backup existing configuration
check_success "Failed to back up SSH configuration."

sed -i '/^AllowUsers/d' $SSHD_CONFIG
echo "AllowUsers $USERNAME" >> $SSHD_CONFIG
check_success "Failed to configure SSH to allow only '$USERNAME'."

systemctl restart ssh
check_success "Failed to restart SSH service."

echo "Setup completed successfully!"
exit 0

