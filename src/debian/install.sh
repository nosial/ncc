#!/bin/bash
set -e
# compile and make ta
cd ..
make redist tar

# extract compiled tar to /usr/share/ncc directory 
sudo mkdir -p /usr/share/ncc
sudo tar -xzvf build/ncc_2.0.0.tar.gz -C /usr/share/ncc/
