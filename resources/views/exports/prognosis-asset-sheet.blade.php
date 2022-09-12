<table>
    <thead>
    <tr>
        <td><h2>{{ $name }}</h2></td>
    </tr>
    <tr>
        <th>Asset Year</th>
        <th>Inntekt</th>
        <th>Utgift</th>
        <th>Cashflow</th>
        <th>Cashflow akkumulert</th>
        <th>Asset</th>
        <th>Mortgage payment</th>
        <th>Mortgage interest</th>
        <th>Mortgage principal</th>
        <th>Mortgage balance</th>
    </tr>
    </thead>
    <tbody>
    @foreach($asset as $year => $data)
        <tr>
            <td>{{ $year }}</td>
            <td>{{ Arr::get($data, "income.income") }}</td>
            <td>{{ Arr::get($data, "expence.expence") }}</td>
            <td>{{ Arr::get($data, "cashflow.amount") }}</td>
            <td>{{ Arr::get($data, "cashflow.amountAccumulated") }}</td>
            <td>{{ Arr::get($data, "asset.amount") }}</td>
            <td>{{ Arr::get($data, "mortgage.payment") }}</td>
            <td>{{ Arr::get($data, "mortgage.interest") }}</td>
            <td>{{ Arr::get($data, "mortgage.principal") }}</td>
            <td>{{ Arr::get($data, "mortgage.balance") }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
