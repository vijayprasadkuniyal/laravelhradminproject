<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\StateController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\DesignationController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\BranchTypeController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\EmployeeTypeController;
use App\Http\Controllers\Api\DocumentTypeController;
use App\Http\Controllers\Api\EmpBasicinfoController;
use App\Http\Controllers\Api\EmpDocumentController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\SallaryController;
use App\Http\Controllers\Api\LeaveController;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\PasswordChangeController;
use App\Http\Controllers\Api\LoginHoursController;
use App\Http\Controllers\Api\CreateworkingDayController;
use App\Http\Controllers\Api\LeaveTypeController;
use App\Http\Controllers\Api\TeamRecordController;
use App\Http\Controllers\Api\WorkFromHomeController;
use App\Http\Controllers\Api\RosterController;
use App\Http\Controllers\Api\ModuleController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\TicketRiseController;
use App\Http\Controllers\Api\TicketPriorityController;
use App\Http\Controllers\Api\VendorController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\SalesDocumentController;
use App\Http\Controllers\Api\ManagmentOpeningController;
use App\Http\Controllers\Api\FollowupController;
use App\Http\Controllers\Api\AdminProcessController;
use App\Http\Controllers\Api\CompanyTargetController;
use App\Http\Controllers\Api\EmpHistoryController;
use App\Http\Controllers\Api\DmDashboardController;
use App\Http\Controllers\Api\CsDashboardController;
use App\Http\Controllers\Api\HrDashboardController;
use App\Http\Controllers\Api\AppApiController;
use App\Http\Controllers\SalesApi\adminFollowupController;
use App\Http\Controllers\SalesApi\adminProductController;
use App\Http\Controllers\SalesApi\adminPackageController;
use App\Http\Controllers\FinanceApi\financeController;
use App\Http\Controllers\Invoice\ClientInvoiceController;
use App\Http\Controllers\CustomerSupport\csDetailsController;
use App\Http\Controllers\CustomerSupport\tataDialer;
use App\Http\Controllers\SalesApi\clientDetailsController;
use App\Http\Controllers\ThirdPartyApi\DialerApis;
use App\Http\Controllers\ThirdPartyApi\EnquiryController;

use App\Http\Middleware\EnsureAPITokenIsValid;
use App\Http\Controllers\Reports\Reports;
use App\Http\Controllers\SendSystem\LeadSendController;
use App\Http\Controllers\Offer\offersController;

use App\Http\Controllers\Api\UtmController;
use App\Http\Controllers\SalesApi\AttributeController;
use App\Http\Controllers\Api\SalesFunnelController;
use App\Http\Controllers\ThirdPartyApi\CommunicationApis;
use App\Http\Controllers\Api\ReturnLeadController;
use App\Http\Controllers\SendSystem\CronSendController;
use App\Http\Controllers\Api\SalesChangesController;
use App\Http\Controllers\ThirdPartyApi\GoogleKey;
use App\Http\Controllers\ThirdPartyApi\ZapierController;
use App\Http\Controllers\Api\GenerateLattersController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::get('generate-latters',[GenerateLattersController::class,'generate_latters']);
Route::get('get-emp-letters',[GenerateLattersController::class,'get_emp_letters']);
Route::get('manager-team-targets',[CompanyTargetController::class,'manager_team_targets']);
Route::get('managers-targets',[CompanyTargetController::class,'managers_targets']);
Route::get('send-welcome-mail',[EmpBasicinfoController::class,'send_welcome_mail']);

Route::get('get-all-client-reports',[AppApiController::class,'get_all_client_reports']);
Route::get('calculate-salary-pdf/{id}',[SallaryController::class,'calculate_salary_for_pdf']);
Route::get('salary/generate-pdf/{id}/{month}/{year}', [SallaryController::class,'generatePdf']);
Route::post('login',[LoginController::class,'login'])->name('login');
Route::get('send-birthday-mail',[EmpHistoryController::class,'send_birthday_mail']);
Route::get('check-emp-anniversary',[EmpHistoryController::class,'check_emp_anniversary']);
Route::get('get-all-client-reports',[Reports::class,'get_all_client_reports']);

Route::get('get-enquiry-csv-reports',[Reports::class,'get_enquiry_csv_reports']);
Route::get('download-ledger-details', [financeController::class, 'downloadLedgerDetails']);

