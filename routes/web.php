<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\VisitController;
use App\Http\Controllers\VitalSignController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\LabRequestController;
use App\Http\Controllers\LabResultController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\MedicineController;
use App\Http\Controllers\MedicalRecordController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth'])->group(function () {
    
    // Dashboard (All Authenticated Users)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Admin & Staff Management
    Route::middleware('role:admin')->group(function () {
        Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
        Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
        Route::put('/staff/{user}', [StaffController::class, 'update'])->name('staff.update');
        Route::delete('/staff/{user}', [StaffController::class, 'destroy'])->name('staff.destroy');
    });

    // Receptionist Actions (Patients, Visits, Medical Records)
    Route::middleware('role:admin,reception,doctor,nurse')->group(function () {
        Route::get('/patients', [PatientController::class, 'index'])->name('patients.index');
        Route::get('/patients/create', [PatientController::class, 'create'])->name('patients.create');
        Route::post('/patients', [PatientController::class, 'store'])->name('patients.store');
        Route::get('/patients/{patient}', [PatientController::class, 'show'])->name('patients.show');
        Route::get('/patients/{patient}/edit', [PatientController::class, 'edit'])->name('patients.edit');
        Route::put('/patients/{patient}', [PatientController::class, 'update'])->name('patients.update');
        Route::delete('/patients/{patient}', [PatientController::class, 'destroy'])->name('patients.destroy');

        Route::post('/patients/{patient}/visits', [VisitController::class, 'store'])->name('visits.store');
        Route::get('/visits/{visit}', [VisitController::class, 'show'])->name('visits.show');

        Route::get('/medical-records', [MedicalRecordController::class, 'index'])->name('medical-records.index');
    });

    // Nurse Actions (Vitals)
    Route::middleware('role:admin,nurse')->group(function () {
        Route::post('/visits/{visit}/vitals', [VitalSignController::class, 'store'])->name('vitals.store');
    });

    // Doctor Actions (Consultations, Final Diagnosis, Lab Orders, Prescriptions)
    Route::middleware('role:admin,doctor')->group(function () {
        Route::post('/visits/{visit}/consultations', [ConsultationController::class, 'store'])->name('consultations.store');
        Route::post('/visits/{visit}/final-diagnosis', [ConsultationController::class, 'storeFinalDiagnosis'])->name('consultations.final_dx');
        Route::post('/visits/{visit}/lab-requests', [LabRequestController::class, 'store'])->name('lab_requests.store');
        Route::post('/visits/{visit}/prescriptions', [PrescriptionController::class, 'store'])->name('prescriptions.store');
    });

    // Reception / Cashier Actions (Lab Payments)
    Route::middleware('role:admin,reception')->group(function () {
        Route::post('/lab-requests/{labRequest}/pay', [LabRequestController::class, 'pay'])->name('lab_requests.pay');
    });

    // Lab Technician Actions (Lab Results)
    Route::middleware('role:admin,lab,lab_tech')->group(function () {
        Route::post('/visits/{visit}/lab-results', [LabResultController::class, 'store'])->name('lab_results.store');
    });

    // Pharmacist & Inventory Actions (Medicine Management & Prescription Dispensing)
    Route::middleware('role:admin,pharmacy,pharmacist')->group(function () {
        Route::get('/medicines', [MedicineController::class, 'index'])->name('medicines.index');
        Route::post('/medicines', [MedicineController::class, 'store'])->name('medicines.store');
        Route::post('/medicines/quick-store', [MedicineController::class, 'quickStore'])->name('medicines.quick_store');
        Route::put('/medicines/{medicine}', [MedicineController::class, 'update'])->name('medicines.update');
        Route::delete('/medicines/{medicine}', [MedicineController::class, 'destroy'])->name('medicines.destroy');
        Route::post('/prescriptions/{prescription}/dispense', [PrescriptionController::class, 'dispense'])->name('prescriptions.dispense');
    });

    // Logout Route
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

require __DIR__.'/auth.php';