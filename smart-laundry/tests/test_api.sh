#!/usr/bin/env bash
# ==============================================================================
# Smart Laundry Basket System - API Automated Smoke Test (Bash/cURL)
# File: tests/test_api.sh
# Arsitektur: Thin Client – Server-Side Centralized Logic
# ==============================================================================

set -u

BASE_URL="${1:-http://127.0.0.1/smart-laundry/public}"
API_KEY_VALID="SMART_LAUNDRY_SECRET_KEY_2026"
API_KEY_INVALID="WRONG_SECRET_KEY_9999"

CLR_RESET="\033[0m"
CLR_RED="\033[1;31m"
CLR_GREEN="\033[1;32m"
CLR_YELLOW="\033[1;33m"
CLR_CYAN="\033[1;36m"
CLR_GRAY="\033[0;90m"

TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

echo -e "\n${CLR_CYAN}=======================================================${CLR_RESET}"
echo -e "${CLR_CYAN} SMART LAUNDRY BASKET - AUTOMATED API SMOKE TESTING (BASH)${CLR_RESET}"
echo -e "${CLR_CYAN} Target URL: ${BASE_URL}${CLR_RESET}"
echo -e "${CLR_CYAN} Architecture: Thin Client - Server-Side Centralized Logic${CLR_RESET}"
echo -e "${CLR_CYAN} Parameter Bau: <=2000 (Tidak Bau) | 2000-2500 (Normal) | >=2500 (Bau)${CLR_RESET}"
echo -e "${CLR_CYAN}=======================================================\n${CLR_RESET}"

report_result() {
  local test_name="$1"
  local success="$2"
  local detail="$3"

  TOTAL_TESTS=$((TOTAL_TESTS + 1))
  if [ "$success" -eq 1 ]; then
    PASSED_TESTS=$((PASSED_TESTS + 1))
    echo -e " ${CLR_GREEN}[PASS]${CLR_RESET} ${test_name}"
    [ -n "$detail" ] && echo -e "        ${CLR_GRAY}${detail}${CLR_RESET}"
  else
    FAILED_TESTS=$((FAILED_TESTS + 1))
    echo -e " ${CLR_RED}[FAIL]${CLR_RESET} ${test_name}"
    [ -n "$detail" ] && echo -e "        ${CLR_YELLOW}Error: ${detail}${CLR_RESET}"
  fi
}

# ------------------------------------------------------------------------------
# Test Case 1: Kategori Bau (gas_adc >= 2500) -> Expect status_bau = "Bau" & alarm_active = true
# ------------------------------------------------------------------------------
PAYLOAD_1="{\"api_key\":\"${API_KEY_VALID}\",\"jarak_cm\":8.75,\"gas_adc\":2700}"
RESP_1=$(curl -s -w "\n%{http_code}" -X POST "${BASE_URL}/api/data.php" \
  -H "Content-Type: application/json" \
  -d "$PAYLOAD_1")

HTTP_CODE_1=$(echo "$RESP_1" | tail -n1)
BODY_1=$(echo "$RESP_1" | sed '$d')

if [ "$HTTP_CODE_1" -eq 200 ] && echo "$BODY_1" | grep -q '"status_bau":"Bau"' && echo "$BODY_1" | grep -q '"alarm_active":true'; then
  report_result "Test 1: Status Bau (>= 2500 ADC)" 1 "HTTP 200 OK | Body: ${BODY_1}"
else
  report_result "Test 1: Status Bau (>= 2500 ADC)" 0 "Expected status_bau:Bau & alarm_active:true, got ${HTTP_CODE_1} | Body: ${BODY_1}"
fi

# ------------------------------------------------------------------------------
# Test Case 2: Kategori Normal (2000 - 2500 ADC) -> Expect status_bau = "Normal" & alarm_active = false
# ------------------------------------------------------------------------------
PAYLOAD_2="{\"api_key\":\"${API_KEY_VALID}\",\"jarak_cm\":28.5,\"gas_adc\":2250}"
RESP_2=$(curl -s -w "\n%{http_code}" -X POST "${BASE_URL}/api/data.php" \
  -H "Content-Type: application/json" \
  -d "$PAYLOAD_2")

HTTP_CODE_2=$(echo "$RESP_2" | tail -n1)
BODY_2=$(echo "$RESP_2" | sed '$d')

if [ "$HTTP_CODE_2" -eq 200 ] && echo "$BODY_2" | grep -q '"status_bau":"Normal"' && echo "$BODY_2" | grep -q '"alarm_active":false'; then
  report_result "Test 2: Status Normal (2000 - 2500 ADC)" 1 "HTTP 200 OK | Body: ${BODY_2}"
else
  report_result "Test 2: Status Normal (2000 - 2500 ADC)" 0 "Expected status_bau:Normal & alarm_active:false, got ${HTTP_CODE_2} | Body: ${BODY_2}"
fi