Route::get('create-attendance-row',[EmpBasicinfoController::class,'create_attendance_row']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::middleware('auth:api')->group( function () {
//country
Route::post('save-country',[CountryController::class,'save_country']);
Route::get('country-list',[CountryController::class,'country_list']);
Route::post('update-country/{id}',[CountryController::class,'update_country']);
//Route::post('country-change-status',[CountryController::class,'country_status']);
Route::get('edit-country/{id}',[CountryController::class,'country_edit']);
Route::get('country-status/{id}',[CountryController::class,'get_country_status']);
Route::post('country-change-status',[CountryController::class,'country_status']);
Route::get('get-active-country',[CountryController::class,'get_active_country']);

//state

Route::post('save-state',[StateController::class,'save_state']);
Route::get('state-list',[StateController::class,'state_list']);
Route::post('update-state/{id}',[StateController::class,'update_state']);
Route::get('state-edit/{id}',[StateController::class,'state_edit']);
Route::get('country-based-state/{country_id}',[StateController::class,'country_based_state']);
Route::get('state-status/{id}',[StateController::class,'get_state_status']);
Route::post('state-change-status',[StateController::class,'state_status']);
Route::get('get-active-state',[StateController::class,'get_active_state']);

//department

Route::post('save-department',[DepartmentController::class,'save_department']);
Route::get('department-list',[DepartmentController::class,'department_list']);
Route::post('update-department/{id}',[DepartmentController::class,'update_department']);
Route::get('edit-department/{id}',[DepartmentController::class,'department_edit']);
Route::get('department-status/{id}',[DepartmentController::class,'get_department_status']);
Route::post('department-change-status',[DepartmentController::class,'department_status']);

//designation

Route::post('save-designation',[DesignationController::class,'save_designation']);
Route::get('designation-list',[DesignationController::class,'designation_list']);
Route::post('update-designation/{id}',[DesignationController::class,'update_designation']);
Route::get('edit-designation/{id}',[DesignationController::class,'designation_edit']);
Route::get('designation-status/{id}',[DesignationController::class,'get_designation_status']);
Route::post('designation-change-status',[DesignationController::class,'designation_status']);

//company

Route::post('save-company',[CompanyController::class,'save_company']);
Route::get('company-list',[CompanyController::class,'company_list']);
Route::post('update-company/{id}',[CompanyController::class,'update_company']);
Route::get('edit-company/{id}',[CompanyController::class,'company_edit']);
Route::get('company-status/{id}',[CompanyController::class,'get_company_status']);
Route::post('company-change-status',[CompanyController::class,'company_status']);
Route::get('get-active-company',[CompanyController::class,'get_active_company']);
Route::get('get-group-wise-employee',[CompanyTargetController::class,'get_group_wise_employee']);

//product

Route::post('save-product',[ProductController::class,'save_product']);
Route::get('product-list',[ProductController::class,'product_list']);
Route::post('update-product/{id}',[ProductController::class,'update_product']);
Route::get('edit-product/{id}',[ProductController::class,'product_edit']);
Route::get('product-status/{id}',[ProductController::class,'get_product_status']);
Route::post('product-change-status',[ProductController::class,'product_status']);
Route::get('company-based-product/{country_id}',[ProductController::class,'company_based_product']);
Route::get('active-product',[ProductController::class,'get_active_product']);

//branchtype

Route::post('save-branch-type',[BranchTypeController::class,'save_branch_type']);
Route::get('branch-type-list',[BranchTypeController::class,'branch_type_list']);
Route::post('update-branch-type/{id}',[BranchTypeController::class,'update_branch_type']);
Route::get('edit-branch-type/{id}',[BranchTypeController::class,'branch_type_edit']);
Route::get('branch-type-status/{id}',[BranchTypeController::class,'get_branch_type_status']);
Route::post('branch-type-change-status',[BranchTypeController::class,'branch_type_status']);
Route::get('get-active-branch-type',[BranchTypeController::class,'get_active_branch_type']);


//branch

Route::post('save-branch',[BranchController::class,'save_branch']);
Route::get('branch-list',[BranchController::class,'branch_list']);
Route::post('update-branch/{id}',[BranchController::class,'update_branch']);
Route::get('edit-branch/{id}',[BranchController::class,'branch_edit']);
Route::get('branch-status/{id}',[BranchController::class,'get_branch_status']);
Route::post('branch-change-status',[BranchController::class,'branch_status']);
Route::get('get-active-branch',[BranchController::class,'get_active_branch']);

//service

Route::post('save-service',[ServiceController::class,'save_service']);
Route::get('service-list',[ServiceController::class,'service_list']);
Route::post('update-service/{id}',[ServiceController::class,'update_service']);
Route::get('edit-service/{id}',[ServiceController::class,'service_edit']);
Route::get('service-status/{id}',[ServiceController::class,'get_service_status']);
Route::post('service-change-status',[ServiceController::class,'service_status']);

//emp_type
Route::post('save-emp_type',[EmployeeTypeController::class,'save_emp_type']);
Route::get('emp_type-list',[EmployeeTypeController::class,'emp_type_list']);
Route::post('update-emp_type/{id}',[EmployeeTypeController::class,'update_emp_type']);
//Route::post('emp_type-change-status',[EmployeeTypeController::class,'emp_type_status']);
Route::get('edit-emp_type/{id}',[EmployeeTypeController::class,'emp_type_edit']);
Route::get('emp_type-status/{id}',[EmployeeTypeController::class,'get_emp_type_status']);
Route::post('emp_type-change-status',[EmployeeTypeController::class,'emp_type_status']);

//document_type

Route::post('save-document_type',[DocumentTypeController::class,'save_document_type']);
Route::get('document_type-list',[DocumentTypeController::class,'document_type_list']);
Route::post('update-document_type/{id}',[DocumentTypeController::class,'update_document_type']);
//Route::post('document_type-change-status',[DocumentTypeController::class,'document_type_status']);
Route::get('edit-document_type/{id}',[DocumentTypeController::class,'document_type_edit']);
Route::get('document_type-status/{id}',[DocumentTypeController::class,'get_document_type_status']);
Route::post('document_type-change-status',[DocumentTypeController::class,'document_type_status']);
Route::get('get-active-document-type',[DocumentTypeController::class,'get_active_documenttype']);


//basic info

Route::post('save-employee',[EmpBasicinfoController::class,'save_employee']);
Route::post('emp-list',[EmpBasicinfoController::class,'emp_list']);
Route::get('edit-employee/{id}',[EmpBasicinfoController::class,'edit_employee']);
Route::post('update-employee/{id}',[EmpBasicinfoController::class,'update_employee']);
Route::get('employee-status/{id}',[EmpBasicinfoController::class,'get_employee_status']);
Route::post('employee-change-status',[EmpBasicinfoController::class,'employee_status']);
Route::get('company-based-branch/{id}',[EmpBasicinfoController::class,'company_based_branch']);
Route::get('get-active-department',[EmpBasicinfoController::class,'active_departments_list']);
Route::get('department-based-designation/{id}',[EmpBasicinfoController::class,'department_based_designation']);
Route::get('active-employee-type',[EmpBasicinfoController::class,'active_employee_type']);
Route::get('emp-profile/{id}',[EmpBasicinfoController::class,'emp_profile']);
Route::get('get-manager/{id}',[EmpBasicinfoController::class,'get_manager']);
Route::get('show-emp-birthday',[EmpBasicinfoController::class,'get_current_month_birthday']);
Route::get('birthday-notification/{id}',[EmpBasicinfoController::class,'show_birthday_notification']);
Route::get('delete-emp/{id}',[EmpBasicinfoController::class,'delete_emp']);
Route::get('get-all-manager',[EmpBasicinfoController::class,'get_all_managers']);
Route::get('get-all-manager-employee/{id}',[EmpBasicinfoController::class,'manager_based_employee']);
Route::post('save-emp-manager',[EmpBasicinfoController::class,'save_emp_manager']);
Route::post('department-based-employee',[EmpBasicinfoController::class,'department_based_employee']);
Route::get('get-all-emp-details',[EmpBasicinfoController::class,'get_all_emp_details']);
Route::post('save-enquiry',[EmpBasicinfoController::class,'save_enquiry']);
Route::get('recent-enquiry',[EmpBasicinfoController::class,'recent_enquiry']);
Route::get('attendance-regularize-list/{id}',[EmpBasicinfoController::class,'attendance_regularize_list']);
Route::post('send-attendance-request',[EmpBasicinfoController::class,'send_attendance_request']);
Route::get('show-regulrize-data/{id}',[EmpBasicinfoController::class,'show_regulrize_data']);
Route::post('save-attendance-regularize',[EmpBasicinfoController::class,'save_attendance_regularize']);


///generate_latters






//empdocument

Route::post('save-emp-document',[EmpDocumentController::class,'save_emp_document']);
Route::get('document-list/{id}',[EmpDocumentController::class,'document_list']);
Route::get('edit-document/{id}',[EmpDocumentController::class,'edit_document']);
Route::post('update-document/{id}',[EmpDocumentController::class,'update_document']);
Route::post('document-status',[EmpDocumentController::class,'document_status']);
Route::get('get-document-status/{id}',[EmpDocumentController::class,'get_document_status']);

///account 
Route::post('save-account',[AccountController::class,'save_account']);
Route::get('edit-account/{id}',[AccountController::class,'edit_account']);
Route::get('account-details/{id}',[AccountController::class,'account_details']);
Route::post('account-status',[AccountController::class,'account_status']);
Route::get('get-account-status/{id}',[AccountController::class,'get_account_status']);


//sallary 
Route::post('salary_calculate',[SallaryController::class,'sallary_calculate']);
Route::get('edit-salary/{id}',[SallaryController::class,'edit_sallary']);
Route::post('save-salary-attribute',[SallaryController::class,'salary_attribute']);
Route::get('attribute-list',[SallaryController::class,'attribute_list']);
Route::post('update-attribute/{id}',[SallaryController::class,'update_attribute']);
Route::get('edit-attribute/{id}',[SallaryController::class,'edit_attribute']);
Route::post('attribute-status',[SallaryController::class,'attribute_status']);
Route::get('get-attribute-status/{id}',[SallaryController::class,'get_attribute_status']);
Route::get('salary-fields',[SallaryController::class,'salary_fields']);
Route::get('get-salary-details/{id}',[SallaryController::class,'get_salary']);
Route::post('salary-status',[SallaryController::class,'salary_status']);
Route::get('get-salary-status/{id}',[SallaryController::class,'get_salary_status']);
Route::get('submitted-salary/{id}',[SallaryController::class,'submitted_salary_attribute']);
Route::post('salary-calc',[SallaryController::class,'salary_calc']);
Route::get('save-emp-salary',[SallaryController::class,'save_salary']);
Route::get('download-salary-data/{id}',[SallaryController::class,'show_salary_list']);
Route::get('get-deduction-attribute',[SallaryController::class,'deduction_attribute']);
Route::get('get-tds-data/{emp_id}',[SallaryController::class,'get_tds_data']);


//leave

Route::post('create-leave',[LeaveController::class,'create_leave']);
Route::post('leave-list',[LeaveController::class,'leave_list']);
Route::get('leave-edit/{id}',[LeaveController::class,'leave_edit']);
Route::post('leave-update/{id}',[LeaveController::class,'leave_update']);
Route::post('leave-status',[LeaveController::class,'leave_status']);
Route::get('get-leave-status/{id}',[LeaveController::class,'get_leave_status']);
Route::get('current-year-leave/{id}',[LeaveController::class,'current_year_leave']);
Route::get('leave-show-in-attendance/{id}',[LeaveController::class,'show_leave_on_attendance']);

//chaNGE PASSWORD

Route::post('change-password',[PasswordChangeController::class,'change_password']);
Route::post('save-permission',[PasswordChangeController::class,'save_permission']);
Route::get('get-permission/{id}',[PasswordChangeController::class,'get_permission']);

//logout 
Route::post('logout',[LoginController::class,'logout_emp']);
Route::get('emp-profile-details/{id}',[LoginController::class,'emp_profile']);
Route::get('session-destroy',[LoginController::class,'session_destroy']);
Route::get('session-logout',[LoginController::class,'session_logout']);
//
Route::get('login-hours/{id}',[LoginHoursController::class,'calculate_login_hours']);
Route::get('view-daily-attendance/{id}',[LoginHoursController::class,'daily_attendence']);

////working_days

Route::post('create-working-day',[CreateworkingDayController::class,'create_working_day']);
Route::get('working-day-list',[CreateworkingDayController::class,'working_day_list']);
Route::get('working-day-edit/{id}',[CreateworkingDayController::class,'working_day_edit']);
Route::post('update-working-day/{id}',[CreateworkingDayController::class,'update_working_day']);
Route::post('working-day-status',[CreateworkingDayController::class,'working_day_status']);
Route::get('get-working-day-status/{id}',[CreateworkingDayController::class,'get_working_day_status']);
Route::get('show-working-days/{id}',[CreateworkingDayController::class,'show_working_days']);

///leave=type
Route::post('create-leave-type',[LeaveTypeController::class,'create_leave_type']);
Route::get('leave-type-list',[LeaveTypeController::class,'leave_type_list']);
Route::get('leave-type-edit/{id}',[LeaveTypeController::class,'leave_type_edit']);
Route::post('leave-type-update/{id}',[LeaveTypeController::class,'leave_type_update']);
Route::post('leave-type-status',[LeaveTypeController::class,'leave_type_status']);
Route::get('get-leave-type-status/{id}',[LeaveTypeController::class,'get_leave_type_status']);
Route::get('active-leave-type/{id}',[LeaveTypeController::class,'get_active_leavetype']);
Route::get('active-confirmation-leave',[LeaveTypeController::class,'get_active_confirmation_leave']);
Route::get('get-emp-confirmation/{id}',[LeaveTypeController::class,'get_confirmation_status']);
Route::get('show-leave-blance/{id}',[LeaveTypeController::class,'show_leave_blance']);
Route::post('emp-leave-count',[LeaveTypeController::class,'emp_leave_count']);
Route::post('save-emp-leave',[LeaveTypeController::class,'save_emp_leave']);
Route::post('get-leave-list',[LeaveTypeController::class,'get_leave_list']);
Route::get('assign-leave-to-employee',[LeaveTypeController::class,'assign_leave']);
Route::get('emp-leave-data/{id}',[LeaveTypeController::class,'emp_leave_data']);
Route::post('view-team',[TeamRecordController::class,'team_record_data']);
Route::post('team-leave-data',[TeamRecordController::class,'team_leave_data']);
Route::post('emp-leave-status',[TeamRecordController::class,'emp_leave_status']);
Route::post('change-confirmation-status',[TeamRecordController::class,'change_confirmation_status']);
Route::get('emp-leave-taken-list/{id}',[LeaveTypeController::class,'emp_leave_taken_list']);
Route::get('get-all-managers/{id}',[TeamRecordController::class,'get_all_managers']);
Route::get('leave-permission/{id}',[LeaveTypeController::class,'leave_permission']);
Route::post('update-leave-permission',[LeaveTypeController::class,'update_leave_permission']);
Route::get('show-leave-type-based-on-emp-status/{emp_id}',[LeaveTypeController::class,'show_leave_type_based_on_emp_status']);
Route::post('save-leave-assign-request',[LeaveTypeController::class,'save_leave_assign_request']);
Route::get('get-leave-assign-request/{emp_id}',[LeaveTypeController::class,'get_leave_request_details']);
Route::get('leave-request-details-hr-dashboard',[LeaveTypeController::class,'leave_request_details_hr_dashboard']);
Route::post('action-on-emp-leave-request',[LeaveTypeController::class,'action_on_emp_leave_request']);
Route::get('category-based-on-group-id-with-total-target-data',[CompanyTargetController::class,'category_based_on_group_id_with_total_target_data']);

//wfh
Route::post('apply-wfh',[WorkFromHomeController::class,'apply_wfh']);
Route::post('count-days-wfh',[WorkFromHomeController::class,'count_days_wfh']);
Route::post('wfh-list',[WorkFromHomeController::class,'wfh_list']);
Route::post('team-wfh-list',[TeamRecordController::class,'team_wfh']);
Route::post('work-from-home-status',[TeamRecordController::class,'work_from_home_status']);
Route::get('work-from-home-attendance/{id}',[WorkFromHomeController::class,'work_from_home_attendance']);
Route::get('get-wfh-task/{id}',[WorkFromHomeController::class,'get_wfh_task']);
Route::post('update-wfh-task',[WorkFromHomeController::class,'update_wfh_task']);
Route::post('update-wfh-task-status/{id}',[WorkFromHomeController::class,'update_wfh_task_status']);
Route::get('get-wfh-task-status/{id}',[WorkFromHomeController::class,'get_wfh_task_status']);
//Route::get('get-wfh-task-details/{id}',[WorkFromHomeController::class,'get_wfh_task_details']);

//roster
Route::get('manager-employee/{id}',[RosterController::class,'get_manager_employee']);
Route::post('create-roster',[RosterController::class,'create_roster']);
Route::post('roster-list',[RosterController::class,'roster_list']);
Route::get('roster-attendance/{id}',[RosterController::class,'roster_wise_attendance']);
Route::get('show-emp-roster',[RosterController::class,'show_emp_roster']);
Route::post('request-to-change-roster',[RosterController::class,'request_to_change_roster']);
Route::post('roster-change-request-list',[RosterController::class,'roster_change_request']);
Route::get('roster-status/{id}',[RosterController::class,'get_roster_status']);
Route::post('roster-change-status',[RosterController::class,'roster_status']);
Route::get('edit-roster/{id}',[RosterController::class,'edit_roster']);
Route::post('update-roster/{id}',[RosterController::class,'update_roster']);
Route::post('pending-confirmation-notification',[RosterController::class,'confirmation_notifications']);
Route::get('update-confirmation-seen-status',[RosterController::class,'update_confirmation_seen_status']);

Route::post('update-roster-request',[RosterController::class,'update_roster_request']);

Route::post('roster-change-request-list',[RosterController::class,'roster_change_request']);

Route::get('emp-confirmation-details',[RosterController::class,'emp_confirmation_details']);

Route::post('emp-confirmation-status-update',[RosterController::class,'emp_confirmation_status_update']);

Route::get('roster-change-request-of-particular-employee',[RosterController::class,'roster_change_request_of_particular_employee']);
Route::post('update-roster-request',[RosterController::class,'update_roster_request']);
Route::get('get-gross-and-net-salary/{emp_id}',[SallaryController::class,'get_gross_and_net_salary']);


//modulecontroller

Route::post('save-module',[ModuleController::class,'save_module']);
Route::post('module-list',[ModuleController::class,'module_list']);
Route::get('module-edit/{id}',[ModuleController::class,'module_edit']);
Route::post('module-update/{id}',[ModuleController::class,'module_update']);
Route::get('module-status/{id}',[ModuleController::class,'module_status']);
Route::post('update-module-status',[ModuleController::class,'update_module_status']);
Route::post('department-based-module',[ModuleController::class,'department_based_module']);
Route::get('submitted-module/{id}',[ModuleController::class,'submitted_module']);
Route::get('fetch-emp-permission/{id}',[ModuleController::class,'fetched_emp_permission']);
Route::get('update-emp-id',[EmpBasicinfoController::class,'update_emp_id']);

//notification 

//Route::get('notification/{id}',[NotificationController::class,'notification_list']);
Route::post('enquiry-list',[NotificationController::class,'enquiry_list']);
//Route::post('enquiry-list',[NotificationController::class,'enquiry_list']);
Route::get('live-notification/{id}',[NotificationController::class,'live_notification']);
Route::get('notification-seen-status/{id}',[NotificationController::class,'seen_status']);
Route::get('update-notification-status/{id}',[NotificationController::class,'change_notification_status']);
Route::get('notification-count/{id}',[NotificationController::class,'live_notification_count']);

///TICKET-PRIORITY


Route::post('save-priority',[TicketPriorityController::class,'save_priority']);
Route::get('priority-list',[TicketPriorityController::class,'priority_list']);
Route::post('update-priority/{id}',[TicketPriorityController::class,'update_priority']);
//Route::post('country-change-status',[TicketPriorityController::class,'country_status']);
Route::get('edit-priority/{id}',[TicketPriorityController::class,'priority_edit']);
Route::get('priority-status/{id}',[TicketPriorityController::class,'get_priority_status']);
Route::post('priority-change-status',[TicketPriorityController::class,'priority_status']);
//Route::get('get-active-country',[TicketPriorityController::class,'get_active_country']);
Route::get('ticket-subattribute-list',[TicketPriorityController::class,'ticket_subattribute']);
Route::post('save-ticket-subattribute',[TicketPriorityController::class,'save_ticket_subattribute']);
Route::get('edit-ticket-subattribute/{id}',[TicketPriorityController::class,'edit_ticket_subattribute']);
Route::post('update-ticket-subattribute/{id}',[TicketPriorityController::class,'update_ticket_subattribute']);
Route::get('ticket-subattribute-status/{id}',[TicketPriorityController::class,'ticket_subattribute_status']);
Route::post('ticket-subattribute-status-update',[TicketPriorityController::class,'ticket_subattribute_status_update']);
Route::get('get-active-ticket-attribute',[TicketPriorityController::class,'get_active_ticket_attribute']);
Route::get('edit-ticket/{ticket_id}',[TicketRiseController::class,'edit_ticket']);
Route::post('update-ticket',[TicketRiseController::class,'update_ticket']);
Route::post('ticket-notification-seen-status',[TicketRiseController::class,'ticket_notification_seen_status']);
Route::get('assign-member-details/{ticket_id}',[TicketRiseController::class,'assign_member_details']);
Route::post('update-assigned-member-task',[TicketRiseController::class,'update_assigned_member_task']);

//ticket_rise

Route::post('ticket-raise',[TicketRiseController::class,'ticket_rise']);
//Route::post('ticket-raise',[TicketRiseController::class,'ticket_rise']);
Route::get('ticket-raise-notification/{emp_id}',[TicketRiseController::class,'ticket_raise_notification']);
Route::get('cross-status/{id}',[TicketRiseController::class,'cross_status']);
Route::get('update-seen-status/{id}',[TicketRiseController::class,'update_seen_status']);
Route::get('ticket-raise-list/{id}',[TicketRiseController::class,'ticket_raise_list']);
Route::get('ticket-receive-list/{id}',[TicketRiseController::class,'ticket_receive_list']);
Route::post('save-assign-ticket',[TicketRiseController::class,'save_assign_ticket']);
//Route::get('department-employee/{id}',[TicketRiseController::class,'department_employee']);
Route::get('assign-ticket-list/{id}',[TicketRiseController::class,'get_assign_ticket_list']);
Route::get('assign-notification/{id}',[TicketRiseController::class,'assign_notification']);
Route::get('assign-notification-status/{id}',[TicketRiseController::class,'assign_notification_status']);
Route::post('assign-ticket-update',[TicketRiseController::class,'assign_ticket_update']);
Route::get('priority-attribute',[TicketRiseController::class,'priority_attribute']);
Route::get('update-status-on-click/{id}',[TicketRiseController::class,'update_status_click_on_notification']);
Route::get('click-on-assign-notification/{id}',[TicketRiseController::class,'click_on_assign_notification']);
Route::get('view-ticket-status/{ticket_id}',[TicketRiseController::class,'view_ticket_status']);
Route::post('close-ticket',[TicketRiseController::class,'close_ticket']);
Route::get('ticket-active-subattribute/{attribute_id}',[TicketRiseController::class,'ticket_active_subattribute']);
Route::get('get-ticket-details/{ticket_id}',[TicketRiseController::class,'get_ticket_details']);
Route::get('get-employee-details-with-group',[TicketRiseController::class,'get_employee_details_with_group']);
Route::get('get-ticket-solve-status',[TicketRiseController::class,'get_ticket_solve_status']);
Route::post('update-remark-of-ticket',[TicketRiseController::class,'update_remark_of_ticket']);
Route::get('ticket-list-raise-in-org',[TicketRiseController::class,'ticket_list_raise_in_org']);
Route::post('save-new-time-request',[TicketRiseController::class,'save_new_time_request']);
Route::get('get-new-time-request',[TicketRiseController::class,'get_new_time_request']);
Route::get('ticket-time-for-approval',[TicketRiseController::class,'ticket_time_for_approval']);
Route::post('ticket-time-approval-status-update',[TicketRiseController::class,'ticket_time_approval_status_update']);
Route::get('check-ticket-action-time',[TicketRiseController::class,'check_ticket_action_time']);
Route::get('check-ticket-working-status/{id}',[TicketRiseController::class,'check_ticket_working_status']);
Route::get('dept-list-for-ticket-raise',[TicketRiseController::class,'dept_list_for_ticket_raise']);
Route::post('pick-ticket',[TicketRiseController::class,'pick_ticket']);


//vendordetails
Route::post('create-vendor',[VendorController::class,'create_vendor']);
Route::get('vendor-list',[VendorController::class,'vendor_list']);
Route::get('vendor-edit/{id}',[VendorController::class,'vendor_edit']);
Route::post('vendor-update/{id}',[VendorController::class,'vendor_update']);
Route::get('vendor-status/{id}',[VendorController::class,'vendor_status']);
Route::post('change-vendor-status',[VendorController::class,'update_vendor_status']);
////

Route::post('create-category',[CategoryController::class,'create_category']);
Route::get('category-list',[CategoryController::class,'category_list']);
Route::get('category-edit/{id}',[CategoryController::class,'category_edit']);
Route::post('category-update/{id}',[CategoryController::class,'category_update']);
Route::get('category-status/{id}',[CategoryController::class,'category_status']);
Route::post('change-category-status',[CategoryController::class,'update_category_status']);


//stock 

Route::post('add-stock',[StockController::class,'add_stock']);
Route::get('stock-list',[StockController::class,'stock_list']);
Route::get('stock-edit/{id}',[StockController::class,'stock_edit']);
Route::post('stock-update/{id}',[StockController::class,'stock_update']);
Route::get('stock-status/{id}',[StockController::class,'stock_status']);
Route::post('change-stock-status',[StockController::class,'update_stock_status']);
Route::get('active-vendor',[StockController::class,'active_vendor']);
Route::get('active-category',[StockController::class,'active_category']);
Route::get('active-stock',[StockController::class,'get_active_stock']);
Route::get('category-based-stock/{id}/{branch}',[StockController::class,'category_based_stock']);
Route::get('department-employee/{id}',[StockController::class,'department_employee']);
Route::post('assign-stock',[StockController::class,'assign_stock']);
Route::get('assign-stock-list',[StockController::class,'assign_stock_list']);
Route::get('edit-assign-stock/{id}',[StockController::class,'edit_assign_stock']);
Route::post('update-assign-stock/{id}',[StockController::class,'update_assign_stock']);
Route::get('emp-stock-list/{id}',[StockController::class,'emp_stock_list']);
Route::get('change-stock-status/{id}/{stock_id}/{status}/{rejectreason}/{assign_to}/{employee_id}',[StockController::class,'change_stock_status']);
Route::get('return-stock/{id}/{reason}/{emp_id}/{employee_id}',[StockController::class,'return_stock']);
Route::get('return-stock-list',[StockController::class,'return_stock_list']);
Route::get('return-stock-status/{id}/{stock_id}/{emp_id}/{status}/{remark}/{date}/{employee}',
	[StockController::class,'return_stock_status']);
Route::get('admin-return-edit/{id}/{emp_id}',[StockController::class,'admin_return_edit']);
Route::get('emp-return-edit/{id}/{emp_id}',[StockController::class,'emp_return_edit']);
Route::get('stock-reject-reason/{id}/{emp_id}',[StockController::class,'emp_stock_reject']);
Route::post('raise-asset-request',[StockController::class,'raise_asset_request']);
Route::get('edit-raise-asset-request/{id}',[StockController::class,'raise_asset_request_edit']);
Route::post('update-raise-asset-request/{id}',[StockController::class,'update_asset_request']);
Route::get('raise-asset-request-list',[StockController::class,'raise_asset_request_list']);
Route::post('change-asset-request-status',[StockController::class,'change_asset_request_status']);
Route::get('get-asset-request-status/{id}',[StockController::class,'get_asset_request_status']);
Route::post('import-stock',[StockController::class,'import_stock']);
Route::get('export-stock',[StockController::class,'export_excel']);
Route::get('count-stock/{id}',[StockController::class,'count_stock']);
Route::get('export-avaliable-stock',[StockController::class,'export_avaliable_stock']);
Route::post('import-stock-excel',[StockController::class,'import_stock_excel']);
Route::get('branch-export',[StockController::class,'branch_export']);
Route::get('category-based-stock-list/{id}',[StockController::class,'category_based_stock_list']);
Route::get('stock-history/{id}',[StockController::class,'stock_history']);
Route::get('emp-stock-history/{emp_id}/{stock_id}',[StockController::class,'emp_stock_history']);


///emp_history 
Route::get('emp-salary-history/{emp_id}',[EmpHistoryController::class,'emp_salary_history']);
Route::get('emp-leave-history/{emp_id}',[EmpHistoryController::class,'emp_leave_history']);
Route::get('emp-wfh-history',[EmpHistoryController::class,'emp_wfh_history']);
Route::post('save-resign',[EmpHistoryController::class,'save_resign']);
Route::get('emp-resign-list/{id}',[EmpHistoryController::class,'emp_resign_list']);
Route::get('show-team-resign-list',[EmpHistoryController::class,'show_team_resign_list']);
Route::post('save-resign-remark',[EmpHistoryController::class,'save_resign_remark']);
Route::get('get-resign-description-details/{id}',[EmpHistoryController::class,'get_resign_description']);
Route::post('save-resign-description-details',[EmpHistoryController::class,'save_resign_description_details']);
Route::get('emp-asset-details-for-fnf/{id}',[EmpHistoryController::class,'emp_asset_details_for_fnf']);
//Route::get('emp-reporting-history/{emp_id}',[EmpHistoryController::class,'emp_reporting_history']);


////salesdocument
Route::get('sales-package-type',[SalesDocumentController::class,'sales_package_type']);
Route::get('product-category',[SalesDocumentController::class,'product_category']);
Route::post('save-sales-document',[SalesDocumentController::class,'save_sales_document']);
Route::get('sales-document-list',[SalesDocumentController::class,'document_list']);
Route::get('sales-document-status/{id}',[SalesDocumentController::class,'sales_doc_status']);
Route::post('sales-document-status-change',[SalesDocumentController::class,'sales_document_status_change']);
Route::get('sales-document-edit/{id}',[SalesDocumentController::class,'sales_document_edit']);
Route::post('sales-document-update/{id}',[SalesDocumentController::class,'sales_document_update']);
Route::post('save-sales-document-type',[SalesDocumentController::class,'save_sales_document_type']);
Route::get('get-sales-document-type',[SalesDocumentController::class,'get_sales_document_type']);
Route::get('get-sales-document-details/{id}',[SalesDocumentController::class,'get_sales_document_details']);
Route::post('update-sales-document-type',[SalesDocumentController::class,'update_sales_document_type']);
Route::get('get-active-sales-document-type',[SalesDocumentController::class,'get_active_sales_document_type']);

///
Route::post('save-job-open-request',[ManagmentOpeningController::class,'save_opening']);
Route::get('job-request-list/{id}',[ManagmentOpeningController::class,'job_request_list']);
Route::get('job-position-request',[ManagmentOpeningController::class,'job_position_request']);
Route::post('opening-request-status',[ManagmentOpeningController::class,'opening_status']);
Route::get('opening-status-edit/{id}',[ManagmentOpeningController::class,'opening_status_edit']);
Route::get('opening-request-hr',[ManagmentOpeningController::class,'opening_request_list_hr']);
Route::post('save-assign-job-request',[ManagmentOpeningController::class,'save_assign_job_request']);
Route::get('edit-assign-list/{id}',[ManagmentOpeningController::class,'edit_assign_list']);
Route::get('followup-details/{id}',[ManagmentOpeningController::class,'followup_details']);
Route::post('save-followup-details',[ManagmentOpeningController::class,'save_followup_details']);
Route::get('job-position-details/{id}',[ManagmentOpeningController::class,'job_position_details']);
Route::get('followup-candidate-details/{id}/{emp_id}',[ManagmentOpeningController::class,'followup_candidate_details']);
Route::get('emp-details/{id}',[ManagmentOpeningController::class,'get_employee_details']);
Route::get('candidate-list/{id}',[ManagmentOpeningController::class,'candidate_list']);
Route::get('candidate-details/{id}',[ManagmentOpeningController::class,'candidate_details']);
Route::post('candidate-followup-save',[ManagmentOpeningController::class,'save_candidate_followup']);
Route::get('candidate-followup-history/{id}',[ManagmentOpeningController::class,'candidate_followup_history']);
Route::get('download-offer-letter',[ManagmentOpeningController::class,'download_offer_leter']);
Route::get('edit-candidate-profile/{id}',[ManagmentOpeningController::class,'edit_candidate_profile']);
Route::post('update-candidate-profile/{id}',[ManagmentOpeningController::class,'update_candidate_profile']);
Route::get('close-job-request/{id}',[ManagmentOpeningController::class,'close_job_position']);
Route::get('action-on-request/{id}/{emp_id}',[ManagmentOpeningController::class,'action_on_request']);
/// followup-attribute

Route::post('save-followup-attribute',[FollowupController::class,'save_followup_status']);
Route::get('followup-attribute-list',[FollowupController::class,'followup_list']);
Route::get('edit-followup-attribute/{id}',[FollowupController::class,'followup_edit']);
Route::post('followup-attribute-update/{id}',[FollowupController::class,'update_followup_attribute']);
Route::get('get-followup-status/{id}',[FollowupController::class,'get_followup_status']);
Route::post('followup-status',[FollowupController::class,'followup_status']);
Route::get('active-followup-attribute',[FollowupController::class,'active_followup_details']);
Route::get('kyc-document-list',[AdminProcessController::class,'kyc_document_list']);
Route::get('kyc-document-status/{id}',[AdminProcessController::class,'kyc_document_status']);
Route::post('kyc-document-status-change',[AdminProcessController::class,'kyc_document_status_change']);

//company_target

Route::get('target-list',[CompanyTargetController::class,'company_target_list']);
Route::post('save-target',[CompanyTargetController::class,'save_target']);
Route::get('get-target-status/{id}',[CompanyTargetController::class,'get_target_status']);
Route::post('update-target-status',[CompanyTargetController::class,'update_target_status']);
Route::get('target-edit/{id}',[CompanyTargetController::class,'target_edit']);
Route::post('update-target/{id}',[CompanyTargetController::class,'update_target']);
Route::get('kra-kpi-attribute/{dept_id}',[CompanyTargetController::class,'kra_kpi_attribute']);
Route::get('category-details/{id}',[CompanyTargetController::class,'category_details']);
Route::get('group-details',[CompanyTargetController::class,'group_details']);
Route::post('save-target-attribute',[CompanyTargetController::class,'save_target_attribute']);
Route::get('target-attribute-list',[CompanyTargetController::class,'attribute_list']);
Route::get('edit-target-attribute/{id}',[CompanyTargetController::class,'edit_attribute']);
Route::post('update-target-attribute/{id}',[CompanyTargetController::class,'update_attribute']);
Route::get('target-attribute-status/{id}',[CompanyTargetController::class,'attribute_status']);
Route::post('update-target-atribute-status',[CompanyTargetController::class,'attribute_status_update']);
Route::get('target-assign-list-for-sales',[CompanyTargetController::class,'assign_lead_target_for_sales']);
Route::get('lead-amount-details',[CompanyTargetController::class,'lead_amount_details']);

//

Route::post('save-target-subattribute',[CompanyTargetController::class,'save_target_subattribute']);
Route::get('target-subattribute-list',[CompanyTargetController::class,'subattribute_list']);
Route::get('edit-target-subattribute/{id}',[CompanyTargetController::class,'edit_subattribute']);
Route::post('update-target-subattribute/{id}',[CompanyTargetController::class,'update_subattribute']);
Route::get('target-subattribute-status/{id}',[CompanyTargetController::class,'subattribute_status']);
Route::post('update-target-subatribute-status',[CompanyTargetController::class,'subattribute_status_update']);
Route::get('active-attribute',[CompanyTargetController::class,'active_attribute']);

Route::get('target-details',[CompanyTargetController::class,'target_details']);
Route::get('department-based-attribute/{id}',[CompanyTargetController::class,'department_based_attribute']);
Route::get('department-subattribute/{id}',[CompanyTargetController::class,'department_subattribute']);
Route::get('department-based-target',[CompanyTargetController::class,'department_wise_target_details']);
Route::post('save-assign-target',[CompanyTargetController::class,'save_assign_target']);
Route::get('get-subattribute/{id}',[CompanyTargetController::class,'get_subattribute']);
Route::get('assign-target-list',[CompanyTargetController::class,'assign_target_list']);
Route::post('import-target-excel',[CompanyTargetController::class,'import_target_excel']);
Route::get('export-target',[CompanyTargetController::class,'export_target']);
Route::get('emp-target-list',[CompanyTargetController::class,'emp_target_list']);
Route::get('goal-attributes',[CompanyTargetController::class,'goal_attributes']);
Route::get('goal-subattributes/{id}',[CompanyTargetController::class,'goal_subattributes']);
Route::post('save-map-attribute',[CompanyTargetController::class,'save_map_attribute']);
Route::get('attribute-map-list',[CompanyTargetController::class,'attribute_map_list']);
Route::get('get-active-attribute',[CompanyTargetController::class,'get_active_attribute_list']);
Route::get('get-lead-based-data',[CompanyTargetController::class,'get_lead_based_data']);

///


Route::post('save-goal-subattribute',[CompanyTargetController::class,'save_goal_subattribute']);
Route::get('goal-subattribute-list',[CompanyTargetController::class,'goal_subattribute__list']);
Route::get('edit-goal-subattribute/{id}',[CompanyTargetController::class,'edit_goal_subattribute']);
Route::post('update-goal-subattribute/{id}',[CompanyTargetController::class,'update_goal_subattribute']);
Route::get('get-subattribute-status/{id}',[CompanyTargetController::class,'get_subattribute_status']);
Route::post('sub-atribute-status-update',[CompanyTargetController::class,'sub_attribute_status_update']);
Route::get('goal-subattributes-list',[CompanyTargetController::class,'active_goal_subattributes_list']);

Route::post('save-goal-sub-sub-attribute',[CompanyTargetController::class,'save_goal_sub_sub_attribute']);
Route::get('goal-sub-sub-attribute-list',[CompanyTargetController::class,'goal_sub_sub_attribute_list']);
Route::get('edit-goal-sub-sub-attribute/{id}',[CompanyTargetController::class,'edit_goal_sub_sub_attribute']);
Route::post('update-goal-sub-sub-attribute/{id}',[CompanyTargetController::class,'update_goal_sub_sub_attribute']);
Route::get('get-sub-sub-attribute-status/{id}',[CompanyTargetController::class,'get_sub_sub_attribute_status']);
Route::post('sub-sub-atribute-status-update',[CompanyTargetController::class,'sub_sub_attribute_status_update']);
Route::get('goal-based-attribute/{id}/{dept_id}',[CompanyTargetController::class,'goal_based_attribute']);
Route::get('goal-based-subattribute/{id}/{dept_id}',[CompanyTargetController::class,'goal_based_subattribute']);
Route::get('goal-based-sub-subattribute/{id}/{dept_id}',[CompanyTargetController::class,'goal_based_sub_subattribute']);
Route::get('source-category',[CompanyTargetController::class,'source_category']);
Route::get('show-assign-data/{id}/{emp_id}/{financial_year}',[CompanyTargetController::class,'show_assign_data']);
Route::get('assign-groups/{id}/{group_id}',[CompanyTargetController::class,'assign_to_groups']);
Route::post('save-assign-groups-leads',[CompanyTargetController::class,'save_assign_leads_to_groups']);
Route::get('get-monthly-lead-data',[CompanyTargetController::class,'get_monthly_data']);
Route::get('lead-edit/{id}/{group_id}/{category_id}',[CompanyTargetController::class,'lead_edit']);
Route::post('save-edit-lead/{id}/{group_id}/{category_id}',[CompanyTargetController::class,'save_edit_lead']);
Route::get('service-wise-target-details',[CompanyTargetController::class,'service_wise_targets_details']);
Route::get('manager-team-targets',[CompanyTargetController::class,'manager_team_targets']);
Route::post('save-assign-target-for-sales',[CompanyTargetController::class,'save_assign_target_for_sales']);

Route::post('calculate',[CompanyTargetController::class,'calculate_data']);
Route::get('count-assign-lead-data/{id}/{group}/{category}/{source}',[CompanyTargetController::class,'count_assign_lead']);
Route::get('get-department-based-target',[CompanyTargetController::class,'get_department_based_target']);
Route::get('get-product',[CompanyTargetController::class,'get_product']);
Route::get('get-target-save-data',[CompanyTargetController::class,'get_target_saved_data']);
Route::get('product-service',[CompanyTargetController::class,'product_service']);
Route::get('product-categoey-list/{id}',[CompanyTargetController::class,'get_category_details']);
Route::get('get-sales-lead',[CompanyTargetController::class,'get_sales_lead']);
Route::get('get-group-employee',[CompanyTargetController::class,'get_group_employee']);
Route::get('show-group-saved-data',[CompanyTargetController::class,'show_group_saved_data']);
Route::get('count-sales-lead-data/{group}/{category}/{month}/{year}',[CompanyTargetController::class,'count_sales_lead_data']);
Route::get('emp-data',[CompanyTargetController::class,'emp_data']);
Route::get('employee-group-list',[CompanyTargetController::class,'employee_group_list']);
Route::get('get-active-groups/{id}',[CompanyTargetController::class,'get_active_groups']);
Route::post('save-assign-group',[CompanyTargetController::class,'save_assign_group']);
Route::get('get-followup-status-list',[CompanyTargetController::class,'get_followup_status_list']);
//admin Sales route
Route::post('add-followup-status',[adminFollowupController::class,'add_followup_status']);
Route::get('followup-list',[adminFollowupController::class,'followup_list']);
Route::get('followup-status/{id}',[adminFollowupController::class,'get_followup_status']);
Route::post('followup-change-status',[adminFollowupController::class,'followup_change_status']);
Route::get('followup-edit/{id}',[adminFollowupController::class,'followup_edit']);

Route::post('add-followup-status',[adminFollowupController::class,'add_followup_status']);
Route::get('followup-list',[adminFollowupController::class,'followup_list']);
Route::get('followup-status/{id}',[adminFollowupController::class,'get_followup_status']);
Route::post('followup-change-status',[adminFollowupController::class,'followup_change_status']);
Route::get('followup-edit/{id}',[adminFollowupController::class,'followup_edit']);

Route::get('sales-product-list/{type}',[adminProductController::class,'sales_product_list']);
Route::post('add-product-details',[adminProductController::class,'add_product_details']);
Route::get('sales-product-status/{id}',[adminProductController::class,'sales_product_status']);
Route::post('sales-product-change-status',[adminProductController::class,'sales_product_change_status']);

Route::post('add-service-details',[adminProductController::class,'add_service_details']);
Route::get('sales-service-list/{type}',[adminProductController::class,'sales_service_list']);
Route::get('sales-service-status/{id}',[adminProductController::class,'sales_service_status']);
Route::post('sales-service-change-status',[adminProductController::class,'sales_service_change_status']);


//Sales admin create package
Route::post('add-duration-type-factor',[adminPackageController::class,'add_duration_type_factor']);
Route::get('get-duration-list/{type}',[adminPackageController::class,'get_duration_list']);
Route::get('get-package-type-list/{type}',[adminPackageController::class,'get_package_type_list']);
Route::get('get-category-list/{type}',[adminProductController::class,'get_category_list']);
Route::get('get-service-list-by-product-id/{product_id}',[adminProductController::class,'get_service_list_by_product_id']);
Route::post('add-category-details',[adminProductController::class,'add_category_details']);

Route::get('get-location-list',[adminPackageController::class,'get_location_list']);
Route::post('add-location-factor',[adminPackageController::class,'add_location_factor']);
Route::get('get-location-fact-list',[adminPackageController::class,'get_location_fact_list']);

Route::post('add-locality-factor',[adminPackageController::class,'add_locality_factor']);
Route::get('get-locality-fact-list',[adminPackageController::class,'get_locality_fact_list']);
Route::get('get-to-location-fact-list',[adminPackageController::class,'get_to_location_fact_list']);
Route::get('get-duration-type-fact-list/{fact_type}',[adminPackageController::class,'get_duration_type_fact_list']);
Route::get('get-location-wise-locality-fact/{location_id}',[adminPackageController::class,'get_location_wise_locality_fact']);
Route::get('get-day-fact-list',[adminPackageController::class,'get_day_fact_list']);
Route::post('add-days-factor',[adminPackageController::class,'add_days_factor']);
Route::get('get-lead-fact-list',[adminPackageController::class,'get_lead_fact_list']);
Route::post('add-lead-factor',[adminPackageController::class,'add_lead_factor']);
Route::get('get-category-fact-list',[adminPackageController::class,'get_category_fact_list']);
Route::get('get-category-list-by-service-id/{service_id}',[adminPackageController::class,'get_category_list_by_service_id']);

Route::post('add-category-factor-details',[adminPackageController::class,'add_category_factor_details']);
Route::get('get-product-list',[adminPackageController::class,'get_product_list']);
Route::post('check-package-price-details',[adminPackageController::class,'check_package_price_details']);

Route::get('check-client-mobile-number/{mobile_no}',[clientDetailsController::class,'check_client_mobile_number']);
Route::get('get-city-details',[clientDetailsController::class,'get_city_details']);
Route::get('get-followup-list/{call_status}',[clientDetailsController::class,'get_followup_list']);
Route::get('get-next-followup-date/{followup_id}',[clientDetailsController::class,'get_next_followup_date']);
Route::post('add-guest-client',[clientDetailsController::class,'add_guest_client']);

Route::get('get-client-details',[clientDetailsController::class,'get_client_details']);
Route::get('get-today-followup-client-details',[clientDetailsController::class,'get_today_followup_client_details']);
Route::post('add-client-followup',[clientDetailsController::class,'add_client_followup']);
Route::get('no-of-attempts-on-enquiry',[csDetailsController::class,'no_of_attempt_on_enquiry']);
Route::get('show-enq-status',[csDetailsController::class,'show_enq_status']);


Route::get('check-client-followup-details/{client_id}',[clientDetailsController::class,'check_client_followup_details']);
Route::get('check-client-details/{client_id}',[clientDetailsController::class,'check_client_details']);
Route::get('get-document-type/{org_type}/{product_id}/{service_id}',[clientDetailsController::class,'get_document_type']);
Route::get('get-organization-type',[clientDetailsController::class,'get_organization_type']);
Route::post('add-mature-client-details',[clientDetailsController::class,'add_mature_client_details']);

Route::get('check-mature-client-company-details/{emp_id}/{client_id}',[clientDetailsController::class,'check_mature_client_company_details']);
Route::get('check-mature-client-details/{emp_id}',[clientDetailsController::class,'check_mature_client_details']);
Route::get('create-client-package/{client_id}',[clientDetailsController::class,'create_client_package']);
Route::get('attendance-report',[EmpBasicinfoController::class,'attendance_report']);
Route::post('update-punch-in-time',[EmpBasicinfoController::class,'update_punch_in_time']);
Route::get('punch-in-show-button-condition/{emp_id}',[EmpBasicinfoController::class,'punch_in_show_button_condition']);

Route::post('add-lead-package-factor',[adminPackageController::class,'add_lead_package_factor']);
Route::post('add-unlimited-package-factor',[adminPackageController::class,'add_unlimited_package_factor']);
Route::get('get-package-type',[adminPackageController::class,'get_package_type']);
Route::get('get-group-list',[adminPackageController::class,'get_group_list']);
Route::get('get-locality-list/{location_id}',[adminPackageController::class,'get_locality_list']);
Route::post('add-lead-location-price',[adminPackageController::class,'add_lead_location_price']);
Route::post('add-unlimited-location-price',[adminPackageController::class,'add_unlimited_location_price']);
Route::get('get-package-category-list/{product_id}',[adminProductController::class,'get_package_category_list']);
Route::get('get-package-category-listing',[adminProductController::class,'get_package_category_listing']);
Route::post('add-package-category-details',[adminProductController::class,'add_package_category_details']);
Route::get('get-package-category-type-list',[adminProductController::class,'get_package_category_type_list']);
Route::post('add-package-category-type-details',[adminProductController::class,'add_package_category_type_details']);

Route::get('get-service-list-by-product-id-for-category/{product_id}',[adminProductController::class,'get_service_list_by_product_id_for_category']);

Route::get('get-leadbased-packages-list',[adminPackageController::class,'get_leadbased_packages_list']);

Route::get('get-leadbased-packages-details/{product_id}/{service_id}',[adminPackageController::class,'get_leadbased_packages_details']);

Route::get('get-package-duration',[adminPackageController::class,'get_package_duration']);
Route::get('get-package-category-by-product-id/{product_id}',[adminPackageController::class,'get_package_category_by_product_id']);

Route::get('get-package-category-type-by-category-id/{product_id}/{category_id}',[adminPackageController::class,'get_package_category_type_by_category_id']);

Route::post('check-package-price',[adminPackageController::class,'check_package_price']);
Route::post('create-pre-package',[adminPackageController::class,'create_pre_package']);
Route::get('get-pre-packages-list',[adminPackageController::class,'get_pre_packages_list']);

Route::get('check-client-wallet-balance',[adminPackageController::class,'check_client_wallet_balance']);
Route::get('buy-new-package',[adminPackageController::class,'buy_new_package']);


Route::get('get-client-package-details/{client_id}',[clientDetailsController::class,'get_client_package_details']);
Route::get('get-package-count/{client_id}',[clientDetailsController::class,'get_package_count']);

Route::get('get-wallet-history/{client_id}',[clientDetailsController::class,'get_wallet_history']);

Route::post('add-new-wallet-amount',[clientDetailsController::class,'add_new_wallet_amount']);
Route::get('get-mature-client-details/{emp_id}',[clientDetailsController::class,'get_mature_client_details']);
Route::get('mature-client-details/{client_id}',[clientDetailsController::class,'mature_client_details']);

Route::get('get-unlimitedbased-packages-list',[adminPackageController::class,'get_unlimitedbased_packages_list']);
Route::get('get-pre-package-list/{product_id}/{city_id}/{service_id}',[adminPackageController::class,'get_pre_package_list']);

Route::get('get-sale-package-details/{pre_package_id}',[clientDetailsController::class,'get_sale_package_details']);
Route::get('get-tax-and-reg-details/{product_id}',[clientDetailsController::class,'get_tax_and_reg_details']);

Route::get('create-client-package',[clientDetailsController::class,'create_client_package']);
Route::get('check-service-base-price-details/{product_id}/{service_id}/{category_id}',[adminPackageController::class,'check_service_base_price_details']);

Route::get('check-service-base-price-details-update/{product_id}/{service_id}/{category_id}/{package_id}',[adminPackageController::class,'check_service_base_price_details_update']);

Route::get('check-client-pancard-number/{pancard_no}/{client_id}',[clientDetailsController::class,'check_client_pancard_number']);

Route::get('check-company-pancard-number/{pancard_no}',[clientDetailsController::class,'check_company_pancard_number']);

Route::get('get-wallet-details/{client_id}/{clientType}',[clientDetailsController::class,'get_wallet_details']);

Route::get('get-client-company-details/{client_id}',[clientDetailsController::class,'get_client_company_details']);

Route::get('check-product-details/{productName}',[adminProductController::class,'check_product_details']);
Route::get('check-service-details/{product_id}/{service_name}',[adminProductController::class,'check_service_details']);

Route::get('check-category-details/{product_id}/{service_name}/{category_name}',[adminProductController::class,'check_category_details']);

Route::get('check-package-category-details/{product_id}/{package_category_name}',[adminProductController::class,'check_package_category_details']);

Route::get('check-package-category-type-details/{product_id}/{package_type_name}',[adminProductController::class,'check_package_category_type_details']);

Route::get('package-status-change/{id}',[adminProductController::class,'package_status_change']);

Route::get('view-leadbased-package-details-by-id/{id}',[adminPackageController::class,'view_leadbased_package_details_by_id']);

Route::post('update-lead-package-factor',[adminPackageController::class,'update_lead_package_factor']);

Route::get('check-leadbased-location-price/{product_id}/{service_id}/{group_id}',[adminProductController::class,'check_leadbased_location_price']);

Route::get('check-leadbased-location-price-by-id/{product_id}/{service_id}/{group_id}/{id}',[adminProductController::class,'check_leadbased_location_price_by_id']);

Route::get('get-leadbased-location-factor',[adminProductController::class,'get_leadbased_location_factor']);
Route::get('leadbase-factor-package-status-change/{id}',[adminProductController::class,'leadbase_factor_package_status_change']);

Route::get('view-leadbased-factor-details-by-id/{id}',[adminPackageController::class,'view_leadbased_factor_details_by_id']);
Route::post('update-lead-location-price',[adminPackageController::class,'update_lead_location_price']);

Route::get('check-unlimited-service-base-price-details/{product_id}/{service_id}/{category_id}',[adminPackageController::class,'check_unlimited_service_base_price_details']);

Route::get('check-unlimited-service-base-price-details-update/{product_id}/{service_id}/{category_id}/{package_id}',[adminPackageController::class,'check_unlimited_service_base_price_details_update']);

Route::get('unlimited-package-status-change/{id}',[adminProductController::class,'unlimited_package_status_change']);

Route::get('view-unlimited-package-details-by-id/{id}',[adminPackageController::class,'view_unlimited_package_details_by_id']);

Route::post('update-unlimited-package-factor',[adminPackageController::class,'update_unlimited_package_factor']);

Route::get('check-unlimited-location-price/{product_id}/{service_id}/{group_id}',[adminProductController::class,'check_unlimited_location_price']);

Route::get('check-unlimited-location-price-by-id/{product_id}/{service_id}/{group_id}/{id}',[adminProductController::class,'check_unlimited_location_price_by_id']);

Route::get('get-unlimited-location-factor',[adminProductController::class,'get_unlimited_location_factor']);

Route::get('unlimited-factor-package-status-change/{id}',[adminProductController::class,'unlimited_factor_package_status_change']);

Route::get('view-unlimited-factor-details-by-id/{id}',[adminPackageController::class,'view_unlimited_factor_details_by_id']);

Route::post('update-unlimited-location-price',[adminPackageController::class,'update_unlimited_location_price']);

Route::get('renew-current-package/{id}',[clientDetailsController::class,'renew_current_package']);

Route::get('get-tax-and-service-charges/{id}',[clientDetailsController::class,'get_tax_and_service_charges']);



Route::get('package-due-payment',[adminPackageController::class,'package_due_payment']);
Route::post('uplode-client-document',[clientDetailsController::class,'uplode_client_document']);

Route::get('get-client-uploded-document/{client_id}',[clientDetailsController::class,'get_client_uploded_document']);
Route::get('get-new-kyc-details',[clientDetailsController::class,'get_new_kyc_details']);
Route::get('get-client-kyc-details/{id}',[clientDetailsController::class,'get_client_kyc_details']);
Route::get('approve-client-document',[clientDetailsController::class,'approve_client_document']);
Route::get('get-client-package-details-by-id/{package_id}',[clientDetailsController::class,'get_client_package_details_by_id']);
Route::get('get-company-compititor-details/{package_id}',[clientDetailsController::class,'get_company_compititor_details']);

//20-06-2024
Route::get('check-attribute-details',[adminPackageController::class,'check_attribute_details']);
Route::post('add-attribute-details',[adminPackageController::class,'add_attribute_details']);
Route::get('get-category-list-by-id/{product_id}/{service_id}',[adminPackageController::class,'get_category_list_by_id']);

Route::get('get-attribute-list-by-id/{productId}/{serviceId}/{categoryId}',[adminPackageController::class,'get_attribute_list_by_id']);

Route::post('add-attribute-price',[adminPackageController::class,'add_attribute_price']);

Route::get('get-attribute-list',[adminPackageController::class,'get_attribute_list']);
Route::get('get-attribute-price-details',[adminPackageController::class,'get_attribute_price_details']);
Route::post('update-package-area',[clientDetailsController::class,'update_package_area']);
Route::post('package-active-deactive',[clientDetailsController::class,'package_active_deactive']);
Route::get('get-package-enable-disable-history/{pkgId}',[clientDetailsController::class,'get_package_enable_disable_history']);

Route::post('add-new-company-details',[clientDetailsController::class,'add_new_company_details']);
Route::post('uplode-company-document',[clientDetailsController::class,'uplode_company_document']);
Route::get('get-company-uploded-document/{client_id}',[clientDetailsController::class,'get_company_uploded_document']);

Route::get('get-compititor-details/{mobileNo}',[clientDetailsController::class,'get_compititor_details']);
Route::get('set-compititor/{comp_id}/{packageId}/{compId}/{pkgId}',[clientDetailsController::class,'set_compititor']);
Route::get('remove-compititor/{comp_id}/{packageId}/{compId}',[clientDetailsController::class,'remove_compititor']);
Route::get('get-to-location-details/{pkg_id}',[clientDetailsController::class,'get_to_location_details']);

Route::post('update-package-to-location-area',[clientDetailsController::class,'update_package_to_location_area']);
Route::get('get-verified-client-details',[clientDetailsController::class,'get_verified_client_details']);
Route::get('get-active-client-details',[clientDetailsController::class,'get_active_client_details']);

Route::get('get-inactive-client-details',[clientDetailsController::class,'get_inactive_client_details']);

Route::get('get-expire-client-details',[clientDetailsController::class,'get_expire_client_details']);

Route::get('get-left-client-details/{emp_id}',[clientDetailsController::class,'get_left_client_details']);

Route::get('get-left-followup-client-details',[clientDetailsController::class,'get_left_followup_client_details']);
Route::get('get-executive-details/{emp_id}',[clientDetailsController::class,'get_executive_details']);

Route::get('get-package-category',[clientDetailsController::class,'get_package_category']);
Route::get('get-pre-package-by-category',[clientDetailsController::class,'get_pre_package_by_category']);
Route::get('get-lmart-pre-packages-list',[adminPackageController::class,'get_lmart_pre_packages_list']);

Route::get('update-category-status/{id}/{status}',[adminPackageController::class,'update_category_status']);
Route::get('update-attribute-status/{id}/{status}',[adminPackageController::class,'update_attribute_status']);
Route::get('update-package-category-status/{id}/{status}',[adminPackageController::class,'update_package_category_status']);

Route::get('update-pre-package-status/{id}/{status}',[adminPackageController::class,'update_pre_package_status']);
Route::get('get-package-leads-details/{pkg_id}',[clientDetailsController::class,'get_package_leads_details']);

// Customer Support Api Start here

Route::get('get-not-sent-enquiry',[csDetailsController::class,'get_not_sent_enquiry']);
Route::get('get-otp-verified-not-sent-enq',[csDetailsController::class,'get_otp_verified_not_sent_enq']);
Route::get('get-overnight-verified-enq',[csDetailsController::class,'get_overnight_verified_enq']);
Route::get('get-overnight-not-verified-enq',[csDetailsController::class,'get_overnight_not_verified_enq']);
//Route::get('get-overnight-not-verified-enq',[csDetailsController::class,'get_overnight_not_verified_enq']);
Route::get('lmart-toll-free',[csDetailsController::class,'lmart_toll_free']);
Route::post('update-lead-info-details',[csDetailsController::class,'update_lead_info_details']);
Route::get('get-customer-history',[csDetailsController::class,'get_customer_history']);
Route::get('get-social-enquiry',[csDetailsController::class,'get_social_enquiry']);
Route::post('update-social-enq-status',[csDetailsController::class,'update_social_enq_status']);
Route::get('get-sent-enquiry',[csDetailsController::class,'get_sent_enquiry']);
Route::get('get-item-details',[csDetailsController::class,'get_item_details']);

Route::get('update-feedback-comment-client-wise',[csDetailsController::class,'update_feedback_comment_client_wise']);

Route::get('get-enquiry-status',[csDetailsController::class,'get_enquiry_status']);

Route::get('get-client-details-by-enqid',[csDetailsController::class,'get_client_details_by_enqid']);
Route::post('save-enq-feedback-by-cs',[csDetailsController::class,'save_enq_feedback_by_cs']);

Route::get('get-enq-followup-history-details-by-enqid',[csDetailsController::class,'get_enq_followup_history_details_by_enqid']);

Route::get('get-return-lead',[csDetailsController::class,'get_return_lead']);
Route::get('get-return-lead-by-return-id',[csDetailsController::class,'get_return_lead_by_return_id']);
Route::get('get-return-lead-status',[csDetailsController::class,'get_return_lead_status']);
Route::post('update-return-lead-status',[csDetailsController::class,'update_return_lead_status']);


//Finance Route
Route::get('get-recived-amount',[financeController::class,'get_recived_amount']);
Route::get('get-recived-amount-by-id/{payment_id}',[financeController::class,'get_recived_amount_by_id']);
Route::get('new-payment-amount',[financeController::class,'new_payment_amount']);
Route::get('get-approved-amount-reports',[financeController::class,'get_approved_amount_reports']);
Route::get('get-payment-history-reports',[financeController::class,'get_payment_history_reports']);
Route::get('get-ledger-details-reports',[financeController::class,'get_ledger_details_reports']);

//dialer code




// Route::post('zoopgo-toll-free-incoming-add',[tataDialer::class,'AddzoopgoTollFreeResponse']);
// Route::post('zoopgo-toll-free-incoming-update',[tataDialer::class,'UpdatezoopgoTollFreeResponse']);
// Route::post('click-2-call-api',[tataDialer::class,'click2callTata']);
// Route::post('all-call-recordi',[tataDialer::class,'allCallData']);
// Route::post('live-call-hangup',[tataDialer::class,'liveCallHangup']);


//offer type

Route::post('add-offer-type',[offersController::class,'add_offer_type']);
Route::post('update-offer-type',[offersController::class,'update_offer_type']);
Route::post('update-offer-type-status/{id}', [offersController::class, 'update_type_status']);


Route::get('get-offer-type',[offersController::class,'get_offer_type']);
Route::post('add-offer-category',[offersController::class,'add_offer_category']);
Route::post('update-offer-category',[offersController::class,'update_offer_category']);
Route::post('update-offer-category-status/{id}', [offersController::class, 'update_offer_category_status']);
Route::get('get-offer-category-details', [offersController::class, 'get_offer_category_details']);

Route::get('get-offer-type-details',[offersController::class,'get_offer_type_details']);

Route::get('get-offer-category-by-offer-id/{offerId}', [offersController::class, 'get_offer_category_by_offer_id']);
Route::post('add-offer-details', [offersController::class, 'add_offer_details']);
Route::get('get-pre-package-details', [offersController::class, 'get_pre_package_details']);

Route::get('get-offer-details', [offersController::class, 'get_offer_details']);
Route::get('get-offer-discount-details', [offersController::class,'get_offer_discount_details']);


Route::get('get-source-from',[adminProductController::class,'get_source_from']);
Route::get('get-enq-status',[adminProductController::class,'get_enq_status']);
Route::get('get-verify-enq-status',[csDetailsController::class,'get_verify_enq_status']);
Route::post('add-enquiry',[csDetailsController::class,'add_enquiry']);
Route::get('get-enquiry-by-id/{id}',[csDetailsController::class,'get_enquiry_by_id']);
Route::post('add-business-enquiry',[csDetailsController::class,'add_business_enquiry']);

Route::get('get-client-feedback-details',[clientDetailsController::class,'get_client_feedback_details']);

// reports 
Route::get('get-cs-exe-wise-reports',[Reports::class,'get_cs_exe_wise_reports']);

Route::get('get-client-reports-downlode',[Reports::class,'get_client_reports_downlode']);
Route::get('get-enquiry-reports',[Reports::class,'get_enquiry_reports']);
Route::get('get-download-reports',[Reports::class,'get_download_reports']);
Route::get('sent-purposal-to-client',[clientDetailsController::class,'sent_purposal_to_client']);
Route::get('get-guest-client-details',[clientDetailsController::class,'get_guest_client_details']);
Route::post('update-guest-client-details',[clientDetailsController::class,'update_guest_client_details']);
Route::get('get-client-followup-status/{followupStatus}',[clientDetailsController::class,'get_client_followup_status']);
Route::get('get-package-offer-details',[clientDetailsController::class,'get_package_offer_details']);
Route::get('check-company-package-info',[clientDetailsController::class,'check_company_package_info']);
Route::get('check-offer-details',[adminPackageController::class,'check_offer_details']);
Route::get('check-duplicate-utr/{utrNo}',[clientDetailsController::class,'check_duplicate_utr']);
Route::get('check-duplicate-transaction/{trnId}',[clientDetailsController::class,'check_duplicate_transaction']);
Route::get('get-state-list',[clientDetailsController::class,'get_state_list']);
Route::get('get-city-by-state-name/{cityName}',[clientDetailsController::class,'get_city_by_state_name']);
Route::get('get-client-package-lead-details',[csDetailsController::class,'get_client_package_lead_details']);
Route::get('check-last-followup-details',[clientDetailsController::class,'check_last_followup_details']);
Route::get('update-admin-package-status',[clientDetailsController::class,'update_admin_package_status']);
Route::get('get-request-service-details',[clientDetailsController::class,'get_request_service_details']);
Route::get('get-raise-request-details',[clientDetailsController::class,'get_raise_request_details']);
Route::get('get-request-category-details',[clientDetailsController::class,'get_request_category_details']);
Route::get('get-request-active-package-details',[clientDetailsController::class,'get_request_active_package_details']);
Route::post('raise-new-requeat',[clientDetailsController::class,'raise_new_requeat']);
Route::get('get-company-list',[clientDetailsController::class,'get_company_list']);
Route::get('get-request-details-by-id',[clientDetailsController::class,'get_request_details_by_id']);
Route::get('get-new-raise-request-details',[clientDetailsController::class,'get_new_raise_request_details']);
Route::post('select-company-details',[clientDetailsController::class,'select_company_detail']);

Route::get('change-package-status-by-admin',[clientDetailsController::class,'change_package_status_by_admin']);
Route::get('get-ticket-status-details',[clientDetailsController::class,'get_ticket_status_details']);
Route::get('update-request-status',[clientDetailsController::class,'update_request_status']);
Route::get('get-enquiry-details-by-enqid',[csDetailsController::class,'get_enquiry_details_by_enqid']);
Route::get('get-attempts-details-by-enqid',[csDetailsController::class,'get_attempts_details_by_enqid']);

Route::get('get-hourly-enquiry-reports',[Reports::class,'get_hourly_enquiry_reports']);
Route::get('get-hourly-enquiry-csv-reports',[Reports::class,'get_hourly_enquiry_csv_reports']);





// Admin Return Lead 
Route::get('get-return-leads',[ReturnLeadController::class,'get_return_leads']);
Route::post('approved-return-leads',[ReturnLeadController::class,'approved_return_leads']);
Route::post('get-return-package-detail', [ReturnLeadController::class, 'get_return_package_detail']);
Route::post('details-Return-lead-package', [ReturnLeadController::class, 'detail_return_lead_package']);
Route::post('submit-manual-return-package', [ReturnLeadController::class, 'submit_manual_return_package']);



////utm

Route::post('save-utm',[UtmController::class,'save_utm']);
Route::get('utm-status/{id}',[UtmController::class,'utm_status']);
Route::post('utm-status-change',[UtmController::class,'utm_status_change']);
Route::get('utm-edit/{id}',[UtmController::class,'utm_edit']);
Route::post('utm-update/{id}',[UtmController::class,'utm_update']);
Route::get('utm-list',[UtmController::class,'utm_list']);


///

Route::post('save-question',[UtmController::class,'save_question']);
Route::get('question-edit/{id}',[UtmController::class,'question_edit']);
Route::post('question-update/{id}',[UtmController::class,'question_update']);
Route::get('question-list',[UtmController::class,'question_list']);
Route::get('question-option-type/{id}',[UtmController::class,'question_option_type']);
Route::get('option-edit/{id}',[UtmController::class,'option_edit']);
Route::post('option-update/{id}',[UtmController::class,'option_update']);
Route::get('option-status/{id}',[UtmController::class,'option_status']);
Route::post('option-status-change',[UtmController::class,'option_status_change']);


//enq_status

Route::post('add-enq-followup',[UtmController::class,'add_enq_followup']);
Route::get('edit-enq-followup/{id}',[UtmController::class,'enq_followup_status_edit']);
Route::post('update-enq-followup/{id}',[UtmController::class,'enq_followup_status_update']);
Route::get('enq-followup-list',[UtmController::class,'enq_followup_list']);
Route::get('enq-followup-status/{id}',[UtmController::class,'enq_followup_status']);
Route::post('enq-followup-status-change',[UtmController::class,'enq_followup_status_change']);
Route::get('get-question-details/{id}',[UtmController::class,'get_question_details']);
Route::post('add-question-option',[UtmController::class,'add_question_option']);

//reporting 

Route::get('team-member-leaves/{id}',[TeamRecordController::class,'team_member_leave']);
Route::get('team-member-wfh/{id}',[TeamRecordController::class,'team_member_wfh']);
Route::get('team-member-stock/{id}',[TeamRecordController::class,'team_member_stock']);
Route::get('team-member-target/{id}',[TeamRecordController::class,'team_member_target']);
//points-attribute

Route::post('add-points-attribute',[AttributeController::class,'add_points_attribute']);
Route::get('point-attribute-list',[AttributeController::class,'points_attribute_list']);
Route::get('points-attribute-edit/{id}',[AttributeController::class,'points_attribute_edit']);
Route::get('points-attribute-status/{id}',[AttributeController::class,'points_attribute_status']);
Route::post('points-attribute-status-update',[AttributeController::class,'points_attribute_status_update']);
Route::get('subservice/{id}',[AttributeController::class,'get_subservice']);
Route::post('points-attribute-update/{id}',[AttributeController::class,'points_attribute_update']);

//points-subattribute
Route::post('add-points-subattribute',[AttributeController::class,'add_points_subattribute']);
Route::get('point-subattribute-list',[AttributeController::class,'points_subattribute_list']);
Route::get('points-subattribute-edit/{id}',[AttributeController::class,'points_subattribute_edit']);
Route::get('points-subattribute-status/{id}',[AttributeController::class,'points_subattribute_status']);
Route::post('points-subattribute-status-update',[AttributeController::class,'points_subattribute_status_update']);
Route::get('points-active-attribute',[AttributeController::class,'points_active_attribute']);
Route::post('points-subattribute-update/{id}',[AttributeController::class,'points_subattribute_update']);
Route::get('get-unlimitedbased-packages-list',[adminPackageController::class,'get_unlimitedbased_packages_list']);
Route::get('get-managers-details',[EmpBasicinfoController::class,'get_managers_details']);


///sales funnel

Route::get('sales-funnel',[SalesFunnelController::class,'sales_funnel_details']);
Route::get('stop-packages-report',[SalesFunnelController::class,'stop_packages_report']);
Route::get('lead-not-sent-more-then-two-days',[SalesFunnelController::class,'lead_not_sent_more_then_two_days']);
Route::get('get-followup-status-details',[SalesFunnelController::class,'get_followup_status_details']);
Route::get('view-sales-funnel-details/{emp_id}/{product_id}/{start}/{end}',[SalesFunnelController::class,'view_sales_funnel_details']);
Route::get('view-sales-funnel-team-details/{emp_id}/{product_id}/{start}/{end}',[SalesFunnelController::class,'view_sales_funnel_team_details']);
Route::get('get-team-member-details',[SalesFunnelController::class,'get_team_member_details']);
Route::get('sales-funnel-manager-emp/{id}',[SalesFunnelController::class,'sales_funnel_manager_emp']);
Route::get('get-sales-followup-data',[SalesFunnelController::class,'get_sales_followup_data']);
Route::get('show-year-in-dropdown/{year}',[LeaveController::class,'show_months']);
Route::get('show-emp-history/{id}',[EmpBasicinfoController::class,'show_emp_history']);
Route::get('team-data-count/{id}',[TeamRecordController::class,'team_data_count']);
Route::get('asset-repair-list',[StockController::class,'asset_repair_list']);
Route::post('save-repair-asset',[StockController::class,'save_repair_asset']);
Route::get('edit-repair-asset/{id}',[StockController::class,'edit_repair_asset']);
Route::post('update-repair-asset/{id}',[StockController::class,'update_repair_asset']);
Route::get('get-asset-repair-report',[StockController::class,'get_asset_repair_report']);
Route::get('count-sales-data',[SalesFunnelController::class,'count_sales_data']);
Route::get('get-sales-team-details',[SalesFunnelController::class,'get_sales_team_details']);
Route::get('get-missing-followup/{dept_id}/{emp_id}',[SalesFunnelController::class,'get_missing_followup']);
Route::get('show-emp-notification/{emp_id}',[NotificationController::class,'show_emp_notification']);
Route::get('get-sales-managers',[SalesFunnelController::class,'show_sales_managers']);
Route::get('sales-dashboard-data',[SalesFunnelController::class,'sales_dashboard_data']);
Route::get('sales-inner-page-details',[SalesFunnelController::class,'sales_inner_page_description']);
Route::get('sales-upcoming-renewal-details',[SalesFunnelController::class,'sales_upcoming_renewal_details']);
Route::get('sales-dashboard-show-manager-team/{emp_id}',[SalesFunnelController::class,'sales_dashboard_show_manager_team']);
Route::post('assigned-business-lead-to-manager',[SalesFunnelController::class,'assigned_business_lead_to_manager']);

Route::get('get-monthly-payment-followups',[SalesFunnelController::class,'get_monthly_payment_followups']);
Route::get('get-group-based-on-sales-emp/{emp_id}',[SalesFunnelController::class,'get_group_based_on_sales_emp']);

Route::get('get-total-collection',[SalesFunnelController::class,'get_total_collection']);
Route::get('get-regular-client-details',[SalesFunnelController::class,'get_regular_client_details']);
Route::get('count-sales-kra-kpi',[SalesFunnelController::class,'count_sales_kra_and_kpi']);
Route::get('missing-followup',[SalesFunnelController::class,'today_missing_followup']);
Route::get('show-kra-and-kpi-sales-dashboard',[SalesFunnelController::class,'show_kra_and_kpi_sales_dashboard']);
Route::post('store-idle-time',[LoginController::class,'store_idle_time']);
Route::post('store-break-time',[LoginController::class,'store_break_time']);
Route::get('get-break-time-status/{emp_id}',[LoginController::class,'get_break_time_status']);
Route::get('get-emp-idle-time',[LoginController::class,'get_emp_idle_time']);
Route::get('get-emp-break-time/{emp_id}',[LoginController::class,'get_emp_break_time']);
Route::get('check-reporting-manager/{emp_id}',[EmpBasicinfoController::class,'check_reporting_manager']);
Route::get('zero-days-payment',[SalesFunnelController::class,'zero_days_payment']);
Route::get('show-business-lead-data/{emp_id}',[SalesFunnelController::class,'show_business_lead_data']);
Route::post('update-business-lead-data',[SalesFunnelController::class,'update_business_lead_data']);
Route::post('update-guest-client-data',[SalesFunnelController::class,'update_business_lead_followup_data']);
Route::get('get-business-lead-status/{id}',[SalesFunnelController::class,'get_business_lead_status']);
Route::get('count-sales-followup',[SalesFunnelController::class,'count_sales_followup']);
Route::get('due-amount',[SalesFunnelController::class,'due_amount_details']);
Route::get('send-sales-notification/{emp_id}',[SalesFunnelController::class,'send_sales_notification']);
Route::get('missing-kra-kpi-history',[SalesFunnelController::class,'missing_kra_kpi_history']);
Route::get('assigned-member-on-business-lead/{id}',[SalesFunnelController::class,'assigned_member_on_business_lead']);
Route::get('update-missing-followup-seen-status/{emp_id}',[SalesFunnelController::class,'update_missing_followup_seen_status']);
Route::get('get-organization-type',[SalesDocumentController::class,'get_organization_type']);
Route::get('calciulate-sent-percent-lmart-on-sales-dashboard/{emp_id}',[SalesFunnelController::class,'calciulate_sent_percent_lmart_on_sales_dashboard']);
Route::get('show-enquiry-data-on-sales-dashboard',[SalesFunnelController::class,'show_enquiry_data_on_sales_dashboard']);
Route::get('get-sales-forcast-escalation/{emp_id}',[SalesFunnelController::class,'get_sales_forcast_escalation']);

Route::get('save-enquiry-history',[SalesFunnelController::class,'save_enquiry_history']);
Route::get('get-enquiry-history-list',[SalesFunnelController::class,'get_enquiry_history_list']);
Route::get('get-enquiry-description',[SalesFunnelController::class,'get_enquiry_description']);
Route::post('add-remark-on-enquiry',[SalesFunnelController::class,'add_remark_on_enquiry']);
Route::get('get-enquiry-history-details',[SalesFunnelController::class,'get_enquiry_history_details']);
Route::get('get-followups-details',[SalesFunnelController::class,'get_followups_details']);
Route::get('carry-forward-followups',[SalesFunnelController::class,'carry_forward_followups']);
Route::get('get-active-clients-details',[SalesFunnelController::class,'get_active_clients_details']);
Route::get('get-active-package-list',[SalesFunnelController::class,'get_active_packages_list']);
Route::get('group-loss',[SalesFunnelController::class,'group_loss']);
Route::get('category-loss',[SalesFunnelController::class,'category_loss']);
Route::get('lead-sale',[SalesFunnelController::class,'lead_sale']);
Route::get('lead-sale-price',[SalesFunnelController::class,'lead_sale_price']);
Route::post('add-remark-on-followups',[SalesFunnelController::class,'add_remark_on_followup_history']);
Route::get('kra-kpi-seen-status/{emp_id}',[SalesFunnelController::class,'kra_kpi_seen_status']);
Route::get('show-manager-wise-payment-followup',[SalesFunnelController::class,'show_manager_wise_followup']);
Route::get('show-team-wise-payment-followups',[SalesFunnelController::class,'show_team_wise_payments_followups']);
Route::get('package-inactive-notifications',[SalesFunnelController::class,'package_inactive_notifications']);
Route::get('get-inactive-package-details',[SalesFunnelController::class,'show_inactive_packages_details']);
Route::get('update-inactive-client-notification-seen-status/{emp_id}',[SalesFunnelController::class,'update_inactive_client_notification_seen_status']);
Route::get('seen-status-of-payment-approval',[SalesFunnelController::class,'seen_status_of_payment_approval']);
Route::get('recent-inactive-packages',[SalesFunnelController::class,'recent_inactive_packages']);
Route::get('mature-followups',[SalesFunnelController::class,'get_mature_followups']);
Route::get('sales-upcoming-renewal-data',[SalesFunnelController::class,'sales_upcoming_renewal_data']);
Route::get('lead-sale-price-sum',[SalesFunnelController::class,'lead_sale_price_sum']);
Route::get('sales-missing-followup',[SalesFunnelController::class,'sales_missing_followup']);
Route::get('followupus-details',[SalesFunnelController::class,'followupus_details']);
Route::get('buffer-amount-details',[SalesFunnelController::class,'buffer_amount_details']);
Route::get('team-kra-kpi-details',[SalesFunnelController::class,'team_kra_kpis']);
Route::get('count-active-packages-with-category',[SalesFunnelController::class,'count_active_packages_with_category_details']);
Route::get('get-group-category-packages-count',[SalesFunnelController::class,'get_group_category_packages_count']);

//dm-dashboard
Route::get('dm-dashboard-count',[DmDashboardController::class,'dm_dashboard_count']);
Route::get('dm-dashboard-enquiry-data',[DmDashboardController::class,'dm_dashboard_enquiry_inner_page']);
Route::get('source-type-list',[DmDashboardController::class,'source_type_list']);
Route::get('package-over-running',[DmDashboardController::class,'package_over_running']);
Route::get('lead-feedback',[DmDashboardController::class,'lead_feedback']);
Route::get('get-sent-percent-calculation-lmart/{id}/{dashboard_type}',[DmDashboardController::class,'get_sent_percent_calculation_lmart']);
Route::get('dm-department-employees',[DmDashboardController::class,'dm_department_employees']);
Route::get('lead-not-sent-due-to-quality',[DmDashboardController::class,'lead_not_sent_due_to_quality']);
Route::get('renewal-gone-due-to-lead',[DmDashboardController::class,'get_renewal_gone_due_to_lead_data']);


//cs-dashboard
Route::get('cs-dashboard-count',[CsDashboardController::class,'cs_dashboard_show_count']);
Route::get('cs-employee',[CsDashboardController::class,'cs_employee']);
Route::get('lead-verification-details',[CsDashboardController::class,'get_current_day_lead_verification_details']);
Route::get('lead-verification-tat',[CsDashboardController::class,'lead_verification_tat']);
Route::get('lead-verification-status-seen-status/{emp_id}',[CsDashboardController::class,'lead_verification_status_seen_status']);
//hr-dashboard

Route::get('hr-dashboard-count',[HrDashboardController::class,'hr_dashboard_count']);
Route::get('hr-dashboard-emp-count-graph',[HrDashboardController::class,'show_emp_count_on_graph']);
Route::get('get-category-based-on-service-id/{id}',[CsDashboardController::class,'get_category_based_on_service_id']);
//Route::


///app-api
Route::post('save-otp-template',[AppApiController::class,'save_opt_template']);
Route::get('otp-template-list',[AppApiController::class,'otp_template_list']);
Route::get('edit-otp-template/{id}',[AppApiController::class,'edit_otp_template']);
Route::post('update-otp-template',[AppApiController::class,'update_otp_template']);
Route::get('get-otp-template-status/{id}',[AppApiController::class,'get_otp_template_status']);
Route::post('update-otp-template-status',[AppApiController::class,'update_otp_template_status']);
Route::post('app-version-add',[AppApiController::class,'app_version_add']);
Route::get('app-version-list',[AppApiController::class,'app_version_list']);
Route::get('edit-app-version/{id}',[AppApiController::class,'edit_app_version']);
Route::post('update-app-version',[AppApiController::class,'update_app_version']);
Route::post('add-item-type',[AppApiController::class,'add_item_type']);
Route::get('item-type-list',[AppApiController::class,'item_type_list']);
Route::get('item-type-edit/{id}',[AppApiController::class,'item_type_edit']);
Route::post('item-type-update',[AppApiController::class,'item_type_update']);
Route::post('update-item-type-status',[AppApiController::class,'update_item_type_status']);
Route::get('active-item-type-based-on-service-id/{service_id}',[AppApiController::class,'get_active_item_type']);
Route::post('save-item',[AppApiController::class,'add_item_info']);
Route::get('item-list',[AppApiController::class,'item_list']);
Route::get('item-details/{id}',[AppApiController::class,'item_details']);
Route::post('item-update',[AppApiController::class,'item_update']);
Route::post('item-status-update',[AppApiController::class,'item_status_update']);
Route::post('add-transport-charge-type',[AppApiController::class,'add_transport_charge_type']);
Route::get('transport-charges-type-list',[AppApiController::class,'transport_charges_type_list']);
Route::get('transport-charges-type-details/{id}',[AppApiController::class,'transport_charges_type_details']);
Route::post('transport-charges-type-update',[AppApiController::class,'transport_charges_type_update']);
Route::post('transport-charges-type-status-update',[AppApiController::class,'transport_charges_type_status_update']);
Route::post('add-genric-type',[AppApiController::class,'add_genric_change_type']);
Route::get('generic-type-list',[AppApiController::class,'genric_type_list']);
Route::get('generic-type-details/{id}',[AppApiController::class,'genric_type_details']);
Route::post('generic-type-update',[AppApiController::class,'generic_type_update']);
Route::post('generic-type-status-update',[AppApiController::class,'generic_type_status_update']);
Route::post('add-support-links',[AppApiController::class,'add_support_links']);
Route::get('support-links-list',[AppApiController::class,'support_links_list']);
Route::get('support-links-details/{id}',[AppApiController::class,'support_links_details']);
Route::post('support-links-status-update',[AppApiController::class,'support_links_status_update']);
Route::post('support-links-update',[AppApiController::class,'support_links_update']);
Route::post('add-locality',[AppApiController::class,'add_locality']);
Route::get('locality-list',[AppApiController::class,'locality_list']);
Route::get('get-locality-details/{id}',[AppApiController::class,'get_locality_details']);
Route::post('update-locality-details',[AppApiController::class,'update_locality_details']);
Route::post('update-locality-status',[AppApiController::class,'update_locality_status']);
Route::get('get-country-currency-type/{country_id}',[AppApiController::class,'get_country_currency_type']);
Route::post('save-base-point-info',[AppApiController::class,'save_base_point_info']);
Route::get('base-points-list',[AppApiController::class,'base_points_list']);
Route::get('get-base-points-details/{id}',[AppApiController::class,'get_base_points_details']);
Route::post('update-base-points',[AppApiController::class,'update_base_points']);
Route::post('update-base-points-status',[AppApiController::class,'update_base_points_status']);
Route::post('add-source-from',[AppApiController::class,'add_source_from']);
Route::get('get-source-from-list',[AppApiController::class,'get_source_from_list']);
Route::get('get-source-from-details/{id}',[AppApiController::class,'get_source_from_details']);
Route::post('update-source-from',[AppApiController::class,'update_source_from']);
Route::post('update-source-from-status',[AppApiController::class,'update_source_from_status']);
Route::post('add-complaint-status',[AppApiController::class,'add_complaint_status']);
Route::get('get-complaint-list',[AppApiController::class,'get_complaint_list']);
Route::get('get-complain-status-details/{id}',[AppApiController::class,'get_complain_status_details']);
Route::post('complain-status-update',[AppApiController::class,'complain_status_update']);
Route::post('complain-status-change',[AppApiController::class,'complain_status_change']);
Route::post('add-payment-gateway-info',[AppApiController::class,'add_payment_gateway_info']);
Route::get('get-payment-gateway-list',[AppApiController::class,'get_payment_gateway_list']);
Route::get('get-payment-gateway-details/{id}',[AppApiController::class,'get_payment_gateway_details']);
Route::post('update-payment-gateway',[AppApiController::class,'update_payment_gateway']);
Route::post('update-gateway-status',[AppApiController::class,'update_gateway_status']);
Route::post('update-gateway-status',[AppApiController::class,'update_gateway_status']);
Route::post('update-gateway-app-status',[AppApiController::class,'update_gateway_app_status']);
Route::get('get-active-service-list',[AppApiController::class,'get_active_service_list']);
Route::post('save-milestone-cancel-reason',[AppApiController::class,'save_milestone_cancel_reason']);
Route::get('get-milestone-cancel-reason-list',[AppApiController::class,'get_milestone_cancel_reason_list']);
Route::get('milestone-reason-details/{id}',[AppApiController::class,'milestone_reason_details']);
Route::post('update-milestone-reason-details',[AppApiController::class,'update_milestone_reason_details']);
Route::post('mileston-reason-status-update',[AppApiController::class,'mileston_reason_status_update']);
Route::post('add-app-call-reason',[AppApiController::class,'add_app_call_reason']);
Route::get('app-call-log-reason-list',[AppApiController::class,'app_call_log_reason_list']);
Route::get('app-call-log-reason-details/{id}',[AppApiController::class,'app_call_log_reason_details']);
Route::post('app-call-log-reason-update',[AppApiController::class,'app_call_log_reason_update']);
Route::post('app-call-log-reason-status-update',[AppApiController::class,'app_call_log_reason_status_update']);
Route::get('get-support-service-list',[AppApiController::class,'get_support_service_list']);
Route::post('add-support-service',[AppApiController::class,'add_support_service']);
Route::get('get-support-service-details/{id}',[AppApiController::class,'get_support_service_details']);
Route::post('update-support-service',[AppApiController::class,'update_support_service']);
Route::post('update-support-service-status',[AppApiController::class,'update_support_service_status']);
Route::get('get-active-support-service',[AppApiController::class,'get_active_support_service']);
Route::get('sales-partcular-member-target',[CompanyTargetController::class,'sales_partcular_member_target']);

///superadmin-dashboard

Route::get('show-data-on-superadmin-dashboard',[SalesFunnelController::class,'show_data_on_superadmin_dashboard']);
Route::get('get-category-list-by-product-id/{id}',[SalesFunnelController::class,'get_category_list_by_product_id']);

Route::post('add-remark-on-kra-kpis',[SalesFunnelController::class,'add_remark_on_kra_kpis']);
Route::get('get-manager-wise-collection',[SalesFunnelController::class,'get_manager_wise_collection']);
Route::get('get-team-collection-details',[SalesFunnelController::class,'view_manager_team_collection']);

 
Route::post('get-company-details',[clientDetailsController::class,'get_company_deatil']);//MAHI
});
Route::get('get-package-city-details/{group_id}',[adminPackageController::class,'get_package_city_details']);



Route::post('update-uplode-company-document',[clientDetailsController::class,'update_uplode_company_document']);
Route::post('uplode-new-company-document',[clientDetailsController::class,'uplode_new_company_document']);
Route::get('get-document-listing',[clientDetailsController::class,'get_document_listing']);
Route::get('get-bank-details-listing',[adminPackageController::class,'get_bank_details_listing']);

//mahi
Route::post('send-otp-mobile',[SalesChangesController::class,'send_otp_mobile']);
Route::post('send-otp-email',[SalesChangesController::class,'send_otp_email']);
Route::post('update-client-number-email',[SalesChangesController::class,'update_client_number_email']); 
Route::post('check-old-mobile',[SalesChangesController::class,'check_old_mobile']);
Route::post('check-new-mobile',[SalesChangesController::class,'check_new_mobile']);
//mahi
Route::get('check-new-mobile-number',[clientDetailsController::class,'check_new_mobile_number']);

Route::get('get-wallet-history-by-id',[clientDetailsController::class,'get_wallet_history_by_id']);

Route::get('check-duplicate-utr-edit',[clientDetailsController::class,'check_duplicate_utr_edit']);
Route::get('check-duplicate-transaction-edit',[clientDetailsController::class,'check_duplicate_transaction_edit']);
Route::post('edit-new-wallet-amount',[clientDetailsController::class,'edit_new_wallet_amount']);


// Route::get('get-client-followup-status/{followupStatus}',[clientDetailsController::class,'get_client_followup_status']);

Route::get('check-last-guest-followup-details',[clientDetailsController::class,'check_last_guest_followup_details']);
Route::get('get-tempo-package-redius',[clientDetailsController::class,'get_tempo_package_redius']);

    // API For Communication starts here....
    //    Route::middleware([EnsureAPITokenIsValid::class])->group(function () {
    Route::post('communications/send-email',[CommunicationApis::class,'send_email']);
    Route::post('communications/send-sms',[CommunicationApis::class,'send_sms']);
    Route::post('communications/send-whatsapp',[CommunicationApis::class,'send_whatsapp']);
    // Google Keys..
    Route::post('third-party/google-key',[GoogleKey::class,'google_key']);
    // Dialer
    Route::post('dialer/make-a-call',[DialerApis::class,'make_a_call']);
    Route::post('dialer/drop-a-call',[DialerApis::class,'drop_a_call']);
    Route::post('dialer/live-call-track',[DialerApis::class,'live_call_track']);
    Route::post('dialer/live-call-track-using-agent-number',[DialerApis::class,'live_call_track_using_agent_number']);
    Route::post('dialer/call-detail',[DialerApis::class,'call_detail']);
    Route::post('dialer/agent-details',[DialerApis::class,'agent_details']);
    // Create Virtual Account
    Route::post('wallet/create-virtual-account',[ClientWalletController::class,'create_virtual_account']);
    Route::post('third-party/create-enquiry',[EnquiryController::class,'create_enquiry']);
    Route::post('third-party/update-enquiry-send-status',[EnquiryController::class,'update_enquiry_send_status']);
    Route::get('get-google-access-token',[CommunicationApis::class,'getGoogleAccessTokenLmartVendorApp']);
    Route::get('get-google-access-token-zoopgo',[CommunicationApis::class,'getGoogleAccessTokenZoopgoVendorApp']);
    

    
//for invoice genrate by app
Route::post('genrate-new-invoice-app',[ClientInvoiceController::class,'genrate_new_invoice_app']);
Route::get('send-lead',[LeadSendController::class,'leadSend']);
Route::get('create-hourly-enq-report',[Reports::class,'create_hourly_enq_report']);

Route::post('lmart-toll-free-incoming-add',[tataDialer::class,'AddlmartTollFreeResponse']);
Route::post('lmart-toll-free-incoming-update',[tataDialer::class,'UpdatelmartTollFreeResponse']);
Route::post('AutoC2CResponce',[tataDialer::class,'AutoC2CResponce']);



//set today lead send zero... 
Route::get('today-lead-set-zero',[CronSendController::class,'todayleadsetzero']);
Route::get('unlimited-stop-packages',[CronSendController::class,'unlimitedstoppackages']);
Route::get('send-overnight-enquiries',[CronSendController::class,'send11to6']);
Route::get('check-company-status',[CronSendController::class,'checkCompanyStatus']);

// zapier... links..
Route::post('lmart-facebook-social-enquiry',[ZapierController::class,'facebook_enquiry']);

Route::post('save-target-type',[CompanyTargetController::class,'save_target_type']);
Route::get('target-type-list',[CompanyTargetController::class,'target_type_list']);
Route::get('edit-target-type/{id}',[CompanyTargetController::class,'edit_target_type']);
Route::post('update-target-type/{id}',[CompanyTargetController::class,'update_target_type']);
Route::get('target-type-status/{id}',[CompanyTargetController::class,'target_type_status']);
Route::post('target-type-status-update',[CompanyTargetController::class,'target_type_status_update']);
Route::get('active-target-type-list',[CompanyTargetController::class,'active_target_type_list']);
Route::get('customer-target-list',[CompanyTargetController::class,'customer_target_list']);
Route::get('assign-target-no-counts',[CompanyTargetController::class,'assign_target_no_counts']);
Route::get('no-of-remaining-lead-to-assign',[CompanyTargetController::class,'no_of_remaining_lead_to_assign']);
Route::get('category-based-on-group-id-with-total-assign-data',[CompanyTargetController::class,
'category_based_on_group_id_with_total_assign_data']);
Route::get('get-category-based-on-product-id/{product_id}',[CompanyTargetController::class,'get_category_based_on_product_id']);


