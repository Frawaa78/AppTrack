<?php
require_once '../src/config/config.php';
require_once '../src/db/db.php';
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Ensure user data is loaded in session
if (!isset($_SESSION['user_display_name']) || !isset($_SESSION['user_email'])) {
    try {
        $db = Database::getInstance();
        $pdo = $db->getConnection();
        
        $stmt = $pdo->prepare("SELECT email, display_name, role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_display_name'] = $user['display_name'];
            $_SESSION['user_role'] = $user['role'];
        }
    } catch (Exception $e) {
        // If we can't load user data, continue with what we have
    }
}

// Get application ID from URL parameter
$application_id = isset($_GET['application_id']) ? intval($_GET['application_id']) : null;

if (!$application_id) {
    // Redirect with more helpful error
    header('Location: /public/dashboard.php?error=AI Insights requires an application ID. Please select an application first.');
    exit;
}

// Get application details for context
try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $stmt = $pdo->prepare("SELECT short_description as name, business_need as description, status FROM applications WHERE id = ?");
    $stmt->execute([$application_id]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$application) {
        header('Location: /public/dashboard.php?error=Application not found. Please check the application ID.');
        exit;
    }
} catch (Exception $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Insights - <?php echo htmlspecialchars($application['name']); ?> | AppTrack</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/favicon/favicon-16x16.png">
    <link rel="manifest" href="../assets/favicon/site.webmanifest">
    <link rel="shortcut icon" href="../assets/favicon/favicon.ico">
    <!-- FontAwesome Pro -->
    <script src="https://kit.fontawesome.com/d67c79608d.js" crossorigin="anonymous"></script>
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- AppTrack CSS -->
    <link href="/assets/css/main.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/components/ai-analysis.css">
    
    <style>
        .header-with-buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: none;
        }
        
        .header-buttons {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .header-action-btn {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            border: 1px solid #ddd;
            background: #fff;
            color: #666;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .header-action-btn:hover {
            background: #f8f9fa;
            color: #333;
            text-decoration: none;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .header-action-btn i {
            margin-right: 6px;
        }
        
        .analysis-controls {
            margin-bottom: 20px;
            padding: 0;
            background: transparent;
            border-radius: 0;
        }
        
        .analysis-type-row {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 0;
            justify-content: flex-end;
            border: none !important;
            background: transparent !important;
            padding: 0 !important;
            box-shadow: none !important;
            outline: none !important;
        }
        
        .analysis-type-row *,
        .analysis-type-row *:before,
        .analysis-type-row *:after {
            border: none !important;
            box-shadow: none !important;
            outline: none !important;
        }
        
        .analysis-type-row .form-select:focus {
            border: 1px solid #86b7fe !important;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
        }
        
        .analysis-type-row label {
            margin: 0;
            white-space: nowrap;
            font-weight: 500;
        }
        
        .analysis-type-row select {
            flex: 1;
            max-width: 300px;
        }
        
        .analysis-type-row .btn {
            white-space: nowrap;
        }
        
        .analysis-content {
            min-height: 400px;
            border: none;
            border-radius: 0;
            padding: 0;
            background: transparent;
            box-shadow: none;
        }
        
        .loading-spinner {
            text-align: center;
            padding: 40px;
        }
        
        .analysis-result {
            background: #fff;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border: none;
        }
        
        .recent-analyses {
            margin-top: 30px;
        }
        
        .analysis-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 12px 16px;
            margin-bottom: 8px;
            background: #fff;
            transition: all 0.2s;
        }
        
        .analysis-item:hover {
            background: #f8f9fa;
            border-color: #dee2e6;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .analysis-meta {
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        
        .analysis-type {
            font-weight: 600;
            color: #495057;
            margin-bottom: 2px;
        }
        
        .analysis-date {
            font-size: 12px;
            color: #6c757d;
        }
        
        .load-more-container {
            text-align: center;
            margin-top: 15px;
        }
        
        /* Analysis text formatting styles */
        .analysis-heading-h2 {
            font-size: 1.8em;
            font-weight: 600;
            color: #495057;
            margin: 30px 0 15px 0;
            line-height: 1.3;
        }
        
        .analysis-heading-h3 {
            font-size: 1.6em;
            font-weight: 600;
            color: #495057;
            margin: 25px 0 15px 0;
            line-height: 1.3;
        }
        
        .analysis-heading-h4 {
            font-size: 1.4em;
            font-weight: 600;
            color: #495057;
            margin: 20px 0 10px 0;
            line-height: 1.3;
        }
        
        .analysis-list {
            margin: 15px 0 25px 0;
            padding-left: 20px;
            list-style-type: disc;
        }
        
        .analysis-list-item {
            margin-bottom: 8px;
            line-height: 1.5;
        }
        
        .analysis-paragraph {
            margin-bottom: 15px;
            line-height: 1.6;
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'shared/topbar.php'; ?>
    
    
    <div class="container">
        <!-- Header with Back button and title -->
        <div class="header-with-buttons">
            <div class="d-flex align-items-center">
                <a href="<?php echo 'app_view.php?id=' . $application_id; ?>" 
                   class="header-action-btn me-3" 
                   title="Go back to application view">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
                <h5 class="mb-0">AI Insights: <?php echo htmlspecialchars($application['name']); ?></h5>
            </div>
        </div>
        
        <!-- AI Analysis Section -->
        <div class="row g-3">
            <div class="col-12">
                <!-- Analysis Controls -->
                <div class="analysis-controls">
                    <div class="analysis-type-row">
                        <label for="analysisType" style="border: none !important; background: transparent !important;">Select AI Analysis Type:</label>
                        <select class="form-select" id="analysisType" onchange="handleAnalysisTypeChange()" style="border: 1px solid #ced4da !important;">
                            <option value="summary">Application Summary</option>
                            <option value="timeline">Timeline Analysis</option>
                            <option value="risk_assessment">Risk Assessment</option>
                            <option value="relationship_analysis">Relationship Analysis</option>
                            <option value="trend_analysis">Trend Analysis</option>
                        </select>
                        <button class="btn btn-primary" onclick="generateAnalysis()" style="border: 1px solid #0d6efd !important;">
                            <i class="fas fa-magic"></i> Generate Analysis
                        </button>
                        <button class="btn btn-secondary" onclick="loadRecentAnalyses()" style="border: 1px solid #6c757d !important;">
                            <i class="fas fa-history"></i> Load Recent
                        </button>
                    </div>
                </div>
                
                <!-- Analysis Content -->
                <div class="analysis-content" id="analysisContent">
                    <div class="text-center text-muted" style="background: #fff; border-radius: 8px; padding: 40px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <i class="fas fa-brain fa-3x mb-3" style="color: #ddd;"></i>
                        <h5>Ready for AI Analysis</h5>
                        <p>Select an analysis type and click "Generate Analysis" to get AI-powered insights about this application.</p>
                    </div>
                </div>
                
                <!-- Recent Analyses -->
                <div class="recent-analyses">
                    <h6>Recent Analyses</h6>
                    <div id="recentAnalyses">
                        <div class="text-muted text-center py-4">
                            <i class="fas fa-history fa-2x mb-2" style="color: #ddd;"></i>
                            <p>No previous analyses found. Generate your first analysis above.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const applicationId = <?php echo $application_id; ?>;
        let isGenerating = false;
        
        // Generate new AI analysis
        async function generateAnalysis(forceRefresh = false) {
            console.log('generateAnalysis called'); // Debug log
            
            if (isGenerating) {
                console.log('Already generating, exiting'); // Debug log
                return;
            }
            
            const analysisType = document.getElementById('analysisType').value;
            const contentDiv = document.getElementById('analysisContent');
            
            console.log('Analysis type:', analysisType); // Debug log
            console.log('Application ID:', applicationId); // Debug log
            
            isGenerating = true;
            
            // Show loading state
            contentDiv.innerHTML = `
                <div class="loading-spinner" style="background: #fff; border-radius: 8px; padding: 40px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3">Generating ${analysisType} analysis...</p>
                    <small class="text-muted">This may take a few moments</small>
                </div>
            `;
            
            try {
                console.log('About to fetch:', 'api/ai_analysis.php'); // Debug log
                
                const response = await fetch('api/ai_analysis.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        application_id: applicationId,
                        analysis_type: analysisType,
                        force_refresh: forceRefresh
                    })
                });
                
                console.log('Response received:', response); // Debug log
                console.log('Response status:', response.status); // Debug log
                
                const result = await response.json();
                console.log('Debug result parsed:', result); // Debug log
                
                if (result.success) {
                    // Handle the response structure from production API
                    const analysisContent = result.data.result ? result.data.result.raw_content : result.data.analysis_content;
                    const timestamp = result.data.created_at;
                    
                    displayAnalysis(analysisContent, analysisType, timestamp);
                    
                    // Load recent analyses to update the list
                    setTimeout(() => loadRecentAnalyses(), 1000);
                } else {
                    let errorMessage = result.error || 'An error occurred while generating the analysis.';
                    if (result.debug) {
                        errorMessage += '<br><br><strong>Debug Info:</strong><br>' + result.debug.join('<br>');
                    }
                    contentDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Analysis Failed</strong><br>
                            ${errorMessage}
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error generating analysis:', error);
                contentDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Connection Error</strong><br>
                        Failed to connect to the AI analysis service. Please try again.
                    </div>
                `;
            } finally {
                isGenerating = false;
            }
        }
        
        // Display analysis result
        function displayAnalysis(analysisText, analysisType, timestamp) {
            const contentDiv = document.getElementById('analysisContent');
            const formattedDate = new Date(timestamp).toLocaleString();
            
            contentDiv.innerHTML = `
                <div class="analysis-result">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5><i class="fas fa-brain text-primary"></i> ${getAnalysisTypeLabel(analysisType)} Analysis</h5>
                        <div class="d-flex align-items-center gap-2">
                            <small class="text-muted">Generated: ${formattedDate}</small>
                            <button class="btn btn-sm btn-outline-primary" onclick="generateAnalysis(true)" title="Generate fresh analysis">
                                <i class="fas fa-refresh"></i> Refresh
                            </button>
                        </div>
                    </div>
                    <div class="analysis-text">
                        ${formatAnalysisText(analysisText)}
                    </div>
                </div>
            `;
        }
        
        // Load recent analyses
        async function loadRecentAnalyses(loadAll = false) {
            const recentDiv = document.getElementById('recentAnalyses');
            
            try {
                const limit = loadAll ? 50 : 3; // Load 3 by default, 50 when "Load more" is clicked
                const response = await fetch(`api/get_ai_analysis.php?application_id=${applicationId}&recent=true&limit=${limit}`);
                const result = await response.json();
                
                if (result.success && result.data && result.data.length > 0) {
                    let html = '';
                    result.data.forEach(analysis => {
                        const formattedDate = new Date(analysis.created_at).toLocaleDateString('nb-NO', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                        
                        html += `
                            <div class="analysis-item">
                                <div class="analysis-meta">
                                    <div class="analysis-type">${getAnalysisTypeLabel(analysis.analysis_type)}</div>
                                    <div class="analysis-date">${formattedDate}</div>
                                </div>
                                <button class="btn btn-sm btn-outline-primary" onclick="loadFullAnalysis('${analysis.id}')">
                                    <i class="fas fa-eye"></i> View Full Analysis
                                </button>
                            </div>
                        `;
                    });
                    
                    // Add "Load more" button if we're showing limited results and there might be more
                    if (!loadAll && result.data.length === 3) {
                        html += `
                            <div class="load-more-container">
                                <button class="btn btn-outline-secondary btn-sm" onclick="loadRecentAnalyses(true)">
                                    <i class="fas fa-chevron-down"></i> Load More
                                </button>
                            </div>
                        `;
                    }
                    
                    recentDiv.innerHTML = html;
                } else {
                    recentDiv.innerHTML = `
                        <div class="text-muted text-center py-4">
                            <i class="fas fa-history fa-2x mb-2" style="color: #ddd;"></i>
                            <p>No previous analyses found. Generate your first analysis above.</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading recent analyses:', error);
                recentDiv.innerHTML = `
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Failed to load recent analyses.
                    </div>
                `;
            }
        }
        
        // Load full analysis
        async function loadFullAnalysis(analysisId) {
            const contentDiv = document.getElementById('analysisContent');
            
            // Show loading state
            contentDiv.innerHTML = `
                <div class="loading-spinner" style="background: #fff; border-radius: 8px; padding: 40px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3">Loading analysis...</p>
                </div>
            `;
            
            try {
                const response = await fetch(`api/get_ai_analysis.php?analysis_id=${analysisId}`);
                const result = await response.json();
                
                console.log('loadFullAnalysis - API response:', result); // Debug log
                
                if (result.success && result.data) {
                    // Extract content from the analysis - note: using result.data instead of result.analysis
                    let content = '';
                    const analysis = result.data;
                    
                    console.log('loadFullAnalysis - analysis object:', analysis); // Debug log
                    
                    if (analysis.analysis_result && typeof analysis.analysis_result === 'object') {
                        content = analysis.analysis_result.data?.analysis || 
                                 analysis.analysis_result.raw_content || 
                                 JSON.stringify(analysis.analysis_result);
                    } else if (analysis.analysis_result && typeof analysis.analysis_result === 'string') {
                        content = analysis.analysis_result;
                    } else if (analysis.analysis_content) {
                        content = analysis.analysis_content;
                    }
                    
                    console.log('loadFullAnalysis - extracted content:', content ? content.substring(0, 100) + '...' : 'No content'); // Debug log
                    
                    if (content) {
                        displayAnalysis(content, analysis.analysis_type, analysis.created_at);
                        
                        // Update the dropdown to match the loaded analysis
                        document.getElementById('analysisType').value = analysis.analysis_type;
                    } else {
                        contentDiv.innerHTML = `
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Analysis Found But No Content Available</strong><br>
                                This analysis appears to be empty or corrupted.
                            </div>
                        `;
                    }
                } else {
                    console.log('loadFullAnalysis - API error or no data:', result); // Debug log
                    contentDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Analysis Not Found</strong><br>
                            The requested analysis could not be loaded. ${result.error || ''}
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading full analysis:', error);
                contentDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Loading Error</strong><br>
                        Failed to load the analysis. Please try again.
                    </div>
                `;
            }
        }
        
        // Helper functions
        function getAnalysisTypeLabel(type) {
            const labels = {
                'summary': 'Application Summary',
                'timeline': 'Timeline Analysis',
                'risk_assessment': 'Risk Assessment',
                'relationship_analysis': 'Relationship Analysis',
                'trend_analysis': 'Trend Analysis'
            };
            return labels[type] || type;
        }
        
        function formatAnalysisText(text) {
            if (!text) return '';
            
            console.log('🧹 formatAnalysisText - Input text preview:', text.substring(0, 200));
            
            // Convert markdown-style formatting to HTML using CSS classes instead of inline styles
            let formattedText = text
                // Convert ### headings to h4 with class
                .replace(/###\s*(.*?)$/gm, '<h4 class="analysis-heading-h4">$1</h4>')
                // Convert ## headings to h3 with class
                .replace(/##\s*(.*?)$/gm, '<h3 class="analysis-heading-h3">$1</h3>')
                // Convert # headings to h2 with class
                .replace(/#\s*(.*?)$/gm, '<h2 class="analysis-heading-h2">$1</h2>')
                // Bold text
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                // Italic text
                .replace(/\*(.*?)\*/g, '<em>$1</em>');
            
            // Handle bullet points properly - split into lines first
            let lines = formattedText.split('\n');
            let result = [];
            let inList = false;
            
            for (let i = 0; i < lines.length; i++) {
                let line = lines[i].trim();
                
                if (line.startsWith('- ')) {
                    if (!inList) {
                        result.push('<ul class="analysis-list">');
                        inList = true;
                    }
                    // Remove the "- " and create list item
                    let listContent = line.substring(2);
                    result.push(`<li class="analysis-list-item">${listContent}</li>`);
                } else {
                    if (inList) {
                        result.push('</ul>');
                        inList = false;
                    }
                    
                    if (line.length > 0) {
                        // Check if it's a heading (already processed above)
                        if (line.includes('<h2') || line.includes('<h3') || line.includes('<h4')) {
                            result.push(line);
                        } else {
                            result.push(`<p class="analysis-paragraph">${line}</p>`);
                        }
                    }
                }
            }
            
            // Close any open list
            if (inList) {
                result.push('</ul>');
            }
            
            console.log('🧹 formatAnalysisText - After formatting preview:', result.join('').substring(0, 200));
            
            return result.join('');
        }
        
        // Load recent analyses on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadRecentAnalyses();
            loadLatestAnalysis(); // Load the most recent analysis automatically
        });
        
        // Load the latest analysis automatically
        async function loadLatestAnalysis() {
            console.log('🔍 loadLatestAnalysis started'); // Debug log
            try {
                const url = `api/get_ai_analysis.php?application_id=${applicationId}&recent=true&limit=1`;
                console.log('🌐 Fetching from URL:', url); // Debug log
                
                const response = await fetch(url);
                console.log('📡 Response status:', response.status); // Debug log
                console.log('📡 Response ok:', response.ok); // Debug log
                
                const result = await response.json();
                console.log('📦 Full API response:', result); // Debug log
                
                if (result.success && result.data && result.data.length > 0) {
                    console.log('✅ Data found, processing...'); // Debug log
                    const latestAnalysis = result.data[0];
                    console.log('📊 Latest analysis data:', latestAnalysis); // Debug log
                    
                    // Check different possible content fields
                    let content = '';
                    if (latestAnalysis.analysis_result && typeof latestAnalysis.analysis_result === 'object') {
                        console.log('📄 Using analysis_result object'); // Debug log
                        content = latestAnalysis.analysis_result.data?.analysis || 
                                 latestAnalysis.analysis_result.raw_content || 
                                 JSON.stringify(latestAnalysis.analysis_result);
                        console.log('📝 Content found:', content.substring(0, 100) + '...'); // Debug log
                    } else if (latestAnalysis.analysis_result && typeof latestAnalysis.analysis_result === 'string') {
                        console.log('📄 Using analysis_result string'); // Debug log
                        content = latestAnalysis.analysis_result;
                        console.log('📝 Content found:', content.substring(0, 100) + '...'); // Debug log
                    } else if (latestAnalysis.analysis_content) {
                        console.log('📄 Using analysis_content'); // Debug log
                        content = latestAnalysis.analysis_content;
                        console.log('📝 Content found:', content.substring(0, 100) + '...'); // Debug log
                    }
                    
                    if (content) {
                        console.log('✅ Displaying analysis with content'); // Debug log
                        displayAnalysis(
                            content, 
                            latestAnalysis.analysis_type, 
                            latestAnalysis.created_at
                        );
                        
                        // Set the dropdown to match the loaded analysis
                        document.getElementById('analysisType').value = latestAnalysis.analysis_type;
                        console.log('🎯 Set analysis type to:', latestAnalysis.analysis_type); // Debug log
                    } else {
                        console.log('❌ No content found in analysis:', latestAnalysis);
                    }
                } else {
                    console.log('⚠️ No data in response or data is empty'); // Debug log
                    console.log('📦 Result success:', result.success); // Debug log
                    console.log('📦 Result data:', result.data); // Debug log
                    if (result.data) {
                        console.log('📦 Data length:', result.data.length); // Debug log
                    }
                }
            } catch (error) {
                console.error('❌ Error loading latest analysis:', error);
                // Keep the default empty state if loading fails
            }
        }
        
        // Handle analysis type change
        async function handleAnalysisTypeChange() {
            const selectedType = document.getElementById('analysisType').value;
            
            // Try to find existing analysis of this type
            try {
                const response = await fetch(`api/get_ai_analysis.php?application_id=${applicationId}&analysis_type=${selectedType}&limit=1`);
                const result = await response.json();
                
                if (result.success && result.data && result.data.length > 0) {
                    const analysis = result.data[0];
                    console.log('Selected analysis data:', analysis); // Debug log
                    
                    // Check different possible content fields
                    let content = '';
                    if (analysis.analysis_result && typeof analysis.analysis_result === 'object') {
                        content = analysis.analysis_result.data?.analysis || 
                                 analysis.analysis_result.raw_content || 
                                 JSON.stringify(analysis.analysis_result);
                    } else if (analysis.analysis_result && typeof analysis.analysis_result === 'string') {
                        content = analysis.analysis_result;
                    } else if (analysis.analysis_content) {
                        content = analysis.analysis_content;
                    }
                    
                    if (content) {
                        displayAnalysis(
                            content, 
                            analysis.analysis_type, 
                            analysis.created_at
                        );
                    } else {
                        console.log('No content found in selected analysis:', analysis);
                        showEmptyStateForType(selectedType);
                    }
                } else {
                    // Show empty state with hint to generate
                    showEmptyStateForType(selectedType);
                }
            } catch (error) {
                console.error('Error loading analysis for type:', error);
                showEmptyStateForType(selectedType);
            }
        }
        
        // Show empty state for specific analysis type
        function showEmptyStateForType(analysisType) {
            const contentDiv = document.getElementById('analysisContent');
            contentDiv.innerHTML = `
                <div class="text-center text-muted" style="background: #fff; border-radius: 8px; padding: 40px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <i class="fas fa-brain fa-3x mb-3" style="color: #ddd;"></i>
                    <h5>No ${getAnalysisTypeLabel(analysisType)} Analysis Found</h5>
                    <p>Click "Generate Analysis" to create a new ${getAnalysisTypeLabel(analysisType).toLowerCase()} for this application.</p>
                    <button class="btn btn-primary" onclick="generateAnalysis()">
                        <i class="fas fa-magic"></i> Generate ${getAnalysisTypeLabel(analysisType)} Analysis
                    </button>
                </div>
            `;
        }
    </script>
</body>
</html>
