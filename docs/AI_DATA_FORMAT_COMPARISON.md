# AI Data Format Comparison for Simulation Analysis

## Executive Summary

**Recommendation: Use CSV format for AI analysis**

CSV provides the best balance of:
- ✅ **19.3% smaller** than JSON (Compact)
- ✅ **47% smaller** than JSON (Full)
- ✅ **Native AI understanding** - LLMs excel at tabular data
- ✅ **Lower cost** - ~$0.04 savings per comparison
- ✅ **Faster processing** - Less tokens to parse

## Format Comparison Results (OPTIMIZED - Empty Rows Filtered)

### Single Simulation (110 asset-years, 106 non-empty)

| Format | Size | Tokens | Cost/Request |
|--------|------|--------|--------------|
| JSON (Full) | 60,382 bytes | 15,096 | $0.1510 |
| JSON (Compact) | 39,877 bytes | 9,969 | $0.0997 |
| Excel (.xlsx) | 38,361 bytes | binary | N/A |
| **CSV** | **31,757 bytes** | **7,939** | **$0.0794** |

### Two Simulations (AI Comparison)

| Format | Total Size | Tokens | Cost/Request |
|--------|-----------|--------|--------------|
| JSON (Compact) | 79,441 bytes | 19,860 | $0.1986 |
| Excel (.xlsx) | 75,869 bytes | binary | N/A |
| **CSV** | **63,726 bytes** | **15,932** | **$0.1593** |

**Savings with CSV:**
- Size: 19.8% smaller than JSON
- Tokens: 3,929 fewer tokens
- Cost: $0.0393 per comparison
- **Annual savings (100 comparisons): $3.93**

**Optimization Impact:**
- Empty rows filtered: 4 rows removed (3.6%)
- All formats now exclude rows where all amount fields are null or 0

## Format Details

### CSV Format

**Structure:**
- Header row with column names
- One row per asset-year combination
- Flat structure (no nesting)
- Simulation metadata repeated on each row

**Advantages:**
1. **Tabular data is LLM-native** - Models are trained extensively on CSV/TSV data
2. **No parsing overhead** - No JSON brackets, quotes, or nesting
3. **Compact** - Minimal syntax overhead
4. **Easy to scan** - LLMs can quickly identify patterns in rows
5. **Consistent structure** - Every row has the same format

**Disadvantages:**
1. **Repetition** - Simulation metadata repeated on every row
2. **No hierarchy** - Flat structure loses asset grouping
3. **Type ambiguity** - All values are strings (but LLMs handle this well)

### JSON (Compact) Format

**Structure:**
- Nested hierarchy: simulation → assets → years
- Only essential calculated fields
- Pretty-printed for readability

**Advantages:**
1. **Hierarchical** - Clear asset grouping
2. **No repetition** - Metadata stored once
3. **Type-safe** - Numbers are numbers, not strings
4. **Familiar** - Standard format

**Disadvantages:**
1. **Verbose** - Lots of brackets, quotes, commas
2. **Parsing overhead** - LLM must parse nested structure
3. **Larger** - 25% more tokens than CSV

### JSON (Full) Format

**Structure:**
- Complete database export
- All fields including input data
- Matches original import format

**Advantages:**
1. **Complete** - All data available
2. **Reversible** - Can recreate simulation

**Disadvantages:**
1. **Very large** - 89% larger than CSV
2. **Noisy** - Includes many unused fields
3. **Expensive** - Nearly 2x the cost of CSV

## Other Compact Formats Considered

### Excel (.xlsx)
- **Binary spreadsheet format** with multiple sheets
- **19% larger than CSV** (75,869 vs 63,726 bytes for 2 simulations)
- **NOT AI-readable** - Binary format cannot be sent to LLMs
- **Recommendation:** Avoid for AI - use CSV instead
- **Use case:** Human-readable exports only

### TSV (Tab-Separated Values)
- **Similar to CSV** but uses tabs instead of commas
- **Slightly more compact** - No need to escape commas in text
- **Recommendation:** Use CSV for better compatibility

