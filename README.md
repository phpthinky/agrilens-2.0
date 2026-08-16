# Agrilens 2.0

A web-based soil analysis system with crop and fertilizer recommendation, built around a QR Code-Based NPK Sensor, for the Office of the Municipal Agriculturist (OMA) in Sablayan, Occidental Mindoro.

Developed as an undergraduate research project for the Bachelor of Science in Information Technology program, College of Bachelor of Science and Information Technology, Occidental Mindoro State College — Sablayan Campus (March 2025).

**Live demo:** [agrilens.capstonedemo.com/map](https://agrilens.capstonedemo.com/map) — public interactive fertility map.

## Overview

Agrilens 2.0 provides a unified platform for farm and farmer registration, farm boundary mapping, soil testing, and agricultural decision support across Sablayan's 24 barangays. Every analysis begins with a **Create Sample** step before an analysis type is chosen, and all three soil-test input modes feed the same fertility scoring, crop-matching, and fertilizer recommendation engine — so recommendations stay consistent no matter how the data was collected.

### Soil test input modes

1. **QR Code-Based NPK Sensor (primary)** — an Arduino-based RS485/Modbus digital NPK reader measures Nitrogen, Phosphorus, and Potassium and displays a scannable QR code on its TFT screen. The technician scans the code (or pastes the JSON payload) to instantly import readings — laboratory-grade precision, no chemicals, no network required. The device has no pH sensor.
2. **Colorimetric Webcam Testing (legacy)** — implements the full BSWM reagent-based colorimetric procedure. A webcam captures reagent-treated test tube colors, which are converted through CIE L\*a\*b\* color space and matched against BSWM reference charts using the CIEDE2000 (ΔE) perceptual color-distance formula (ISO 11664-6), eliminating subjective visual comparison.
3. **Manual Input (legacy)** — direct numeric/qualitative entry of pH and NPK values, or selection of the observed reagent color from the same BSWM chart used by the colorimetric mode. Color selections resolve through the same `ColorScienceService` engine, so manual and automated readings stay consistent.

## Key Features

- **Farmer & Farm Management** — digital farmer registration with biographical/contact info and education level; multi-farm tracking per farmer with land tenure classification; barangay-level distribution.
- **Farm Boundary Mapping** — draw a polygon directly on the Leaflet map (Leaflet-Draw), or enter a manual latitude/longitude pin when a polygon can't be drawn on site. Area (hectares) and centroid are computed automatically via the shoelace formula; new boundaries are validated server-side against existing farm polygons to catch overlaps before saving.
- **Sample-First Workflow** — every analysis starts with Create Sample (name, farmer, farm, date received, date of analysis, analyst, notes), then routes to Select Analysis Type.
- **Automated Fertility Scoring** — a weighted 100-point scale across pH (15%), Nitrogen (35%), Phosphorus (25%), and Potassium (25%); when pH is absent (QR sensor samples), N/P/K weights are renormalized over 0.85. Scores classify as Good (≥75), Fair (50–74), or Poor (<50).
- **Crop Suitability Matching** — ranks crops from the database via Tolerance Match, Fertility Match, and pH Threshold strategies; returns the top 10 suitable crops per test.
- **BSWM/PhilRice Fertilizer Recommendations** — Urea (46-0-0), TSP (0-46-0), and MOP (0-0-60) application rates in kg/ha, tiered by nutrient level, plus advisory pH guidance.
- **Interactive GIS Map** — public Leaflet.js map with color-coded farm polygons, farm detail pop-ups (fertility score, NPK/pH, recommended crops), and barangay boundary overlays.
- **Multi-Temporal Analysis** — farm-level trend classification (Improving / Stable / Declining), significant-event detection (major changes >15 pts, pH swings >1.0), and barangay/municipality comparison reports.
- **Role-Based Access Control** — OMA Administrator (full access), Technician (barangay-scoped via a technician-barangay pivot table), and anonymous Public map access.
- **Unified Reporting** — printable PDF and CSV/Excel exports combining farmer/farm/mapping details with analysis results and the fertilizer schedule, regardless of which input mode produced the data.

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 11 (PHP 8.2) |
| Frontend | Bootstrap 5.3, Font Awesome 6.5 |
| Database | MySQL / MariaDB |
| Mapping | Leaflet.js 1.9.4, Leaflet-Draw |
| Color Science | CIE L\*a\*b\*, CIEDE2000 (ISO 11664-6) |
| QR Scanning | Browser-based QR reader (HTML5 camera) |
| IoT Hardware | Arduino/ESP32, RS485/Modbus RTU, 3.5" TFT, QR generator |
| Charting | Chart.js |
| Auth & RBAC | Laravel Auth + custom `user_type` roles (admin/professional/farmer) with technician-barangay pivot |

## Core Services

- `ColorScienceService` — CIEDE2000 colorimetric matching (sRGB → linearize → XYZ D65 → CIE L\*a\*b\* → ΔE → inverse-distance weighted value).
- `FertilizerService` — BSWM/PhilRice NPK threshold-based recommendations.
- `PhTestService` — BSWM 2-step pH workflow (CPR, then BCG/BTB) logic.
- `SoilTestCalculationService` — fertility scoring and crop suitability matching.
- `AnalysisController::storeProbe` — N/P/K validation and QR sensor record storage (pH forced to null).
- `Farm` model — polygon area (shoelace formula) and centroid calculation, overlap validation.

## Database Schema

12 domain tables (plus Laravel framework tables): `users`, `barangays`, `farmers`, `farms`, `technician_barangay`, `soil_samples`, `soil_color_readings`, `ph_tests`, `crops`, `ph_color_charts`, `npk_color_charts`, `crop_fertilizer_schedules`.

## QR Payload Format

```json
{"v": 1, "probe_id": "SCANNER-001", "n": 42, "p": 18.5, "k": 150}
```

## Getting Started

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run dev
```

## Evaluation

Evaluated against the ISO 25010 software quality framework (Functional Suitability, Performance Efficiency, Compatibility, Usability, Reliability, Security, Maintainability, Portability) via a five-point Likert scale questionnaire administered to 47 respondents: 4 OMA staff, 8 agricultural technicians, 30 farmers, and 5 IT experts.

## License

Built on the [Laravel](https://laravel.com) framework, open-sourced under the [MIT license](https://opensource.org/licenses/MIT).
