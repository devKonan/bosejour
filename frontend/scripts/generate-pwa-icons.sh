#!/bin/bash

# Script pour générer les icônes PWA à partir du logo
# Usage: ./scripts/generate-pwa-icons.sh

set -e

LOGO_PATH="public/images/payment-methods/logo/logo.png"
ICONS_DIR="public/icons"

echo "🎨 Génération des icônes PWA..."
echo ""

# Vérifier que le logo existe
if [ ! -f "$LOGO_PATH" ]; then
    echo "❌ Logo non trouvé: $LOGO_PATH"
    exit 1
fi

# Créer le dossier icons
mkdir -p "$ICONS_DIR"

# Vérifier si ImageMagick est installé
if ! command -v convert &> /dev/null; then
    echo "⚠️  ImageMagick n'est pas installé"
    echo ""
    echo "Options:"
    echo "1. Installer ImageMagick:"
    echo "   macOS: brew install imagemagick"
    echo "   Ubuntu: sudo apt-get install imagemagick"
    echo ""
    echo "2. Utiliser un outil en ligne:"
    echo "   - https://realfavicongenerator.net/"
    echo "   - https://www.pwabuilder.com/imageGenerator"
    echo ""
    echo "3. Utiliser le logo existant et le redimensionner manuellement"
    exit 1
fi

echo "📐 Génération des icônes..."

# Générer toutes les tailles
sizes=(72 96 128 144 152 192 384 512)

for size in "${sizes[@]}"; do
    echo "  - Génération icon-${size}x${size}.png"
    convert "$LOGO_PATH" \
        -resize "${size}x${size}" \
        -background white \
        -gravity center \
        -extent "${size}x${size}" \
        "$ICONS_DIR/icon-${size}x${size}.png"
done

echo ""
echo "✅ Icônes générées dans: $ICONS_DIR"
echo ""
echo "📋 Fichiers créés:"
ls -lh "$ICONS_DIR"/*.png

echo ""
echo "✅ Génération terminée !"



