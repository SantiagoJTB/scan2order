<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Endpoints</title>
    <style>
        body { font-family: Arial, sans-serif; margin:20px; }
        table { width:100%; border-collapse: collapse; }
        th, td { border:1px solid #ccc; padding:8px; text-align:left; }
        th { background:#f4f4f4; }
    </style>
</head>
<body>
    <h1>API Endpoints</h1>

    @foreach($grouped as $section => $routes)
        <h2>{{ ucfirst($section) }}</h2>
        @if(isset($descriptions[$section]))
            <p>{{ $descriptions[$section] }}</p>
        @endif
        <table>
            <thead>
                <tr>
                    <th>Method</th>
                    <th>URI</th>
                    <th>Name</th>
                    <th>Action</th>
                    <th>Middleware</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($routes as $route)
                    <tr>
                        <td>{{ implode(', ', $route['methods']) }}</td>
                        <td>{{ $route['uri'] }}</td>
                        <td>{{ $route['name'] }}</td>
                        <td>{{ $route['action'] }}</td>
                        <td>{{ implode(', ', $route['middleware'] ?? []) }}</td>
                        <td>{{ $route['status'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
</body>
</html>