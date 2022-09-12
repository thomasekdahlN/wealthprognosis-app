<table>
    <thead>
    <tr>
        <td><h2>{{ $name }}</h2></td>
    </tr>
    <tr>
        <th>Total Year</th>
        <th>Asset</th>
        <th>Changerate</th>
        <th>Description</th>
    </tr>
    </thead>
    <tbody>
    @foreach($asset as $year => $data)
        <tr>
            <td>{{ $year }}</td>
            <td>{{ Arr::get($data, "asset.amount") }}</td>
            <td>{{ Arr::get($data, "asset.changerate") }}</td>
            <td>{{ Arr::get($data, "asset.description") }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
