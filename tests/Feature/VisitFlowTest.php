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
        $user = User::factory()->create();
        $patient = Patient::create([
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
        $user = User::factory()->create();
        $patient = Patient::create([
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
            'height' => 175,
        ]);

        $response->assertRedirect(route('patients.show', $patient));
        $this->assertDatabaseHas('vital_signs', [
            'visit_id' => $visit->id,
            'systolic_bp' => 120,
        ]);
    }
}
