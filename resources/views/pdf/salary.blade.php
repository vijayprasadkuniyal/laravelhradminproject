 
                <!DOCTYPE html >
                <html xmlns='http://www.w3.org/1999/xhtml'>
                <head>
                    <title>Pay Slip</title>
                    <meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1' />
                </head>

                <body lang=EN-US style='font-family: sans-serif;tab-interval:.5in'>

                <div align='center' id='pdf'>
                <div style='width:500.25pt;margin-left:19.65pt;text-align:left'>
                    <img src='https://hr.r-ims.com/scripts/images/logo.jpg' width='150' height='60' >
                </div>
                <table border='0' cellspacing='0' cellpadding='0' width='667' style='width:500.25pt;margin-left:19.65pt;border-collapse:collapse;border:
                none;mso-border-alt:solid windowtext .5pt;mso-padding-alt:0in 5.4pt 0in 5.4pt;
                mso-border-insideh:.5pt solid windowtext;mso-border-insidev:.5pt solid windowtext'>
                    <tr align='center' >
                        <td align='center'>
                            <b style='font-size:33px;'>RIMS Bizzserve Pvt. Ltd. </b> <br>
                            309, 3rd Floor, Vipul Agora Mall, MG Road, Gurgaon-122002(HR.) <br>
                            Ph: 0124-4245046 | E-mail: info@r-ims.com | Web: www.r-ims.com
                        </td> 
                    </tr>
                    <tr align='center'>
                        <td align='center' style='color:red; padding:5px 0px' ><br>
                            <b style='color:red; display:block'>Pay Slip For The Month of {{$month}} </b><br>
                        </td>
                    </tr>
                </table>
 
                <div style='display:flex;width:500.25pt;margin-left:19.65pt;border: solid windowtext 1pt;'> 
                    <div style='width:50%'>
                    <table class=MsoNormalTable border=1 cellspacing=0 cellpadding=0 style='width:100%;border-collapse:collapse;border: none;mso-border-alt:solid windowtext .5pt;mso-padding-alt:0in 5.4pt 0in 5.4pt; mso-border-insideh:.5pt solid windowtext;mso-border-insidev:.5pt solid windowtext'>
                            <tr><td style='padding:5px;'>Employee Name:<strong>{{$data->emp_fname}} {{$data->emp_lame}}</strong></td></tr>
                            <tr><td style='padding:5px;'>Father's Name:  <strong>{{$data->emp_father_name}}</strong></td></tr>
                            <tr><td style='padding:5px;'>Gender:  <strong>{{$data->emp_sex}}</strong></td></tr>
                            <tr><td style='padding:5px;'>Date Of Birth:  <strong>{{$data->emp_dob}}</strong></td></tr>
                        </table>
                    </div>
                    <div style='width:50%'>
                    <table class=MsoNormalTable border=1 cellspacing=0 cellpadding=0 
                    style='width:100%;border-collapse:collapse;border:
                    none;mso-border-alt:solid windowtext .5pt;mso-padding-alt:0in 5.4pt 0in 5.4pt;
                    mso-border-insideh:.5pt solid windowtext;mso-border-insidev:.5pt solid windowtext'>
                            <tr><td  style='padding:5px;'>Designation: <strong>{{$data->designation_name}}</strong></td></tr>
                            <tr><td  style='padding:5px;'>Dept: <strong>{{$data->department_name}}</strong> </td></tr>
                            <tr><td style='padding:5px;'>Date of Joining:<strong>{{$data->emp_doj}}</strong>  </td></tr>
                            <tr><td style='padding:5px;'>Location: <strong>{{$data->branch_name}}</strong>  </td></tr>
                        </table>
                    </div>  
                </div>
                <div style='display:flex;width:500.25pt;margin-left:19.65pt;border: solid windowtext 1pt;'> 
                    <div style='width:50%;'>  
                            <table class=MsoNormalTable border=1 cellspacing=0 cellpadding=0 style='width:100%;border-collapse:collapse;border: none;mso-border-alt:solid windowtext .5pt;mso-padding-alt:0in 5.4pt 0in 5.4pt; mso-border-insideh:.5pt solid windowtext;mso-border-insidev:.5pt solid windowtext'>
                                <tr><td style='padding:5px;border-bottom:0px;border-top:0px;'>Total Working Days:<strong>{{$numberOfDaysInMonth}}</strong></td></tr>
                                <tr><td style='padding:5px;border-bottom:0px;border-top:0px;'>Total Paid Days:  <strong>{{$numberOfDaysInMonth - $no_of_lop}}</strong></td></tr> 
                                <tr><td style='padding:5px;border-bottom:0px;border-top:0px;'>Total Unpaid Days:  <strong>{{$no_of_lop}}</strong></td></tr> 
                            </table> 
                    </div>
                    <div style='width:50%'>
                    <table border=1 cellspacing=0 cellpadding=0 
                    style='width:100%;border-collapse:collapse;border:
                    none;mso-border-alt:solid windowtext .5pt;mso-padding-alt:0in 5.4pt 0in 5.4pt;
                    mso-border-insideh:.5pt solid windowtext;mso-border-insidev:.5pt solid windowtext'>
                            <tr><td  style='padding:5px;border-bottom:0px;border-top:0px;'>Bank Name :<strong>{{$data->bank_name}}</strong></td></tr>
                            <tr><td  style='padding:5px;border-bottom:0px;border-top:0px;'>Bank Account No.: <strong>{{$data->account_no}}</strong> </td></tr>
                            <tr><td style='padding:5px;border-bottom:0px;border-top:0px;'>IFSC:<strong>{{$data->ifsc_code}}</strong>  </td></tr> 
                            <tr><td style='padding:5px;border-bottom:0px;border-top:0px;'>UAN Number:<strong>{{$data->uan_no}}</strong>  </td></tr> 
                            <tr><td style='padding:5px;border-bottom:0px;border-top:0px;'>Esic:<strong>{{$data->esic_no}}</strong>  </td></tr> 
                        </table>
                    </div>  
                </div>
                
                <br> <br>
 

                </table>
                <table class=MsoNormalTable border=1 cellspacing=0 cellpadding=0 width=665
                style='width:498.75pt;margin-left:21.9pt;border-collapse:collapse;border:none;
                mso-border-alt:solid windowtext .5pt;mso-padding-alt:0in 5.4pt 0in 5.4pt;
                mso-border-insideh:.5pt solid windowtext;mso-border-insidev:.5pt solid windowtext'>
                    <tr style='mso-yfti-irow:0;mso-yfti-firstrow:yes;height:19.5pt'>
                        <td width=229 valign=top style='width:171.75pt;border:solid windowtext 1.0px; mso-border-alt:solid windowtext .5pt;padding:0in 5.4pt 0in 5.4pt;height:19.5pt'>
                            <p class=MsoNormal><b style='mso-bidi-font-weight:normal'>Earnings Heading<span style='mso-spacerun:yes'>                                      </span><o:p></o:p></b></p>
                        </td>
                        <td width=229 valign=top style='width:171.75pt;border:solid windowtext 1.0px; mso-border-alt:solid windowtext .5pt;padding:0in 5.4pt 0in 5.4pt;height:19.5pt'>
                            <p class=MsoNormal><b style='mso-bidi-font-weight:normal'>Gross Salary<span style='mso-spacerun:yes'>                                      </span><o:p></o:p></b></p>
                        </td>
                        <td width=90 valign=top style='width:67.5pt;border:solid windowtext 1.0px;
                        border-left:none;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:
                        solid windowtext .5pt;padding:0in 5.4pt 0in 5.4pt;height:19.5pt'>
                            <p class=MsoNormal><b style='mso-bidi-font-weight:normal'>Earning Amount<o:p></o:p></b></p>
                        </td>
                       <!--  <td width=90 valign=top style='width:67.5pt;border:solid windowtext 1.0px;
                        border-left:none;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:
                        solid windowtext .5pt;padding:0in 5.4pt 0in 5.4pt;height:19.5pt'>
                            <p class=MsoNormal><b style='mso-bidi-font-weight:normal'>Arrears<o:p></o:p></b></p>
                        </td> -->
                        <td width=264 valign=top style='width:2.75in;border:solid windowtext 1.0px;
                        border-left:none;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:
                        solid windowtext .5pt;padding:0in 5.4pt 0in 5.4pt;height:19.5pt'>
                            <p class=MsoNormal><b style='mso-bidi-font-weight:normal'>Deductions 
                            Heading<o:p></o:p></b></p>
                        </td>
                        <td width=82 valign=top style='width:61.5pt;border:solid windowtext 1.0px;
                        border-left:none;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:
                        solid windowtext .5pt;padding:0in 5.4pt 0in 5.4pt;height:19.5pt'>
                            <p class=MsoNormal><b style='mso-bidi-font-weight:normal'>Deduction Amount<o:p></o:p></b></p>
                        </td>
                </tr>
                <tr style='mso-yfti-irow:1;height:90.75pt'>
                <td width=229 valign=top style='width:340.75pt;border:solid windowtext 1.0px;
                border-top:none;mso-border-top-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;
                padding:0in 5.4pt 0in 5.4pt;height:90.75pt'>
                 @foreach($salary_amount_data as $row)
                    <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;text-align:
                    justify'>{{$row->attribute}}</p>
                @endforeach
                </td>
                <td width=90 valign=top style='width:67.5pt;border-top:none;border-left:none;
                border-bottom:solid windowtext 1.0px;border-right:solid windowtext 1.0px;
                mso-border-top-alt:solid windowtext .5pt;mso-border-left-alt:solid windowtext .5pt;
                mso-border-alt:solid windowtext .5pt;padding:0in 5.4pt 0in 5.4pt;height:90.75pt'>
                    @foreach($salary_amount_data as $row)
                    <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;text-align:
                    justify'><b style='mso-bidi-font-weight:normal'>&#8377;{{$row->actual_amount}}<o:p></o:p></b></p>
                    @endforeach 
                </td>
                <td width=90 valign=top style='width:67.5pt;border-top:none;border-left:none;
                border-bottom:solid windowtext 1.0px;border-right:solid windowtext 1.0px;
                mso-border-top-alt:solid windowtext .5pt;mso-border-left-alt:solid windowtext .5pt;
                mso-border-alt:solid windowtext .5pt;padding:0in 5.4pt 0in 5.4pt;height:90.75pt'>
                     @foreach($salary_amount_data as $row)
                    <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;text-align:
                    justify'>₹{{$row->amount}}</p>
                   @endforeach
                    <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;text-align:
                    justify'><b style='mso-bidi-font-weight:normal'><o:p>&nbsp;</o:p></b></p>
                </td>
               <!--  <td width=90 valign=top style='width:67.5pt;border-top:none;border-left:none;
                border-bottom:solid windowtext 1.0px;border-right:solid windowtext 1.0px;
                mso-border-top-alt:solid windowtext .5pt;mso-border-left-alt:solid windowtext .5pt;
                mso-border-alt:solid windowtext .5pt;padding:0in 5.4pt 0in 5.4pt;height:90.75pt'>
                    <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;text-align:
                    justify'><b style='mso-bidi-font-weight:normal'>0.00<o:p></o:p></b></p>
                    <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;text-align:
                    justify'><b style='mso-bidi-font-weight:normal'>0.00<o:p></o:p></b></p>
                    <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;text-align:
                    justify'><b style='mso-bidi-font-weight:normal'>0.00<o:p></o:p></b></p>
                    <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;text-align:
                    justify'><b style='mso-bidi-font-weight:normal'>0.00<o:p></o:p></b></p>
                    <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;text-align:
                    justify'><b style='mso-bidi-font-weight:normal'>0.00<o:p></o:p></b></p>
                    <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;text-align:
                    justify'><b style='mso-bidi-font-weight:normal'><o:p>&nbsp;</o:p></b></p>
                    <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;text-align:
                    justify'><b style='mso-bidi-font-weight:normal'><o:p>&nbsp;</o:p></b></p>
                </td> -->
                <td width=264 valign=top style='width:3.75in;border-top:none;border-left:
                none;border-bottom:solid windowtext 1.0px;border-right:solid windowtext 1.0px;
                mso-border-top-alt:solid windowtext .5pt;mso-border-left-alt:solid windowtext .5pt;
                mso-border-alt:solid windowtext .5pt;padding:0in 5.4pt 0in 5.4pt;height:90.75pt'>
                  @foreach($salary_deduction_data as $row)
                  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt'><o:p>&nbsp;</o:p>{{$row->attribute}}</p>
                 @endforeach
                </td>
                <td width=82 valign=top style='width:62.5pt;border-top:none;border-left:none;
                border-bottom:solid windowtext 1.0px;border-right:solid windowtext 1.0px;
                mso-border-top-alt:solid windowtext .5pt;mso-border-left-alt:solid windowtext .5pt;
                mso-border-alt:solid windowtext .5pt;padding:0in 5.4pt 0in 5.4pt;height:90.75pt'>
                  @foreach($salary_deduction_data as $row)
                    <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt'><b style='mso-bidi-font-weight:normal'><o:p>&nbsp;</o:p>&#8377;{{$row->amount}}</b></p>
                    @endforeach
                </td>
                </tr>

                <tr style='mso-yfti-irow:2;height:13.5pt'>
                    <td width=229 valign=top style='width:171.75pt;border:solid windowtext 1.0px;
                    border-top:none;mso-border-top-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;
                    padding:0in 5.4pt 0in 5.4pt;height:13.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;text-align:  justify'><b style='mso-bidi-font-weight:normal'>Gross Pay</b></p>
                    </td>
                    
                    <td width=90 valign=top style='width:67.5pt;border-top:none;border-left:none;
                    border-bottom:solid windowtext 1.0px;border-right:solid windowtext 1.0px;
                    mso-border-top-alt:solid windowtext .5pt;mso-border-left-alt:solid windowtext .5pt;
                    mso-border-alt:solid windowtext .5pt;padding:0in 5.4pt 0in 5.4pt;height:13.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;text-align: justify'><b style='mso-bidi-font-weight:normal'>&#8377;{{$orignal_salary}}<o:p></o:p></b></p>
                    </td>
                    <td width=90 valign=top style='width:67.5pt;border-top:none;border-left:none;
                    border-bottom:solid windowtext 1.0px;border-right:solid windowtext 1.0px;
                    mso-border-top-alt:solid windowtext .5pt;mso-border-left-alt:solid windowtext .5pt;
                    mso-border-alt:solid windowtext .5pt;padding:0in 5.4pt 0in 5.4pt;height:13.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;text-align: justify'><b style='mso-bidi-font-weight:normal'>&#8377;{{$salary_amount_sum}}<o:p></o:p></b></p>
                    </td>
                   <!--  <td style='width:171.75pt;border:solid windowtext 1.0px;
                    border-top:none;mso-border-top-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;
                    padding:0in 5.4pt 0in 5.4pt;height:13.5pt'></td> -->
                    <td width=264 valign=top style='width:2.75in;border-top:none;border-left:
                    none;border-bottom:solid windowtext 1.0px;border-right:solid windowtext 1.0px;
                    mso-border-top-alt:solid windowtext .5pt;mso-border-left-alt:solid windowtext .5pt;
                    mso-border-alt:solid windowtext .5pt;padding:0in 5.4pt 0in 5.4pt;height:13.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt'><b style='mso-bidi-font-weight:normal'>Total Deduction</b></p>
                    </td>
                    <td width=82 valign=top style='width:61.5pt;border-top:none;border-left:none;
                    border-bottom:solid windowtext 1.0px;border-right:solid windowtext 1.0px;
                    mso-border-top-alt:solid windowtext .5pt;mso-border-left-alt:solid windowtext .5pt;
                    mso-border-alt:solid windowtext .5pt;padding:0in 5.4pt 0in 5.4pt;height: 38.75pt;'>
                        <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt'><b style='mso-bidi-font-weight:normal'><o:p>&nbsp;</o:p>&#8377;{{$salary_deduction_sum}}</b></p>
                    </td>
                </tr>
                <tr style='mso-yfti-irow:3;height:33.0pt'>
                    <td colspan='6' width=665 colspan=4 valign=top style='width:498.75pt;border:solid windowtext 1.0px;  border-top:none;mso-border-top-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;  padding:0in 5.4pt 0in 5.4pt;height:33.0pt'> 
                        <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt'>Net Pay<b style='mso-bidi-font-weight:normal'><span  style='mso-spacerun:yes'>   </span>:<span style='mso-spacerun:yes'>&#8377; </span><o:p></o:p> {{$salary_amount_sum - $salary_deduction_sum}}</b></p>  
                    </td>
                </tr>
                
                <tr style='mso-yfti-irow:5;mso-yfti-lastrow:yes;height:58.5pt'>
                    <td colspan='6' width=665  colspan=4 valign=top style='width:498.75pt;border:solid windowtext 1.0px;
                    border-top:none;mso-border-top-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;
                    padding:0in 5.4pt 0in 5.4pt;height:58.5pt'>
                        <p class=MsoNormal align='center' style='margin-bottom:0in;margin-bottom:.0001pt,text-align:centre'> <span style='mso-spacerun:yes'> </span>{{$salary_in_words}}</p>
                     
                        <p class=MsoNormal align='center' style='margin-bottom:0in;margin-bottom:.0001pt,text-align:centre;margin-bottom:10px'> Note : This is a system generated salary slip and does not require any stamp or signature. </p>
                    </td>
                </tr>
                </table>

                <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt'><b
                style='mso-bidi-font-weight:normal'><span style='mso-spacerun:yes'>
                </span><o:p></o:p></b></p>

                </div>
                <button style='position:absolute; right:30px; top:20px; padding:10px 20px;cursor:pointer;' onclick='printPDF()'>Print PDF </button>
                 
                <script>
                    function printPDF() {
                        var divToPrint=document.getElementById('pdf');

                        var newWin=window.open('','Print-Window');
                      
                        newWin.document.open();
                      
                        newWin.document.write("<html><body onload='window.print()'>"+divToPrint.innerHTML+"</body></html>");
                      
                        newWin.document.close();
                      
                        setTimeout(function(){newWin.close();},10);
                    }
                </script>

                </body>

                </html>