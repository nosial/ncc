#!/bin/bash
set -e

# Download redistributable source
wget -O ncc-2.0.0.tar.gz https://git.n64.cc/nosial/ncc/-/archive/dev/ncc-dev.tar.gz

# Extract and install
tar -xzvf ncc-2.0.0.tar.gz
mv ncc-dev ncc-2.0.0
cd ncc-2.0.0
make redist tar
sudo mkdir -p /usr/share/ncc
sudo tar -xzvf build/ncc_2.0.0.tar.gz -C /usr/share/ncc/
