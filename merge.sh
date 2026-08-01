#!/bin/bash

# Ordner mit HTML-Dateien
INPUT_DIR="./html"
# Ziel-PDF
OUTPUT_PDF="output.pdf"
# Temporärer Ordner für Einzel-PDFs
TEMP_DIR="./temp_pdfs"

# Prüfen, ob WeasyPrint installiert ist
if ! command -v weasyprint &> /dev/null; then
    echo "❌ WeasyPrint ist nicht installiert. Bitte mit 'sudo apt install weasyprint' installieren."
    exit 1
fi

# Prüfen, ob pdfunite installiert ist
if ! command -v pdfunite &> /dev/null; then
    echo "❌ pdfunite ist nicht installiert. Bitte mit 'sudo apt install poppler-utils' installieren."
    exit 1
fi

# Temporären Ordner vorbereiten
mkdir -p "$TEMP_DIR"
rm -f "$TEMP_DIR"/*.pdf

# HTML-Dateien sortiert verarbeiten
counter=1
for file in $(ls "$INPUT_DIR"/*.html | sort); do
    base=$(basename "$file" .html)
    out="$TEMP_DIR/$(printf "%03d_%s.pdf" "$counter" "$base")"
    echo "📄 Konvertiere: $file → $out"
    weasyprint "$file" "$out"
    ((counter++))
done

# PDFs zusammenfügen
echo "🧩 Füge PDFs zusammen zu: $OUTPUT_PDF"
pdfunite "$TEMP_DIR"/*.pdf "$OUTPUT_PDF"

if [ -d "$TEMP_DIR" ]; then
  echo "🧹 Lösche temporären Ordner: $TEMP_DIR"
  rm -rf "$TEMP_DIR"
else
  echo "ℹ️ Temporärer Ordner nicht gefunden: $TEMP_DIR"
fi


echo "✅ Fertig! Ergebnis: $OUTPUT_PDF"
