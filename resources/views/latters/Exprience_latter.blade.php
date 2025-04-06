<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Experience Letter</title>
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
        .signature {
            margin-top: 40px;
        }
        .company-name {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>Experience Letter</h1>
    <p><strong>Date of Issue:</strong> {{ \Carbon\Carbon::now()->format('jS F Y') }}</p>
    <div class="letter-body">
        <p>TO WHOM-SO-EVER IT MAY CONCERN</p>
        <p>
            This is to certify that Mr./Ms <strong>{{$emp_details->emp_fname}} {{$emp_details->emp_lame}}</strong>, 
            Son/Daughter of Mr. <strong>{{$emp_details->emp_father_name}}</strong>, worked 
            as <strong>{{$emp_details->designation_name}}</strong> in our company from 
            <strong>{{$emp_details->emp_doj}}</strong> with our entire satisfaction.
        </p>
        <p>
            During his/her working period, we found him/her to be a sincere, honest, hardworking, 
            and dedicated employee with a professional attitude and very good job knowledge.
        </p>
        <p>
            He/She is amiable in nature, and their character is well. We have no objection to 
            allowing him/her in any better position and have no liabilities in our company.
        </p>
        <p>
            During his/her tenure, the CTC was <strong>{{$ctc}}</strong> only.
        </p>
        <p>We wish him/her every success in life.</p>
    </div>
    <div class="signature">
        <p>Sincerely,</p>
        <p><strong>HR Department</strong></p>
        <p class="company-name">RIMS BIZZSERVE PVT LTD</p>
    </div>
</body>
</html>
