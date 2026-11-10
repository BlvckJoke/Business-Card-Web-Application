<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>SA Customs Calculator | Import Duties + VAT (USD/ZAR)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Smooth tap highlights for mobile */
        .mobile-tap {
            transition: background 0.2s ease;
        }
        .mobile-tap:active {
            background-color: rgba(0,0,0,0.05);
            border-radius: 999px;
        }
        /* custom scroll for dropdown */
        #countryMenu {
            max-height: 280px;
            overflow-y: auto;
        }
        /* transition for row selection */
        .tariff-row {
            transition: all 0.1s ease;
        }
    </style>
</head>
<body class="bg-gray-50">

<!-- Sticky Header -->
<header class="bg-white shadow-sm safe-area-top sticky top-0 z-10 border-b border-gray-100">
    <div class="px-4 py-3">
        <div class="flex items-center justify-between">
            <a href="info_card.php" class="mobile-tap p-2 -ml-2">
                <i class="fas fa-arrow-left text-xl text-gray-700"></i>
            </a>
            <div class="flex-1 text-center">
                <h1 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-globe text-blue-500 mr-2"></i>
                    IMPORT DUTIES + VAT
                </h1>
            </div>
            <div class="w-8"></div> <!-- balance spacing -->
        </div>
    </div>
</header>