# ------------------------------------------------------------------------------
# Test Case 3: Kategori Tidak Bau (<= 2000 ADC) -> Expect status_bau = "Tidak Bau" & alarm_active = false
# ------------------------------------------------------------------------------
PAYLOAD_3="{\"api_key\":\"${API_KEY_VALID}\",\"jarak_cm\":28.5,\"gas_adc\":1450}"
RESP_3=$(curl -s -w "\n%{http_code}" -X POST "${BASE_URL}/api/data.php" \
  -H "Content-Type: application/json" \
  -d "$PAYLOAD_3")

HTTP_CODE_3=$(echo "$RESP_3" | tail -n1)
BODY_3=$(echo "$RESP_3" | sed '$d')

if [ "$HTTP_CODE_3" -eq 200 ] && echo "$BODY_3" | grep -q '"status_bau":"Tidak Bau"' && echo "$BODY_3" | grep -q '"alarm_active":false'; then
  report_result "Test 3: Status Tidak Bau (<= 2000 ADC)" 1 "HTTP 200 OK | Body: ${BODY_3}"
else
  report_result "Test 3: Status Tidak Bau (<= 2000 ADC)" 0 "Expected status_bau:'Tidak Bau' & alarm_active:false, got ${HTTP_CODE_3} | Body: ${BODY_3}"
fi

# ------------------------------------------------------------------------------
# Test Case 4: Unauthorized API Key (POST /api/data.php) -> Expect 401
# ------------------------------------------------------------------------------
PAYLOAD_4="{\"api_key\":\"${API_KEY_INVALID}\",\"jarak_cm\":25.0,\"gas_adc\":600}"
RESP_4=$(curl -s -w "\n%{http_code}" -X POST "${BASE_URL}/api/data.php" \
  -H "Content-Type: application/json" \
  -d "$PAYLOAD_4")

HTTP_CODE_4=$(echo "$RESP_4" | tail -n1)
BODY_4=$(echo "$RESP_4" | sed '$d')

if [ "$HTTP_CODE_4" -eq 401 ]; then
  report_result "Test 4: Invalid API Key Rejection" 1 "HTTP 401 Unauthorized correctly enforced | Body: ${BODY_4}"
else
  report_result "Test 4: Invalid API Key Rejection" 0 "Expected HTTP 401, got ${HTTP_CODE_4}"
fi

# ------------------------------------------------------------------------------
# Test Case 5: Malformed Payload (POST /api/data.php) -> Expect 400
# ------------------------------------------------------------------------------
PAYLOAD_5="{\"api_key\":\"${API_KEY_VALID}\",\"gas_adc\":\"BUKAN_ANGKA\"}"
RESP_5=$(curl -s -w "\n%{http_code}" -X POST "${BASE_URL}/api/data.php" \
  -H "Content-Type: application/json" \
  -d "$PAYLOAD_5")

HTTP_CODE_5=$(echo "$RESP_5" | tail -n1)
BODY_5=$(echo "$RESP_5" | sed '$d')

if [ "$HTTP_CODE_5" -eq 400 ]; then
  report_result "Test 5: Malformed Payload Rejection" 1 "HTTP 400 Bad Request correctly triggered | Body: ${BODY_5}"
else
  report_result "Test 5: Malformed Payload Rejection" 0 "Expected HTTP 400, got ${HTTP_CODE_5}"
fi

# ------------------------------------------------------------------------------
# Test Case 6: Fetch Latest Data (GET /api/latest.php) -> Expect 200 & verify
# ------------------------------------------------------------------------------
RESP_6=$(curl -s -w "\n%{http_code}" -X GET "${BASE_URL}/api/latest.php" \
  -H "Accept: application/json")

HTTP_CODE_6=$(echo "$RESP_6" | tail -n1)
BODY_6=$(echo "$RESP_6" | sed '$d')

if [ "$HTTP_CODE_6" -eq 200 ] && echo "$BODY_6" | grep -q '"status":"success"' && echo "$BODY_6" | grep -q '"status_bau":'; then
  report_result "Test 6: Fetch Latest Data (GET /api/latest.php)" 1 "HTTP 200 OK | Data telemetry successfully verified"
else
  report_result "Test 6: Fetch Latest Data (GET /api/latest.php)" 0 "Expected HTTP 200 with latest object, got ${HTTP_CODE_6}"
fi

# ------------------------------------------------------------------------------
# Ringkasan Pengujian
# ------------------------------------------------------------------------------
echo -e "\n${CLR_CYAN}-------------------------------------------------------${CLR_RESET}"
echo -e "${CLR_CYAN} RINGKASAN HASIL PENGUJIAN SMOKE TEST:${CLR_RESET}"
echo -e " Total Pengujian : ${TOTAL_TESTS}"
echo -e " ${CLR_GREEN}Lulus (Passed)  : ${PASSED_TESTS}${CLR_RESET}"
if [ "$FAILED_TESTS" -gt 0 ]; then
  echo -e " ${CLR_RED}Gagal (Failed)  : ${FAILED_TESTS}${CLR_RESET}"
  echo -e "${CLR_CYAN}-------------------------------------------------------\n${CLR_RESET}"
  exit 1
else
  echo -e " ${CLR_GREEN}Gagal (Failed)  : 0${CLR_RESET}"
  echo -e "${CLR_CYAN}-------------------------------------------------------\n${CLR_RESET}"
  exit 0
fi
