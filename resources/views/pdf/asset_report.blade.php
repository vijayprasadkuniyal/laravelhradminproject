<!DOCTYPE html>
<html>
<head>
    <title>Asset Repair Report</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
    </style>
</head>
<body>
    <h1>Asset Repair Report</h1>
    <table>
        <thead>
            <tr>
                <th>Category</th>
                <th>Stock</th>
                <th>Quantity</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $row)
                <tr>
                    <td>{{ $row['category'] }}</td>
                    <td>{{ $row['stock'] }}</td>
                    <td>{{ $row['quantity'] }}</td>
                    <td>{{ $row['sum'] }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2"><strong>Total</strong></td>
                <td>{{ $total_quantity }}</td>
                <td>{{ $total_sum }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