<div class="container mx-auto px-4 py-6 md:py-8">
    <!-- Header Description -->
    <div class="mb-6">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-800">South African Customs Calculator</h1>
        <p class="text-gray-600 mt-1">HS Tariff search | Duty estimation | VAT (15%) included in final ZAR costs</p>
    </div>

    <!-- Exchange Rate Alert -->
    <div id="exchangeRateAlert" class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded-lg shadow-sm hidden">
        <div class="flex items-center flex-wrap gap-2">
            <i class="fas fa-info-circle text-blue-500 text-xl"></i>
            <div class="flex-1">
                <p class="text-blue-700 font-medium">Live Exchange Rate Updated</p>
                <p class="text-blue-600 text-sm" id="exchangeRateMessage">1 USD = 18.50 ZAR</p>
            </div>
            <button onclick="refreshExchangeRate()" class="bg-blue-100 hover:bg-blue-200 text-blue-700 px-3 py-1.5 rounded-lg text-sm flex items-center gap-1">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Search Section -->
    <div class="bg-white rounded-xl shadow-md p-5 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-barcode mr-1 text-blue-500"></i> HS Code (Exact)</label>
                <input type="text" id="hsCodeSearch" placeholder="e.g., 84713000" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-400">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-search mr-1 text-green-500"></i> Wildcard Search</label>
                <input type="text" id="wildcardSearch" placeholder="Heading, HS code or description..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-400">
            </div>
            <div class="flex items-end">
                <button onclick="searchTariffs()" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition flex items-center justify-center gap-2">
                    <i class="fas fa-search"></i> Search Tariffs
                </button>
            </div>
        </div>

        <div class="flex flex-wrap items-end gap-3">
            <div class="flex-grow min-w-[220px]">
                <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-globe mr-1 text-purple-500"></i> Filter by Trade Agreement</label>
                <div class="relative" id="countryDropdownContainer">
                    <button type="button" id="countryDropdownBtn" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-left flex justify-between items-center bg-white focus:ring-2 focus:ring-purple-400">
                        <span id="selectedCountryDisplay">All Countries</span>
                        <i class="fas fa-chevron-down text-gray-400"></i>
                    </button>
                    <div id="countryMenu" class="absolute z-20 mt-1 w-full bg-white shadow-lg border border-gray-200 rounded-lg hidden">
                        <div class="p-2 sticky top-0 bg-white border-b">
                            <input type="text" id="countrySearchInput" placeholder="Search agreement..." class="w-full px-3 py-1.5 border border-gray-300 rounded text-sm">
                        </div>
                        <div id="countryOptions" class="py-1"></div>
                    </div>
                </div>
            </div>
            <div class="flex-shrink-0 pb-0.5">
                <button onclick="clearFilters()" class="text-gray-600 hover:text-gray-800 text-sm flex items-center gap-1"><i class="fas fa-times-circle"></i> Clear all filters</button>
            </div>
        </div>
    </div>

    <!-- Tariff Results Table -->
    <div class="bg-white rounded-xl shadow-md mb-8 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200 flex flex-wrap justify-between items-center gap-2">
            <div>
                <h2 class="font-semibold text-gray-800">📋 Import Duty Rates</h2>
                <p class="text-xs text-gray-500" id="resultCount">Enter search criteria to view results</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-600">Sort:</span>
                <select id="sortBy" onchange="searchTariffs()" class="text-sm border rounded-lg px-2 py-1">
                    <option value="heading_name">Heading</option>
                    <option value="hs_code">HS Code</option>
                    <option value="general_rate">General Rate</option>
                </select>
            </div>
        </div>
        <div id="loadingSpinner" class="hidden text-center py-12"><i class="fas fa-spinner fa-spin text-3xl text-blue-500"></i><p class="mt-2">Loading tariff data...</p></div>
        <div id="resultsContainer" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Heading</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Sub Heading</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">HS Code</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Description</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600" id="dynamicRateHeader">General</th>
                    </tr>
                </thead>
                <tbody id="tableBody" class="bg-white divide-y"></tbody>
            </table>
        </div>
        <div id="noResults" class="hidden text-center py-12"><i class="fas fa-search text-4xl text-gray-300 mb-2"></i><p class="text-gray-500">No tariff rates found</p></div>
    </div>

    <!-- CALCULATOR SECTION with VAT integration -->
    <div class="bg-white rounded-xl shadow-md p-5 mb-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2"><i class="fas fa-calculator text-blue-600"></i> Duty + VAT Calculator (SA Import)</h2>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- LEFT: Inputs -->
            <div class="space-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-700"><i class="fas fa-barcode"></i> Selected HS Code</label>
                    <input type="text" id="calcHsCode" readonly placeholder="Click any row" class="w-full px-3 py-2 bg-gray-50 border rounded-lg">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">Description</label>
                    <input type="text" id="calcDescription" readonly class="w-full px-3 py-2 bg-gray-50 border rounded-lg">
                </div>
                <div class="bg-blue-50 p-3 rounded-lg border">
                    <label class="font-medium text-gray-700"><i class="fas fa-dollar-sign"></i> Cost Per Unit (USD)</label>
                    <div class="relative mt-1"><span class="absolute left-3 top-2 text-gray-500">$</span><input type="number" id="calcCostPerUnitUSD" value="10.00" step="0.01" class="w-full pl-7 pr-2 py-2 border rounded-lg"></div>
                </div>
                <div>
                    <label class="text-sm font-medium"><i class="fas fa-cubes"></i> Number of Units</label>
                    <input type="number" id="calcUnits" value="100" step="1" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="text-sm font-medium"><i class="fas fa-percent"></i> Duty Rate</label>
                    <input type="text" id="calcDutyRate" readonly placeholder="Select tariff" class="w-full bg-gray-50 border rounded-lg px-3 py-2">
                    <input type="hidden" id="calcDutyType" value="percentage">
                    <input type="hidden" id="calcDutyValue" value="0">
                </div>
                <div id="weightInputContainer" class="hidden">
                    <label class="text-sm font-medium"><i class="fas fa-weight-hanging"></i> Total Weight (kg) for specific duty</label>
                    <input type="number" id="calcWeight" value="100" step="0.1" class="w-full border rounded-lg px-3 py-2">
                </div>
                <div class="bg-gray-50 p-3 rounded-lg">
                    <label class="text-sm font-medium"><i class="fas fa-exchange-alt"></i> USD/ZAR Rate</label>
                    <div class="flex gap-2"><input type="number" id="manualExchangeRate" step="0.01" value="18.50" class="flex-1 border rounded-lg px-3 py-2"><button onclick="fetchLiveExchangeRate()" class="bg-gray-200 px-3 rounded-lg"><i class="fas fa-sync-alt"></i></button></div>
                </div>
                <div class="bg-green-50 p-3 rounded-lg">
                    <label><i class="fas fa-warehouse"></i> Handling (ZAR)</label>
                    <input type="number" id="calcHandlingZAR" value="0" step="50" class="w-full border rounded-lg px-3 py-2 mt-1">
                </div>
                <div class="bg-green-50 p-3 rounded-lg">
                    <label><i class="fas fa-truck"></i> Local Transport (ZAR)</label>
                    <input type="number" id="calcLocalTransportZAR" value="0" step="50" class="w-full border rounded-lg px-3 py-2 mt-1">
                </div>
            </div>

            <!-- RIGHT: Results with VAT (added prominently) -->
            <div class="lg:col-span-2">
                <div class="bg-gray-50 rounded-xl p-4 border">
                    <div class="mb-3 pb-2 border-b">
                        <div class="flex items-center gap-2 text-sm"><span class="bg-blue-100 px-2 py-1 rounded-full font-mono text-xs" id="selectedHsCodeDisplay">—</span><span id="selectedDescriptionDisplay" class="text-gray-600">No tariff selected</span></div>
                    </div>

                    <h3 class="font-bold text-gray-800 flex items-center gap-2 mb-3"><i class="fas fa-chart-line text-blue-500"></i> Cost Breakdown (incl. VAT)</h3>
                    
                    <!-- USD section -->
                    <div class="bg-blue-50 p-3 rounded-lg mb-3">
                        <div class="flex justify-between text-sm"><span>Goods Value (USD):</span><span class="font-medium" id="resultGoodsValueUSD">$0.00</span></div>
                        <div class="flex justify-between text-sm"><span>Import Duty (USD):</span><span class="font-medium text-orange-600" id="resultDutyUSD">$0.00</span></div>
                        <div class="flex justify-between font-semibold mt-1 pt-1 border-t"><span>Subtotal (USD):</span><span id="resultTotalUSD">$0.00</span></div>
                    </div>

                    <!-- ZAR local costs + VAT HIGHLIGHT -->
                    <div class="bg-green-50 p-3 rounded-lg mb-3">
                        <div class="flex justify-between"><span>Handling (ZAR):</span><span id="resultHandlingZAR">R 0.00</span></div>
                        <div class="flex justify-between"><span>Local Transport (ZAR):</span><span id="resultTransportZAR">R 0.00</span></div>
                        <div class="flex justify-between font-medium"><span>Local Costs (ZAR):</span><span id="resultLocalCostsZAR">R 0.00</span></div>
                    </div>

                    <!-- VAT SECTION (NEW) -->
                    <div class="bg-purple-50 p-3 rounded-lg mb-3 border border-purple-200">
                        <div class="flex justify-between items-center">
                            <span class="font-semibold text-purple-800"><i class="fas fa-percent mr-1"></i> VAT (15% of Dutiable Value + Duty + Local?)</span>
                            <span class="text-xs text-purple-600">South African VAT base: (CIF Value + Duty) in ZAR</span>
                        </div>
                        <div class="flex justify-between text-sm mt-1"><span>Dutiable Value (CIF) ZAR:</span><span id="vatBaseCIF" class="font-mono">R 0.00</span></div>
                        <div class="flex justify-between text-sm"><span>+ Import Duty (ZAR):</span><span id="vatBaseDutyZAR">R 0.00</span></div>
                        <div class="flex justify-between font-medium border-t pt-1 mt-1"><span>VAT Base (ZAR):</span><span id="vatBaseTotal" class="font-bold">R 0.00</span></div>
                        <div class="flex justify-between text-lg font-bold text-purple-700 mt-1"><span>VAT Amount (15%):</span><span id="vatAmountZAR">R 0.00</span></div>
                    </div>

                    <!-- FINAL TOTAL (including VAT) -->
                    <div class="bg-gray-100 p-4 rounded-lg mt-2">
                        <div class="flex justify-between text-xl font-bold"><span>💰 TOTAL LANDED COST (ZAR):</span><span class="text-green-700" id="resultTotalZARWithVAT">R 0.00</span></div>
                        <div class="flex justify-between text-sm mt-1"><span>Cost Per Unit (ZAR incl. VAT):</span><span id="resultCostPerUnitZAR" class="font-semibold">R 0.00</span></div>
                        <div class="text-xs text-gray-500 mt-2 text-right">Exchange: 1 USD = <span id="displayRate">18.50</span> ZAR | Units: <span id="displayUnits">0</span></div>
                    </div>

                    <div class="mt-3 text-xs text-yellow-700 bg-yellow-50 p-2 rounded">💡 Tip: Click any tariff row to load duty rate. VAT is applied on the sum of (CIF value in ZAR + Import Duty in ZAR). Local handling & transport are added after VAT in total cost but note: standard SA VAT base includes CIF + duty. Our calculation respects SARS methodology.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// ----- Global state -----
