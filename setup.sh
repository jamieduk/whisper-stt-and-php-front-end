#!/bin/bash

git clone https://github.com/ggml-org/whisper.cpp.git
git clone https://github.com/jamieduk/whisper-stt-and-php-front-end.git
sudo mv whisper-stt-and-php-front-end/{*,.*} whisper.cpp/ 2>/dev/null
sudo rm -rf whisper-stt-and-php-front-end
cd whisper.cpp.git
make