### Minified JSON
- **Remove whitespace** from JSON
- **Saves ~15-20%** but harder for LLM to parse
- **Recommendation:** Keep pretty-printing for better LLM understanding

### Protocol Buffers / MessagePack
- **Binary formats** - Very compact
- **Not LLM-friendly** - Models can't read binary
- **Recommendation:** Avoid for AI analysis

### YAML
- **More readable** than JSON for humans
- **Larger** than JSON due to indentation
- **Recommendation:** Avoid - larger and slower

### Markdown Tables
- **Human-readable** tabular format
- **Larger** than CSV due to formatting characters
- **Recommendation:** Avoid - CSV is more compact

## Implementation

### CSV Export Function

```php
SimulationExportService::toCsvFull($simulation)
```

**Features:**
- Flat structure with one row per asset-year
- 27 columns covering all essential data
- Proper CSV escaping for commas, quotes, newlines
- Simulation metadata on every row for context
- **Optimized:** Excludes rows where all amount fields are null or 0

**Column Headers:**
```
simulation_name, simulation_description, birth_year, pension_wish_age,
pension_official_age, death_age, risk_tolerance, prognosis_type, group,
tax_country, asset_name, asset_type, asset_group, asset_description,
year, income_amount, expence_amount, cashflow_after_tax_amount,
cashflow_tax_amount, asset_market_amount, asset_market_mortgage_deducted_amount,
mortgage_balance_amount, mortgage_interest_amount, asset_tax_amount,
realization_tax_amount, fire_percent, metrics_ltv_percent
```

## Usage in AI Comparison

### Current (JSON Compact)
```php
$simulationAJson = SimulationExportService::toCompactJson($simulationA);
$simulationBJson = SimulationExportService::toCompactJson($simulationB);
```

### Recommended (CSV)
```php
$simulationACsv = SimulationExportService::toCsvFull($simulationA);
$simulationBCsv = SimulationExportService::toCsvFull($simulationB);
```

### Excel Export (for human download only)
```php
$excelPath = SimulationExportService::toExcel($simulationA);
// Returns file path to .xlsx file
// Note: Cannot be sent to AI - binary format
```

### Prompt Template Update

**Current:**
```
**Simulation A (Baseline):**
{simulation_a_json}

**Simulation B (Alternative Scenario):**
{simulation_b_json}
```

**Recommended:**
```
**Simulation A (Baseline) - CSV Format:**
{simulation_a_csv}

**Simulation B (Alternative Scenario) - CSV Format:**
{simulation_b_csv}

Note: Data is in CSV format with headers. Each row represents one asset-year.
```

## Performance Metrics

### Token Efficiency
- **CSV:** 8,209 tokens per simulation
- **JSON Compact:** 10,243 tokens per simulation
- **Improvement:** 20% fewer tokens

### Cost Efficiency (per comparison)
- **CSV:** $0.1648
- **JSON Compact:** $0.2041
- **Savings:** $0.0393 (19.3%)

### Processing Speed
- **CSV:** Faster - simpler structure
- **JSON:** Slower - nested parsing required

## Recommendations

1. ✅ **Switch to CSV format** for AI comparisons
2. ✅ **Update AI instruction prompts** to mention CSV format
3. ✅ **Keep JSON exports** for human readability and debugging
4. ✅ **Monitor AI response quality** after switching to ensure no degradation
5. ✅ **Consider TSV** if text fields frequently contain commas

## Next Steps

1. Update `ProcessAiComparisonAnalysis` job to use CSV
2. Update `AiInstruction` seeder to mention CSV format in prompts
3. Test AI comparison quality with CSV vs JSON
4. Monitor token usage and costs
5. Document any quality differences

## References

- OpenAI Tokenizer: ~4 characters per token (average)
- GPT-4o Input Cost: $0.01 per 1K tokens (as of 2024)
- LLMs excel at tabular data (CSV/TSV) due to training data
- Minified JSON is harder for LLMs to parse than pretty-printed

