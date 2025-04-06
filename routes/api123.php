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
Route::get('calculate-salary-pdf/{id}',[SallaryController::class,'calculate_salary_for_pdf']);
Route::get('salary/generate-pdf/{id}/{month}', [SallaryController::class,'generatePdf']);
Route::post('login',[LoginController::class,'login'])->name('login');
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

//product

Route::post('save-product',[ProductController::class,'save_product']);
Route::get('product-list',[ProductController::class,'product_list']);
Route::post('update-product/{id}',[ProductController::class,'update_product']);
Route::get('edit-product/{id}',[ProductController::class,'product_edit']);
Route::get('product-status/{id}',[ProductController::class,'get_product_status']);
Route::post('product-change-status',[ProductController::class,'product_status']);
Route::get('company-based-product/{country_id}',[ProductController::class,'company_based_product']);

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
Route::get('create-attendance-row',[EmpBasicinfoController::class,'create_attendance_row']);




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
Route::get('logout/{id}',[LoginController::class,'logout_emp']);
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
Route::get('active-leave-type',[LeaveTypeController::class,'get_active_leavetype']);
Route::get('active-confirmation-leave',[LeaveTypeController::class,'get_active_confirmation_leave']);
Route::get('get-emp-confirmation/{id}',[LeaveTypeController::class,'get_confirmation_status']);
Route::get('show-leave-blance/{id}',[LeaveTypeController::class,'show_leave_blance']);
Route::post('emp-leave-count',[LeaveTypeController::class,'emp_leave_count']);
Route::post('save-emp-leave',[LeaveTypeController::class,'save_emp_leave']);
Route::post('get-leave-list',[LeaveTypeController::class,'get_leave_list']);
Route::get('assign-leave-to-employee/{id}',[LeaveTypeController::class,'assign_leave']);
Route::get('emp-leave-data/{id}',[LeaveTypeController::class,'emp_leave_data']);
Route::post('view-team',[TeamRecordController::class,'team_record_data']);
Route::post('team-leave-data',[TeamRecordController::class,'team_leave_data']);
Route::post('emp-leave-status',[TeamRecordController::class,'emp_leave_status']);
Route::post('change-confirmation-status',[TeamRecordController::class,'change_confirmation_status']);
Route::get('emp-leave-taken-list/{id}',[LeaveTypeController::class,'emp_leave_taken_list']);
Route::get('get-all-managers/{id}',[TeamRecordController::class,'get_all_managers']);

//wfh
Route::post('apply-wfh',[WorkFromHomeController::class,'apply_wfh']);
Route::post('count-days-wfh',[WorkFromHomeController::class,'count_days_wfh']);
Route::post('wfh-list',[WorkFromHomeController::class,'wfh_list']);
Route::post('team-wfh-list',[TeamRecordController::class,'team_wfh']);
Route::post('work-from-home-status',[TeamRecordController::class,'work_from_home_status']);
Route::get('work-from-home-attendance/{id}',[WorkFromHomeController::class,'work_from_home_attendance']);

//roster
Route::get('manager-employee/{id}',[RosterController::class,'get_manager_employee']);
Route::post('create-roster',[RosterController::class,'create_roster']);
Route::post('roster-list',[RosterController::class,'roster_list']);
Route::get('roster-attendance/{id}',[RosterController::class,'roster_wise_attendance']);
Route::post('show-emp-roster',[RosterController::class,'show_emp_roster']);
Route::post('request-to-change-roster',[RosterController::class,'request_to_change_roster']);
Route::post('roster-change-request-list',[RosterController::class,'roster_change_request']);
Route::get('roster-status/{id}',[RosterController::class,'get_roster_status']);
Route::post('roster-change-status',[RosterController::class,'roster_status']);

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



});



