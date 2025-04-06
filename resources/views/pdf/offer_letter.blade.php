<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offer Letter</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .container {
            border: 1px solid #000;
            padding: 20px;
            max-width: 800px;
            margin: auto;
        }
        .header, .footer {
            text-align: center;
        }
        .content {
            margin-top: 20px;
        }
        .content p {
            line-height: 1.6;
        }
        .content table {
            width: 100%;
            margin-top: 20px;
        }
        .content table th, .content table td {
            text-align: left;
            padding: 8px;
        }
        .signature {
            margin-top: 40px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>RIMS Bizzserve Private Limited</h2>
            <p>309, 3rd floor, Vipul Agora Mall, MG Road, Gurugram – 122002</p>
            <p>India Tel: 0124-4245046 | Website: www.r-ims.com</p>
        </div>
        <div class="content">
            <p>Mr. {{$emp_name}}</p>
            <p>{{$address}}</p>
            <p>Date: {{$date}}</p>
            <h3>Offer of Employment</h3>
            <p>Dear {{$emp_name}},</p>
            <p>With reference to your application and subsequent interview; we are pleased to offer you an employment with RIMS Bizzserve Private Limited on the following terms and conditions:</p>
            <ol>
                <li>You will be designated as {{$job_title}} – {{$dept_name}}. You will be reporting to {{$manager_name}} (Sr. Manager -{{$dept_name}}+).</li>
                <li>Your date of joining shall be {{$date_of_join}} or at the earliest. In case you fail to join on 21st February, this offer shall stand withdrawn unless the date is extended by RIMS in writing.</li>
                <li>Your workplace will be RIMS Office at Gurugram, Haryana. However, basis the requirement of the Company, RIMS may transfer you anywhere across the country.</li>
                <li>The detailed appointment letter shall be issued to you on the date of joining. The terms and conditions of your service shall be governed as per the appointment letter and by RIMS’ policies and codes of conduct as applicable to you and amended from time to time (Policies).</li>
                <li>You will be entitled to the following benefits:</li>
                <table>
                    <tr>
                        <th>a. Cost to Company:</th>
                        <td>The Cost to Company comprises of Fixed Component and retention bonus including Retirals (Employer’s contribution). Your total CTC will be INR {{$ctc}}/- ({{$ctc_in_words}})/- per annum.</td>
                    </tr>
                    <tr>
                        <th>b. Fixed Compensation:</th>
                        <td>Fixed compensation of INR {{$fix_salary}}/- ({{$fix_salary_in_words}})/- per annum which is all inclusive. The Fixed Annual Compensation shall include basic salary, cash allowances, choice pay and retirement benefits. The precise compensation structure shall be determined as per the company’s policies.</td>
                    </tr>
                    <tr>
                        <th>c. Tax and deductions:</th>
                        <td>All compensation numbers in this paragraph 5 are before tax. All payments received by you pursuant to your appointment as an employee of RIMS are subject to statutory deductions. You are solely responsible for your personal and other taxes and for preparing and filing your tax returns. RIMS may, subject to applicable laws, at any time during your employment or afterwards, deduct from your salary, or final settlement, any amounts owed by you to RIMS.</td>
                    </tr>
                </table>
                <li>The benefits set out in this offer letter are subject to the terms of the relevant incentive plans/schemes as approved by Compensation Committee of RIMS and the applicable Policies. The benefits may change based on merit, overall performance, business conditions and other parameters as determined by RIMS at its sole discretion.</li>
                <li>You will be entitled to annual leave, public holidays and leave for sickness in accordance with RIMS’ Policies. The Policies are being developed by way of benchmarking against comparable companies in India.</li>
                <li>This letter shall be governed by Indian law. You and RIMS agree to the exclusive jurisdiction and exclusive jurisdiction of the civil courts in Gurugram for the resolution of all disputes arising under this letter.</li>
                <li>By signing and returning this letter you confirm that (1) you accept the terms set out in this letter, and (2) you are not under any restrictions in an agreement that would prevent you from being employed by RIMS.</li>
                <li>At the time of your joining, you need to submit to us the following list of documents in a soft copy:</li>
                <ul>
                    <li>Self-Attested copies of the academic certificates and marksheet – graduation; post-graduation and professional degree.</li>
                    <li>Relieving letter from the last organization.</li>
                    <li>Last increment letter or appointment letter giving details of the last drawn salary or last payslip giving the required details on salary.</li>
                    <li>Any 2 proofs of residence (Passport, Driver’s License, Voters ID, bank statement).</li>
                    <li>PAN Card.</li>
                    <li>Aadhar Card.</li>
                    <li>3 passport sized photographs</li>
                </ul>
                <li>If we do not receive your signed acceptance within 2 working days of receipt of offer by you; this offer will lapse. The contents of this letter and related information sent to you are confidential.</li>
            </ol>
            <div class="signature">
                <p>Yours Sincerely,</p>
                <p>For RIMS Bizzserve Private Limited</p>
                <p>{{$name}}<br>Manager - Human Resource Officer</p>
            </div>
            <div class="footer">
                <h3>Acceptance of Offer</h3>
                <p>I acknowledge receipt of the letter set out above. I accept the offer and agree to abide by the terms and conditions contained in this letter.</p>
                <p>Signed : _________________________</p>
                <p>Date   : _________________________</p>
            </div>
        </div>
    </div>
</body>
</html>
