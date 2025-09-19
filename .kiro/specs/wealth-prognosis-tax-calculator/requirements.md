# Requirements Document

## Introduction

This feature will create a comprehensive wealth prognosis system that calculates complex tax scenarios and projects wealth growth from the current date until the user's estimated death. The system will integrate with existing asset configurations and provide detailed projections considering various tax implications, asset growth rates, and life events to give users a complete picture of their financial future.

## Requirements

### Requirement 1

**User Story:** As a wealth planner, I want to generate a comprehensive wealth prognosis that projects my net worth from today until my estimated death, so that I can make informed long-term financial decisions.

#### Acceptance Criteria

1. WHEN a user initiates a wealth prognosis THEN the system SHALL calculate projections from the current date until the user's estimated death date
2. WHEN generating projections THEN the system SHALL incorporate all existing asset configurations and their associated growth rates
3. WHEN calculating future wealth THEN the system SHALL apply compound growth calculations annually for each asset type
4. IF a user has not specified a death date THEN the system SHALL use actuarial life expectancy tables based on age and demographics

### Requirement 2

**User Story:** As a tax-conscious investor, I want the system to calculate complex tax implications for each year of my wealth projection, so that I can understand the true after-tax growth of my wealth.

#### Acceptance Criteria

1. WHEN calculating annual projections THEN the system SHALL apply appropriate tax rates for each asset type based on the configured tax types
2. WHEN assets generate income THEN the system SHALL calculate income tax on dividends, interest, and rental income
3. WHEN assets are sold or transferred THEN the system SHALL calculate capital gains tax based on holding periods and tax brackets
4. WHEN calculating wealth tax THEN the system SHALL apply fortune/wealth taxes based on total net worth thresholds
5. IF tax rates change over time THEN the system SHALL apply the appropriate tax rate for each projection year

### Requirement 3

**User Story:** As a financial planner, I want to model different economic scenarios in my wealth prognosis, so that I can understand how market conditions might affect my long-term wealth.

#### Acceptance Criteria

1. WHEN generating a prognosis THEN the system SHALL allow selection of different economic scenarios (optimistic, realistic, pessimistic)
2. WHEN applying scenarios THEN the system SHALL adjust asset growth rates according to the selected scenario parameters
3. WHEN calculating projections THEN the system SHALL apply scenario-specific inflation rates to expenses and tax brackets
4. IF multiple scenarios are selected THEN the system SHALL generate comparative projections showing the range of outcomes

### Requirement 4

**User Story:** As a user planning major life events, I want to incorporate planned financial events into my wealth prognosis, so that I can see how purchases, inheritances, or career changes will impact my long-term wealth.

#### Acceptance Criteria

1. WHEN creating a prognosis THEN the system SHALL allow users to define future financial events with specific dates and amounts
2. WHEN processing events THEN the system SHALL apply one-time income additions (inheritance, bonuses) to the appropriate year
3. WHEN processing events THEN the system SHALL apply one-time expenses (major purchases, education costs) to the appropriate year
4. WHEN events affect ongoing income THEN the system SHALL adjust annual income projections from the event date forward

### Requirement 5

**User Story:** As an investor with diverse assets, I want detailed year-by-year breakdowns of my wealth composition, so that I can understand how my asset allocation changes over time.

#### Acceptance Criteria

1. WHEN generating projections THEN the system SHALL provide annual snapshots showing the value of each asset category
2. WHEN displaying results THEN the system SHALL show percentage allocation changes over time for each asset type
3. WHEN calculating growth THEN the system SHALL track both gross and net (after-tax) values for each asset
4. IF assets have different liquidity characteristics THEN the system SHALL categorize and track liquid vs illiquid wealth separately

### Requirement 6

**User Story:** As a retirement planner, I want to identify key financial milestones in my wealth prognosis, so that I can understand when I might achieve financial independence or other goals.

#### Acceptance Criteria

1. WHEN calculating projections THEN the system SHALL identify the year when investment income exceeds annual expenses (financial independence)
2. WHEN projecting wealth THEN the system SHALL highlight years when net worth reaches user-defined milestone amounts
3. WHEN analyzing cash flow THEN the system SHALL identify periods of negative cash flow or wealth depletion
4. IF withdrawal strategies are defined THEN the system SHALL model sustainable withdrawal rates and their impact on wealth longevity

### Requirement 7

**User Story:** As a user concerned about accuracy, I want the wealth prognosis to account for inflation and changing tax brackets over time, so that my projections reflect realistic future purchasing power.

#### Acceptance Criteria

1. WHEN calculating future values THEN the system SHALL apply inflation adjustments to all monetary amounts
2. WHEN applying tax calculations THEN the system SHALL adjust tax brackets and thresholds for inflation annually
3. WHEN projecting expenses THEN the system SHALL inflate living costs and other expenses at appropriate rates
4. WHEN displaying results THEN the system SHALL provide both nominal and real (inflation-adjusted) wealth values

### Requirement 8

**User Story:** As a data-driven investor, I want to export and visualize my wealth prognosis data, so that I can analyze trends and share projections with advisors.

#### Acceptance Criteria

1. WHEN a prognosis is complete THEN the system SHALL provide export functionality for projection data in Excel format
2. WHEN displaying results THEN the system SHALL generate interactive charts showing wealth growth over time
3. WHEN visualizing data THEN the system SHALL provide charts for asset allocation changes, tax burden trends, and milestone achievements
4. IF multiple scenarios are calculated THEN the system SHALL provide comparative visualizations showing scenario differences