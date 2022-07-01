<table>
    <thead>
    <tr>
        <th>Year</th>
    </tr>
    </thead>
    <tbody>
    @foreach($asset as $year => $data)
        <tr>
            <td>{{ $year }}</td>
            <td>{{ $year }}</td>
            <td>{{ $year }}</td>
            <td>{{ $year }}</td>
            <td>{{ $year }}</td>
            <td>{{ $year }}</td>
            <td>{{ $year }}</td>
            <td>{{ $year }}</td>
            <td>{{ $year }}</td>
        </tr>
    @endforeach
    </tbody>
</table>