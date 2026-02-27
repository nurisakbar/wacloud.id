#!/bin/bash

# Script untuk test webhook WAHA
# Usage: ./test-webhook.sh [session_id]

SESSION_ID="${1:-5bb93730-31f4-4a8f-aaff-04bfb8401611}"
WEBHOOK_URL="http://localhost:8000/webhook/receive/${SESSION_ID}"
LOG_FILE="frontend/storage/logs/laravel.log"
DEBUG_LOG="/tmp/webhook-test.log"

# Function untuk menulis ke Laravel log dengan format yang sesuai
write_laravel_log() {
    local level=$1
    shift
    local message="$*"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    local log_entry="[${timestamp}] local.${level}: WebhookTestScript: ${message}"
    
    # Tulis ke laravel.log jika file ada
    if [ -f "${LOG_FILE}" ]; then
        echo "${log_entry}" >> "${LOG_FILE}"
    fi
    
    # Juga tulis ke debug log
    echo "[${level}] ${timestamp} - ${message}" >> "${DEBUG_LOG}"
    
    # Output ke console juga
    echo "${log_entry}"
}

# Function untuk log debug dengan context JSON
log_debug() {
    local message="$1"
    local context="${2:-{}}"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    local log_entry="[${timestamp}] local.DEBUG: WebhookTestScript: ${message} ${context}"
    
    # Tulis ke laravel.log jika file ada
    if [ -f "${LOG_FILE}" ]; then
        echo "${log_entry}" >> "${LOG_FILE}"
    fi
    
    # Juga tulis ke debug log
    echo "[DEBUG] ${timestamp} - ${message} ${context}" >> "${DEBUG_LOG}"
}

# Function untuk log info dengan context JSON
log_info() {
    local message="$1"
    local context="${2:-{}}"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    local log_entry="[${timestamp}] local.INFO: WebhookTestScript: ${message} ${context}"
    
    # Tulis ke laravel.log jika file ada
    if [ -f "${LOG_FILE}" ]; then
        echo "${log_entry}" >> "${LOG_FILE}"
    fi
    
    # Juga tulis ke debug log
    echo "[INFO] ${timestamp} - ${message} ${context}" >> "${DEBUG_LOG}"
}

# Function untuk log error dengan context JSON
log_error() {
    local message="$1"
    local context="${2:-{}}"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    local log_entry="[${timestamp}] local.ERROR: WebhookTestScript: ${message} ${context}"
    
    # Tulis ke laravel.log jika file ada
    if [ -f "${LOG_FILE}" ]; then
        echo "${log_entry}" >> "${LOG_FILE}"
    fi
    
    # Juga tulis ke debug log
    echo "[ERROR] ${timestamp} - ${message} ${context}" >> "${DEBUG_LOG}"
}

log_info "Starting WAHA Webhook Test" "{\"action\":\"test_start\",\"session_id\":\"${SESSION_ID}\"}"
log_debug "Session ID" "{\"session_id\":\"${SESSION_ID}\"}"
log_debug "Webhook URL" "{\"webhook_url\":\"${WEBHOOK_URL}\"}"
log_debug "Log file path" "{\"log_file\":\"${LOG_FILE}\",\"debug_log\":\"${DEBUG_LOG}\"}"
log_debug "Working directory" "{\"working_directory\":\"$(pwd)\"}"

echo "=========================================="
echo "Testing WAHA Webhook"
echo "=========================================="
echo "Session ID: ${SESSION_ID}"
echo "Webhook URL: ${WEBHOOK_URL}"
echo "Debug log: ${DEBUG_LOG}"
echo "Laravel log: ${LOG_FILE}"
echo ""

# Test 1: Check if webhook endpoint is accessible
echo "Test 1: Checking webhook endpoint accessibility..."
log_debug "Test 1: Checking webhook endpoint accessibility" "{\"test\":1,\"webhook_url\":\"${WEBHOOK_URL}\"}"

