<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relieving Letter</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        .letter-container {
            max-width: 600px;
            margin: auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 22px;
            margin-bottom: 5px;
        }
        .date {
            text-align: right;
            margin-bottom: 20px;
        }
        .content {
            margin-bottom: 20px;
        }
        .signature {
            margin-top: 40px;
        }
    </style>
</head>
<body>
    <div class="letter-container">
        <div class="header">
            <h1>RIMS Bizzserve Private Limited</h1>
        </div>
        <div class="date">
            <p>Date of Issue: {{ \Carbon\Carbon::now()->format('jS F Y') }}</p></p>
        </div>
        <div class="content">
            <p><strong>Name:</strong> {{$emp_details->emp_fname}} {{$emp_details->emp_lame}}</p>
            <p><strong>Employee Code:</strong> {{$emp_details->emp_id}}</p>
            <br>
            <p><strong>To Whomsoever It May Concern</strong></p>
            <br>
            <p>
                This is to certify that Mr/Ms <strong>{{$emp_details->emp_fname}} {{$emp_details->emp_lame}}</strong> was employed with us from <strong>{{$emp_details->emp_doj}}</strong>.
                He/She was last designated as <strong>{{$emp_details->designation_name}}</strong> and was operating from <strong>{{$emp_details->branch_name}}</strong>.
            </p>
            <p>
                He/She has been relieved from the services of the company with effect from the close of working 
                hours of <strong>{{$emp_details->emp_eod}}</strong>.
            </p>
            <p>
                We appreciate your contribution to the company and wish you all the best for your future endeavors.
            </p>
        </div>
        <div class="signature">
            <p>For RIMS Bizzserve Private Limited</p>
            <p><strong>Shivangi Singh</strong></p>
            <p>Senior-HR</p>
        </div>
    </div>
</body>
</html>
