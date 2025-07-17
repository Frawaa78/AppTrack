<?php
// scripts/setup_ai_features.php
// Setup script for AI features - Phase 1

require_once __DIR__ . '/../src/db/db.php';

echo "🚀 Setting up AI Features (Phase 1)...\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Read and execute SQL setup file - try multiple possible locations
    $possible_paths = [
        __DIR__ . '/../docs/ai-database-setup.sql',
        dirname(__DIR__) . '/docs/ai-database-setup.sql',
        realpath(__DIR__ . '/../docs/ai-database-setup.sql'),
        '/workspaces/AppTrack/docs/ai-database-setup.sql'
    ];
    
    $sql_file = null;
    foreach ($possible_paths as $path) {
        if ($path && file_exists($path)) {
            $sql_file = $path;
            break;
        }
    }
    
    if (!$sql_file) {
        echo "❌ Tried the following paths:\n";
        foreach ($possible_paths as $path) {
            echo "   - $path\n";
        }
        throw new Exception("SQL setup file not found in any expected location");
    }
    
    echo "📁 Using SQL file: $sql_file\n";
    
    $sql_content = file_get_contents($sql_file);
    
    // Split SQL into individual statements
    $statements = explode(';', $sql_content);
    
    echo "📊 Creating AI database tables...\n";
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            $db->exec($statement);
            
            // Extract table name for feedback
            if (preg_match('/CREATE TABLE\s+(\w+)/i', $statement, $matches)) {
                echo "✅ Created table: {$matches[1]}\n";
            } elseif (preg_match('/INSERT INTO\s+(\w+)/i', $statement, $matches)) {
                echo "📝 Inserted data into: {$matches[1]}\n";
            }
        } catch (PDOException $e) {
            // Skip if table already exists
            if (strpos($e->getMessage(), 'already exists') !== false) {
                if (preg_match('/CREATE TABLE\s+(\w+)/i', $statement, $matches)) {
                    echo "ℹ️  Table already exists: {$matches[1]}\n";
                }
            } else {
                throw $e;
            }
        }
    }
    
    echo "\n🔧 Verifying AI setup...\n";
    
    // Verify tables were created
    $tables = ['ai_analysis', 'data_snapshots', 'ai_configurations', 'ai_usage_log'];
    
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table verified: $table\n";
        } else {
            echo "❌ Table missing: $table\n";
        }
    }
    
    // Check if AI configurations were inserted
    $stmt = $db->query("SELECT COUNT(*) FROM ai_configurations");
    $config_count = $stmt->fetchColumn();
    echo "📋 AI configurations loaded: $config_count\n";
    
    echo "\n🔑 AI Configuration Setup:\n";
    echo "1. Set your OpenAI API key in src/config/config.php\n";
    echo "2. Or set environment variable: OPENAI_API_KEY\n";
    echo "3. Test the AI functionality in app_view.php\n";
    
    echo "\n✨ AI Features (Phase 1) setup complete!\n";
    echo "🤖 You can now use AI analysis in the application details view.\n";
    
} catch (Exception $e) {
    echo "❌ Setup failed: " . $e->getMessage() . "\n";
    echo "📋 Error details: " . $e->getTraceAsString() . "\n";
    exit(1);
}
?>