HTTP_CODE=$(curl -s -o /tmp/webhook-test-response.json -w "%{http_code}" -X POST "${WEBHOOK_URL}" \
  -H "Content-Type: application/json" \
  -d '{"event":"test","payload":{}}' 2>&1)

RESPONSE_BODY=$(cat /tmp/webhook-test-response.json 2>/dev/null || echo "")
# Escape JSON untuk context
RESPONSE_BODY_ESCAPED=$(echo "$RESPONSE_BODY" | sed 's/"/\\"/g' | tr '\n' ' ')

log_debug "Test 1: HTTP response received" "{\"test\":1,\"http_code\":\"${HTTP_CODE}\",\"response_length\":${#RESPONSE_BODY}}"

if [ "$HTTP_CODE" == "200" ] || [ "$HTTP_CODE" == "404" ]; then
    log_info "Test 1: PASSED - Webhook endpoint is accessible" "{\"test\":1,\"http_code\":\"${HTTP_CODE}\",\"status\":\"success\"}"
    echo "✓ Webhook endpoint is accessible (HTTP ${HTTP_CODE})"
    if [ "$HTTP_CODE" == "404" ]; then
        log_debug "Test 1: 404 response received" "{\"test\":1,\"http_code\":\"404\",\"note\":\"session_might_not_exist\"}"
        echo "  Note: 404 might be OK if session doesn't exist, but endpoint should be reachable"
    fi
else
    log_error "Test 1: FAILED - Webhook endpoint returned unexpected HTTP code" "{\"test\":1,\"http_code\":\"${HTTP_CODE}\",\"response\":\"${RESPONSE_BODY_ESCAPED}\",\"status\":\"failed\"}"
    echo "✗ Webhook endpoint returned HTTP ${HTTP_CODE}"
    echo "  Response: ${RESPONSE_BODY}"
    echo "  Note: 404 might be OK if session doesn't exist, but endpoint should be reachable"
fi
echo ""

# Test 2: Test from Docker container (if WAHA is in Docker)
if docker ps | grep -q waha-plus; then
    echo "Test 2: Testing webhook from Docker container..."
    log_debug "Test 2: Testing from Docker container" "{\"test\":2,\"docker_container\":\"waha-plus\"}"
    
    # Try using host.docker.internal first (works on Mac/Windows)
    log_info "Attempting from Docker container via host.docker.internal..."
    
    # Try host.docker.internal
    DOCKER_WEBHOOK_URL="http://host.docker.internal:8000/webhook/receive/${SESSION_ID}"
    echo "  Testing: ${DOCKER_WEBHOOK_URL}"
    log_debug "Test 2: Docker webhook URL" "{\"test\":2,\"docker_webhook_url\":\"${DOCKER_WEBHOOK_URL}\"}"
    log_debug "Executing curl inside waha-plus" "POST http://host.docker.internal:8000/webhook/receive/${SESSION_ID}"
    
    HTTP_CODE=$(docker exec waha-plus curl -s -o /dev/null -w "%{http_code}" -X POST "${DOCKER_WEBHOOK_URL}" \
      -H "Content-Type: application/json" \
      -d '{"event":"test","payload":{}}' 2>/dev/null || echo "000")
    
    log_debug "Test 2: Docker test response" "{\"test\":2,\"http_code\":\"${HTTP_CODE}\"}"
    
    if [ "$HTTP_CODE" == "200" ] || [ "$HTTP_CODE" == "404" ]; then
        log_info "Test 2: PASSED - Docker container can reach webhook" "{\"test\":2,\"http_code\":\"${HTTP_CODE}\",\"status\":\"success\"}"
        echo "  ✓ Docker container can reach webhook via host.docker.internal (HTTP ${HTTP_CODE})"
    else
        log_error "Test 2: FAILED - Docker container cannot reach webhook" "{\"test\":2,\"http_code\":\"${HTTP_CODE}\",\"status\":\"failed\"}"
        echo "  ✗ Docker container cannot reach webhook (HTTP ${HTTP_CODE})"
        echo "  → Try setting DOCKER_HOST_IP in .env file"
    fi
    echo ""
fi

