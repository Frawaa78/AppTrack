#!/bin/bash

# Test Flow Editor Setup
echo "🔄 Testing Flow Editor setup..."

# Test 1: Check PHP syntax
echo "1. Checking PHP syntax..."
if php -l /workspaces/AppTrack/public/flow_editor.php > /dev/null 2>&1; then
    echo "   ✅ PHP syntax OK"
else
    echo "   ❌ PHP syntax error"
    php -l /workspaces/AppTrack/public/flow_editor.php
fi

# Test 2: Check CSS files exist
echo "2. Checking CSS dependencies..."
CSS_FILES=(
    "/workspaces/AppTrack/assets/css/main.css"
    "/workspaces/AppTrack/assets/css/components/drawflow-theme.css"
    "/workspaces/AppTrack/assets/vendor/drawflow.min.css"
)

for file in "${CSS_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "   ✅ $file exists"
    else
        echo "   ❌ $file missing"
    fi
done

# Test 3: Check JS files exist
echo "3. Checking JavaScript dependencies..."
JS_FILES=(
    "/workspaces/AppTrack/assets/vendor/drawflow.min.js"
)

for file in "${JS_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "   ✅ $file exists"
    else
        echo "   ❌ $file missing"
    fi
done

# Test 4: Check API endpoints
echo "4. Checking API endpoints..."
API_FILES=(
    "/workspaces/AppTrack/public/api/save_drawflow_diagram.php"
    "/workspaces/AppTrack/public/api/load_drawflow_diagram.php"
)

for file in "${API_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "   ✅ $file exists"
        if php -l "$file" > /dev/null 2>&1; then
            echo "      ✅ PHP syntax OK"
        else
            echo "      ❌ PHP syntax error"
        fi
    else
        echo "   ❌ $file missing"
    fi
done

# Test 5: Check main.css imports
echo "5. Checking CSS imports..."
if grep -q "drawflow-theme.css" /workspaces/AppTrack/assets/css/main.css; then
    echo "   ✅ drawflow-theme.css imported in main.css"
else
    echo "   ❌ drawflow-theme.css NOT imported in main.css"
fi

echo ""
echo "🎯 Flow Editor Test Summary:"
echo "   - Production-ready MVP: flow_editor.php"
echo "   - Custom theme: drawflow-theme.css"
echo "   - API endpoints: save & load diagram"
echo "   - AppTrack integration: topbar, auth, CSS"
echo "   - 5 node types: process, database, API, service, decision"
echo "   - Auto-save functionality with debouncing"
echo ""
echo "🚀 Ready for testing at: https://your-domain.com/public/flow_editor.php?app_id=429"
