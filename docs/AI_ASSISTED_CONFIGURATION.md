# AI Assisted Configuration

The AI Assisted Configuration feature allows users to create comprehensive asset configurations by simply describing their economic situation in natural language. The AI analyzes the description and automatically generates a structured asset configuration with assets and yearly data.

## Features

- **Natural Language Input**: Users describe their financial situation in plain text
- **AI Analysis**: OpenAI GPT-4 analyzes the description and extracts financial information
- **Automatic Structure Generation**: Creates AssetConfiguration, Assets, and AssetYears automatically
- **Fallback Support**: Works even without OpenAI API key (uses basic fallback configuration)
- **Validation**: Ensures all generated data uses valid asset types and tax types
- **User-Friendly Interface**: Large textarea with helpful placeholder and guidance

## How to Use

1. **Navigate to Asset Configurations**: Go to the Asset Configurations list page in the admin panel
2. **Click "New AI Assisted Configuration"**: Look for the sparkles icon button at the top of the page
3. **Describe Your Situation**: In the large textarea, provide detailed information about:
   - Your age and financial goals
   - Current assets (savings, investments, property, etc.)
   - Income sources and amounts
   - Monthly/yearly expenses
   - Retirement plans and timeline
   - Any other relevant financial information

4. **Submit**: Click the action button to process your description
5. **Review Results**: You'll be redirected to the assets page of your new configuration

## Example Input

```
I am 35 years old, earn $80,000 annually as a software engineer. I have $50,000 in savings, 
own a house worth $400,000 with a $250,000 mortgage. I contribute $500 monthly to my 401k 
and want to retire at 65. I also have some stocks worth about $20,000 and pay $2,000 monthly 
in living expenses. My goal is to have enough saved for a comfortable retirement.
```

## Configuration Requirements

### OpenAI API Key (Optional)

To use the full AI analysis capabilities, set your OpenAI API key in your environment:

```bash
OPENAI_API_KEY=your_openai_api_key_here
```

**Note**: The feature works without an API key by using a fallback configuration system.

### Required Database Tables

The feature requires these models to exist with sample data:
- `AssetType` - Various asset types (cash, equity, real_estate, etc.)
- `TaxType` - Tax classifications (none, capital_gains, etc.)

## Technical Implementation

### AI Analysis Service

The `AiConfigurationAnalysisService` handles:
- OpenAI API communication
- Response parsing and validation
- Fallback configuration generation
- Data sanitization and type validation

### Action Integration

The `CreateAiAssistedConfigurationAction` provides:
- Filament form interface
- Database transaction management
- Error handling and user feedback
- Automatic redirection to asset management

### Data Structure

The AI generates this JSON structure:

```json
{
  "configuration": {
    "name": "Configuration Name",
    "description": "Brief description",
    "birth_year": 1988,
    "prognose_age": 65,
    "pension_official_age": 67,
    "pension_wish_age": 65,
    "death_age": 85,
    "export_start_age": 25
  },
  "assets": [
    {
      "name": "Asset Name",
      "description": "Asset description",
      "code": "unique_code",
      "asset_type": "cash|equity|real_estate|other",
      "tax_type": "none|capital_gains|etc",
      "group": "private|business",
      "years": [
        {
          "year": 2024,
          "market_amount": 50000,
          "income_amount": 1000,
          "expence_amount": 100,
          "income_factor": "yearly|monthly",
          "expence_factor": "yearly|monthly"
        }
      ]
    }
  ]
}
```

## Error Handling

The system includes comprehensive error handling:

1. **API Failures**: Falls back to basic configuration
2. **Invalid Data**: Sanitizes and corrects invalid asset/tax types
3. **Missing Fields**: Provides sensible defaults
4. **Database Errors**: Rolls back transactions and shows user-friendly messages

## Testing

The feature includes comprehensive tests:
- Unit tests for the AI service
- Integration tests for the complete workflow
- Fallback scenario testing
- Data validation testing

Run tests with:
```bash
php artisan test --filter="AiAssisted"
```

## Limitations

- Requires internet connection for full AI analysis
- OpenAI API usage costs apply when using real API key
- Analysis quality depends on input description detail
- Currently supports Norwegian tax system defaults
- Limited to predefined asset and tax types

## Future Enhancements

Potential improvements:
- Support for multiple languages
- More sophisticated financial analysis
- Integration with external financial data sources
- Custom asset type creation
- Batch processing for multiple configurations
- Export/import of AI analysis results
