#!/bin/bash
# PDF Export Feature Verification Script

echo "================================"
echo "PaperVista PDF Export Verification"
echo "================================"
echo ""

# Check TCPDF Installation
echo "1. Checking TCPDF Installation..."
if [ -f "vendor/autoload.php" ]; then
    echo "   ✓ Composer autoloader found"
else
    echo "   ✗ Composer autoloader NOT found"
    exit 1
fi

if [ -d "vendor/tecnickcom/tcpdf" ]; then
    echo "   ✓ TCPDF library installed"
    TCPDF_VERSION=$(cat vendor/tecnickcom/tcpdf/composer.json | grep '"version"' | head -1)
    echo "   ✓ $TCPDF_VERSION"
else
    echo "   ✗ TCPDF library NOT installed"
    exit 1
fi

echo ""
echo "2. Checking PHP Configuration..."
# Check PHP Memory
MEMORY=$(php -i | grep "memory_limit" | grep -v "^;" | awk '{print $NF}')
echo "   Memory Limit: $MEMORY"
if [ "$MEMORY" = "1024M" ]; then
    echo "   ✓ Memory limit is sufficient"
else
    echo "   ⚠ Memory limit is $MEMORY (recommended: 1024M)"
fi

# Check PHP Execution Time
EXEC_TIME=$(php -i | grep "max_execution_time" | grep -v "^;" | tail -1 | awk '{print $NF}')
echo "   Max Execution Time: $EXEC_TIME seconds"
if [ "$EXEC_TIME" = "0" ] || [ "$EXEC_TIME" -ge "300" ]; then
    echo "   ✓ Execution time is sufficient"
else
    echo "   ⚠ Execution time is $EXEC_TIME (recommended: 300+)"
fi

echo ""
echo "3. Checking Export Functionality..."
if [ -f "export.php" ]; then
    echo "   ✓ export.php file exists"
    
    # Check if TCPDF is used
    if grep -q "TCPDF" export.php; then
        echo "   ✓ TCPDF integration found in export.php"
    else
        echo "   ✗ TCPDF integration NOT found"
        exit 1
    fi
    
    # Check for PDF export function
    if grep -q "function exportPDF" export.php; then
        echo "   ✓ exportPDF function exists"
    else
        echo "   ✗ exportPDF function NOT found"
        exit 1
    fi
else
    echo "   ✗ export.php NOT found"
    exit 1
fi

echo ""
echo "4. Checking Documentation..."
if [ -f "PDF_EXPORT_GUIDE.md" ]; then
    echo "   ✓ PDF_EXPORT_GUIDE.md found"
else
    echo "   ⚠ PDF_EXPORT_GUIDE.md not found"
fi

if [ -f "PDF_EXPORT_FIX.md" ]; then
    echo "   ✓ PDF_EXPORT_FIX.md found"
else
    echo "   ⚠ PDF_EXPORT_FIX.md not found"
fi

echo ""
echo "================================"
echo "✓ All checks passed!"
echo "================================"
echo ""
echo "PDF Export Feature Status: READY ✓"
echo ""
echo "Next Steps:"
echo "1. Generate a summary in PaperVista"
echo "2. Click 'View Summary'"
echo "3. Click 'Export as PDF'"
echo "4. Verify PDF downloads correctly"
echo ""