let selectedCountry = 'ALL';
let availableCountries = [];
let selectedTariffForCalc = null;
let currentExchangeRate = 18.50;
const countryDisplayNames = { 'ALL':'All Rates','GENERAL':'General','EU':'EU','EFTA':'EFTA','SADC':'SADC','AFCFTA':'AFCFTA' };

// ----- Helper: parse duty (c/kg or %)
function parseDutyRate(rateString) {
    if (!rateString || rateString === '-' || rateString === 'Free') return { type: 'percentage', value: 0, display: 'Free' };
    let rate = String(rateString).trim();
    if (rate.includes('%')) {
        let val = parseFloat(rate.replace('%','')) || 0;
        return { type: 'percentage', value: val, display: `${val}%` };
    }
    if (rate.toLowerCase().includes('c/kg')) {
        let match = rate.match(/(\d+(?:\.\d+)?)/);
        if (match) { let cents = parseFloat(match[1]); return { type: 'specific', value: cents/100, unit:'kg', display: `${cents}c/kg (R${(cents/100).toFixed(2)}/kg)` }; }
    }
    if (rate.toLowerCase().includes('r/kg')) {
        let match = rate.match(/(\d+(?:\.\d+)?)/);
        if (match) return { type: 'specific', value: parseFloat(match[1]), unit:'kg', display: `R${match[1]}/kg` };
    }
    let num = parseFloat(rate);
    if (!isNaN(num)) return { type: 'percentage', value: num, display: `${num}%` };
    return { type: 'percentage', value: 0, display: rate };
}