# Test 3: Send test message event
echo "Test 3: Sending test message event..."
log_debug "Test 3: Sending test message event" "{\"test\":3,\"webhook_url\":\"${WEBHOOK_URL}\"}"

TIMESTAMP=$(date +%s)
TEST_PAYLOAD='{
    "event": "message",
    "payload": {
      "from": "6281234567890@c.us",
      "to": "6289876543210@c.us",
      "body": "Test message",
      "timestamp": '$TIMESTAMP',
      "id": {
        "fromMe": false,
        "remote": "6281234567890@c.us",
        "id": "test_'$TIMESTAMP'"
      }
    }
  }'

# Escape payload untuk log JSON
TEST_PAYLOAD_ESCAPED=$(echo "$TEST_PAYLOAD" | sed 's/"/\\"/g' | tr '\n' ' ')
log_debug "Test 3: Test payload prepared" "{\"test\":3,\"payload_length\":${#TEST_PAYLOAD},\"timestamp\":${TIMESTAMP}}"

RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "${WEBHOOK_URL}" \
  -H "Content-Type: application/json" \
  -d "${TEST_PAYLOAD}")

HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
RESPONSE_BODY=$(echo "$RESPONSE" | head -n-1)
RESPONSE_BODY_ESCAPED=$(echo "$RESPONSE_BODY" | sed 's/"/\\"/g' | tr '\n' ' ')

log_debug "Test 3: HTTP response received" "{\"test\":3,\"http_code\":\"${HTTP_CODE}\",\"response_length\":${#RESPONSE_BODY}}"

if echo "$RESPONSE_BODY" | grep -q "success" || [ "$HTTP_CODE" == "200" ]; then
    log_info "Test 3: PASSED - Test message event sent successfully" "{\"test\":3,\"http_code\":\"${HTTP_CODE}\",\"status\":\"success\"}"
    echo "✓ Test message event sent successfully"
    echo "  HTTP Code: ${HTTP_CODE}"
    echo "  Response: ${RESPONSE_BODY}"
else
    log_error "Test 3: FAILED - Failed to send test message event" "{\"test\":3,\"http_code\":\"${HTTP_CODE}\",\"response\":\"${RESPONSE_BODY_ESCAPED}\",\"status\":\"failed\"}"
    echo "✗ Failed to send test message event"
    echo "  HTTP Code: ${HTTP_CODE}"
    echo "  Response: ${RESPONSE_BODY}"
fi
echo ""

# Test 4: Check Laravel logs
echo "Test 4: Checking Laravel logs for webhook activity..."
log_debug "Test 4: Checking Laravel logs" "{\"test\":4,\"log_file\":\"${LOG_FILE}\"}"

if [ -f "${LOG_FILE}" ]; then
    LOG_SIZE=$(wc -l < "${LOG_FILE}" 2>/dev/null || echo "0")
    log_debug "Test 4: Log file exists" "{\"test\":4,\"log_file\":\"${LOG_FILE}\",\"log_size_lines\":${LOG_SIZE}}"
    echo "  Recent webhook logs:"
    RECENT_LOGS=$(tail -n 50 "${LOG_FILE}" | grep -i "webhook\|receive\|WebhookTest" | tail -n 10)
    if [ -n "$RECENT_LOGS" ]; then
        LOG_COUNT=$(echo "$RECENT_LOGS" | wc -l)
        log_info "Test 4: Found recent webhook logs" "{\"test\":4,\"log_count\":${LOG_COUNT}}"
        echo "$RECENT_LOGS" | while IFS= read -r line; do
            echo "  $line"
        done
    else
        log_debug "Test 4: No recent webhook logs found" "{\"test\":4}"
        echo "  No recent webhook logs found"
    fi
    
    # Check for WebhookTest debug logs
    echo ""
    echo "  Recent WebhookTest debug logs:"
    DEBUG_LOGS=$(tail -n 100 "${LOG_FILE}" | grep "WebhookTest" | tail -n 5)
    if [ -n "$DEBUG_LOGS" ]; then
        DEBUG_COUNT=$(echo "$DEBUG_LOGS" | wc -l)
        log_debug "Test 4: Found WebhookTest debug logs" "{\"test\":4,\"debug_log_count\":${DEBUG_COUNT}}"
        echo "$DEBUG_LOGS" | while IFS= read -r line; do
            echo "  $line"
        done
    else
        log_debug "Test 4: No WebhookTest debug logs found" "{\"test\":4}"
        echo "  No WebhookTest debug logs found"
    fi
