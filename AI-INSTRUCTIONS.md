About the system

The system name: wealth prognosis.
This is a system for keeping track of the tax in every country pr year
This is a system for keeping track of all the financial assets of a person or company
This is a system for keeping prognosis for alltypes of assets
This is a system for giving detailed analysis of your current (based on assets) and future economy running prognosis and simulations of different scenarios with the assets.
The system is multi tenant and should not mix one users assets with another users assets



Instructions to the AI

We use filament 4 - for everything

Always use Filament for userinterfaces, do not use blade files.
Follow laravel 12 best practises
Follow Filament 4 best practices - and make a Filament native solution
Always use valid Filament 4 components available in the installed version (avoid deprecated/missing classes).

User interface instructions:
- - Follow Tailwind best practices but prefer native Filament 4 solutions where possible.

- Always use filament native tables if data is listed
- Tables should always be responsive in width to the full widht of the browser by using >maxContentWidth(Width::Full)
- Tables should always have filters visible on top
- Tables should always have the possibility to choose columns to show on all columns
- Tables should never have delete/duplicate functionality on the rows in list formats, only on the edit pages
- Tables and lists should always set 50,100,150 per page as pagination with 50 per page as default.
- Tables and lists from and to filters should always be on the same line with from first and to last.

Tabs
- Always use filament 4 tables when adding tables under a tab.
- When creating, editing and deleting of records should always be done on the same tab

Input fields
- All text fields shall have a RichEditor and a height of 8 lines
- All color fields shall have a color picker
- All icon fields shall have a icon picker
- All tag fields should have a tag picker
- All label fields should have a label picker

Formatting
Formatting of years and age in presentation should be done as a 4 digits - do not apply amount formatting to years or age and aligned to the left in tables
All amount, integer, number fields should be aligned to the right in tables and input forms and have norwegian amount formatting (space as thousand separator and comma as decimal separator)
Use input masking for amounts and numbers formatting in input fields


Coding instructions
The code should be correctly formatted according to PSR
The code should be valid for Larastan level 9
The code should be syntactically correct
The code should be complete and work
Always return working code, not just a part of the code, but all the code.
Always generate seeders for new database fields and tables
Always change the original migrations, do not create new migrations when we have exising migrations on the same table
When we adjust data model - remember to refactor models, seeders and tests
Always use standard Laravel/Filament translation for supporting a multi language system
Always use Filaments built in theming system - avoid css customization.
Always clear cached components, view, route and config after updating.
Use Filament native icon components - not SVG or HTML.
Always add team_id, user_id, updated_at, created_at, updated_by, created_by, created_checksum, updated_checksum in all new table migrations
Prefer to use Filament components for custom design where filament components can not solve the problem
All export files should have a YYYY-MM-DD date prefix in the filename, and a logical filename after that.
Do not use blade views - use filament 4 native components and views.

Implement audit stamping into every model that has a db table that supports it.
Do not over engineer but prefer clean and simple solutions


Never change navigation menus and tabs unless explisitly asked for change.
All save / delete / add functionality under a tab should keep the user in the tab while doing changes using filament best practises
Always use filament 4 native components and ways of coding,

Testing instructions
Use pest 4 for testing.
Always generate feature tests for new models
Always generate page tests that check for HTTP 200 for all new pages
Always run feature and page tests after making new functionality
Always run the tests for all pages, models, controllers and resources that have been involved in a change.

Use pretty routes, avoid using query parameters to identify resources. Always use routes like /admin/config/{configuration}/
Remove html tags from the presentation of string and text fields that are listed in tables.
HTML has to be removed from textfields before they are rendered in tables. (Not from the database, just to render the view nicely when you limit the amountof characters in a description)
On labels and sentences it should only be uppercase letters on the first word

Do not commit code to Git unless explicitly asked for it. Use conventional commits when asked to check in code to Git.

Do not create files, classes and methods based on a test that thinks it is missing. Just remove the test.