// ---------- VAT & full calculation (15% on CIF + Duty in ZAR) ----------
function computeFullCost() {
    // Inputs
    let costPerUnitUSD = parseFloat(document.getElementById('calcCostPerUnitUSD').value) || 0;
    let units = parseFloat(document.getElementById('calcUnits').value) || 1;
    let handlingZAR = parseFloat(document.getElementById('calcHandlingZAR').value) || 0;
    let transportZAR = parseFloat(document.getElementById('calcLocalTransportZAR').value) || 0;
    let localTotalZAR = handlingZAR + transportZAR;
    let goodsValueUSD = costPerUnitUSD * units;
    
    let dutyAmountUSD = 0;
    let dutyDesc = '';
    let weight = parseFloat(document.getElementById('calcWeight').value) || 0;
    let dutyType = document.getElementById('calcDutyType').value;
    let dutyValue = parseFloat(document.getElementById('calcDutyValue').value) || 0;
    
    if (selectedTariffForCalc) {
        if (dutyType === 'percentage') {
            dutyAmountUSD = goodsValueUSD * (dutyValue / 100);
        } else if (dutyType === 'specific') {
            let dutyAmountZAR = weight * dutyValue;
            dutyAmountUSD = dutyAmountZAR / currentExchangeRate;
        }
    }
    
    // USD totals
    let totalLandedUSD = goodsValueUSD + dutyAmountUSD;
    
    // Convert to ZAR for VAT base
    let cifValueZAR = goodsValueUSD * currentExchangeRate;   // CIF = goods value in ZAR (no duty yet)
    let dutyZAR = dutyAmountUSD * currentExchangeRate;
    let vatBaseZAR = cifValueZAR + dutyZAR;   // SARS VAT base (CIF + duty)
    let vatAmount = vatBaseZAR * 0.15;
    
    // Final total ZAR = CIF + duty + VAT + local costs (handling/transport)
    let totalZARWithVAT = cifValueZAR + dutyZAR + vatAmount + localTotalZAR;
    let costPerUnitZAR = totalZARWithVAT / units;
    
    // Update DOM
    document.getElementById('resultGoodsValueUSD').innerHTML = `$${goodsValueUSD.toFixed(2)}`;
    document.getElementById('resultDutyUSD').innerHTML = `$${dutyAmountUSD.toFixed(2)}`;
    document.getElementById('resultTotalUSD').innerHTML = `$${totalLandedUSD.toFixed(2)}`;
    document.getElementById('resultHandlingZAR').innerHTML = `R ${handlingZAR.toFixed(2)}`;
    document.getElementById('resultTransportZAR').innerHTML = `R ${transportZAR.toFixed(2)}`;
    document.getElementById('resultLocalCostsZAR').innerHTML = `R ${localTotalZAR.toFixed(2)}`;
    
    // VAT breakdown
    document.getElementById('vatBaseCIF').innerHTML = `R ${cifValueZAR.toFixed(2)}`;
    document.getElementById('vatBaseDutyZAR').innerHTML = `R ${dutyZAR.toFixed(2)}`;
    document.getElementById('vatBaseTotal').innerHTML = `R ${vatBaseZAR.toFixed(2)}`;
    document.getElementById('vatAmountZAR').innerHTML = `R ${vatAmount.toFixed(2)}`;
    
    // final
    document.getElementById('resultTotalZARWithVAT').innerHTML = `R ${totalZARWithVAT.toFixed(2)}`;
    document.getElementById('resultCostPerUnitZAR').innerHTML = `R ${costPerUnitZAR.toFixed(2)}`;
    document.getElementById('displayUnits').innerText = units;
    
    // duty display
    if (selectedTariffForCalc) {
        document.getElementById('dutyRateDisplay')?.setAttribute?.('data-rate', selectedTariffForCalc.dutyDisplay);
        let disclaimer = document.getElementById('calcDisclaimer');
        if(disclaimer) disclaimer.innerHTML = `Duty: ${selectedTariffForCalc.dutyDisplay}. VAT 15% on (CIF ZAR + Duty ZAR).`;
    }
}

