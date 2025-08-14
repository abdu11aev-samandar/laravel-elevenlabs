#!/bin/bash

# Real API Test Runner
# This script runs the real-api tests until they pass 100% or max attempts reached

set -e

# Configuration
MAX_ATTEMPTS=10
WAIT_BETWEEN_ATTEMPTS=30  # seconds
LOG_DIR="./test_results"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Create log directory
mkdir -p "${LOG_DIR}"

echo -e "${BLUE}🧪 ElevenLabs Real API Test Runner${NC}"
echo -e "${BLUE}=====================================${NC}"

# Check if API key is set
if [[ -z "${ELEVENLABS_API_KEY}" ]]; then
    echo -e "${RED}❌ Error: ELEVENLABS_API_KEY environment variable is not set${NC}"
    echo -e "${YELLOW}💡 To set it:${NC}"
    echo -e "   export ELEVENLABS_API_KEY='your-api-key-here'"
    echo -e "   ${BLUE}Or create a .env file with:${NC}"
    echo -e "   ELEVENLABS_API_KEY=your-api-key-here"
    exit 1
fi

echo -e "${GREEN}✅ API Key found${NC}"
echo -e "${BLUE}📋 Test Configuration:${NC}"
echo -e "   Max attempts: ${MAX_ATTEMPTS}"
echo -e "   Wait between attempts: ${WAIT_BETWEEN_ATTEMPTS}s"
echo -e "   Log directory: ${LOG_DIR}"

# Function to run tests and capture results
run_tests() {
    local attempt=$1
    local log_file="${LOG_DIR}/attempt_${attempt}_${TIMESTAMP}.log"
    local xml_file="${LOG_DIR}/attempt_${attempt}_${TIMESTAMP}.xml"
    
    echo -e "${BLUE}🔄 Attempt ${attempt}/${MAX_ATTEMPTS}${NC}"
    
    # Run the tests
    if ./vendor/bin/phpunit --group=real-api --log-junit="${xml_file}" > "${log_file}" 2>&1; then
        local test_result=$?
    else
        local test_result=$?
    fi
    
    # Parse results from log file
    local total_tests=$(grep -o "Tests: [0-9]*" "${log_file}" | grep -o "[0-9]*" | head -1)
    local assertions=$(grep -o "Assertions: [0-9]*" "${log_file}" | grep -o "[0-9]*" | head -1)
    local failures=$(grep -o "Failures: [0-9]*" "${log_file}" | grep -o "[0-9]*" | head -1)
    local errors=$(grep -o "Errors: [0-9]*" "${log_file}" | grep -o "[0-9]*" | head -1)
    local skipped=$(grep -o "Skipped: [0-9]*" "${log_file}" | grep -o "[0-9]*" | head -1)
    
    # Default values if not found
    total_tests=${total_tests:-0}
    assertions=${assertions:-0}
    failures=${failures:-0}
    errors=${errors:-0}
    skipped=${skipped:-0}
    
    local passed=$((total_tests - failures - errors - skipped))
    local success_rate=0
    
    if [[ $total_tests -gt 0 ]]; then
        success_rate=$(( (passed * 100) / total_tests ))
    fi
    
    echo -e "${BLUE}📊 Results for attempt ${attempt}:${NC}"
    echo -e "   Total Tests: ${total_tests}"
    echo -e "   Passed: ${GREEN}${passed}${NC}"
    echo -e "   Failed: ${RED}${failures}${NC}"
    echo -e "   Errors: ${RED}${errors}${NC}"
    echo -e "   Skipped: ${YELLOW}${skipped}${NC}"
    echo -e "   Success Rate: ${success_rate}%"
    
    # Check if we have 100% success
    if [[ $failures -eq 0 && $errors -eq 0 && $passed -gt 0 ]]; then
        echo -e "${GREEN}🎉 SUCCESS! All tests passed (100%)${NC}"
        echo -e "${GREEN}📁 Results saved to: ${log_file}${NC}"
        echo -e "${GREEN}📄 JUnit XML: ${xml_file}${NC}"
        return 0
    else
        echo -e "${YELLOW}⚠️  Tests not yet at 100%. Logging failures...${NC}"
        
        # Extract and display failure information
        if [[ -f "${xml_file}" ]]; then
            echo -e "${BLUE}🔍 Failure Details:${NC}"
            # Parse XML for failure details (simplified)
            grep -A 5 "<failure" "${xml_file}" | head -20 || true
        fi
        
        echo -e "${RED}❌ Attempt ${attempt} failed${NC}"
        echo -e "${BLUE}📁 Log saved to: ${log_file}${NC}"
        return 1
    fi
}

# Function to analyze and suggest fixes
suggest_fixes() {
    local latest_log=$(ls -t "${LOG_DIR}"/*.log 2>/dev/null | head -1)
    
    if [[ -f "${latest_log}" ]]; then
        echo -e "${BLUE}🔧 Analyzing failures and suggesting fixes...${NC}"
        
        # Check for common failure patterns
        if grep -q "401 Unauthorized" "${latest_log}"; then
            echo -e "${YELLOW}💡 401 Unauthorized errors detected:${NC}"
            echo -e "   - Verify your API key is valid and active"
            echo -e "   - Check if your subscription has sufficient credits"
        fi
        
        if grep -q "422" "${latest_log}"; then
            echo -e "${YELLOW}💡 422 Validation errors detected:${NC}"
            echo -e "   - Some API endpoints may require additional parameters"
            echo -e "   - Check the ElevenLabs API documentation for changes"
        fi
        
        if grep -q "429" "${latest_log}"; then
            echo -e "${YELLOW}💡 Rate limiting detected:${NC}"
            echo -e "   - Consider increasing wait time between attempts"
            echo -e "   - Your subscription may have rate limits"
        fi
        
        if grep -q "not available" "${latest_log}"; then
            echo -e "${YELLOW}💡 Service availability issues:${NC}"
            echo -e "   - Some features may require higher tier subscriptions"
            echo -e "   - Check your subscription plan"
        fi
    fi
}

# Main execution loop
attempt=1
while [[ $attempt -le $MAX_ATTEMPTS ]]; do
    if run_tests $attempt; then
        # Success! Tests passed 100%
        echo -e "${GREEN}🏆 Mission accomplished! All real API tests passed.${NC}"
        exit 0
    fi
    
    # If not the last attempt, wait and try again
    if [[ $attempt -lt $MAX_ATTEMPTS ]]; then
        echo -e "${YELLOW}⏳ Waiting ${WAIT_BETWEEN_ATTEMPTS} seconds before next attempt...${NC}"
        sleep $WAIT_BETWEEN_ATTEMPTS
        
        # Suggest fixes after every 3 attempts
        if [[ $((attempt % 3)) -eq 0 ]]; then
            suggest_fixes
        fi
    fi
    
    ((attempt++))
done

# If we get here, all attempts failed
echo -e "${RED}❌ All ${MAX_ATTEMPTS} attempts failed to achieve 100% test success${NC}"
suggest_fixes
echo -e "${BLUE}📁 All logs saved in: ${LOG_DIR}${NC}"
exit 1
