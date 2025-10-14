#!/bin/bash

# Function to URL encode strings
urlencode() {
    local string="$1"
    local strlen=${#string}
    local encoded=""
    local pos c o

    for (( pos=0 ; pos<strlen ; pos++ )); do
        c=${string:$pos:1}
        case "$c" in
            [-_.~a-zA-Z0-9] ) o="$c" ;;
            * )               printf -v o '%%%02x' "'$c" ;;
        esac
        encoded+="$o"
    done
    echo "$encoded"
}

# Function to extract CSRF token
extract_token() {
    grep -o 'token=[^"]*' | cut -d= -f2
}

# Credentials
USERNAME="admin"
PASSWORD="Welcome2Choma@2025"

# URL encode the password
ENCODED_PASSWORD=$(urlencode "$PASSWORD")

# Get initial page to get CSRF token
echo "Getting initial page..."
TOKEN=$(curl -s -c cookies.txt "http://localhost:8080/index.php" | extract_token)

# Login with token
echo "Logging in..."
LOGIN_RESPONSE=$(curl -s -b cookies.txt -c cookies.txt -L \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "USERNAME=$USERNAME&PASSWORD=$ENCODED_PASSWORD&token=$TOKEN" \
  "http://localhost:8080/index.php")

# Extract new token from login response
TOKEN=$(echo "$LOGIN_RESPONSE" | extract_token)

# Get the report cards page
echo "Accessing report cards page..."
REPORT_PAGE=$(curl -s -b cookies.txt -c cookies.txt -L \
  -H "X-Requested-With: XMLHttpRequest" \
  "http://localhost:8080/Modules.php?modname=Grades/ReportCards.php&modfunc=&search_modfunc=list&advanced=&token=$TOKEN")

# Extract new token from report page
TOKEN=$(echo "$REPORT_PAGE" | extract_token)

# Get report cards page with student list
echo "Getting report cards page..."
REPORT_PAGE=$(curl -s -b cookies.txt -c cookies.txt \
  -H "X-Requested-With: XMLHttpRequest" \
  "http://localhost:8080/Modules.php?modname=Grades/ReportCards.php&modfunc=&search_modfunc=list&advanced=&token=$TOKEN")

# Save the page for inspection
echo "$REPORT_PAGE" > report_page.html

# Extract new token from report page
TOKEN=$(echo "$REPORT_PAGE" | extract_token)

# Generate report card with minimal options
echo "Generating report card..."
curl -v -b cookies.txt -c cookies.txt \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "X-Requested-With: XMLHttpRequest" \
  -d "mp_arr[]=6&st_arr[]=1&_ROSARIO_PDF=true&token=$TOKEN" \
  "http://localhost:8080/Modules.php?modname=Grades/ReportCards.php&modfunc=save" | tee response.html

# Clean up
rm -f cookies.txt
