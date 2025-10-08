#!/bin/bash
#(c)J~Net jnetai.com 2025
# ./setup.sh
#
#
echo "Starting Setup..."
git clone https://github.com/ggml-org/whisper.cpp.git
git clone https://github.com/jamieduk/whisper-stt-and-php-front-end.git
sudo mv whisper-stt-and-php-front-end/{*,.*} whisper.cpp/ 2>/dev/null
sudo rm -rf whisper-stt-and-php-front-end
cd whisper.cpp.git
sudo chmod +x *.sh
make
echo "Setup Complete!"

