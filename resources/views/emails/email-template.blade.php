<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Sales Report</title>
</head>
<body>


    <p>Dear User,</p>

    <p>Attached is the daily sales report for {{ \Carbon\Carbon::yesterday()->format('l, F j, Y') }}.</p>

    <p>Thank you.</p>
</body>
</html>
