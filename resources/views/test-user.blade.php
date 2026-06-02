<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Test Jaze User</title>
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            margin: 32px;
            max-width: 760px;
        }

        form {
            display: grid;
            gap: 12px;
        }

        label {
            display: grid;
            gap: 4px;
            font-weight: 600;
        }

        input {
            font: inherit;
            padding: 10px;
        }

        button {
            font: inherit;
            padding: 12px 16px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <h1>Test Jaze User</h1>
    <form method="post" action="/test-user" enctype="multipart/form-data">
        @csrf
        @foreach ($defaults as $name => $value)
            <label>
                {{ $name }}
                <input name="{{ $name }}" value="{{ old($name, $value) }}">
            </label>
        @endforeach
        <label>
            idFile
            <input name="idFile" type="file" accept="image/*,.pdf" required>
        </label>
        <button type="submit">Create test user</button>
    </form>
</body>
</html>
