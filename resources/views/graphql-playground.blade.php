<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GraphQL Playground - Studio Management System</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background: #2c3e50;
            color: white;
            padding: 20px;
            text-align: center;
        }

        .playground {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            padding: 20px;
        }

        .query-section,
        .result-section {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 20px;
        }

        .query-section h3,
        .result-section h3 {
            margin-top: 0;
            color: #2c3e50;
        }

        textarea {
            width: 100%;
            height: 300px;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            resize: vertical;
        }

        button {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }

        button:hover {
            background: #2980b9;
        }

        .result {
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-top: 15px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            white-space: pre-wrap;
            max-height: 400px;
            overflow-y: auto;
        }

        .examples {
            background: #e8f4fd;
            border-radius: 6px;
            padding: 20px;
            margin-top: 20px;
        }

        .examples h4 {
            margin-top: 0;
            color: #2c3e50;
        }

        .example-query {
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            cursor: pointer;
        }

        .example-query:hover {
            background: #f0f0f0;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>GraphQL Playground</h1>
            <p>Studio Management System - Test your GraphQL queries</p>
        </div>

        <div class="playground">
            <div class="query-section">
                <h3>Query</h3>
                <textarea id="queryInput" placeholder="Enter your GraphQL query here...">query {
  assets {
    id
    name
    type
    status
    location
    assigned_to
  }
}</textarea>
                <button onclick="executeQuery()">Execute Query</button>

                <div class="examples">
                    <h4>Example Queries</h4>
                    <div class="example-query" onclick="loadExample('assets')">
                        Get all assets with basic info
                    </div>
                    <div class="example-query" onclick="loadExample('employees')">
                        Get all employees
                    </div>
                    <div class="example-query" onclick="loadExample('locations')">
                        Get all locations
                    </div>
                    <div class="example-query" onclick="loadExample('projects')">
                        Get all projects
                    </div>
                </div>
            </div>

            <div class="result-section">
                <h3>Result</h3>
                <div id="result" class="result">Results will appear here...</div>
            </div>
        </div>
    </div>

    <script>
        const examples = {
            assets: `query {
  assets {
    id
    name
    type
    status
    location
    assigned_to
    department
    brand
    model
  }
}`,
            employees: `query {
  employees {
    id
    name
    email
    employee_id
    department
    position
    location
    status
  }
}`,
            locations: `query {
  locations {
    id
    name
    address
    city
    state
    country
    postal_code
    status
  }
}`,
            projects: `query {
  projects {
    id
    name
    description
    status
    start_date
    end_date
    manager
    client
    budget
    priority
  }
}`
        };

        function loadExample(type) {
            document.getElementById('queryInput').value = examples[type];
        }

        async function executeQuery() {
            const query = document.getElementById('queryInput').value;
            const resultDiv = document.getElementById('result');

            if (!query.trim()) {
                resultDiv.textContent = 'Please enter a query first.';
                return;
            }

            resultDiv.textContent = 'Executing query...';

            try {
                const response = await fetch('/api/graphql', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        query: query
                    })
                });

                const data = await response.json();

                if (data.errors) {
                    resultDiv.textContent = 'Error: ' + JSON.stringify(data.errors, null, 2);
                } else {
                    resultDiv.textContent = JSON.stringify(data.data, null, 2);
                }
            } catch (error) {
                resultDiv.textContent = 'Error: ' + error.message;
            }
        }
    </script>
</body>

</html>