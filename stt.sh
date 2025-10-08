#!/bin/bash
# (c) J~Net 2025
# Record 10 seconds from USB mic and transcribe full sentence
# Removes timestamps and replaces dashes with spaces

output_wav="input.wav"
record_duration=10
AUDIO_DEVICE="hw:2,0"
whisper_executable="./build/bin/whisper-cli"
whisper_model="/home/jay/Documents/Scripts/HTML/ai-tts-stt-chat-bot/whisper.cpp/models/ggml-tiny.en.bin"

# Record full duration
echo "🎤 Recording for $record_duration seconds..."
arecord -D "$AUDIO_DEVICE" -f S16_LE -r 48000 -c1 "$output_wav" --duration=$record_duration
[ ! -f "$output_wav" ] && { echo "❌ Recording failed."; exit 1; }

# Optional: trim silence at start/end
if command -v sox >/dev/null 2>&1; then
    sox "$output_wav" tmp.wav silence 1 0.1 1% 1 0.5 1%
    mv tmp.wav "$output_wav"
fi

# Run Whisper on full audio and extract only words
echo "🤖 Transcribing full audio..."
transcript=$("$whisper_executable" -m "$whisper_model" -f "$output_wav" 2>/dev/null | \
    sed -E 's/^\[[0-9:.]+ --> [0-9:.]+\][[:space:]]*//g' | tr '\n' ' ' | sed -E 's/ +/ /g')

# Replace dashes with spaces
transcript=${transcript//-/ }

# Output full sentence
echo "📝 Full sentence: $transcript"

