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

        pre {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            overflow-x: auto;
            padding: 12px;
        }
    </style>
</head>
<body>
    <h1>Test Jaze User</h1>
    @if (! empty($branchCredentials))
        <h2>Branch Jaze Credentials</h2>
        <pre>{{ json_encode($branchCredentials, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    @endif
    @if (! empty($groupError))
        <p style="color: #b91c1c; font-weight: 600;">{{ $groupError }}</p>
    @endif
    <form method="post" action="/test-user" enctype="multipart/form-data">
        @csrf
        @foreach ($defaults as $name => $value)
            <label>
                {{ $name }}
                @if ($name === 'userGroupId' && ! empty($groups))
                    <select name="{{ $name }}">
                        @foreach ($groups as $group)
                            @php
                                $groupId = (string) data_get($group, 'Group_id');
                                $groupName = (string) data_get($group, 'Group_name', $groupId);
                                $profileName = (string) data_get($group, 'Profile_Name', '');
                            @endphp
                            <option value="{{ $groupId }}" @selected(old($name, $value) === $groupId)>
                                {{ $groupName }}{{ $profileName !== '' ? ' / '.$profileName : '' }} ({{ $groupId }})
                            </option>
                        @endforeach
                    </select>
                @else
                    <input name="{{ $name }}" value="{{ old($name, $value) }}">
                @endif
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
