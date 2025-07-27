<!DOCTYPE html>
<html>
<head>
    <title>API Test</title>
</head>
<body>
    <h1>API Test for Drawflow</h1>
    
    <div id="results"></div>
    
    <script>
        async function testAPI() {
            const resultsDiv = document.getElementById('results');
            
            // Test load API
            try {
                resultsDiv.innerHTML += '<h3>Testing Load API...</h3>';
                const loadResponse = await fetch('/api/load_drawflow_diagram_test.php?application_id=429');
                const loadResult = await loadResponse.json();
                resultsDiv.innerHTML += '<p><strong>Load Result:</strong> ' + JSON.stringify(loadResult, null, 2) + '</p>';
            } catch (error) {
                resultsDiv.innerHTML += '<p><strong>Load Error:</strong> ' + error.message + '</p>';
            }
            
            // Test save API
            try {
                resultsDiv.innerHTML += '<h3>Testing Save API...</h3>';
                const testData = {
                    application_id: 429,
                    diagram_data: {"drawflow":{"Home":{"data":{}}}},
                    notes: 'Test save'
                };
                
                const saveResponse = await fetch('/api/save_drawflow_diagram_test.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(testData)
                });
                
                const saveResult = await saveResponse.json();
                resultsDiv.innerHTML += '<p><strong>Save Result:</strong> ' + JSON.stringify(saveResult, null, 2) + '</p>';
            } catch (error) {
                resultsDiv.innerHTML += '<p><strong>Save Error:</strong> ' + error.message + '</p>';
            }
        }
        
        // Run test when page loads
        window.addEventListener('DOMContentLoaded', testAPI);
    </script>
</body>
</html>