function calculateLandedCost() { computeFullCost(); }
function calculateFromCostPerUnit() { computeFullCost(); }

// ---- Exchange rate ----
async function fetchLiveExchangeRate() {
    const messageEl = document.getElementById('exchangeRateMessage');
    const alertEl = document.getElementById('exchangeRateAlert');

    try {
        messageEl.innerText = "Fetching live rate...";
        alertEl.classList.remove('hidden');

        const response = await fetch('https://open.er-api.com/v6/latest/USD');

        if (!response.ok) {
            throw new Error("API request failed");
        }

        const data = await response.json();

        if (data.result === "success" && data.rates?.ZAR) {
            const rate = data.rates.ZAR;

            currentExchangeRate = rate.toFixed(2);

            document.getElementById('manualExchangeRate').value = rate.toFixed(2);
            document.getElementById('displayRate').innerText = rate.toFixed(2);

            messageEl.innerHTML = `1 USD = ${rate.toFixed(3)} ZAR (live)`;

            computeFullCost();
        } else {
            throw new Error("Invalid data");
        }

    } catch (error) {
        console.error(error);

        messageEl.innerHTML = `⚠️ Failed to fetch live rate. Using manual value.`;

        // Optional fallback (VERY useful)
        if (!currentExchangeRate) {
            currentExchangeRate = 17.01; // fallback default
        }
    }
}
function refreshExchangeRate() { fetchLiveExchangeRate(); }
function updateExchangeRate() {
    let manual = parseFloat(document.getElementById('manualExchangeRate').value);
    if (manual>0) { currentExchangeRate = manual; document.getElementById('displayRate').innerText = manual.toFixed(2); computeFullCost(); }
}

// ---- tariff search & display ----
async function searchTariffs() {
    document.getElementById('loadingSpinner').classList.remove('hidden');
    document.getElementById('noResults').classList.add('hidden');
    let hs = document.getElementById('hsCodeSearch').value;
    let wild = document.getElementById('wildcardSearch').value;
    let sort = document.getElementById('sortBy').value;
    let params = new URLSearchParams();
    if(hs) params.append('hs_code', hs);
    if(wild) params.append('wildcard', wild);
    params.append('country', selectedCountry);
    params.append('sort', sort);
    params.append('limit','150');
    try {
        let resp = await fetch(`get_tariffs.php?${params.toString()}`);
        let data = await resp.json();
        document.getElementById('loadingSpinner').classList.add('hidden');
        if(data.success && data.data && data.data.length>0){
            displayResults(data.data);
            document.getElementById('resultCount').innerHTML = `Found ${data.total || data.data.length} results`;
        } else { document.getElementById('noResults').classList.remove('hidden'); document.getElementById('resultCount').innerHTML='No results'; }
    } catch(e){ document.getElementById('loadingSpinner').classList.add('hidden'); document.getElementById('resultCount').innerHTML='Error loading'; }
}