else
    log_error "Test 4: Laravel log file not found" "{\"test\":4,\"log_file\":\"${LOG_FILE}\",\"error\":\"file_not_found\"}"
    echo "  Laravel log file not found: ${LOG_FILE}"
fi
echo ""

# Test 5: Check WAHA session status
echo "Test 5: Checking WAHA session status..."
WAHA_URL="${WAHA_URL:-http://localhost:3002}"
WAHA_API_KEY="${WAHA_API_KEY:-}"

log_debug "Test 5: Checking WAHA session status" "{\"test\":5,\"waha_url\":\"${WAHA_URL}\",\"api_key_set\":$([ -n "$WAHA_API_KEY" ] && echo "true" || echo "false")}"

if [ -n "$WAHA_API_KEY" ]; then
    STATUS=$(curl -s -X GET "${WAHA_URL}/api/sessions/default" \
      -H "X-Api-Key: ${WAHA_API_KEY}" | jq -r '.status // "unknown"' 2>/dev/null || echo "unknown")
    
    log_debug "Test 5: WAHA session status retrieved" "{\"test\":5,\"status\":\"${STATUS}\"}"
    echo "  WAHA session status: ${STATUS}"
    
    if [ "$STATUS" == "WORKING" ]; then
        log_info "Test 5: Session is WORKING" "{\"test\":5,\"status\":\"${STATUS}\",\"webhook_ready\":true}"
        echo "  ✓ Session is working - webhook should receive messages"
    else
        log_info "Test 5: Session not WORKING" "{\"test\":5,\"status\":\"${STATUS}\",\"webhook_ready\":false}"
        echo "  ⚠ Session status is ${STATUS} - webhook may not receive messages until session is WORKING"
    fi
else
    log_debug "Test 5: WAHA_API_KEY not set" "{\"test\":5,\"skipped\":true}"
    echo "  ⚠ WAHA_API_KEY not set - skipping WAHA status check"
fi
echo ""

log_info "Webhook test completed" "{\"action\":\"test_complete\",\"session_id\":\"${SESSION_ID}\"}"

echo "=========================================="
echo "Webhook Test Complete"
echo "=========================================="
echo ""
echo "Debug Information:"
echo "- Debug log file: ${DEBUG_LOG}"
echo "- Laravel log file: ${LOG_FILE}"
echo "- All logs are written to both files"
echo ""
echo "Next steps:"
echo "1. Ensure Laravel app is running on port 8000"
echo "2. Check Laravel logs: tail -f ${LOG_FILE}"
echo "3. Check WebhookTestScript logs: tail -f ${LOG_FILE} | grep WebhookTestScript"
echo "4. Check WebhookTest command logs: tail -f ${LOG_FILE} | grep WebhookTest"
echo "5. Check all webhook logs: tail -f ${LOG_FILE} | grep -E 'WebhookTest|webhook'"
echo "6. Send a test message to your WhatsApp number"
echo "7. Check if webhook receives the message in logs"
echo ""
echo "Troubleshooting:"
echo "- If Docker can't reach webhook, set DOCKER_HOST_IP in .env"
echo "- For Linux, you may need: DOCKER_HOST_IP=<your-host-ip>"
echo "- For macOS/Windows, host.docker.internal should work"
echo "- View detailed debug: cat ${DEBUG_LOG}"
echo "- View Laravel debug: tail -f ${LOG_FILE} | grep -E 'WebhookTestScript|WebhookTest|webhook'"
echo "- Filter by test number: tail -f ${LOG_FILE} | grep 'Test 1'"
echo ""

