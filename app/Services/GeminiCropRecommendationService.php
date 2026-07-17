<?php

namespace App\Services;

use App\Models\SoilSample;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Gemini AI Crop Recommendation Service
 *
 * Calls the Google Gemini API server-side — the API key is NEVER exposed
 * to the frontend. The browser only receives the generated text recommendation.
 */
class GeminiCropRecommendationService
{
    public function __construct(
        private readonly FertilizerService $fertilizerService
    ) {}

    /**
     * Generate Gemini AI crop recommendations for a soil sample.
     *
     * @param  SoilSample   $sample          Analyzed soil sample record
     * @param  string|null  $preferredCrop   Optional crop the farmer wants to prioritize
     * @return array{success: bool, recommendation?: string, message?: string}
     */
    public function generate(SoilSample $sample, ?string $preferredCrop = null, float $areaSize = 1.0): array
    {
        $apiKey = config('services.gemini.api_key', '');
        if (empty($apiKey)) {
            return [
                'success' => false,
                'message' => 'Gemini AI service is not configured. Set the GEMINI_API_KEY environment variable.',
            ];
        }

        $model = config('services.gemini.model', 'gemini-2.0-flash');

        $fertRec     = $this->fertilizerService->recommend(
            (float) $sample->ph_level,
            (float) $sample->nitrogen_level,
            (float) $sample->phosphorus_level,
            (float) $sample->potassium_level
        );
        $fertSummary = $this->fertilizerService->summary($fertRec);

        $phStatus = $this->fertilizerService->getNutrientStatus('ph',         (float) $sample->ph_level);
        $nStatus  = $this->fertilizerService->getNutrientStatus('nitrogen',   (float) $sample->nitrogen_level);
        $pStatus  = $this->fertilizerService->getNutrientStatus('phosphorus', (float) $sample->phosphorus_level);
        $kStatus  = $this->fertilizerService->getNutrientStatus('potassium',  (float) $sample->potassium_level);

        $targetCrop  = $preferredCrop ?? ($sample->recommended_crop ?? 'Rice');
        $location    = trim($sample->address . ($sample->location ? ', ' . $sample->location : ''));
        $displayArea = number_format($areaSize, 2) . ' hectare' . ($areaSize == 1 ? '' : 's');

        $prompt = <<<PROMPT
IMPORTANT: You must respond entirely in English. Do not use Filipino, Tagalog, or any other language.

You are a senior agronomist advising the Office of the Municipal Agriculturist (OMA) in the Philippines.
Provide practical fertilizer guidance for a smallholder farmer based on the soil diagnostic below.

### SOIL DIAGNOSTIC REPORT (OFFLINE ANALYSIS)
Farmer: {$sample->farmer_name}
Location: {$location}
Target Crop: {$targetCrop}
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

Respond with clear, numbered sections matching the 4 requests above. Focus on practical guidance for smallholder farmers. Do not repeat the raw test values. Respond in English only.
PROMPT;

        try {
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->timeout(45)->post("{$endpoint}?key={$apiKey}", [
                'contents' => [
                    [
                        'parts' => [['text' => $prompt]],
                    ],
                ],
                'generationConfig' => [
                    'temperature'     => 0,
                    'maxOutputTokens' => 8192,
                ],
            ]);

            if ($response->failed()) {
                $err = $response->json('error.message') ?? "HTTP {$response->status()}";
                Log::error("Gemini crop recommendation API error: {$err}");
                return ['success' => false, 'message' => "Gemini API error: {$err}"];
            }

            $text       = trim($response->json('candidates.0.content.parts.0.text') ?? '');
            $finishReason = $response->json('candidates.0.finishReason') ?? 'UNKNOWN';

            if (empty($text)) {
                Log::warning("Gemini returned empty text. finishReason={$finishReason}", [
                    'raw' => $response->json(),
                ]);
                return ['success' => false, 'message' => "Gemini returned no content (finishReason: {$finishReason})."];
            }

            if ($finishReason === 'MAX_TOKENS') {
                Log::warning('Gemini response was cut off (MAX_TOKENS). Increase maxOutputTokens.');
            }

            $sample->update(['gemini_crop_recommendation' => $text]);

            return ['success' => true, 'recommendation' => $text];

        } catch (\Exception $e) {
            Log::error('Gemini crop recommendation error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to connect to Gemini AI service.'];
        }
    }
}