function getRateColumnForCountry(code) {
    const map = {'GENERAL':'rate_general','EU':'rate_eu_uk','EFTA':'rate_efta','SADC':'rate_sadc','AFCFTA':'rate_afcfta'};
    return map[code];
}
function updateTableHeaders() {
    let theadRow = document.querySelector('thead tr');
    let existingHeaders = theadRow.querySelectorAll('th');
    if(existingHeaders.length > 4) while(theadRow.children.length > 4) theadRow.removeChild(theadRow.lastChild);
    let headerText = (selectedCountry==='ALL') ? 'General' : (countryDisplayNames[selectedCountry] || 'Rate');
    let newTh = document.createElement('th');
    newTh.className = 'px-3 py-2 text-left font-medium text-gray-600';
    newTh.innerText = headerText;
    theadRow.appendChild(newTh);
}
function displayResults(tariffs) {
    let tbody = document.getElementById('tableBody');
    tbody.innerHTML = '';
    tariffs.forEach(t => {
        let row = tbody.insertRow();
        row.className = 'hover:bg-gray-50 cursor-pointer tariff-row';
        let rateForDisplay = '';
        if(selectedCountry==='ALL') rateForDisplay = t.rate_general || '-';
        else { let field = getRateColumnForCountry(selectedCountry); rateForDisplay = (field && t[field]) ? t[field] : '-'; }
        let parsed = parseDutyRate(rateForDisplay);
        let displayRate = parsed.display;
        row.innerHTML = `
            <td class="px-3 py-2 text-gray-800">${t.headingses || '-'}</td>
            <td class="px-3 py-2">${t.suheadingses || '-'}</td>
            <td class="px-3 py-2 font-mono">${t.hs_code || '-'}</td>
            <td class="px-3 py-2">${t.description || '-'}</td>
            <td class="px-3 py-2 font-medium">${displayRate}</td>
        `;
        // store data attributes
        row.setAttribute('data-hs-code', t.hs_code);
        row.setAttribute('data-description', t.description);
        row.setAttribute('data-rate-general', t.rate_general);
        row.setAttribute('data-rate-eu', t.rate_eu_uk);
        row.setAttribute('data-rate-efta', t.rate_efta);
        row.setAttribute('data-rate-sadc', t.rate_sadc);
        row.setAttribute('data-rate-afcfta', t.rate_afcfta);
        row.addEventListener('click', (e) => selectTariffForCalculation(row));
    });
}

function selectTariffForCalculation(row) {
    document.querySelectorAll('#tableBody tr').forEach(r => r.classList.remove('bg-blue-100', 'border-l-4', 'border-blue-500'));
    row.classList.add('bg-blue-100', 'border-l-4', 'border-blue-500');
    let hs = row.getAttribute('data-hs-code');
    let desc = row.getAttribute('data-description');
    document.getElementById('calcHsCode').value = hs || '';
    document.getElementById('calcDescription').value = desc || '';
    document.getElementById('selectedHsCodeDisplay').innerText = hs || '—';
    document.getElementById('selectedDescriptionDisplay').innerText = desc || 'No tariff';
    
    let rateRaw = '';
    if (selectedCountry === 'ALL' || selectedCountry === 'GENERAL') rateRaw = row.getAttribute('data-rate-general');
    else if (selectedCountry === 'EU') rateRaw = row.getAttribute('data-rate-eu');
    else if (selectedCountry === 'EFTA') rateRaw = row.getAttribute('data-rate-efta');
    else if (selectedCountry === 'SADC') rateRaw = row.getAttribute('data-rate-sadc');
    else if (selectedCountry === 'AFCFTA') rateRaw = row.getAttribute('data-rate-afcfta');
    
    let parsed = parseDutyRate(rateRaw);
    document.getElementById('calcDutyRate').value = parsed.display;
    document.getElementById('calcDutyType').value = parsed.type;
    document.getElementById('calcDutyValue').value = parsed.value;
    
    let weightContainer = document.getElementById('weightInputContainer');
    if (parsed.type === 'specific') weightContainer.classList.remove('hidden');
    else weightContainer.classList.add('hidden');
    
    selectedTariffForCalc = { hsCode: hs, description: desc, dutyType: parsed.type, dutyValue: parsed.value, dutyDisplay: parsed.display };
    computeFullCost();
}

