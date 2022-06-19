##Run

php artisan ReadFile

Will soon make it possible to adda config file as a input

    2016 => array:3 [
      "cashflow" => array:6 [
        "changerate" => 1.05
        "income" => 10000
        "expence" => 0
        "amount" => 10000
        "amountAccumulated" => 20000
        "description" => "Skilsmisse"
      ]
      "asset" => Illuminate\Support\Collection^ {#689
        #items: array:3 [
          "value" => 5000000
          "changerate" => 1.06
          "description" => "Skilsmisse"
        ]
        #escapeWhenCastingToString: false
      }
      "mortgage" => array:7 [
        "payment" => 106291.04899632
        "paymentExtra" => 10000
        "interest" => 4167.4633672926
        "principal" => 112123.58562902
        "balance" => 94186.482058729
        "gebyr" => 0
        "description" => ""
      ]
    ]
    2017 => array:3 [
      "cashflow" => array:6 [
        "changerate" => 1.05
        "income" => 10000
        "expence" => 0
        "amount" => 10000
        "amountAccumulated" => 30000
        "description" => null
      ]
      "asset" => Illuminate\Support\Collection^ {#690
        #items: array:3 [
          "value" => 5300000.0
          "changerate" => 1.06
          "description" => null
        ]
        #escapeWhenCastingToString: false
      }
      "mortgage" => array:7 [
        "payment" => 96089.048996315
        "paymentExtra" => 10000
        "interest" => 1902.5669375863
        "principal" => 104186.48205873
        "balance" => -9999.9999999999
        "gebyr" => 0
        "description" => ""
      ]
    ]