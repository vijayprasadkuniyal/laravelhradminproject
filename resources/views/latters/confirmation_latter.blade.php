<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation Letter</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        h1 {
            text-align: center;
            text-decoration: underline;
        }
        .letter-body {
            margin-top: 20px;
        }
        .signature-section {
            margin-top: 40px;
        }
        .signature-block {
            width: 100%;
            display: flex;
            justify-content: between;
            margin-top: 20px;
        }  
    </style>
</head>
<body>
    <h3>Employment Confirmation Letter</h3>
    <p><strong>Date:</strong> {{ \Carbon\Carbon::now()->format('jS F Y') }}</p>
    <p><strong>Employee Name:</strong> {{$emp_details->emp_fname}} {{$emp_details->emp_lame}}</p>
    <p><strong>Employee Code:</strong> {{$emp_details->emp_id}}</p>
    <div class="letter-body">
        <p>Dear <strong>{{$emp_details->emp_fname}} {{$emp_details->emp_lame}}</strong>,</p>
        <p>
            After completion of your probation period, we are satisfied to acknowledge that you are 
            confirmed with effect from <strong>{{$emp_details->emp_doc}}</strong>.
        </p>
        <p>
            The various terms & conditions remain the same as mentioned in your appointment letter.
        </p>
        <p>
            We look forward to your contribution to our company. We wish you all the best for your career.
        </p>
        <p>Please sign this letter as a token of acknowledgement.</p>
    </div>
    <div class="signature-section">
    <p>Thank you,</p>
    <b>For RIMS BIZZSERVE PRIVATE LIMITED</b>
    </div>
         <table> 
                 <tr>
                    <td>Shivangi Singh</td>
                    <td></td>
                 </tr>
                <tr>
                     <td>Senior HR</td>
                     <td style="padding-left: 250px">Employee Signature</td>
                </tr> 
         </table>
  
</body>
</html>
