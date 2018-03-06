#!/bin/bash
touch /tmp/dependancy_ruby_in_progress
echo 0 > /tmp/dependancy_ruby_in_progress
echo "Lancement de l'installation ruby - Launch install of ruby"
sudo apt-get clean
echo 5 > /tmp/dependancy_ruby_in_progress
sudo apt-get update
echo 20 > /tmp/dependancy_ruby_in_progress
sudo apt-get install -y ruby
echo 40 > /tmp/dependancy_ruby_in_progress
sudo gem install httparty
echo 60 > /tmp/dependancy_ruby_in_progress
sudo gem install concurrent-ruby
echo 80 > /tmp/dependancy_ruby_in_progress
sudo gem install thread
echo 100 > /tmp/dependancy_ruby_in_progress
echo "Tout est installé avec succès - Everything is successfully installed!"
rm /tmp/dependancy_ruby_in_progress
