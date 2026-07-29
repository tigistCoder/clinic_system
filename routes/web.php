<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\VitalSignController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\LabRequestController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\VisitController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // Profile Routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Patient Routes
    Route::get('/patients', [PatientController::class, 'index'])->name('patients.index');
    Route::get('/patients/create', [PatientController::class, 'create'])->name('patients.create');
    Route::post('/patients', [PatientController::class, 'store'])->name('patients.store');
    Route::get('/patients/{patient}', [PatientController::class, 'show'])->name('patients.show');
    Route::get('/patients/{patient}/edit', [PatientController::class, 'edit'])->name('patients.edit');
    Route::put('/patients/{patient}', [PatientController::class, 'update'])->name('patients.update');
    Route::delete('/patients/{patient}', [PatientController::class, 'destroy'])->name('patients.destroy');

    // Visit Routes
    Route::post('/patients/{patient}/visits', [VisitController::class, 'store'])->name('visits.store');
    Route::get('/visits/{visit}', [VisitController::class, 'show'])->name('visits.show');

    // Vital Signs Routes
    Route::post('/visits/{visit}/vitals', [VitalSignController::class, 'store'])->name('vitals.store');

    // Consultation Routes
    Route::post('/visits/{visit}/consultations', [ConsultationController::class, 'store'])->name('consultations.store');

    // Laboratory Request Routes
    Route::post('/visits/{visit}/lab-requests', [LabRequestController::class, 'store'])->name('lab_requests.store');

    Route::post('/visits/{visit}/prescriptions', [PrescriptionController::class, 'store'])->name('prescriptions.store');
});

require __DIR__.'/auth.php';