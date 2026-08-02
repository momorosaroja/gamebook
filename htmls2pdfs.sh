#!/bin/bash
INPUT_DIR="./html_with_pagenumbers"
OUTPUT_DIR="./pdf"

mkdir -p "$OUTPUT_DIR"

for file in "$INPUT_DIR"/*.html; do
    filename=$(basename "$file" .html)
    google-chrome --headless --disable-gpu \
        --print-to-pdf="$OUTPUT_DIR/$filename.pdf" \
        "$file"
done
