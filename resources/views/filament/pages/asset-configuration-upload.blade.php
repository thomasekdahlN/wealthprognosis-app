<x-filament-panels::page>
    <div class="space-y-8">
        <!-- Native Filament Form -->
        {{ $this->form }}

        <!-- Test Button -->
        <div class="mt-6 flex justify-center space-x-4">
            <button
                type="button"
                wire:click="testMethod"
                class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
            >
                Simple Test (No Form)
            </button>
            <button
                type="button"
                wire:click="generateAnalysis"
                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
            >
                Test Generate Analysis
            </button>
        </div>



        <!-- Information Section -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6">
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0 mt-1">
                    <div class="w-5 h-5 text-blue-600 dark:text-blue-400">ℹ️</div>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-medium text-blue-900 dark:text-blue-100">
                        How to Use Asset Configuration Upload
                    </h3>
                    <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                        <p class="mb-3">
                            This tool processes your JSON asset configuration file and generates detailed Excel analysis reports for wealth prognosis.
                        </p>
                        <div class="space-y-2">
                            <p><strong>Step 1:</strong> Select the economic scenario for your analysis</p>
                            <p><strong>Step 2:</strong> Choose the scope of analysis (all assets, private only, or company only)</p>
                            <p><strong>Step 3:</strong> Upload your JSON configuration file containing your asset definitions</p>
                            <p><strong>Step 4:</strong> Click "Generate Excel Analysis" to create and download your report</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Example JSON Structure -->
        <div class="bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                Example JSON Configuration Structure
            </h3>
            <div class="bg-gray-800 rounded-lg p-4 overflow-x-auto">
                <pre class="text-sm text-green-400"><code>{
  "meta": {
    "name": "John Doe",
    "birthYear": "1975",
    "prognoseYear": "50",
    "pensionOfficialYear": "67",
    "pensionWishYear": "63",
    "deathYear": "82"
  },
  "house": {
    "meta": {
      "type": "house",
      "group": "private",
      "name": "Primary Residence",
      "description": "Main house",
      "active": true,
      "tax": "house"
    },
    "2023": {
      "asset": {
        "marketAmount": "3000000",
        "changerate": "changerates.house",
        "description": "Current market value",
        "repeat": true
      },
      "expence": {
        "name": "House Expenses",
        "description": "Municipal fees, insurance, electricity",
        "amount": 7300,
        "factor": 12,
        "changerate": "changerates.kpi",
        "repeat": true
      }
    }
  }
}</code></pre>
            </div>
        </div>

    </div>

    @push('scripts')
    <script>
        document.addEventListener('livewire:init', () => {
            // Livewire v3 dispatch() triggers a browser event on window
            window.addEventListener('download-file', (event) => {
                const detail = event?.detail || {};
                const url = detail.url || event.url; // support both shapes
                const filename = detail.filename || event.filename || ''; // support both shapes
                if (!url) return;

                const link = document.createElement('a');
                link.href = url;
                link.download = filename;
                link.style.display = 'none';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });
        });
    </script>
    @endpush
</x-filament-panels::page>
