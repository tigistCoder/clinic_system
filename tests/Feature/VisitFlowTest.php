<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_can_create_a_visit_and_redirect_to_patient_detail(): void
    {
        $user = User::factory()->create(['role' => 'reception', 'status' => 'active']);
        $patient = Patient::create([
            'card_number' => 'CARD0010',
            'full_name' => 'Test Patient',
            'age' => 30,
            'gender' => 'male',
            'phone_number' => '0911111111',
            'address' => 'Addis Ababa',
        ]);

        $response = $this->actingAs($user)->post(route('visits.store', $patient), [
            'urgency_status' => 'emergency',
        ]);

        $visit = Visit::latest()->first();

        $response->assertRedirect(route('visits.show', $visit));
        $this->assertDatabaseHas('visits', [
            'patient_id' => $patient->id,
        ]);
    }

    public function test_vital_signs_can_be_saved_for_an_existing_visit(): void
    {
        $user = User::factory()->create(['role' => 'nurse']);
        $patient = Patient::create([
            'card_number' => 'CARD0011',
            'full_name' => 'Vital Patient',
            'age' => 29,
            'gender' => 'female',
            'phone_number' => '0922222222',
            'address' => 'Bahir Dar',
        ]);
        $visit = $patient->visits()->create([]);

        $response = $this->actingAs($user)->post(route('vitals.store', $visit), [
            'systolic_bp' => 120,
            'diastolic_bp' => 80,
            'pulse_rate' => 72,
            'respiratory_rate' => 18,
            'temperature' => 36.6,
            'spo2' => 98,
            'weight' => 70,
            'height' => 1.75,
        ]);

        $response->assertRedirect(route('visits.show', $visit));
        $this->assertDatabaseHas('vital_signs', [
            'visit_id' => $visit->id,
            'systolic_bp' => 120,
        ]);
    }

    public function test_nurse_sees_only_vitals_and_patient_info_on_visit_details_page(): void
    {
        $nurse = User::factory()->create(['role' => 'nurse']);
        $patient = Patient::create([
            'card_number' => 'CARD1001',
            'full_name' => 'Nurse Patient',
            'age' => 30,
            'gender' => 'male',
            'phone_number' => '0911111111',
            'address' => 'Addis Ababa',
        ]);
        $visit = $patient->visits()->create([]);

        // 1. Verify Nurse is blocked from inventory sidebar link and route
        $sidebarResponse = $this->actingAs($nurse)->get(route('dashboard'));
        $sidebarResponse->assertStatus(200);
        $sidebarResponse->assertDontSee('Inventory / Pharmacy');
        $sidebarResponse->assertSee('የዛሬ ጉብኝቶች');
        $sidebarResponse->assertSee('የሚጠበቁ ቫይታሎች');
        $sidebarResponse->assertDontSee('አጠቃላይ ሰራተኞች');
        $sidebarResponse->assertDontSee('የላብራቶሪ ምርመራዎች');

        $inventoryResponse = $this->actingAs($nurse)->get(route('medicines.index'));
        $inventoryResponse->assertStatus(403);

        // 2. Verify Patients list & profile page restrictions for Nurse
        $patientsResponse = $this->actingAs($nurse)->get(route('patients.index'));
        $patientsResponse->assertStatus(200);
        $patientsResponse->assertDontSee('Register New Patient');
        $patientsResponse->assertSee('Nurse Patient');
        $patientsResponse->assertSee('Detail');
        $patientsResponse->assertDontSee('>Visit<', false);
        $patientsResponse->assertDontSee('>Edit<', false);
        $patientsResponse->assertDontSee('>Delete<', false);

        $storePatientResponse = $this->actingAs($nurse)->post(route('patients.store'), [
            'name' => 'Nurse Invalid Patient',
            'age' => 25,
            'gender' => 'female',
            'phone' => '0911223344',
            'address' => 'Assosa',
        ]);
        $storePatientResponse->assertStatus(403);

        $profileResponse = $this->actingAs($nurse)->get(route('patients.show', $patient));
        $profileResponse->assertStatus(200);
        $profileResponse->assertDontSee('Start new visit');

        $storeVisitResponse = $this->actingAs($nurse)->post(route('visits.store', $patient));
        $storeVisitResponse->assertStatus(403);

        // 3. Verify Visit page restrictions for Nurse
        $response = $this->actingAs($nurse)->get(route('visits.show', $visit));

        $response->assertStatus(200);
        $response->assertSee('+ Add Vitals');
        $response->assertDontSee('>+ Doctor Note<', false);
        $response->assertDontSee('>+ Order Lab<', false);
        $response->assertDontSee('+ Enter Lab Result');
        $response->assertDontSee('>+ Prescribe Medicine<', false);
        $response->assertDontSee('Doctor Consultation Findings');
        $response->assertDontSee('Laboratory Tests & Billing');
        $response->assertDontSee('Laboratory Results & Findings');
        $response->assertDontSee('Confirmed Final Diagnosis');
        $response->assertDontSee('Prescriptions');
    }

    public function test_doctor_sees_all_buttons_and_sections_on_visit_details_page(): void
    {
        $doctor = User::factory()->create(['role' => 'doctor']);
        $patient = Patient::create([
            'card_number' => 'CARD1002',
            'full_name' => 'Doctor Patient',
            'age' => 40,
            'gender' => 'female',
            'phone_number' => '0922222222',
            'address' => 'Hawassa',
        ]);
        $visit = $patient->visits()->create([]);

        // Dashboard checks for Doctor
        $dashboardResponse = $this->actingAs($doctor)->get(route('dashboard'));
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee("Today's Patients", false);
        $dashboardResponse->assertSee("Today's Pending Consultations", false);
        $dashboardResponse->assertSee("Today's Lab Results Ready", false);
        $dashboardResponse->assertDontSee('አጠቃላይ ሰራተኞች');
        $dashboardResponse->assertDontSee('የላብራቶሪ ምርመራዎች');

        // Patients list checks for Doctor (ONLY sees Detail button)
        $patientsResponse = $this->actingAs($doctor)->get(route('patients.index'));
        $patientsResponse->assertStatus(200);
        $patientsResponse->assertSee('Doctor Patient');
        $patientsResponse->assertSee('Detail');
        $patientsResponse->assertDontSee('>Visit<', false);
        $patientsResponse->assertDontSee('>Edit<', false);
        $patientsResponse->assertDontSee('>Delete<', false);

        $response = $this->actingAs($doctor)->get(route('visits.show', $visit));

        $response->assertStatus(200);
        $response->assertSee('Nurse Vital Signs');
        $response->assertDontSee('+ Add Vitals');
        $response->assertSee('+ Doctor Note');
        $response->assertSee('+ Order Lab');
        $response->assertDontSee('+ Enter Lab Result');
        $response->assertSee('+ Prescribe Medicine');
        $response->assertSee('Doctor Consultation Findings');
        $response->assertSee('Laboratory Tests & Billing', false);
        $response->assertSee('Laboratory Results & Findings', false);
        $response->assertSee('Confirmed Final Diagnosis');
        $response->assertSee('Prescriptions');
        $response->assertDontSee('Mark as Paid');
    }

    public function test_doctor_cannot_access_inventory_and_can_dispense_via_fifo(): void
    {
        $doctor = User::factory()->create(['role' => 'doctor', 'status' => 'active']);

        // 1. Verify Doctor is blocked from inventory page
        $inventoryResponse = $this->actingAs($doctor)->get(route('medicines.index'));
        $inventoryResponse->assertStatus(403);

        // 2. Verify Doctor is blocked from registering patients
        $patientStoreResponse = $this->actingAs($doctor)->post(route('patients.store'), [
            'name' => 'John Doe',
            'age' => 30,
            'gender' => 'male',
            'phone' => '0911111111',
            'address' => 'Addis Ababa',
        ]);
        $patientStoreResponse->assertStatus(403);

        // 3. Verify Doctor can dispense prescription via FIFO logic
        $patient = Patient::create([
            'card_number' => 'CARD1004',
            'full_name' => 'FIFO Patient',
            'age' => 50,
            'gender' => 'male',
            'phone_number' => '0944444444',
            'address' => 'Adama',
        ]);
        $visit = $patient->visits()->create([]);
        $medicine = \App\Models\Medicine::create([
            'name' => 'Paracetamol 500mg',
            'category' => 'Analgesic',
            'stock_quantity' => 100,
            'price_per_unit' => 5.00,
            'reorder_level' => 10,
            'expiry_date' => now()->addYear(),
        ]);
        $batchOld = $medicine->purchases()->create([
            'batch_number' => 'BATCH-OLD',
            'quantity_purchased' => 50,
            'quantity_sold' => 0,
            'remaining_quantity' => 50,
            'purchase_date' => now()->subMonths(2),
            'expiry_date' => now()->addYear(),
        ]);
        $batchNew = $medicine->purchases()->create([
            'batch_number' => 'BATCH-NEW',
            'quantity_purchased' => 50,
            'quantity_sold' => 0,
            'remaining_quantity' => 50,
            'purchase_date' => now()->subMonth(),
            'expiry_date' => now()->addYear(),
        ]);

        $prescription = $visit->prescriptions()->create([
            'medicine_id' => $medicine->id,
            'quantity' => 10,
            'dosage' => '500mg',
            'frequency' => 'Once daily',
            'duration' => '5 days',
            'status' => 'pending',
        ]);

        $dispenseResponse = $this->actingAs($doctor)->post(route('prescriptions.dispense', $prescription));
        $dispenseResponse->assertRedirect();

        $this->assertDatabaseHas('prescriptions', [
            'id' => $prescription->id,
            'status' => 'dispensed',
        ]);

        // Oldest batch should be deducted first (FIFO)
        $this->assertDatabaseHas('purchases', [
            'id' => $batchOld->id,
            'remaining_quantity' => 40,
        ]);
        $this->assertDatabaseHas('purchases', [
            'id' => $batchNew->id,
            'remaining_quantity' => 50,
        ]);
    }

    public function test_reception_sees_only_lab_billing_and_payment_action_on_visit_details_page(): void
    {
        $reception = User::factory()->create(['role' => 'reception', 'status' => 'active']);
        $patient = Patient::create([
            'card_number' => 'CARD1003',
            'full_name' => 'Reception Patient',
            'age' => 32,
            'gender' => 'male',
            'phone_number' => '0933333333',
            'address' => 'Gonder',
        ]);
        $visit = $patient->visits()->create([]);
        $visit->labRequests()->create([
            'test_name' => 'Complete Blood Count (CBC)',
            'price' => 250,
            'payment_status' => 'unpaid',
            'status' => 'pending_payment',
        ]);

        // Sidebar & Medicines route restriction for Reception
        $medicinesResponse = $this->actingAs($reception)->get(route('medicines.index'));
        $medicinesResponse->assertStatus(403);

        // Dashboard checks for Reception
        $dashboardResponse = $this->actingAs($reception)->get(route('dashboard'));
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertDontSee('Inventory / Pharmacy');
        $dashboardResponse->assertSee("Today's Visits", false);
        $dashboardResponse->assertSee('Total Patients');
        $dashboardResponse->assertSee('Pending Lab Payments');
        $dashboardResponse->assertDontSee('አጠቃላይ ሰራተኞች');
        $dashboardResponse->assertDontSee('የላብራቶሪ ምርመራዎች');

        $response = $this->actingAs($reception)->get(route('visits.show', $visit));

        $response->assertStatus(200);
        $response->assertSee('Laboratory Tests & Billing', false);
        $response->assertSee('Mark as Paid');
        $response->assertDontSee('+ Add Vitals');
        $response->assertDontSee('>+ Doctor Note<', false);
        $response->assertDontSee('>+ Order Lab<', false);
        $response->assertDontSee('+ Enter Lab Result');
        $response->assertDontSee('>+ Prescribe Medicine<', false);
        $response->assertDontSee('Nurse Vital Signs');
        $response->assertDontSee('Doctor Consultation Findings');
        $response->assertDontSee('Laboratory Results & Findings');
        $response->assertDontSee('Confirmed Final Diagnosis');
        $response->assertDontSee('Prescriptions');
    }

    public function test_lab_technician_restrictions_dashboard_and_visit_page(): void
    {
        $labTech = User::factory()->create(['role' => 'lab', 'status' => 'active']);

        // 1. Dashboard checks (Patients link visible, Inventory hidden)
        $dashboardResponse = $this->actingAs($labTech)->get(route('dashboard'));
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertDontSee('Inventory / Pharmacy');
        $dashboardResponse->assertSee('Patients</span>', false);
        $dashboardResponse->assertDontSee('አጠቃላይ ሰራተኞች');
        $dashboardResponse->assertSee('የሚጠበቁ የላብ ምርመራዎች');
        $dashboardResponse->assertSee('የተጠናቀቁ የላብ ምርመራዎች');

        // 1b. Medical Records page stat cards check for Lab Tech
        $medicalRecordsResponse = $this->actingAs($labTech)->get(route('medical-records.index'));
        $medicalRecordsResponse->assertStatus(200);
        $medicalRecordsResponse->assertSee('የሚጠበቁ የላብ ምርመራዎች');
        $medicalRecordsResponse->assertSee('የተጠናቀቁ የላብ ምርመራዎች');
        $medicalRecordsResponse->assertSee('አጠቃላይ የላብራቶሪ ምርመራዎች');

        // 2. Patients list page checks
        $patient = Patient::create([
            'card_number' => 'CARD1005',
            'full_name' => 'Lab Patient',
            'age' => 28,
            'gender' => 'female',
            'phone_number' => '0955555555',
            'address' => 'Hawassa',
        ]);

        $patientsResponse = $this->actingAs($labTech)->get(route('patients.index'));
        $patientsResponse->assertStatus(200);
        $patientsResponse->assertDontSee('Register New Patient');
        $patientsResponse->assertDontSee('plus-circle');

        // 3. Patient profile page checks
        $patientProfileResponse = $this->actingAs($labTech)->get(route('patients.show', $patient));
        $patientProfileResponse->assertStatus(200);
        $patientProfileResponse->assertDontSee('Start new visit');

        // 4. Visit details page checks
        $visit = $patient->visits()->create([]);

        $visitResponse = $this->actingAs($labTech)->get(route('visits.show', $visit));
        $visitResponse->assertStatus(200);
        // Header action buttons
        $visitResponse->assertSee('+ Enter Lab Result');
        $visitResponse->assertDontSee('+ Add Vitals');
        $visitResponse->assertDontSee('>+ Doctor Note<', false);
        $visitResponse->assertDontSee('>+ Order Lab<', false);
        $visitResponse->assertDontSee('>+ Prescribe Medicine<', false);

        // Display sections
        $visitResponse->assertSee('Laboratory Tests & Billing', false);
        $visitResponse->assertSee('Laboratory Results & Findings', false);
        $visitResponse->assertDontSee('Nurse Vital Signs');
        $visitResponse->assertDontSee('Doctor Consultation Findings');
        $visitResponse->assertDontSee('Confirmed Final Diagnosis');
        $visitResponse->assertDontSee('Prescriptions');
    }

    public function test_pharmacist_role_permissions_and_visit_page_restrictions(): void
    {
        $pharmacist = User::factory()->create(['role' => 'pharmacy', 'status' => 'active']);

        // 1. Sidebar Navigation & Dashboard cards
        $sidebarResponse = $this->actingAs($pharmacist)->get(route('dashboard'));
        $sidebarResponse->assertStatus(200);
        $sidebarResponse->assertSee('Patients</span>', false);
        $sidebarResponse->assertSee("Today's Pending Prescriptions", false);
        $sidebarResponse->assertSee("Today's Dispensed Prescriptions", false);
        $sidebarResponse->assertSee('Low Stock Medicines');
        $sidebarResponse->assertDontSee('አጠቃላይ ሰራተኞች');
        $sidebarResponse->assertDontSee('የላብራቶሪ ምርመራዎች');

        // 2. Patients list page (Register New Patient hidden, Patients list & Visit action accessible)
        $patient = Patient::create([
            'card_number' => 'CARD1006',
            'full_name' => 'Pharma Patient',
            'age' => 35,
            'gender' => 'male',
            'phone_number' => '0966666666',
            'address' => 'Assosa',
        ]);
        $visit = $patient->visits()->create([]);

        $patientsResponse = $this->actingAs($pharmacist)->get(route('patients.index'));
        $patientsResponse->assertStatus(200);
        $patientsResponse->assertDontSee('Register New Patient');
        $patientsResponse->assertSee('Pharma Patient');
        $patientsResponse->assertSee('Detail');
        $patientsResponse->assertDontSee('>Visit<', false);
        $patientsResponse->assertDontSee('>Edit<', false);
        $patientsResponse->assertDontSee('>Delete<', false);

        // Block patient registration for Pharmacist
        $storePatientResponse = $this->actingAs($pharmacist)->post(route('patients.store'), [
            'name' => 'Invalid Patient',
            'age' => 40,
            'gender' => 'male',
            'phone' => '0977777777',
            'address' => 'Assosa',
        ]);
        $storePatientResponse->assertStatus(403);

        // 3. Patient Profile Page (Start new visit button hidden & blocked)
        $profileResponse = $this->actingAs($pharmacist)->get(route('patients.show', $patient));
        $profileResponse->assertStatus(200);
        $profileResponse->assertDontSee('Start new visit');

        $storeVisitResponse = $this->actingAs($pharmacist)->post(route('visits.store', $patient));
        $storeVisitResponse->assertStatus(403);

        // 4. Visit details page (Creation buttons hidden, Dispense button accessible)
        $visitResponse = $this->actingAs($pharmacist)->get(route('visits.show', $visit));
        $visitResponse->assertStatus(200);

        // Header action buttons hidden for Pharmacist
        $visitResponse->assertDontSee('+ Add Vitals');
        $visitResponse->assertDontSee('>+ Doctor Note<', false);
        $visitResponse->assertDontSee('>+ Order Lab<', false);
        $visitResponse->assertDontSee('+ Enter Lab Result');
        $visitResponse->assertDontSee('>+ Prescribe Medicine<', false);

        // Prescriptions table shows Dispense functionality
        $visitResponse->assertSee('Prescriptions');
        $visitResponse->assertSee('Medications & fulfillment status', false);

        // Unrelated display sections are hidden for Pharmacist
        $visitResponse->assertDontSee('Nurse Vital Signs');
        $visitResponse->assertDontSee('Doctor Consultation Findings');
        $visitResponse->assertDontSee('Laboratory Tests & Billing');
        $visitResponse->assertDontSee('Laboratory Results & Findings');
        $visitResponse->assertDontSee('Confirmed Final Diagnosis');
    }
}
