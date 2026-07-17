<?php

namespace App\Services;

use App\Models\SoilSample;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiRecommendationService
{
    public function __construct(
        private readonly FertilizerService $fertilizerService
    ) {}

    public function generate(SoilSample $sample, string $topCropsStr, float $areaSize = 1.0): array
    {
        $apiKey = env('ANTHROPIC_API_KEY', '');
        if (empty($apiKey)) {
            return ['success' => false, 'message' => 'AI service is not configured. Set the ANTHROPIC_API_KEY environment variable.'];
        }

        $fertRec     = $this->fertilizerService->recommend(
            (float)$sample->ph_level,
            (float)$sample->nitrogen_level,
            (float)$sample->phosphorus_level,
            (float)$sample->potassium_level
        );
        $fertSummary = $this->fertilizerService->summary($fertRec);

        $phStatus = $this->fertilizerService->getNutrientStatus('ph',         (float)$sample->ph_level);
        $nStatus  = $this->fertilizerService->getNutrientStatus('nitrogen',   (float)$sample->nitrogen_level);
        $pStatus  = $this->fertilizerService->getNutrientStatus('phosphorus', (float)$sample->phosphorus_level);
        $kStatus  = $this->fertilizerService->getNutrientStatus('potassium',  (float)$sample->potassium_level);

        $location    = trim($sample->address . ($sample->location ? ', ' . $sample->location : ''));
        $displayArea = number_format($areaSize, 2) . ' hectare' . ($areaSize == 1 ? '' : 's');

        $prompt = <<<PROMPT
You are an expert agronomist advising Filipino farmers through the Office of the Municipal Agriculturist (OMA).
Provide practical, actionable advice based on the soil diagnostic below.
Write in clear, plain English suitable for farmers.

### SOIL DIAGNOSTIC REPORT (OFFLINE ANALYSIS)
Farmer: {$sample->farmer_name}
Location: {$location}
Target Crop: {$topCropsStr}
Field Area: {$displayArea}
------------------------------------------
### TEST STRIP RESULTS (STK)
- Soil pH: {$sample->ph_level} ({$phStatus})
- Nitrogen (N): {$nStatus}
- Phosphorus (P): {$pStatus}
- Potassium (K): {$kStatus}
------------------------------------------
### ANALYSIS REQUEST
1. Recommended fertilizer types and NPK ratios suitable for the target crop and soil status above
2. Calculation of total nutrients (kg/ha) required for 1 hectare based on the soil status and crop demand
3. Optimized application schedule (basal + top-dressing) following PhilRice / BSWM guidelines for Philippine conditions
4. Soil amendment advice if the pH level is outside the ideal range for the target crop

Respond with clear, numbered sections matching the 4 requests above. Keep the total response under 500 words. Focus on practical guidance for smallholder farmers. Do not repeat the raw test values.
PROMPT;

        try {
            $response = Http::withHeaders([
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type'      => 'application/json',
            ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
                'model'      => 'claude-haiku-4-5-20251001',
                'max_tokens' => 1024,
                'messages'   => [['role' => 'user', 'content' => $prompt]],
            ]);

            if ($response->failed()) {
                $err = $response->json('error.message') ?? "HTTP {$response->status()}";
                Log::error("AI recommendation API error: $err");
                return ['success' => false, 'message' => "AI API error: $err"];
            }

            $text = trim($response->json('content.0.text') ?? '');
            if (empty($text)) {
                return ['success' => false, 'message' => 'Empty response from AI service'];
            }

            $sample->update(['ai_recommendation' => $text]);

            return ['success' => true, 'recommendation' => $text];

        } catch (\Exception $e) {
            Log::error("AI recommendation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to connect to AI service'];
        }
    }
}
