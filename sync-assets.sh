#!/bin/bash
# Sync assets from root to public directory (for One.com compatibility)

echo "🔄 Syncing assets to public directory..."
cp -r assets/* public/assets/
echo "✅ Assets synced successfully!"
echo "📁 Files copied to public/assets/"