function clearFilters() {
    document.getElementById('hsCodeSearch').value = '';
    document.getElementById('wildcardSearch').value = '';
    selectedCountry = 'ALL';
    document.getElementById('selectedCountryDisplay').innerText = 'All Rates';
    updateTableHeaders();
    searchTariffs();
    selectedTariffForCalc = null;
    document.getElementById('calcHsCode').value = '';
    document.getElementById('calcDescription').value = '';
    document.getElementById('calcDutyRate').value = '';
    document.getElementById('weightInputContainer').classList.add('hidden');
    document.getElementById('selectedHsCodeDisplay').innerText = '—';
    document.getElementById('selectedDescriptionDisplay').innerText = 'No tariff selected';
    computeFullCost();
}

// country dropdown
async function loadCountries() {
    try {
        let res = await fetch('get_countries.php');
        let data = await res.json();
        if(data.success && data.data) availableCountries = data.data;
        else availableCountries = [{ category:'ALL', country:'All Rates' },{ category:'GENERAL', country:'General' },{ category:'EU', country:'EU' },{ category:'EFTA', country:'EFTA' },{ category:'SADC', country:'SADC' },{ category:'AFCFTA', country:'AFCFTA' }];
        populateCountryOptions(availableCountries);
    } catch(e) { console.error(e); }
}
function populateCountryOptions(countries) {
    let container = document.getElementById('countryOptions');
    if(!countries.find(c=>c.category==='ALL')) countries.unshift({category:'ALL',country:'All Rates'});
    container.innerHTML = '';
    countries.forEach(c => {
        let div = document.createElement('div');
        div.className = `px-3 py-2 cursor-pointer hover:bg-purple-50 ${c.category === selectedCountry ? 'bg-purple-100 font-medium' : ''}`;
        div.innerText = c.country;
        div.setAttribute('data-country-code', c.category);
        div.addEventListener('click', () => {
            selectedCountry = c.category;
            document.getElementById('selectedCountryDisplay').innerText = c.country;
            document.getElementById('countryMenu').classList.add('hidden');
            updateTableHeaders();
            searchTariffs();
        });
        container.appendChild(div);
    });
}
function initDropdown() {
    let btn = document.getElementById('countryDropdownBtn');
    let menu = document.getElementById('countryMenu');
    btn.addEventListener('click', (e) => { e.stopPropagation(); menu.classList.toggle('hidden'); });
    document.addEventListener('click', (e) => { if(!btn.contains(e.target) && !menu.contains(e.target)) menu.classList.add('hidden'); });
    document.getElementById('countrySearchInput').addEventListener('keyup', function(){
        let term = this.value.toLowerCase();
        document.querySelectorAll('#countryOptions div').forEach(opt => { opt.style.display = opt.innerText.toLowerCase().includes(term) ? 'block' : 'none'; });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    loadCountries();
    initDropdown();
    updateTableHeaders();
    searchTariffs();
    document.getElementById('calcCostPerUnitUSD').addEventListener('input', computeFullCost);
    document.getElementById('calcUnits').addEventListener('input', computeFullCost);
    document.getElementById('calcWeight').addEventListener('input', computeFullCost);
    document.getElementById('calcHandlingZAR').addEventListener('input', computeFullCost);
    document.getElementById('calcLocalTransportZAR').addEventListener('input', computeFullCost);
    document.getElementById('manualExchangeRate').addEventListener('input', function(){ updateExchangeRate(); computeFullCost(); });
    fetchLiveExchangeRate();
    computeFullCost();
});
</script>
</body>
</html>