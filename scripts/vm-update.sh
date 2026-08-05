#!/bin/bash
sudo apt update && sudo apt upgrade -y

if [ -f /var/run/reboot-required ]; then
    echo "Reboot required. Enabling maintenance mode..."
    bash "$(dirname "$0")/maintenance-on.sh"
    echo "Rebooting..."
    sudo reboot
else
    echo "Updates applied. No reboot required."
fi
