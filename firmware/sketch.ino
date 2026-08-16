#include <SPI.h>
#include <Adafruit_GFX.h>
#include <Adafruit_ILI9341.h>
#include <ArduinoJson.h>
#include "qrcode.h" // Add this to your libraries.txt in Wokwi

// TFT Pin definitions for Arduino Mega
#define TFT_CS   10
#define TFT_DC    8
#define TFT_RST   9

// Fixed per-device identifier, stored in soil_samples.probe_id on the
// Laravel side for traceability. Set per physical unit at flash time.
#define PROBE_ID "PROBE-001"

// AgriLens QR payload schema version (see CLAUDE.md Phase 4 contract).
// Bump this if the field set below ever changes, so the web app can
// detect and reject payloads from outdated firmware.
#define PAYLOAD_VERSION 1

Adafruit_ILI9341 tft = Adafruit_ILI9341(TFT_CS, TFT_DC, TFT_RST);

void setup() {
  Serial.begin(115200);   // Debug console
  Serial1.begin(9600);    // Simulated RS485 / Modbus stream

  tft.begin();
  tft.setRotation(1);     // Landscape mode
  tft.fillScreen(ILI9341_BLACK);

  tft.setTextColor(ILI9341_WHITE);
  tft.setTextSize(2);
  tft.setCursor(20, 20);
  tft.println("RS485 QR Code System");
  tft.setCursor(20, 50);
  tft.println("Awaiting Data...");

  // Draw a default start QR Code
  generateQR("STK-READY-V2");
}

void loop() {
  // Simulate receiving an RS485 trigger (Type test data in Wokwi serial input).
  // Expected sensor payload: "nitrogen,phosphorus,potassium" e.g. "42,18.5,150"
  if (Serial1.available() > 0) {
    String incomingData = Serial1.readStringUntil('\n');
    incomingData.trim();

    if (incomingData.length() > 0) {
      String payload = buildPayload(incomingData);

      tft.fillScreen(ILI9341_BLACK);
      tft.setCursor(10, 10);
      tft.setTextColor(ILI9341_GREEN);
      tft.print("Received: ");
      tft.println(incomingData);

      // Update the screen with a QR code containing the AgriLens JSON payload
      generateQR(payload.c_str());
    }
  }
}

// Parses the raw "n,p,k" reading from the sensor stream and builds the
// JSON payload the Laravel-side QR scanner expects. This probe hardware
// measures N/P/K only — no pH sensor. Falls back to an error marker (still
// valid JSON, so the web app can surface a clear message instead of failing
// to parse) if the reading isn't in the expected shape.
String buildPayload(const String &rawReading) {
  float values[3];
  int fieldCount = 0;
  int start = 0;

  for (int i = 0; i <= rawReading.length() && fieldCount < 3; i++) {
    if (i == rawReading.length() || rawReading.charAt(i) == ',') {
      String field = rawReading.substring(start, i);
      field.trim();
      values[fieldCount] = field.toFloat();
      fieldCount++;
      start = i + 1;
    }
  }

  StaticJsonDocument<192> doc;

  if (fieldCount != 3) {
    doc["v"] = PAYLOAD_VERSION;
    doc["probe_id"] = PROBE_ID;
    doc["error"] = "unrecognized_sensor_data";
    doc["raw"] = rawReading;
  } else {
    doc["v"] = PAYLOAD_VERSION;
    doc["probe_id"] = PROBE_ID;
    doc["n"] = values[0];
    doc["p"] = values[1];
    doc["k"] = values[2];
  }

  String output;
  serializeJson(doc, output);
  return output;
}

// Function to generate and draw a clean QR Code on the TFT
void generateQR(const char* data) {
  QRCode qrcode;
  // Version 6 (up to ~134 bytes at error-correction level L) comfortably
  // fits the JSON payload, which version 3 (used for the short startup
  // marker) does not.
  uint8_t qrcodeData[qrcode_getBufferSize(6)];

  // Initialize QR code
  qrcode_initText(&qrcode, qrcodeData, 6, 0, data);

  // Math to center the QR code on a 320x240 screen
  int scale = 3; // Size of each QR pixel block (adjust to change overall size)
  int offset_x = (320 - (qrcode.size * scale)) / 2;
  int offset_y = 60 + (180 - (qrcode.size * scale)) / 2; // Keep top area clear for text

  // Draw background white border box
  tft.fillRect(offset_x - 10, offset_y - 10, (qrcode.size * scale) + 20, (qrcode.size * scale) + 20, ILI9341_WHITE);

  // Read QR matrix map and draw black/white blocks
  for (uint8_t y = 0; y < qrcode.size; y++) {
    for (uint8_t x = 0; x < qrcode.size; x++) {
      if (qrcode_getModule(&qrcode, x, y)) {
        tft.fillRect(offset_x + (x * scale), offset_y + (y * scale), scale, scale, ILI9341_BLACK);
      } else {
        tft.fillRect(offset_x + (x * scale), offset_y + (y * scale), scale, scale, ILI9341_WHITE);
      }
    }
  }
}
